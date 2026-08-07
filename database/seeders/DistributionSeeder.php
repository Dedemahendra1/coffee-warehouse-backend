<?php

namespace Database\Seeders;

use App\Models\MerchantProduct;
use App\Models\WarehouseProduct;
use Illuminate\Database\Seeder;

class DistributionSeeder extends Seeder
{
    public function run(): void
    {
        // Saat distribusi dilakukan, stok gudang otomatis berkurang sebesar total
        // yang dikirim ke seluruh outlet (kuantitas tercatat pada merchant_products).
        $distributedPerProduct = MerchantProduct::query()
            ->selectRaw('product_id, SUM(stock) as total')
            ->groupBy('product_id')
            ->get()
            ->pluck('total', 'product_id');

        foreach ($distributedPerProduct as $productId => $total) {
            WarehouseProduct::where('product_id', $productId)->decrement('stock', (int) $total);
        }
    }
}
