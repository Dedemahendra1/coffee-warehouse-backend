<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryByKey = Category::pluck('id', 'name');

        foreach (SenopatiSeedData::products() as $product) {
            Product::factory()->create([
                'name'        => $product['name'],
                'thumbnail'   => '/assets/images/products/' . Str::slug($product['name']) . '.svg',
                'about'       => $product['about'],
                'unit'        => $product['unit'],
                'price'       => $product['price'],
                'category_id' => $categoryByKey[$product['category']],
                'is_popular'  => $product['is_popular'],
            ]);
        }
    }
}
