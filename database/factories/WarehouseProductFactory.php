<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WarehouseProduct>
 */
class WarehouseProductFactory extends Factory
{
    protected $model = WarehouseProduct::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id'   => Product::factory(),
            'stock'        => fake()->numberBetween(0, 500),
        ];
    }
}
