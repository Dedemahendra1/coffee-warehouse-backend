<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\StockOut;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $keeper;
    private User $otherKeeper;
    private Warehouse $warehouse;
    private Product $product;
    private Product $product2;
    private Merchant $merchant;
    private Merchant $otherMerchant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('manager', 'web');
        Role::findOrCreate('keeper', 'web');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->keeper = User::factory()->create();
        $this->keeper->assignRole('keeper');

        $this->otherKeeper = User::factory()->create();
        $this->otherKeeper->assignRole('keeper');

        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
        $this->product2 = Product::factory()->create();

        $this->merchant = Merchant::factory()->create(['keeper_id' => $this->keeper->id]);
        $this->otherMerchant = Merchant::factory()->create(['keeper_id' => $this->otherKeeper->id]);

        WarehouseProduct::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id'   => $this->product->id,
            'stock'        => 50,
        ]);

        WarehouseProduct::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id'   => $this->product2->id,
            'stock'        => 50,
        ]);

        MerchantProduct::factory()->create([
            'merchant_id'  => $this->merchant->id,
            'product_id'   => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'stock'        => 20,
        ]);
    }

    private function actingAsUser(User $user): static
    {
        Sanctum::actingAs($user);

        return $this;
    }

    public function test_unauthenticated_user_cannot_access_products(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    public function test_manager_can_view_all_transactions(): void
    {
        $this->actingAsUser($this->manager)
            ->getJson('/api/transactions')
            ->assertOk();
    }

    public function test_manager_cannot_add_stock_to_warehouse(): void
    {
        $this->actingAsUser($this->manager)
            ->postJson("/api/warehouses/{$this->warehouse->id}/products", [
                'product_id' => $this->product->id,
                'stock'      => 10,
            ])
            ->assertStatus(403);
    }

    public function test_manager_cannot_distribute_to_merchant(): void
    {
        $this->actingAsUser($this->manager)
            ->postJson("/api/merchants/{$this->merchant->id}/products", [
                'product_id'   => $this->product->id,
                'stock'        => 5,
                'warehouse_id' => $this->warehouse->id,
            ])
            ->assertStatus(403);
    }

    public function test_manager_can_detach_product_from_warehouse(): void
    {
        $this->actingAsUser($this->manager)
            ->deleteJson("/api/warehouses/{$this->warehouse->id}/products/{$this->product->id}")
            ->assertOk();

        $this->assertDatabaseMissing('warehouse_products', [
            'warehouse_id' => $this->warehouse->id,
            'product_id'   => $this->product->id,
        ]);
    }

    public function test_manager_cannot_create_stock_out(): void
    {
        $this->actingAsUser($this->manager)
            ->postJson('/api/stock-outs', [
                'merchant_id' => $this->merchant->id,
                'product_id'  => $this->product->id,
                'quantity'    => 1,
                'reason'      => 'Barang rusak',
            ])
            ->assertStatus(403);
    }

    public function test_keeper_can_add_stock_to_warehouse(): void
    {
        $this->actingAsUser($this->keeper)
            ->postJson("/api/warehouses/{$this->warehouse->id}/products", [
                'product_id' => $this->product->id,
                'stock'      => 10,
            ])
            ->assertOk()
            ->assertJson(['message' => 'Product attached successfully']);
    }

    public function test_keeper_can_distribute_to_own_merchant(): void
    {
        $this->actingAsUser($this->keeper)
            ->postJson("/api/merchants/{$this->merchant->id}/products", [
                'product_id'   => $this->product2->id,
                'stock'        => 5,
                'warehouse_id' => $this->warehouse->id,
            ])
            ->assertStatus(201);
    }

    public function test_keeper_cannot_distribute_to_other_merchant(): void
    {
        $this->actingAsUser($this->keeper)
            ->postJson("/api/merchants/{$this->otherMerchant->id}/products", [
                'product_id'   => $this->product->id,
                'stock'        => 5,
                'warehouse_id' => $this->warehouse->id,
            ])
            ->assertStatus(422);
    }

    public function test_keeper_cannot_manage_master_data(): void
    {
        $this->actingAsUser($this->keeper)
            ->postJson('/api/products', [
                'name'        => 'Test Product',
                'price'       => 1000,
                'category_id' => 1,
            ])
            ->assertStatus(403);
    }

    public function test_keeper_can_create_stock_out(): void
    {
        $this->actingAsUser($this->keeper)
            ->postJson('/api/stock-outs', [
                'merchant_id' => $this->merchant->id,
                'product_id'  => $this->product->id,
                'quantity'    => 5,
                'reason'      => 'Barang rusak',
            ])
            ->assertStatus(201)
            ->assertJson(['message' => 'Stock out recorded successfully']);

        $this->assertDatabaseHas('merchant_products', [
            'merchant_id' => $this->merchant->id,
            'product_id'  => $this->product->id,
            'stock'       => 15,
        ]);

        $this->assertDatabaseHas('stock_outs', [
            'merchant_id' => $this->merchant->id,
            'product_id'  => $this->product->id,
            'quantity'    => 5,
            'user_id'     => $this->keeper->id,
        ]);
    }

    public function test_keeper_cannot_create_stock_out_exceeding_stock(): void
    {
        $this->actingAsUser($this->keeper)
            ->postJson('/api/stock-outs', [
                'merchant_id' => $this->merchant->id,
                'product_id'  => $this->product->id,
                'quantity'    => 999,
                'reason'      => 'Barang rusak',
            ])
            ->assertStatus(422);
    }

    public function test_keeper_stock_out_list_scoped_to_own_merchant(): void
    {
        StockOut::factory()->create([
            'merchant_id' => $this->merchant->id,
            'product_id'  => $this->product->id,
            'quantity'    => 1,
            'user_id'     => $this->keeper->id,
        ]);

        StockOut::factory()->create([
            'merchant_id' => $this->otherMerchant->id,
            'product_id'  => $this->product->id,
            'quantity'    => 2,
            'user_id'     => $this->otherKeeper->id,
        ]);

        $this->actingAsUser($this->keeper)
            ->getJson('/api/stock-outs')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'merchant_id' => $this->merchant->id,
                'quantity'    => 1,
            ]);
    }

    public function test_keeper_cannot_view_other_merchant_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'merchant_id' => $this->otherMerchant->id,
        ]);

        $this->actingAsUser($this->keeper)
            ->getJson("/api/transactions/{$transaction->id}")
            ->assertStatus(403);
    }
}
