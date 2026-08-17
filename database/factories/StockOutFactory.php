<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Product;
use App\Models\StockOut;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockOut>
 */
class StockOutFactory extends Factory
{
    protected $model = StockOut::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'product_id'  => Product::factory(),
            'quantity'    => fake()->numberBetween(1, 50),
            'reason'      => fake()->randomElement([
                'Barang rusak',
                'Barang kadaluarsa',
                'Retur supplier',
                'Stok hilang',
                'Opname stok',
            ]),
            'user_id'     => User::factory(),
        ];
    }
}
