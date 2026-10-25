<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Support\UnitConverter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RecipeSeeder extends Seeder
{
    /**
     * Bahan baku tambahan yang belum tersedia di master produk.
     * Stok awal hanya dibuat bila baris inventory belum ada.
     */
    private const NEW_INGREDIENTS = [
        [
            'name'          => 'Mineral Water',
            'category'      => 'Beverage Supplies',
            'unit'          => 'ml',
            'price'         => 100,
            'initial_stock' => 20000,
            'about'         => 'Air mineral untuk espresso-based drink dan teh.',
        ],
        [
            'name'          => 'Ice Cube',
            'category'      => 'Beverage Supplies',
            'unit'          => 'gram',
            'price'         => 50,
            'initial_stock' => 10000,
            'about'         => 'Es batu untuk minuman dingin.',
        ],
        [
            'name'          => 'Cane Sugar',
            'category'      => 'Sweetener',
            'unit'          => 'gram',
            'price'         => 25,
            'initial_stock' => 5000,
            'about'         => 'Gula pasir untuk pemanis minuman.',
        ],
    ];

    /**
     * Komposisi dummy per satu porsi. Satuan mengikuti data inventory aktual
     * (kg/gram, liter/ml, botol, can) sehingga validasi konversi tetap lolos.
     */
    private const MENUS = [
        [
            'name'    => 'Espresso',
            'price'   => 18000,
            'popular' => false,
            'about'   => 'Espresso pekat satu shot dari biji Arabica Gayo.',
            'items'   => [
                ['Arabica Gayo', 18, 'gram'],
            ],
        ],
        [
            'name'    => 'Americano',
            'price'   => 22000,
            'popular' => false,
            'about'   => 'Espresso Arabica Gayo dengan air mineral panas.',
            'items'   => [
                ['Arabica Gayo', 18, 'gram'],
                ['Mineral Water', 150, 'ml'],
            ],
        ],
        [
            'name'    => 'Long Black',
            'price'   => 25000,
            'popular' => false,
            'about'   => 'Air mineral panas dituang di atas espresso House Blend.',
            'items'   => [
                ['House Blend', 18, 'gram'],
                ['Mineral Water', 120, 'ml'],
            ],
        ],
        [
            'name'    => 'Cappuccino',
            'price'   => 28000,
            'popular' => false,
            'about'   => 'Espresso Arabica Toraja dengan susu dan foam lembut.',
            'items'   => [
                ['Arabica Toraja', 18, 'gram'],
                ['Fresh Milk UHT', 120, 'ml'],
            ],
        ],
        [
            'name'    => 'Cafe Latte',
            'price'   => 28000,
            'popular' => true,
            'about'   => 'Espresso House Blend dengan susu full cream.',
            'items'   => [
                ['House Blend', 18, 'gram'],
                ['Fresh Milk UHT', 180, 'ml'],
            ],
        ],
        [
            'name'    => 'Vanilla Latte',
            'price'   => 32000,
            'popular' => false,
            'about'   => 'Cafe latte dengan sirup vanilla.',
            'items'   => [
                ['House Blend', 18, 'gram'],
                ['Fresh Milk UHT', 180, 'ml'],
                ['Vanilla Syrup', 0.02, 'botol'],
            ],
        ],
        [
            'name'    => 'Caramel Latte',
            'price'   => 32000,
            'popular' => false,
            'about'   => 'Cafe latte dengan sirup caramel.',
            'items'   => [
                ['House Blend', 18, 'gram'],
                ['Fresh Milk UHT', 180, 'ml'],
                ['Caramel Syrup', 0.02, 'botol'],
            ],
        ],
        [
            'name'    => 'Hazelnut Latte',
            'price'   => 32000,
            'popular' => false,
            'about'   => 'Cafe latte dengan sirup hazelnut.',
            'items'   => [
                ['House Blend', 18, 'gram'],
                ['Fresh Milk UHT', 180, 'ml'],
                ['Hazelnut Syrup', 0.02, 'botol'],
            ],
        ],
        [
            'name'    => 'Mocha',
            'price'   => 33000,
            'popular' => false,
            'about'   => 'Espresso, susu, dan bubuk cokelat.',
            'items'   => [
                ['House Blend', 18, 'gram'],
                ['Fresh Milk UHT', 150, 'ml'],
                ['Chocolate Powder', 15, 'gram'],
            ],
        ],
        [
            'name'    => 'Kopi Susu Gula Aren',
            'price'   => 25000,
            'popular' => true,
            'about'   => 'Kopi robusta dengan susu dan gula aren, disajikan dingin.',
            'items'   => [
                ['Robusta Lampung', 18, 'gram'],
                ['Fresh Milk UHT', 150, 'ml'],
                ['Palm Sugar', 20, 'gram'],
                ['Ice Cube', 100, 'gram'],
            ],
        ],
        [
            'name'    => 'Es Kopi Susu',
            'price'   => 24000,
            'popular' => true,
            'about'   => 'Espresso House Blend dengan susu dan gula, disajikan dingin.',
            'items'   => [
                ['House Blend', 18, 'gram'],
                ['Fresh Milk UHT', 150, 'ml'],
                ['Cane Sugar', 12, 'gram'],
                ['Ice Cube', 100, 'gram'],
            ],
        ],
        [
            'name'    => 'Caramel Macchiato',
            'price'   => 34000,
            'popular' => false,
            'about'   => 'Latte dengan sirup caramel dan lapisan busa susu.',
            'items'   => [
                ['House Blend', 18, 'gram'],
                ['Fresh Milk UHT', 180, 'ml'],
                ['Caramel Syrup', 0.02, 'botol'],
            ],
        ],
        [
            'name'    => 'Matcha Latte',
            'price'   => 30000,
            'popular' => true,
            'about'   => 'Matcha premium dengan susu full cream.',
            'items'   => [
                ['Matcha Powder', 5, 'gram'],
                ['Fresh Milk UHT', 180, 'ml'],
                ['Cane Sugar', 10, 'gram'],
            ],
        ],
        [
            'name'    => 'Chocolate Latte',
            'price'   => 30000,
            'popular' => true,
            'about'   => 'Bubuk cokelat dengan susu full cream.',
            'items'   => [
                ['Chocolate Powder', 20, 'gram'],
                ['Fresh Milk UHT', 180, 'ml'],
                ['Cane Sugar', 10, 'gram'],
            ],
        ],
        [
            'name'    => 'Chocolate Milk',
            'price'   => 28000,
            'popular' => false,
            'about'   => 'Minuman cokelat susu tanpa kopi.',
            'items'   => [
                ['Chocolate Powder', 20, 'gram'],
                ['Fresh Milk UHT', 200, 'ml'],
                ['Cane Sugar', 10, 'gram'],
            ],
        ],
        [
            'name'    => 'Green Tea Latte',
            'price'   => 30000,
            'popular' => false,
            'about'   => 'Bubuk green tea dengan susu full cream.',
            'items'   => [
                ['Green Tea Powder', 5, 'gram'],
                ['Fresh Milk UHT', 180, 'ml'],
                ['Cane Sugar', 10, 'gram'],
            ],
        ],
        [
            'name'    => 'White Chocolate Latte',
            'price'   => 32000,
            'popular' => false,
            'about'   => 'Bubuk white chocolate dengan susu full cream.',
            'items'   => [
                ['White Chocolate Powder', 20, 'gram'],
                ['Fresh Milk UHT', 180, 'ml'],
                ['Cane Sugar', 10, 'gram'],
            ],
        ],
        [
            'name'    => 'Oat Milk Latte',
            'price'   => 35000,
            'popular' => false,
            'about'   => 'Espresso House Blend dengan oat milk barista.',
            'items'   => [
                ['House Blend', 18, 'gram'],
                ['Oat Milk Barista', 180, 'ml'],
            ],
        ],
        [
            'name'    => 'Chamomile Infusion',
            'price'   => 22000,
            'popular' => false,
            'about'   => 'Seduhan bunga chamomile dengan air mineral panas.',
            'items'   => [
                ['Chamomile Tea', 5, 'gram'],
                ['Mineral Water', 200, 'ml'],
            ],
        ],
        [
            'name'    => 'Iced Tea',
            'price'   => 18000,
            'popular' => false,
            'about'   => 'Teh hijau dingin dengan gula dan es batu.',
            'items'   => [
                ['Green Tea Leaves', 5, 'gram'],
                ['Mineral Water', 200, 'ml'],
                ['Cane Sugar', 15, 'gram'],
                ['Ice Cube', 100, 'gram'],
            ],
        ],
    ];

    public function run(): void
    {
        $beverageCategory = $this->category(
            'Beverages',
            '/assets/images/categories/beverages.svg',
            'Menu minuman siap saji Senopati Coffee'
        );

        $beverageSupplyCategory = $this->category(
            'Beverage Supplies',
            '/assets/images/categories/beverage-supplies.svg',
            'Bahan pendukung minuman seperti air dan es'
        );

        $sweetenerCategory = $this->category(
            'Sweetener',
            '/assets/images/categories/sweetener.svg',
            'Pemanis alami dan gula'
        );

        $categoryByName = [
            'Beverage Supplies' => $beverageSupplyCategory,
            'Sweetener'         => $sweetenerCategory,
        ];

        foreach (self::NEW_INGREDIENTS as $ingredient) {
            $product = $this->ingredient($ingredient, $categoryByName[$ingredient['category']]);
            $this->ensureInitialStock($product, (float) $ingredient['initial_stock']);
        }

        $createdRecipes = 0;
        $skippedRecipes = 0;
        $createdItems = 0;

        foreach (self::MENUS as $menu) {
            $product = $this->menuProduct($menu, $beverageCategory);

            if ($product === null) {
                $skippedRecipes++;

                continue;
            }

            $result = DB::transaction(function () use ($product, $menu): array {
                $recipe = Recipe::firstOrCreate(['product_id' => $product->id]);

                if (! $recipe->wasRecentlyCreated) {
                    return ['created' => false, 'items' => 0];
                }

                $itemCount = 0;

                foreach ($menu['items'] as [$ingredientName, $quantity, $unit]) {
                    $ingredient = Product::where('name', $ingredientName)->first();

                    if ($ingredient === null) {
                        throw new RuntimeException(
                            "Bahan baku '{$ingredientName}' untuk resep '{$menu['name']}' tidak ditemukan."
                        );
                    }

                    if ($ingredient->id === $product->id) {
                        throw new RuntimeException(
                            "Menu '{$menu['name']}' tidak boleh menggunakan dirinya sendiri sebagai bahan."
                        );
                    }

                    $normalizedUnit = UnitConverter::normalize($unit);

                    if (! UnitConverter::isConvertible($normalizedUnit, $ingredient->unit)) {
                        throw new RuntimeException(
                            "Satuan '{$normalizedUnit}' tidak kompatibel dengan satuan inventory "
                            . "'{$ingredient->unit}' untuk bahan '{$ingredientName}'."
                        );
                    }

                    $recipe->items()->create([
                        'ingredient_id' => $ingredient->id,
                        'quantity'      => $quantity,
                        'unit'          => $normalizedUnit,
                    ]);

                    $itemCount++;
                }

                return ['created' => true, 'items' => $itemCount];
            });

            if ($result['created']) {
                $createdRecipes++;
                $createdItems += $result['items'];
            } else {
                $skippedRecipes++;
            }
        }

        $this->command?->info(
            "RecipeSeeder selesai: {$createdRecipes} resep baru, "
            . "{$createdItems} bahan resep, {$skippedRecipes} resep dilewati (sudah ada)."
        );
    }

    private function category(string $name, string $photo, string $tagline): Category
    {
        return Category::firstOrCreate(
            ['name' => $name],
            ['photo' => $photo, 'tagline' => $tagline]
        );
    }

    private function ingredient(array $data, Category $category): Product
    {
        return Product::firstOrCreate(
            ['name' => $data['name']],
            [
                'thumbnail'   => '/assets/images/products/' . Str::slug($data['name']) . '.svg',
                'about'       => $data['about'],
                'unit'        => $data['unit'],
                'price'       => $data['price'],
                'category_id' => $category->id,
                'is_popular'  => false,
            ]
        );
    }

    private function ensureInitialStock(Product $product, float $stock): void
    {
        $warehouse = Warehouse::orderBy('id')->first();

        if (!$warehouse) {
            $this->command?->warn(
                "Gudang belum tersedia; stok awal '{$product->name}' tidak dibuat."
            );

            return;
        }

        WarehouseProduct::firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'product_id'   => $product->id,
            ],
            ['stock' => $stock]
        );
    }

    private function menuProduct(array $menu, Category $category): ?Product
    {
        $product = Product::withTrashed()->where('name', $menu['name'])->first();

        if ($product !== null && $product->trashed()) {
            $this->command?->warn(
                "Produk '{$menu['name']}' sudah dihapus (soft delete); resep dilewati."
            );

            return null;
        }

        if ($product !== null) {
            return $product;
        }

        return Product::create([
            'name'        => $menu['name'],
            'thumbnail'   => '/assets/images/products/' . Str::slug($menu['name']) . '.svg',
            'about'       => $menu['about'],
            'unit'        => 'pcs',
            'price'       => $menu['price'],
            'category_id' => $category->id,
            'is_popular'  => $menu['popular'],
        ]);
    }
}
