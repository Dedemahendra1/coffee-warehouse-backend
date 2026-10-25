<?php

namespace App\Services;

use App\Models\Recipe;
use App\Repositories\RecipeRepository;
use App\Support\UnitConverter;
use Illuminate\Support\Facades\DB;

class RecipeService
{
    private RecipeRepository $recipeRepository;

    public function __construct(RecipeRepository $recipeRepository)
    {
        $this->recipeRepository = $recipeRepository;
    }

    public function getAll()
    {
        return $this->recipeRepository->getAll();
    }

    public function getById(int $id): Recipe
    {
        return $this->recipeRepository->getById($id);
    }

    public function getMenus(): array
    {
        return $this->recipeRepository->getMenus()
            ->map(fn (Recipe $recipe) => [
                'recipe_id' => $recipe->id,
                'product_id' => $recipe->product_id,
                'name' => $recipe->product->name,
                'thumbnail' => $recipe->product->thumbnail,
                'price' => $recipe->product->price,
                'unit' => $recipe->product->unit,
                'category' => $recipe->product->category?->name,
                'ingredient_count' => $recipe->items->count(),
            ])
            ->values()
            ->all();
    }

    public function create(array $data): Recipe
    {
        return DB::transaction(function () use ($data) {
            $recipe = $this->recipeRepository->create([
                'product_id' => $data['product_id'],
            ]);

            $recipe->items()->createMany($this->mapItems($data['items']));

            return $this->recipeRepository->getById($recipe->id);
        });
    }

    public function update(int $id, array $data): Recipe
    {
        return DB::transaction(function () use ($id, $data) {
            $recipe = $this->recipeRepository->update($id, [
                'product_id' => $data['product_id'],
            ]);

            $recipe->items()->delete();
            $recipe->items()->createMany($this->mapItems($data['items']));

            return $this->recipeRepository->getById($id);
        });
    }

    public function delete(int $id): void
    {
        $this->recipeRepository->delete($id);
    }

    public function calculate(int $id, float $productQuantity): array
    {
        $recipe = $this->recipeRepository->getById($id);

        $items = $recipe->items->map(function ($item) use ($productQuantity) {
            $ingredient = $item->ingredient;
            $stock = (float) $ingredient->warehouses->sum('pivot.stock');

            $neededInRecipeUnit = (float) $item->quantity * $productQuantity;
            $neededInStockUnit = UnitConverter::convert($neededInRecipeUnit, $item->unit, $ingredient->unit);
            $compatible = $neededInStockUnit !== null;
            $sufficient = $compatible ? $stock >= $neededInStockUnit : null;

            return [
                'recipe_item_id' => $item->id,
                'ingredient_id' => $ingredient->id,
                'ingredient_name' => $ingredient->name,
                'ingredient_unit' => $ingredient->unit,
                'quantity_per_product' => round((float) $item->quantity, 3),
                'unit' => $item->unit,
                'quantity_needed' => round($neededInRecipeUnit, 3),
                'quantity_needed_in_stock_unit' => $compatible ? round($neededInStockUnit, 3) : null,
                'stock_available' => round($stock, 3),
                'remaining_stock' => $compatible ? round($stock - $neededInStockUnit, 3) : null,
                'unit_compatible' => $compatible,
                'sufficient' => $sufficient,
            ];
        })->values();

        return [
            'recipe_id' => $recipe->id,
            'product_id' => $recipe->product_id,
            'product_name' => $recipe->product?->name,
            'product_unit' => $recipe->product?->unit,
            'product_quantity' => $productQuantity,
            'items' => $items,
            'insufficient_count' => $items->filter(fn ($item) => $item['sufficient'] === false)->count(),
            'has_incompatible_units' => $items->contains(fn ($item) => $item['unit_compatible'] === false),
            'all_sufficient' => $items->every(fn ($item) => $item['sufficient'] === true),
            'note' => 'Perhitungan ini hanya simulasi. Stok aktual tidak diubah.',
        ];
    }

    private function mapItems(array $items): array
    {
        return array_map(fn ($item) => [
            'ingredient_id' => $item['ingredient_id'],
            'quantity' => $item['quantity'],
            'unit' => UnitConverter::normalize($item['unit']),
        ], $items);
    }
}
