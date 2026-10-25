<?php

namespace App\Repositories;

use App\Models\StockOut;

class StockOutRepository
{
    public function getAll()
    {
        return StockOut::with(['merchant', 'product.category', 'user'])
            ->whereNull('warehouse_id')
            ->latest()
            ->paginate(10);
    }

    public function getByMerchant(int $merchantId)
    {
        return StockOut::with(['product.category', 'user'])
            ->where('merchant_id', $merchantId)
            ->whereNull('warehouse_id')
            ->latest()
            ->paginate(10);
    }

    public function getById(int $id)
    {
        return StockOut::with(['merchant', 'product.category', 'user'])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return StockOut::create($data);
    }
}
