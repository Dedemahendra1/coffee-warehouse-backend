<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionProduct>
 */
class TransactionProductFactory extends Factory
{
    protected $model = TransactionProduct::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $price    = fake()->numberBetween(500, 200000);

        return [
            'transaction_id' => Transaction::factory(),
            'product_id'     => Product::factory(),
            'quantity'       => $quantity,
            'price'          => $price,
            'sub_total'      => $quantity * $price,
        ];
    }
}
