<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\Warehouse;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;

class OutletProductSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::firstOrFail();
        $productByKey = Product::pluck('id', 'name');
        $outletNames = collect(SenopatiSeedData::outlets())->pluck('name')->all();
        $stockOutTotals = SenopatiSeedData::stockOutTotals();

        foreach (SenopatiSeedData::products() as $productData) {
            if (empty($productData['outlets'])) {
                continue;
            }

            $productId = $productByKey[$productData['name']];
            $stockOutPerOutlet = $stockOutTotals[$productData['name']] ?? [];

            foreach ($productData['outlets'] as $index => [$finalStock, $sold]) {
                // Setiap baris merchant_products merepresentasikan satu distribusi
                // Gudang Pusat -> Outlet untuk satu produk (kuantitas = stok masuk outlet).
                // Stok masuk mencakup stok akhir, jumlah terjual, dan stock out.
                MerchantProduct::factory()->create([
                    'merchant_id'  => Merchant::where('name', $outletNames[$index])->value('id'),
                    'product_id'   => $productId,
                    'warehouse_id' => $warehouse->id,
                    'stock'        => $finalStock + $sold + ($stockOutPerOutlet[$index] ?? 0),
                ]);
            }
        }
    }
}
