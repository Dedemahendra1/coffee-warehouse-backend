<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MerchantProduct>
 */
class MerchantProductFactory extends Factory
{
    protected $model = MerchantProduct::class;

    public function definition(): array
    {
        return [
            'merchant_id'  => Merchant::factory(),
            'product_id'   => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'stock'        => fake()->numberBetween(0, 200),
        ];
    }
}
