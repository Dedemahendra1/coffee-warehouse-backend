<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Arabica Gayo',
            'Arabica Toraja',
            'Robusta Lampung',
            'House Blend',
            'Ground Arabica Gayo',
            'Ground Robusta Lampung',
            'Fresh Milk UHT',
            'Oat Milk Barista',
            'Vanilla Syrup',
            'Caramel Syrup',
            'Hazelnut Syrup',
            'Brown Sugar Syrup',
            'Matcha Powder',
            'Chocolate Powder',
            'Green Tea Powder',
            'Green Tea Leaves',
            'Chamomile Tea',
            'Dark Chocolate Bar',
            'White Chocolate Powder',
            'Whipped Cream',
            'Caramel Drizzle',
            'Coffee Filter',
            'Takeaway Bag',
            'Paper Cup 12 oz',
            'Paper Cup 16 oz',
            'Cup Lid',
            'Paper Straw',
            'Sanitizer Solution',
            'Dish Soap',
            'Frozen Croissant',
            'Salted Butter Cookies',
            'Banana Bread Slice',
            'Palm Sugar',
        ]);

        $units = ['kg', 'gram', 'liter', 'ml', 'botol', 'can', 'pcs'];

        return [
            'name'        => $name,
            'thumbnail'   => '/assets/images/products/' . Str::slug($name) . '.svg',
            'about'       => fake()->sentence(8),
            'unit'        => fake()->randomElement($units),
            'price'       => fake()->numberBetween(500, 200000),
            'category_id' => Category::factory(),
            'is_popular'  => fake()->boolean(20),
        ];
    }
}
