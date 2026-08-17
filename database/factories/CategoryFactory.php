<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Coffee Beans',
            'Ground Coffee',
            'Milk',
            'Syrup',
            'Powder',
            'Tea',
            'Chocolate',
            'Topping',
            'Packaging',
            'Cup',
            'Lid',
            'Straw',
            'Cleaning Supplies',
            'Snack',
            'Sweetener',
        ]);

        return [
            'name'    => $name,
            'photo'   => '/assets/images/categories/' . Str::slug($name) . '.svg',
            'tagline' => fake()->sentence(6),
        ];
    }
}
