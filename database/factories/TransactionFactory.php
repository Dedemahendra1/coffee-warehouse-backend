<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $subTotal = fake()->numberBetween(10000, 5000000);
        $taxTotal = (int) round($subTotal * 0.1);
        $createdAt = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'name'        => fake('id_ID')->name(),
            'phone'       => '08' . fake()->unique()->numerify('##########'),
            'sub_total'   => $subTotal,
            'tax_total'   => $taxTotal,
            'grand_total' => $subTotal + $taxTotal,
            'merchant_id' => Merchant::factory(),
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ];
    }
}
