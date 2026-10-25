<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $keeper;
    private User $kasir;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('manager', 'web');
        Role::findOrCreate('keeper', 'web');
        Role::findOrCreate('kasir', 'web');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->keeper = User::factory()->create();
        $this->keeper->assignRole('keeper');

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('kasir');

        $this->warehouse = Warehouse::factory()->create();
    }

    private function actingAsUser(User $user): static
    {
        Sanctum::actingAs($user);

        return $this;
    }

    private function makeProduct(string $unit, int $stock = 0): Product
    {
        $product = Product::factory()->create(['unit' => $unit]);

        if ($stock > 0) {
            WarehouseProduct::factory()->create([
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $product->id,
                'stock' => $stock,
            ]);
        }

        return $product;
    }

    private function makeRecipe(Product $product, Product $ingredient, float $quantity, string $unit): Recipe
    {
        $recipe = Recipe::create(['product_id' => $product->id]);

        RecipeItem::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => $quantity,
            'unit' => $unit,
        ]);

        return $recipe;
    }

    public function test_unauthenticated_user_cannot_access_recipes(): void
    {
        $this->getJson('/api/recipes')->assertStatus(401);
    }

    public function test_manager_can_list_recipes(): void
    {
        $product = $this->makeProduct('pcs');
        $ingredient = $this->makeProduct('ml', 1000);
        $this->makeRecipe($product, $ingredient, 100, 'ml');

        $response = $this->actingAsUser($this->manager)->getJson('/api/recipes');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.product_id', $product->id)
            ->assertJsonPath('0.total_items', 1)
            ->assertJsonPath('0.items.0.ingredient.id', $ingredient->id)
            ->assertJsonPath('0.items.0.ingredient.stock', 1000);
    }

    public function test_manager_can_create_recipe_with_multiple_ingredients(): void
    {
        $product = $this->makeProduct('pcs');
        $milk = $this->makeProduct('liter');
        $coffee = $this->makeProduct('gram');

        $response = $this->actingAsUser($this->manager)->postJson('/api/recipes', [
            'product_id' => $product->id,
            'items' => [
                ['ingredient_id' => $milk->id, 'quantity' => 0.1, 'unit' => 'liter'],
                ['ingredient_id' => $coffee->id, 'quantity' => 18, 'unit' => 'gram'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('product_id', $product->id)
            ->assertJsonPath('total_items', 2);

        $this->assertDatabaseHas('recipes', ['product_id' => $product->id]);
        $this->assertDatabaseHas('recipe_items', [
            'ingredient_id' => $milk->id,
            'unit' => 'liter',
        ]);
        $this->assertDatabaseHas('recipe_items', [
            'ingredient_id' => $coffee->id,
            'unit' => 'gram',
        ]);
    }

    public function test_create_recipe_rejects_product_that_already_has_recipe(): void
    {
        $product = $this->makeProduct('pcs');
        $ingredient = $this->makeProduct('ml');
        $this->makeRecipe($product, $ingredient, 100, 'ml');

        $this->actingAsUser($this->manager)->postJson('/api/recipes', [
            'product_id' => $product->id,
            'items' => [
                ['ingredient_id' => $ingredient->id, 'quantity' => 50, 'unit' => 'ml'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('product_id');
    }

    public function test_create_recipe_rejects_duplicate_ingredient(): void
    {
        $product = $this->makeProduct('pcs');
        $ingredient = $this->makeProduct('ml');

        $this->actingAsUser($this->manager)->postJson('/api/recipes', [
            'product_id' => $product->id,
            'items' => [
                ['ingredient_id' => $ingredient->id, 'quantity' => 100, 'unit' => 'ml'],
                ['ingredient_id' => $ingredient->id, 'quantity' => 50, 'unit' => 'ml'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.ingredient_id');
    }

    public function test_create_recipe_rejects_ingredient_equal_to_product(): void
    {
        $product = $this->makeProduct('pcs');

        $this->actingAsUser($this->manager)->postJson('/api/recipes', [
            'product_id' => $product->id,
            'items' => [
                ['ingredient_id' => $product->id, 'quantity' => 1, 'unit' => 'pcs'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.ingredient_id');
    }

    public function test_create_recipe_requires_at_least_one_item(): void
    {
        $product = $this->makeProduct('pcs');

        $this->actingAsUser($this->manager)->postJson('/api/recipes', [
            'product_id' => $product->id,
            'items' => [],
        ])->assertStatus(422)->assertJsonValidationErrors('items');
    }

    public function test_create_recipe_rejects_unknown_ingredient(): void
    {
        $product = $this->makeProduct('pcs');

        $this->actingAsUser($this->manager)->postJson('/api/recipes', [
            'product_id' => $product->id,
            'items' => [
                ['ingredient_id' => 999999, 'quantity' => 1, 'unit' => 'pcs'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.ingredient_id');
    }

    public function test_create_recipe_rejects_non_positive_quantity(): void
    {
        $product = $this->makeProduct('pcs');
        $ingredient = $this->makeProduct('ml');

        $this->actingAsUser($this->manager)->postJson('/api/recipes', [
            'product_id' => $product->id,
            'items' => [
                ['ingredient_id' => $ingredient->id, 'quantity' => 0, 'unit' => 'ml'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');
    }

    public function test_create_recipe_rejects_incompatible_unit(): void
    {
        $product = $this->makeProduct('pcs');
        $ingredient = $this->makeProduct('kg');

        $this->actingAsUser($this->manager)->postJson('/api/recipes', [
            'product_id' => $product->id,
            'items' => [
                ['ingredient_id' => $ingredient->id, 'quantity' => 100, 'unit' => 'ml'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.unit');
    }

    public function test_manager_can_show_recipe(): void
    {
        $product = $this->makeProduct('pcs');
        $ingredient = $this->makeProduct('ml', 250);
        $recipe = $this->makeRecipe($product, $ingredient, 100, 'ml');

        $this->actingAsUser($this->manager)
            ->getJson("/api/recipes/{$recipe->id}")
            ->assertOk()
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('items.0.ingredient.id', $ingredient->id)
            ->assertJsonPath('items.0.ingredient.stock', 250);
    }

    public function test_manager_can_update_recipe_and_replace_items(): void
    {
        $product = $this->makeProduct('pcs');
        $oldIngredient = $this->makeProduct('ml');
        $newIngredient = $this->makeProduct('gram');
        $recipe = $this->makeRecipe($product, $oldIngredient, 100, 'ml');

        $this->actingAsUser($this->manager)->putJson("/api/recipes/{$recipe->id}", [
            'product_id' => $product->id,
            'items' => [
                ['ingredient_id' => $newIngredient->id, 'quantity' => 20, 'unit' => 'gram'],
            ],
        ])->assertOk()
            ->assertJsonPath('total_items', 1)
            ->assertJsonPath('items.0.ingredient.id', $newIngredient->id);

        $this->assertDatabaseMissing('recipe_items', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $oldIngredient->id,
        ]);
        $this->assertDatabaseHas('recipe_items', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $newIngredient->id,
            'quantity' => 20,
        ]);
    }

    public function test_manager_can_delete_recipe_and_items(): void
    {
        $product = $this->makeProduct('pcs');
        $ingredient = $this->makeProduct('ml');
        $recipe = $this->makeRecipe($product, $ingredient, 100, 'ml');

        $this->actingAsUser($this->manager)
            ->deleteJson("/api/recipes/{$recipe->id}")
            ->assertOk()
            ->assertJson(['message' => 'recipe deleted successfully']);

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
        $this->assertDatabaseMissing('recipe_items', ['recipe_id' => $recipe->id]);
    }

    public function test_calculate_recipe_converts_units_and_does_not_change_stock(): void
    {
        $product = $this->makeProduct('pcs');
        $milk = $this->makeProduct('liter', 10);
        $recipe = $this->makeRecipe($product, $milk, 100, 'ml');

        $response = $this->actingAsUser($this->manager)->postJson(
            "/api/recipes/{$recipe->id}/calculate",
            ['quantity' => 10]
        );

        $response->assertOk()
            ->assertJsonPath('product_quantity', 10)
            ->assertJsonPath('items.0.unit', 'ml')
            ->assertJsonPath('items.0.ingredient_unit', 'liter')
            ->assertJsonPath('items.0.unit_compatible', true)
            ->assertJsonPath('items.0.sufficient', true)
            ->assertJsonPath('items.0.quantity_needed_in_stock_unit', 1)
            ->assertJsonPath('items.0.stock_available', 10)
            ->assertJsonPath('items.0.remaining_stock', 9)
            ->assertJsonPath('all_sufficient', true)
            ->assertJsonPath('insufficient_count', 0);

        $this->assertEquals(1000, $response->json('items.0.quantity_needed'));
        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $milk->id,
            'stock' => 10,
        ]);
    }

    public function test_calculate_recipe_flags_insufficient_stock(): void
    {
        $product = $this->makeProduct('pcs');
        $milk = $this->makeProduct('liter', 1);
        $recipe = $this->makeRecipe($product, $milk, 100, 'ml');

        $response = $this->actingAsUser($this->manager)->postJson(
            "/api/recipes/{$recipe->id}/calculate",
            ['quantity' => 20]
        );

        $response->assertOk()
            ->assertJsonPath('items.0.quantity_needed_in_stock_unit', 2)
            ->assertJsonPath('items.0.stock_available', 1)
            ->assertJsonPath('items.0.remaining_stock', -1)
            ->assertJsonPath('items.0.sufficient', false)
            ->assertJsonPath('all_sufficient', false)
            ->assertJsonPath('insufficient_count', 1);

        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $milk->id,
            'stock' => 1,
        ]);
    }

    public function test_calculate_recipe_flags_incompatible_units_without_failing(): void
    {
        $product = $this->makeProduct('pcs');
        $ingredient = $this->makeProduct('pcs', 5);
        $recipe = $this->makeRecipe($product, $ingredient, 2, 'botol');

        $response = $this->actingAsUser($this->manager)->postJson(
            "/api/recipes/{$recipe->id}/calculate",
            ['quantity' => 3]
        );

        $response->assertOk()
            ->assertJsonPath('items.0.unit_compatible', false)
            ->assertJsonPath('items.0.quantity_needed_in_stock_unit', null)
            ->assertJsonPath('items.0.remaining_stock', null)
            ->assertJsonPath('items.0.sufficient', null)
            ->assertJsonPath('has_incompatible_units', true)
            ->assertJsonPath('all_sufficient', false);
    }

    public function test_calculate_recipe_requires_positive_quantity(): void
    {
        $product = $this->makeProduct('pcs');
        $ingredient = $this->makeProduct('ml');
        $recipe = $this->makeRecipe($product, $ingredient, 100, 'ml');

        $this->actingAsUser($this->manager)->postJson(
            "/api/recipes/{$recipe->id}/calculate",
            ['quantity' => 0]
        )->assertStatus(422)->assertJsonValidationErrors('quantity');
    }

    public function test_calculate_recipe_returns_404_for_unknown_recipe(): void
    {
        $this->actingAsUser($this->manager)->postJson(
            '/api/recipes/999999/calculate',
            ['quantity' => 1]
        )->assertStatus(404);
    }

    public function test_keeper_cannot_access_recipes(): void
    {
        $this->actingAsUser($this->keeper)
            ->getJson('/api/recipes')
            ->assertStatus(403);
    }

    public function test_kasir_cannot_access_recipes(): void
    {
        $this->actingAsUser($this->kasir)
            ->getJson('/api/recipes')
            ->assertStatus(403);
    }
}

