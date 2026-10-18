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

class KasirAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $kasir;
    private User $otherKasir;
    private User $kasirWithoutMerchant;
    private Warehouse $warehouse;
    private Product $product;
    private Merchant $merchant;
    private Merchant $otherMerchant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'keeper', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);

        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();

        $this->merchant = Merchant::factory()->create();
        $this->otherMerchant = Merchant::factory()->create();

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->kasir = User::factory()->create([
            'email'       => 'kasir1@senopaticoffee.id',
            'password'    => 'password',
            'merchant_id' => $this->merchant->id,
        ]);
        $this->kasir->assignRole('kasir');

        $this->otherKasir = User::factory()->create([
            'email'       => 'kasir2@senopaticoffee.id',
            'password'    => 'password',
            'merchant_id' => $this->otherMerchant->id,
        ]);
        $this->otherKasir->assignRole('kasir');

        $this->kasirWithoutMerchant = User::factory()->create([
            'email'       => 'kasir3@senopaticoffee.id',
            'password'    => 'password',
            'merchant_id' => null,
        ]);
        $this->kasirWithoutMerchant->assignRole('kasir');

        WarehouseProduct::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id'   => $this->product->id,
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

    public function test_kasir_can_login_and_receives_assigned_merchant(): void
    {
        $this->postJson('/api/login', [
            'email'    => 'kasir1@senopaticoffee.id',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.merchant_id', $this->merchant->id)
            ->assertJsonFragment(['roles' => ['kasir']]);
    }

    public function test_kasir_cannot_access_master_data(): void
    {
        $this->actingAsUser($this->kasir)->getJson('/api/products')->assertStatus(403);
        $this->actingAsUser($this->kasir)->getJson('/api/categories')->assertStatus(403);
        $this->actingAsUser($this->kasir)->getJson('/api/warehouses')->assertStatus(403);
        $this->actingAsUser($this->kasir)->getJson('/api/merchants')->assertStatus(403);
        $this->actingAsUser($this->kasir)->getJson('/api/users')->assertStatus(403);
        $this->actingAsUser($this->kasir)->getJson('/api/roles')->assertStatus(403);
    }

    public function test_kasir_cannot_view_transactions(): void
    {
        $transaction = Transaction::factory()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $this->actingAsUser($this->kasir)->getJson('/api/transactions')->assertStatus(403);
        $this->actingAsUser($this->kasir)->getJson("/api/transactions/{$transaction->id}")->assertStatus(403);
        $this->actingAsUser($this->kasir)->getJson('/api/my-merchant/transactions')->assertStatus(403);
    }

    public function test_kasir_can_view_own_merchant_profile(): void
    {
        $this->actingAsUser($this->kasir)
            ->getJson('/api/my-merchant')
            ->assertOk()
            ->assertJsonPath('id', $this->merchant->id);
    }

    public function test_kasir_stock_out_list_scoped_to_own_merchant(): void
    {
        StockOut::factory()->create([
            'merchant_id' => $this->merchant->id,
            'product_id'  => $this->product->id,
            'quantity'    => 1,
            'user_id'     => $this->kasir->id,
        ]);

        StockOut::factory()->create([
            'merchant_id' => $this->otherMerchant->id,
            'product_id'  => $this->product->id,
            'quantity'    => 2,
            'user_id'     => $this->otherKasir->id,
        ]);

        $this->actingAsUser($this->kasir)
            ->getJson('/api/stock-outs')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'merchant_id' => $this->merchant->id,
                'quantity'    => 1,
            ]);
    }

    public function test_kasir_can_create_stock_out_without_merchant_id(): void
    {
        $this->actingAsUser($this->kasir)
            ->postJson('/api/stock-outs', [
                'product_id' => $this->product->id,
                'quantity'   => 5,
                'reason'     => 'Terjual',
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
            'user_id'     => $this->kasir->id,
        ]);
    }

    public function test_kasir_cannot_create_stock_out_for_other_outlet(): void
    {
        $this->actingAsUser($this->kasir)
            ->postJson('/api/stock-outs', [
                'merchant_id' => $this->otherMerchant->id,
                'product_id'  => $this->product->id,
                'quantity'    => 5,
                'reason'      => 'Terjual',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('stock_outs', [
            'merchant_id' => $this->otherMerchant->id,
            'quantity'    => 5,
        ]);
    }

    public function test_kasir_cannot_create_stock_out_exceeding_stock(): void
    {
        $this->actingAsUser($this->kasir)
            ->postJson('/api/stock-outs', [
                'product_id' => $this->product->id,
                'quantity'   => 999,
                'reason'     => 'Terjual',
            ])
            ->assertStatus(422);
    }

    public function test_kasir_without_merchant_cannot_access_stock_outs(): void
    {
        $this->actingAsUser($this->kasirWithoutMerchant)
            ->getJson('/api/stock-outs')
            ->assertStatus(403);

        $this->actingAsUser($this->kasirWithoutMerchant)
            ->postJson('/api/stock-outs', [
                'product_id' => $this->product->id,
                'quantity'   => 1,
                'reason'     => 'Terjual',
            ])
            ->assertStatus(403);
    }

    public function test_assign_kasir_role_requires_merchant(): void
    {
        $kasirRole = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);

        $userWithoutMerchant = User::factory()->create(['merchant_id' => null]);

        $this->actingAsUser($this->manager)
            ->postJson('/api/users/roles', [
                'user_id' => $userWithoutMerchant->id,
                'role_id' => $kasirRole->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('merchant_id');

        $userWithMerchant = User::factory()->create(['merchant_id' => $this->merchant->id]);

        $this->actingAsUser($this->manager)
            ->postJson('/api/users/roles', [
                'user_id' => $userWithMerchant->id,
                'role_id' => $kasirRole->id,
            ])
            ->assertOk();
    }

    public function test_kasir_without_merchant_cannot_view_my_merchant(): void
    {
        $this->actingAsUser($this->kasirWithoutMerchant)
            ->getJson('/api/my-merchant')
            ->assertStatus(404);
    }
}
