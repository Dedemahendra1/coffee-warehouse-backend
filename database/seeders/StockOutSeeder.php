<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\StockOut;
use App\Models\User;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;

class StockOutSeeder extends Seeder
{
    public function run(): void
    {
        $productByKey = Product::pluck('id', 'name');
        $outletNames = collect(SenopatiSeedData::outlets())->pluck('name')->all();
        $keeperIds = User::role('keeper')->orderBy('id')->pluck('id')->all();

        foreach (SenopatiSeedData::stockOuts() as [$outletIndex, $productName, $quantity, $reason]) {
            $merchant = Merchant::where('name', $outletNames[$outletIndex])->firstOrFail();

            StockOut::create([
                'merchant_id' => $merchant->id,
                'product_id'  => $productByKey[$productName],
                'quantity'    => $quantity,
                'reason'      => $reason,
                'user_id'     => $keeperIds[$outletIndex],
            ]);

            // Stok outlet otomatis berkurang mengikuti barang keluar (stock out).
            MerchantProduct::where('merchant_id', $merchant->id)
                ->where('product_id', $productByKey[$productName])
                ->decrement('stock', $quantity);
        }
    }
}
