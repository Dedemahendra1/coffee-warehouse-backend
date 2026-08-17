<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Merchant>
 */
class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Senopati Coffee Setia Budi',
            'Senopati Coffee Medan Johor',
            'Senopati Coffee Ring Road',
        ]);

        return [
            'name'      => $name,
            'address'   => fake()->streetAddress() . ', ' . fake()->city(),
            'photo'     => '/assets/images/merchants/' . Str::slug($name) . '.jpg',
            'phone'     => fake()->unique()->numerify('(061) 666-####'),
            'keeper_id' => User::factory(),
        ];
    }
}
