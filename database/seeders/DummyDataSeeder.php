<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Models\MerchantProduct;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->createCategories();
        $products = $this->createProducts($categories);
        $keepers = $this->createKeepers();
        $warehouses = $this->createWarehouses();
        $merchants = $this->createMerchants($keepers);
        $this->assignWarehouseStock($warehouses, $products);
        $this->assignOutletStock($merchants, $products, $warehouses);
        $this->createTransactions($merchants, $products, $warehouses);
    }

    private function createCategories()
    {
        $categoryData = [
            ['name' => 'Coffee Beans',  'photo' => '/assets/images/categories/coffee-beans.svg',  'tagline' => 'Premium coffee beans from various regions'],
            ['name' => 'Powder',        'photo' => '/assets/images/categories/powder.svg',        'tagline' => 'Powder ingredients for beverages'],
            ['name' => 'Milk & Cream',  'photo' => '/assets/images/categories/milk.svg',          'tagline' => 'Fresh milk and cream products'],
            ['name' => 'Syrup',         'photo' => '/assets/images/categories/syrup.svg',         'tagline' => 'Flavored syrups for drinks'],
            ['name' => 'Packaging',     'photo' => '/assets/images/categories/packaging.svg',     'tagline' => 'Cups, lids, and packaging supplies'],
            ['name' => 'Sweetener',     'photo' => '/assets/images/categories/sweetener.svg',     'tagline' => 'Sugar and sweetening agents'],
            ['name' => 'Tea',           'photo' => '/assets/images/categories/tea.svg',           'tagline' => 'Tea leaves and tea products'],
            ['name' => 'Other',         'photo' => '/assets/images/categories/other.svg',         'tagline' => 'Miscellaneous ingredients'],
        ];

        $categories = [];
        foreach ($categoryData as $data) {
            $categories[] = Category::create($data);
        }
        return $categories;
    }

    private function createProducts($categories)
    {
        $cat = collect($categories);
        $coffeeBeans = $cat->firstWhere('name', 'Coffee Beans');
        $powder     = $cat->firstWhere('name', 'Powder');
        $milk       = $cat->firstWhere('name', 'Milk & Cream');
        $syrup      = $cat->firstWhere('name', 'Syrup');
        $packaging  = $cat->firstWhere('name', 'Packaging');
        $sweetener  = $cat->firstWhere('name', 'Sweetener');
        $tea        = $cat->firstWhere('name', 'Tea');
        $other      = $cat->firstWhere('name', 'Other');

        $productData = [
            ['name' => 'Arabica Gayo',         'thumbnail' => '/assets/images/products/arabica-gayo.jpg',         'about' => 'Single origin Arabika dari Aceh Gayo, cita rasa floral dan fruity',           'unit' => 'kg',   'price' => 180000, 'category_id' => $coffeeBeans->id, 'is_popular' => true],
            ['name' => 'Arabica Toraja',        'thumbnail' => '/assets/images/products/arabica-toraja.jpg',      'about' => 'Arabika Toraja Sapan, body tebal dengan hints cokelat',                     'unit' => 'kg',   'price' => 195000, 'category_id' => $coffeeBeans->id, 'is_popular' => true],
            ['name' => 'Robusta Lampung',       'thumbnail' => '/assets/images/products/robusta-lampung.jpg',     'about' => 'Robusta Lampung grade 1, strong body cocok untuk espresso blend',            'unit' => 'kg',   'price' => 120000, 'category_id' => $coffeeBeans->id, 'is_popular' => false],
            ['name' => 'House Blend',           'thumbnail' => '/assets/images/products/house-blend.jpg',         'about' => 'Signature blend kami, perpaduan Arabika dan Robusta',                        'unit' => 'kg',   'price' => 150000, 'category_id' => $coffeeBeans->id, 'is_popular' => true],
            ['name' => 'Espresso Beans',        'thumbnail' => '/assets/images/products/espresso-beans.jpg',      'about' => 'Dark roast khusus untuk espresso, crema tebal',                             'unit' => 'kg',   'price' => 165000, 'category_id' => $coffeeBeans->id, 'is_popular' => false],
            ['name' => 'Matcha Powder',         'thumbnail' => '/assets/images/products/matcha-powder.jpg',       'about' => 'Matcha premium grade dari Jepang, warna hijau cerah',                        'unit' => 'gram', 'price' => 25000,  'category_id' => $powder->id,     'is_popular' => true],
            ['name' => 'Chocolate Powder',      'thumbnail' => '/assets/images/products/chocolate-powder.jpg',    'about' => 'Cokelat bubuk premium untuk minuman dan topping',                            'unit' => 'gram', 'price' => 18000,  'category_id' => $powder->id,     'is_popular' => false],
            ['name' => 'Green Tea Powder',      'thumbnail' => '/assets/images/products/green-tea-powder.jpg',    'about' => 'Teh hijau bubuk untuk matcha latte dan green tea',                           'unit' => 'gram', 'price' => 22000,  'category_id' => $powder->id,     'is_popular' => false],
            ['name' => 'Fresh Milk',            'thumbnail' => '/assets/images/products/fresh-milk.jpg',          'about' => 'Susu segar UHT full cream untuk latte dan minuman lainnya',                  'unit' => 'liter','price' => 28000,  'category_id' => $milk->id,       'is_popular' => true],
            ['name' => 'Oat Milk',             'thumbnail' => '/assets/images/products/oat-milk.jpg',            'about' => 'Susu oat plant-based untuk alternatif susu sapi',                            'unit' => 'liter','price' => 35000,  'category_id' => $milk->id,       'is_popular' => true],
            ['name' => 'Vanilla Syrup',        'thumbnail' => '/assets/images/products/vanilla-syrup.jpg',       'about' => 'Sirup vanilla untuk flavored coffee dan beverages',                          'unit' => 'ml',   'price' => 45000,  'category_id' => $syrup->id,      'is_popular' => false],
            ['name' => 'Caramel Syrup',        'thumbnail' => '/assets/images/products/caramel-syrup.jpg',       'about' => 'Sirup karamel untuk caramel macchiato dan lainnya',                         'unit' => 'ml',   'price' => 45000,  'category_id' => $syrup->id,      'is_popular' => false],
            ['name' => 'Hazelnut Syrup',       'thumbnail' => '/assets/images/products/hazelnut-syrup.jpg',      'about' => 'Sirup hazelnut untuk hazelnut latte',                                       'unit' => 'ml',   'price' => 48000,  'category_id' => $syrup->id,      'is_popular' => false],
            ['name' => 'Brown Sugar Syrup',    'thumbnail' => '/assets/images/products/brown-sugar-syrup.jpg',   'about' => 'Sirup gula merah untuk brown sugar latte',                                  'unit' => 'ml',   'price' => 42000,  'category_id' => $syrup->id,      'is_popular' => false],
            ['name' => 'Paper Cup 12 oz',      'thumbnail' => '/assets/images/products/paper-cup-12oz.jpg',      'about' => 'Paper cup 12 oz untuk minuman dingin',                                      'unit' => 'pcs',  'price' => 1500,   'category_id' => $packaging->id,  'is_popular' => false],
            ['name' => 'Paper Cup 16 oz',      'thumbnail' => '/assets/images/products/paper-cup-16oz.jpg',      'about' => 'Paper cup 16 oz untuk minuman dingin ukuran besar',                          'unit' => 'pcs',  'price' => 1800,   'category_id' => $packaging->id,  'is_popular' => false],
            ['name' => 'Cup Lid',              'thumbnail' => '/assets/images/products/cup-lid.jpg',             'about' => 'Tutup paper cup universal',                                                 'unit' => 'pcs',  'price' => 800,    'category_id' => $packaging->id,  'is_popular' => false],
            ['name' => 'Coffee Filter',        'thumbnail' => '/assets/images/products/coffee-filter.jpg',       'about' => 'Filter kopi untuk pour-over',                                               'unit' => 'pcs',  'price' => 500,    'category_id' => $packaging->id,  'is_popular' => false],
            ['name' => 'Palm Sugar',           'thumbnail' => '/assets/images/products/palm-sugar.jpg',          'about' => 'Gula aren asli untuk minuman tradisional dan kopi',                         'unit' => 'gram', 'price' => 12000,  'category_id' => $sweetener->id,  'is_popular' => false],
            ['name' => 'Green Tea Leaves',     'thumbnail' => '/assets/images/products/green-tea-leaves.jpg',    'about' => 'Daun teh hijau pilihan untuk green tea tradisional',                         'unit' => 'gram', 'price' => 30000,  'category_id' => $tea->id,        'is_popular' => false],
            ['name' => 'Ice Cube Bag',         'thumbnail' => '/assets/images/products/ice-cube-bag.jpg',        'about' => 'Kantong es batu food grade',                                                'unit' => 'pcs',  'price' => 2000,   'category_id' => $other->id,      'is_popular' => false],
            ['name' => 'Whipped Cream',        'thumbnail' => '/assets/images/products/whipped-cream.jpg',       'about' => 'Whipped cream siap pakai untuk topping',                                    'unit' => 'gram', 'price' => 35000,  'category_id' => $other->id,      'is_popular' => false],
        ];

        $products = [];
        foreach ($productData as $data) {
            $products[] = Product::create($data);
        }
        return $products;
    }

    private function createKeepers()
    {
        $keeperData = [
            ['name' => 'Rudi Saputra',       'email' => 'rudi@keeper.com',       'phone' => '081234560001', 'photo' => '/assets/images/users/keeper-1.jpg'],
            ['name' => 'Siti Rahayu',        'email' => 'siti@keeper.com',       'phone' => '081234560002', 'photo' => '/assets/images/users/keeper-2.jpg'],
            ['name' => 'Budi Santoso',       'email' => 'budi@keeper.com',       'phone' => '081234560003', 'photo' => '/assets/images/users/keeper-3.jpg'],
            ['name' => 'Dewi Lestari',       'email' => 'dewi@keeper.com',       'phone' => '081234560004', 'photo' => '/assets/images/users/keeper-4.jpg'],
            ['name' => 'Ahmad Fauzi',        'email' => 'ahmad@keeper.com',      'phone' => '081234560005', 'photo' => '/assets/images/users/keeper-5.jpg'],
            ['name' => 'Rina Wati',          'email' => 'rina@keeper.com',       'phone' => '081234560006', 'photo' => '/assets/images/users/keeper-6.jpg'],
            ['name' => 'Hendra Wijaya',      'email' => 'hendra@keeper.com',     'phone' => '081234560007', 'photo' => '/assets/images/users/keeper-7.jpg'],
            ['name' => 'Maya Putri',         'email' => 'maya@keeper.com',       'phone' => '081234560008', 'photo' => '/assets/images/users/keeper-8.jpg'],
        ];

        $keepers = [];
        foreach ($keeperData as $data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'phone'    => $data['phone'],
                'photo'    => $data['photo'],
                'password' => bcrypt('password123'),
            ]);
            $user->assignRole('keeper');
            $keepers[] = $user;
        }
        return $keepers;
    }

    private function createWarehouses()
    {
        $warehouseData = [
            [
                'name'    => 'Gudang Pusat Jakarta',
                'address' => 'Jl. Industri Raya No. 10, Jakarta Barat',
                'photo'   => '/assets/images/warehouses/gudang-jakarta.jpg',
                'phone'   => '021-5550101',
            ],
            [
                'name'    => 'Gudang Cabang Bandung',
                'address' => 'Jl. Soekarno-Hatta No. 45, Bandung',
                'photo'   => '/assets/images/warehouses/gudang-bandung.jpg',
                'phone'   => '022-5550202',
            ],
            [
                'name'    => 'Gudang Cabang Surabaya',
                'address' => 'Jl. Raya Darmo No. 88, Surabaya',
                'photo'   => '/assets/images/warehouses/gudang-surabaya.jpg',
                'phone'   => '031-5550303',
            ],
        ];

        $warehouses = [];
        foreach ($warehouseData as $data) {
            $warehouses[] = Warehouse::create($data);
        }
        return $warehouses;
    }

    private function createMerchants($keepers)
    {
        $merchantData = [
            ['name' => 'Outlet Monas Coffee',        'address' => 'Jl. Tiang Bendera No. 3, Jakarta Pusat',         'photo' => '/assets/images/merchants/outlet-monas.jpg',        'phone' => '021-6660101'],
            ['name' => 'Outlet Blok M Coffee',       'address' => 'Jl. Melawai Raya No. 12, Jakarta Selatan',       'photo' => '/assets/images/merchants/outlet-blokm.jpg',        'phone' => '021-6660202'],
            ['name' => 'Outlet Kemang Coffee',       'address' => 'Jl. Kemang Raya No. 7, Jakarta Selatan',         'photo' => '/assets/images/merchants/outlet-kemang.jpg',       'phone' => '021-6660303'],
            ['name' => 'Outlet Bandung Coffee',      'address' => 'Jl. Braga No. 20, Bandung',                     'photo' => '/assets/images/merchants/outlet-braga.jpg',        'phone' => '022-6660404'],
            ['name' => 'Outlet Dago Coffee',         'address' => 'Jl. Dago No. 55, Bandung',                      'photo' => '/assets/images/merchants/outlet-dago.jpg',         'phone' => '022-6660505'],
            ['name' => 'Outlet Surabaya Coffee',     'address' => 'Jl. Pemuda No. 33, Surabaya',                   'photo' => '/assets/images/merchants/outlet-pemuda.jpg',       'phone' => '031-6660606'],
            ['name' => 'Outlet Kenjeran Coffee',     'address' => 'Jl. Kenjeran No. 99, Surabaya',                 'photo' => '/assets/images/merchants/outlet-kenjeran.jpg',     'phone' => '031-6660707'],
            ['name' => 'Outlet Gubeng Coffee',       'address' => 'Jl. Gubeng No. 15, Surabaya',                   'photo' => '/assets/images/merchants/outlet-gubeng.jpg',       'phone' => '031-6660808'],
        ];

        $merchants = [];
        foreach ($merchantData as $index => $data) {
            $merchants[] = Merchant::create([
                'name'       => $data['name'],
                'address'    => $data['address'],
                'photo'      => $data['photo'],
                'phone'      => $data['phone'],
                'keeper_id'  => $keepers[$index]->id,
            ]);
        }
        return $merchants;
    }

    private function assignWarehouseStock($warehouses, $products)
    {
        $stockMap = [
            // warehouse index => [product index => stock]
            0 => [ // Gudang Pusat Jakarta
                0  => 150,  // Arabica Gayo
                1  => 120,  // Arabica Toraja
                2  => 200,  // Robusta Lampung
                3  => 180,  // House Blend
                4  => 100,  // Espresso Beans
                5  => 80,   // Matcha Powder
                6  => 90,   // Chocolate Powder
                7  => 60,   // Green Tea Powder
                8  => 250,  // Fresh Milk
                9  => 180,  // Oat Milk
                10 => 120,  // Vanilla Syrup
                11 => 110,  // Caramel Syrup
                12 => 95,   // Hazelnut Syrup
                13 => 85,   // Brown Sugar Syrup
                14 => 500,  // Paper Cup 12 oz
                15 => 400,  // Paper Cup 16 oz
                16 => 800,  // Cup Lid
                17 => 600,  // Coffee Filter
                18 => 70,   // Palm Sugar
                19 => 45,   // Green Tea Leaves
                20 => 300,  // Ice Cube Bag
                21 => 30,   // Whipped Cream
            ],
            1 => [ // Gudang Cabang Bandung
                0  => 80,
                1  => 65,
                2  => 90,
                3  => 75,
                4  => 50,
                5  => 3,   // LOW STOCK
                6  => 40,
                7  => 25,
                8  => 120,
                9  => 90,
                10 => 55,
                11 => 2,   // LOW STOCK
                12 => 45,
                13 => 35,
                14 => 250,
                15 => 200,
                16 => 400,
                17 => 300,
                18 => 30,
                19 => 4,   // LOW STOCK
                20 => 150,
                21 => 5,   // LOW STOCK
            ],
            2 => [ // Gudang Cabang Surabaya
                0  => 60,
                1  => 55,
                2  => 100,
                3  => 85,
                4  => 40,
                5  => 35,
                6  => 3,   // LOW STOCK
                7  => 20,
                8  => 100,
                9  => 70,
                10 => 40,
                11 => 38,
                12 => 2,   // LOW STOCK
                13 => 25,
                14 => 180,
                15 => 150,
                16 => 300,
                17 => 200,
                18 => 20,
                19 => 15,
                20 => 100,
                21 => 4,   // LOW STOCK
            ],
        ];

        foreach ($stockMap as $warehouseIndex => $warehouseProducts) {
            foreach ($warehouseProducts as $productIndex => $stock) {
                WarehouseProduct::create([
                    'warehouse_id' => $warehouses[$warehouseIndex]->id,
                    'product_id'   => $products[$productIndex]->id,
                    'stock'        => $stock,
                ]);
            }
        }
    }

    private function assignOutletStock($merchants, $products, $warehouses)
    {
        $outletStockMap = [
            0 => [ // Outlet Monas Coffee
                'products' => [
                    0 => ['stock' => 15, 'warehouse' => 0],
                    1 => ['stock' => 12, 'warehouse' => 0],
                    2 => ['stock' => 20, 'warehouse' => 0],
                    3 => ['stock' => 18, 'warehouse' => 0],
                    4 => ['stock' => 10, 'warehouse' => 0],
                    5 => ['stock' => 8,  'warehouse' => 0],
                    6 => ['stock' => 9,  'warehouse' => 0],
                    8 => ['stock' => 25, 'warehouse' => 0],
                    9 => ['stock' => 18, 'warehouse' => 0],
                    10 => ['stock' => 12, 'warehouse' => 0],
                    14 => ['stock' => 50, 'warehouse' => 0],
                    15 => ['stock' => 40, 'warehouse' => 0],
                    16 => ['stock' => 80, 'warehouse' => 0],
                ],
            ],
            1 => [ // Outlet Blok M Coffee
                'products' => [
                    0 => ['stock' => 10, 'warehouse' => 0],
                    1 => ['stock' => 8,  'warehouse' => 0],
                    3 => ['stock' => 15, 'warehouse' => 0],
                    5 => ['stock' => 3,  'warehouse' => 1], // LOW
                    8 => ['stock' => 20, 'warehouse' => 0],
                    9 => ['stock' => 15, 'warehouse' => 0],
                    11 => ['stock' => 7,  'warehouse' => 0],
                    14 => ['stock' => 40, 'warehouse' => 0],
                    15 => ['stock' => 35, 'warehouse' => 0],
                    16 => ['stock' => 60, 'warehouse' => 0],
                ],
            ],
            2 => [ // Outlet Kemang Coffee
                'products' => [
                    0 => ['stock' => 12, 'warehouse' => 0],
                    2 => ['stock' => 18, 'warehouse' => 0],
                    3 => ['stock' => 14, 'warehouse' => 0],
                    6 => ['stock' => 5,  'warehouse' => 0],
                    7 => ['stock' => 4,  'warehouse' => 0], // LOW
                    8 => ['stock' => 22, 'warehouse' => 0],
                    10 => ['stock' => 8,  'warehouse' => 0],
                    12 => ['stock' => 6,  'warehouse' => 0],
                    14 => ['stock' => 45, 'warehouse' => 0],
                    15 => ['stock' => 38, 'warehouse' => 0],
                ],
            ],
            3 => [ // Outlet Bandung Coffee
                'products' => [
                    0 => ['stock' => 8,  'warehouse' => 1],
                    1 => ['stock' => 6,  'warehouse' => 1],
                    2 => ['stock' => 12, 'warehouse' => 1],
                    3 => ['stock' => 10, 'warehouse' => 1],
                    5 => ['stock' => 2,  'warehouse' => 1], // LOW
                    8 => ['stock' => 15, 'warehouse' => 1],
                    9 => ['stock' => 10, 'warehouse' => 1],
                    14 => ['stock' => 30, 'warehouse' => 1],
                    15 => ['stock' => 25, 'warehouse' => 1],
                    16 => ['stock' => 50, 'warehouse' => 1],
                ],
            ],
            4 => [ // Outlet Dago Coffee
                'products' => [
                    0 => ['stock' => 5,  'warehouse' => 1],
                    1 => ['stock' => 4,  'warehouse' => 1], // LOW
                    3 => ['stock' => 8,  'warehouse' => 1],
                    4 => ['stock' => 3,  'warehouse' => 1], // LOW
                    6 => ['stock' => 6,  'warehouse' => 1],
                    8 => ['stock' => 12, 'warehouse' => 1],
                    11 => ['stock' => 3,  'warehouse' => 1], // LOW
                    14 => ['stock' => 25, 'warehouse' => 1],
                    15 => ['stock' => 20, 'warehouse' => 1],
                ],
            ],
            5 => [ // Outlet Surabaya Coffee
                'products' => [
                    0 => ['stock' => 10, 'warehouse' => 2],
                    1 => ['stock' => 8,  'warehouse' => 2],
                    2 => ['stock' => 15, 'warehouse' => 2],
                    3 => ['stock' => 12, 'warehouse' => 2],
                    5 => ['stock' => 4,  'warehouse' => 2],
                    7 => ['stock' => 3,  'warehouse' => 2], // LOW
                    8 => ['stock' => 18, 'warehouse' => 2],
                    9 => ['stock' => 12, 'warehouse' => 2],
                    14 => ['stock' => 35, 'warehouse' => 2],
                    15 => ['stock' => 30, 'warehouse' => 2],
                    16 => ['stock' => 55, 'warehouse' => 2],
                ],
            ],
            6 => [ // Outlet Kenjeran Coffee
                'products' => [
                    0 => ['stock' => 6,  'warehouse' => 2],
                    2 => ['stock' => 10, 'warehouse' => 2],
                    3 => ['stock' => 8,  'warehouse' => 2],
                    6 => ['stock' => 2,  'warehouse' => 2], // LOW
                    8 => ['stock' => 14, 'warehouse' => 2],
                    10 => ['stock' => 5,  'warehouse' => 2],
                    12 => ['stock' => 2,  'warehouse' => 2], // LOW
                    14 => ['stock' => 28, 'warehouse' => 2],
                    15 => ['stock' => 22, 'warehouse' => 2],
                ],
            ],
            7 => [ // Outlet Gubeng Coffee
                'products' => [
                    0 => ['stock' => 8,  'warehouse' => 2],
                    1 => ['stock' => 5,  'warehouse' => 2],
                    3 => ['stock' => 10, 'warehouse' => 2],
                    4 => ['stock' => 4,  'warehouse' => 2], // LOW
                    8 => ['stock' => 16, 'warehouse' => 2],
                    9 => ['stock' => 11, 'warehouse' => 2],
                    11 => ['stock' => 4,  'warehouse' => 2],
                    14 => ['stock' => 32, 'warehouse' => 2],
                    15 => ['stock' => 27, 'warehouse' => 2],
                    16 => ['stock' => 48, 'warehouse' => 2],
                ],
            ],
        ];

        foreach ($outletStockMap as $merchantIndex => $config) {
            foreach ($config['products'] as $productIndex => $info) {
                MerchantProduct::create([
                    'merchant_id'  => $merchants[$merchantIndex]->id,
                    'product_id'   => $products[$productIndex]->id,
                    'warehouse_id' => $warehouses[$info['warehouse']]->id,
                    'stock'        => $info['stock'],
                ]);
            }
        }
    }

    private function createTransactions($merchants, $products, $warehouses)
    {
        $customerNames = [
            'Andi Pratama', 'Lestari Wulan', 'Fajar Nugroho', 'Ratna Sari', 'Dedi Kurniawan',
            'Salsa Balqis', 'Hendra Gunawan', 'Mega Oktaviani', 'Taufik Hidayat', 'Nina Agustina',
            'Reza Firmansyah', 'Putri Rahmawati', 'Gilang Ramadhan', 'Angga Saputra', 'Vina Melati',
            'Bayu Prasetyo', 'Citra Dewi', 'Rizky Aditya', 'Lia Permata', 'Dimas Putranto',
        ];

        $distributionRecords = [];

        $baseDate = Carbon::now()->subMonths(3);

        for ($i = 0; $i < 50; $i++) {
            $merchantIndex = $i % count($merchants);
            $merchant = $merchants[$merchantIndex];

            $numProducts = rand(1, 4);
            $productIndices = array_rand(array_flip(range(0, count($products) - 1)), $numProducts);

            $subTotal = 0;
            $transactionProducts = [];

            foreach ((array) $productIndices as $productIndex) {
                $product = $products[$productIndex];
                $quantity = rand(2, 20);
                $price = $product->price;
                $lineTotal = $quantity * $price;
                $subTotal += $lineTotal;

                $transactionProducts[] = [
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'price'      => $price,
                    'sub_total'  => $lineTotal,
                ];
            }

            $taxTotal = (int) round($subTotal * 0.11);
            $grandTotal = $subTotal + $taxTotal;

            $daysOffset = $i * rand(1, 3);
            $transactionDate = $baseDate->copy()->addDays($daysOffset);

            $customer = $customerNames[array_rand($customerNames)];
            $phone = '08' . rand(1000000000, 9999999999);

            $transaction = Transaction::create([
                'name'        => $customer,
                'phone'       => $phone,
                'sub_total'   => $subTotal,
                'tax_total'   => $taxTotal,
                'grand_total' => $grandTotal,
                'merchant_id' => $merchant->id,
                'created_at'  => $transactionDate,
                'updated_at'  => $transactionDate,
            ]);

            foreach ($transactionProducts as $tp) {
                TransactionProduct::create([
                    'transaction_id' => $transaction->id,
                    'product_id'     => $tp['product_id'],
                    'quantity'       => $tp['quantity'],
                    'price'          => $tp['price'],
                    'sub_total'      => $tp['sub_total'],
                    'created_at'     => $transactionDate,
                    'updated_at'     => $transactionDate,
                ]);
            }
        }
    }
}
