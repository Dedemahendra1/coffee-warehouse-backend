<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipe_seeder_creates_at_least_ten_menus_with_ingredient_stock_and_is_rerunnable(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(ProductSeeder::class);

        $warehouse = Warehouse::factory()->create();

        $this->seed(RecipeSeeder::class);

        $recipeCount = Recipe::count();
        $menuCount = Product::whereHas('recipe')->count();

        $this->assertGreaterThanOrEqual(10, $recipeCount);
        $this->assertSame($recipeCount, $menuCount);

        $water = Product::where('name', 'Mineral Water')->firstOrFail();

        $waterStock = (float) WarehouseProduct::where('warehouse_id', $warehouse->id)
            ->where('product_id', $water->id)
            ->value('stock');

        $this->assertEqualsWithDelta(20000, $waterStock, 0.001);

        $chamomile = Product::where('name', 'Chamomile Infusion')->firstOrFail();
        $chamomileRecipe = $chamomile->recipe()->firstOrFail();

        $this->assertFalse(
            $chamomileRecipe->items()->where('ingredient_id', $chamomile->id)->exists()
        );

        $this->seed(RecipeSeeder::class);

        $this->assertSame($recipeCount, Recipe::count());
        $this->assertSame($menuCount, Product::whereHas('recipe')->count());
        $this->assertEqualsWithDelta(
            $waterStock,
            (float) WarehouseProduct::where('warehouse_id', $warehouse->id)
                ->where('product_id', $water->id)
                ->value('stock'),
            0.001
        );
    }
}
