<?php

namespace App\Services;

use App\Models\MerchantProduct;
use App\Models\Transaction;
use App\Models\Warehouse;
use App\Repositories\RecipeRepository;
use App\Repositories\StockOutRepository;
use App\Repositories\WarehouseProductRepository;
use App\Support\UnitConverter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RecipeStockService
{
    private RecipeRepository $recipeRepository;
    private WarehouseProductRepository $warehouseProductRepository;
    private StockOutRepository $stockOutRepository;

    public function __construct(
        RecipeRepository $recipeRepository,
        WarehouseProductRepository $warehouseProductRepository,
        StockOutRepository $stockOutRepository
    ) {
        $this->recipeRepository = $recipeRepository;
        $this->warehouseProductRepository = $warehouseProductRepository;
        $this->stockOutRepository = $stockOutRepository;
    }

    public function recipeProductIds(array $productIds): array
    {
        return $this->recipeRepository->getByProductIds($productIds)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function deductForTransaction(
        Transaction $transaction,
        array $products,
        int $merchantId,
        ?int $warehouseId
    ): void {
        $productIds = array_values(array_unique(array_column($products, 'product_id')));
        $recipes = $this->recipeRepository->getByProductIds($productIds);

        if ($recipes->isEmpty()) {
            return;
        }

        $usage = [];

        foreach ($products as $product) {
            $recipe = $recipes->get($product['product_id']);

            if (!$recipe) {
                continue;
            }

            $sourceWarehouseId = $this->sourceWarehouseId(
                $merchantId,
                $product['product_id'],
                $warehouseId
            );

            foreach ($recipe->items as $item) {
                if (!$item->ingredient) {
                    throw ValidationException::withMessages([
                        'recipe' => ['A recipe ingredient is no longer available.'],
                    ]);
                }

                $needed = UnitConverter::convert(
                    (float) $item->quantity * (int) $product['quantity'],
                    $item->unit,
                    $item->ingredient->unit
                );

                if ($needed === null) {
                    throw ValidationException::withMessages([
                        'recipe' => [
                            "Unit '{$item->unit}' for ingredient '{$item->ingredient->name}' "
                            . "is not compatible with inventory unit '{$item->ingredient->unit}'."
                        ],
                    ]);
                }

                $needed = round($needed, 3);
                $key = $sourceWarehouseId . ':' . $item->ingredient_id;

                if (!isset($usage[$key])) {
                    $usage[$key] = [
                        'warehouse_id' => $sourceWarehouseId,
                        'product_id' => $item->ingredient_id,
                        'name' => $item->ingredient->name,
                        'quantity' => 0.0,
                    ];
                }

                $usage[$key]['quantity'] = round($usage[$key]['quantity'] + $needed, 3);
            }
        }

        usort($usage, fn ($a, $b) => [$a['warehouse_id'], $a['product_id']]
            <=> [$b['warehouse_id'], $b['product_id']]);

        foreach ($usage as $item) {
            $warehouseProduct = $this->warehouseProductRepository
                ->getByWarehouseAndProductForUpdate($item['warehouse_id'], $item['product_id']);

            if (!$warehouseProduct) {
                throw ValidationException::withMessages([
                    'stock' => [
                        "Ingredient '{$item['name']}' is not available in the selected warehouse."
                    ],
                ]);
            }

            $available = round((float) $warehouseProduct->stock, 3);

            if ($available < $item['quantity']) {
                throw ValidationException::withMessages([
                    'stock' => [
                        "Insufficient stock for ingredient '{$item['name']}'. "
                        . "Available: {$available}, required: {$item['quantity']}."
                    ],
                ]);
            }

            $this->warehouseProductRepository->decrementStock(
                $item['warehouse_id'],
                $item['product_id'],
                $item['quantity']
            );
        }

        foreach ($usage as $item) {
            $this->stockOutRepository->create([
                'merchant_id' => $merchantId,
                'warehouse_id' => $item['warehouse_id'],
                'transaction_id' => $transaction->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'reason' => "Recipe usage for transaction #{$transaction->id}",
                'user_id' => Auth::id(),
            ]);
        }
    }

    private function sourceWarehouseId(int $merchantId, int $productId, ?int $warehouseId): int
    {
        $merchantProduct = MerchantProduct::where('merchant_id', $merchantId)
            ->where('product_id', $productId)
            ->first();

        if ($merchantProduct?->warehouse_id) {
            return (int) $merchantProduct->warehouse_id;
        }

        if ($warehouseId) {
            return $warehouseId;
        }

        $warehouseIds = Warehouse::orderBy('id')->pluck('id');

        if ($warehouseIds->count() === 1) {
            return (int) $warehouseIds->first();
        }

        throw ValidationException::withMessages([
            'warehouse_id' => [
                'A warehouse must be selected for this recipe sale.'
            ],
        ]);
    }
}
