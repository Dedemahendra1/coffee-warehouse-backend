<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockOut;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecipeSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $keeper;
    private Merchant $merchant;
    private Warehouse $warehouse;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('manager', 'web');
        Role::findOrCreate('keeper', 'web');
        Role::findOrCreate('kasir', 'web');

        $this->keeper = User::factory()->create();
        $this->keeper->assignRole('keeper');

        $this->merchant = Merchant::factory()->create([
            'keeper_id' => $this->keeper->id,
        ]);

        $this->warehouse = Warehouse::factory()->create();
        $this->category = Category::factory()->create();
    }

    private function actingAsUser(User $user): static
    {
        Sanctum::actingAs($user);

        return $this;
    }

    private function makeProduct(string $name, string $unit, int $price, float $stock = 0): Product
    {
        $product = Product::factory()->create([
            'name' => $name,
            'unit' => $unit,
            'price' => $price,
            'category_id' => $this->category->id,
        ]);

        if ($stock > 0) {
            WarehouseProduct::factory()->create([
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $product->id,
                'stock' => $stock,
            ]);
        }

        return $product;
    }

    private function makeRecipe(Product $menu, Product $ingredient, float $quantity, string $unit): Recipe
    {
        $recipe = Recipe::create(['product_id' => $menu->id]);

        $recipe->items()->create([
            'ingredient_id' => $ingredient->id,
            'quantity' => $quantity,
            'unit' => $unit,
        ]);

        return $recipe;
    }

    private function salePayload(Product $menu, int $quantity, string $key = 'sale-key'): array
    {
        return [
            'name' => 'Walk-in Customer',
            'phone' => '-',
            'merchant_id' => $this->merchant->id,
            'idempotency_key' => $key,
            'products' => [
                ['product_id' => $menu->id, 'quantity' => $quantity],
            ],
        ];
    }

    private function stockOf(Product $product): float
    {
        return (float) WarehouseProduct::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $product->id)
            ->value('stock');
    }

    public function test_recipe_sale_deducts_ingredient_stock_and_records_warehouse_stock_out(): void
    {
        $coffee = $this->makeProduct('Test Coffee Bean', 'kg', 150000, 1);
        $menu = $this->makeProduct('Test Espresso', 'pcs', 20000);
        $this->makeRecipe($menu, $coffee, 18, 'gram');

        $response = $this->actingAsUser($this->keeper)
            ->postJson('/api/transactions', $this->salePayload($menu, 2));

        $response->assertStatus(201)
            ->assertJsonPath('data.merchant_id', $this->merchant->id)
            ->assertJsonPath('data.grand_total', 44000);

        $transactionId = $response->json('data.id');

        $this->assertEqualsWithDelta(0.964, $this->stockOf($coffee), 0.0001);
        $this->assertDatabaseCount('merchant_products', 0);

        $stockOut = StockOut::where('transaction_id', $transactionId)->firstOrFail();

        $this->assertSame($this->merchant->id, $stockOut->merchant_id);
        $this->assertSame($this->warehouse->id, $stockOut->warehouse_id);
        $this->assertSame($coffee->id, $stockOut->product_id);
        $this->assertSame($this->keeper->id, $stockOut->user_id);
        $this->assertEqualsWithDelta(0.036, $stockOut->quantity, 0.0001);
        $this->assertStringContainsString("transaction #{$transactionId}", $stockOut->reason);
    }

    public function test_recipe_sale_fails_without_changing_any_stock_when_ingredient_is_insufficient(): void
    {
        $coffee = $this->makeProduct('Test Coffee Bean', 'kg', 150000, 0.01);
        $menu = $this->makeProduct('Test Espresso', 'pcs', 20000);
        $this->makeRecipe($menu, $coffee, 18, 'gram');

        $this->actingAsUser($this->keeper)
            ->postJson('/api/transactions', $this->salePayload($menu, 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors('stock');

        $this->assertEqualsWithDelta(0.01, $this->stockOf($coffee), 0.0001);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_products', 0);
        $this->assertDatabaseCount('stock_outs', 0);
    }

    public function test_same_idempotency_key_does_not_deduct_stock_twice(): void
    {
        $coffee = $this->makeProduct('Test Coffee Bean', 'kg', 150000, 1);
        $menu = $this->makeProduct('Test Espresso', 'pcs', 20000);
        $this->makeRecipe($menu, $coffee, 18, 'gram');

        $payload = $this->salePayload($menu, 1, 'same-sale');

        $first = $this->actingAsUser($this->keeper)
            ->postJson('/api/transactions', $payload)
            ->assertStatus(201);

        $second = $this->actingAsUser($this->keeper)
            ->postJson('/api/transactions', $payload)
            ->assertStatus(201);

        $second->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_products', 1);
        $this->assertDatabaseCount('stock_outs', 1);
        $this->assertEqualsWithDelta(0.982, $this->stockOf($coffee), 0.0001);
    }

    public function test_non_recipe_sale_keeps_merchant_stock_behavior(): void
    {
        $product = $this->makeProduct('Test Packaged Product', 'pcs', 10000, 10);

        MerchantProduct::factory()->create([
            'merchant_id' => $this->merchant->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'stock' => 5,
        ]);

        $this->actingAsUser($this->keeper)
            ->postJson('/api/transactions', $this->salePayload($product, 2))
            ->assertStatus(201);

        $this->assertDatabaseHas('merchant_products', [
            'merchant_id' => $this->merchant->id,
            'product_id' => $product->id,
            'stock' => 3,
        ]);

        $this->assertEqualsWithDelta(10, $this->stockOf($product), 0.0001);
        $this->assertDatabaseCount('stock_outs', 0);
    }

    public function test_recipe_sale_rejects_incompatible_units_without_changing_stock(): void
    {
        $coffee = $this->makeProduct('Test Coffee Bean', 'kg', 150000, 1);
        $menu = $this->makeProduct('Test Espresso', 'pcs', 20000);
        $this->makeRecipe($menu, $coffee, 100, 'ml');

        $this->actingAsUser($this->keeper)
            ->postJson('/api/transactions', $this->salePayload($menu, 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors('recipe');

        $this->assertEqualsWithDelta(1, $this->stockOf($coffee), 0.0001);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('stock_outs', 0);
    }

    public function test_recipe_sale_rejects_other_outlet(): void
    {
        $coffee = $this->makeProduct('Test Coffee Bean', 'kg', 150000, 1);
        $menu = $this->makeProduct('Test Espresso', 'pcs', 20000);
        $this->makeRecipe($menu, $coffee, 18, 'gram');

        $otherKeeper = User::factory()->create();
        $otherKeeper->assignRole('keeper');

        $otherMerchant = Merchant::factory()->create([
            'keeper_id' => $otherKeeper->id,
        ]);

        $payload = $this->salePayload($menu, 1);
        $payload['merchant_id'] = $otherMerchant->id;

        $this->actingAsUser($this->keeper)
            ->postJson('/api/transactions', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('authorization');

        $this->assertEqualsWithDelta(1, $this->stockOf($coffee), 0.0001);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_recipe_warehouse_movements_are_hidden_from_outlet_stock_out_list(): void
    {
        $coffee = $this->makeProduct('Test Coffee Bean', 'kg', 150000, 1);
        $menu = $this->makeProduct('Test Espresso', 'pcs', 20000);
        $this->makeRecipe($menu, $coffee, 18, 'gram');

        $this->actingAsUser($this->keeper)
            ->postJson('/api/transactions', $this->salePayload($menu, 1))
            ->assertStatus(201);

        $this->actingAsUser($this->keeper)
            ->getJson('/api/stock-outs')
            ->assertOk()
            ->assertJsonCount(0);

        $this->assertDatabaseCount('stock_outs', 1);
    }

    public function test_keeper_can_fetch_sellable_menus(): void
    {
        $coffee = $this->makeProduct('Test Coffee Bean', 'kg', 150000, 1);
        $menu = $this->makeProduct('Test Espresso', 'pcs', 20000);
        $this->makeRecipe($menu, $coffee, 18, 'gram');

        $this->actingAsUser($this->keeper)
            ->getJson('/api/sellable-menus')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.product_id', $menu->id)
            ->assertJsonPath('0.name', $menu->name)
            ->assertJsonPath('0.price', 20000);
    }

    public function test_kasir_cannot_fetch_sellable_menus(): void
    {
        $kasir = User::factory()->create(['merchant_id' => $this->merchant->id]);
        $kasir->assignRole('kasir');

        $this->actingAsUser($kasir)
            ->getJson('/api/sellable-menus')
            ->assertStatus(403);
    }
}
