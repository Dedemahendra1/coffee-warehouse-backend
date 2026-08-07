<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;

class WarehouseProductSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::firstOrFail();
        $productByKey = Product::pluck('id', 'name');

        foreach (SenopatiSeedData::products() as $productData) {
            // Stok awal gudang = stok akhir target + seluruh kuantitas yang nantinya
            // didistribusikan ke outlet (DistributionSeeder akan menguranginya).
            $distributed = 0;
            foreach ($productData['outlets'] as [$finalStock, $sold]) {
                $distributed += $finalStock + $sold;
            }

            WarehouseProduct::factory()->create([
                'warehouse_id' => $warehouse->id,
                'product_id'   => $productByKey[$productData['name']],
                'stock'        => $productData['wfinal'] + $distributed,
            ]);
        }
    }
}
