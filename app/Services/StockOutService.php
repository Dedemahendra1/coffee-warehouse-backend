<?php

namespace App\Services;

use App\Repositories\MerchantProductRepository;
use App\Repositories\MerchantRepository;
use App\Repositories\StockOutRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOutService
{
    private StockOutRepository $stockOutRepository;
    private MerchantRepository $merchantRepository;
    private MerchantProductRepository $merchantProductRepository;

    public function __construct(
        StockOutRepository $stockOutRepository,
        MerchantRepository $merchantRepository,
        MerchantProductRepository $merchantProductRepository
    ) {
        $this->stockOutRepository = $stockOutRepository;
        $this->merchantRepository = $merchantRepository;
        $this->merchantProductRepository = $merchantProductRepository;
    }

    public function getAll()
    {
        return $this->stockOutRepository->getAll();
    }

    public function getByMerchant(int $merchantId)
    {
        return $this->stockOutRepository->getByMerchant($merchantId);
    }

    public function createStockOut(array $data)
    {
        return DB::transaction(function () use ($data) {

            $merchant = $this->merchantRepository->getById($data['merchant_id'], ['id', 'keeper_id']);

            if (!$merchant) {
                throw ValidationException::withMessages([
                    'merchant_id' => ['Merchant not found.']
                ]);
            }

            if (Auth::id() !== $merchant->keeper_id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Unauthorized: You can only manage your own outlet.']
                ]);
            }

            $merchantProduct = $this->merchantProductRepository->getByMerchantAndProduct(
                $data['merchant_id'],
                $data['product_id']
            );

            if (!$merchantProduct) {
                throw ValidationException::withMessages([
                    'product_id' => ['Product not assigned to this merchant.']
                ]);
            }

            if ($merchantProduct->stock < $data['quantity']) {
                throw ValidationException::withMessages([
                    'stock' => ['Insufficient stock for this product.']
                ]);
            }

            $newStock = $merchantProduct->stock - $data['quantity'];

            $this->merchantProductRepository->updateStock(
                $data['merchant_id'],
                $data['product_id'],
                $newStock
            );

            return $this->stockOutRepository->create([
                'merchant_id' => $data['merchant_id'],
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'reason' => $data['reason'] ?? null,
                'user_id' => Auth::id(),
            ]);
        });
    }
}
