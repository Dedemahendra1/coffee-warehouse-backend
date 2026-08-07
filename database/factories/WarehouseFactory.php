<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Gudang Pusat Senopati Coffee',
            'Gudang Cabang Senopati Jakarta',
            'Gudang Distribusi Bekasi',
        ]);

        return [
            'name'    => $name,
            'address' => fake()->streetAddress() . ', ' . fake()->city(),
            'photo'   => '/assets/images/warehouses/' . Str::slug($name) . '.jpg',
            'phone'   => fake()->unique()->numerify('(021) 555-####'),
        ];
    }
}
