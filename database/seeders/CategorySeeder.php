<?php

namespace Database\Seeders;

use App\Models\Category;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (SenopatiSeedData::categories() as $category) {
            Category::factory()->create($category);
        }
    }
}
