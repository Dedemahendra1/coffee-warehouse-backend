<?php

namespace Database\Seeders\Data;

/**
 * Sumber data master untuk seluruh seeder Senopati Coffee.
 *
 * Data ini sengaja ditulis eksplisit (bukan murni acak) agar:
 * - nama/alamat/telepon tampak seperti perusahaan sungguhan;
 * - seluruh foreign key valid dan saling berkaitan;
 * - stok gudang, stok outlet, dan penjualan tidak pernah saling bertentangan.
 *
 * Konvensi "outlets" per produk: [stokAkhirOutlet, jumlahTerjual] untuk tiap
 * outlet (diurutkan sesuai outlets()). Kuantitas terdistribusi (q) otomatis
 * dihitung = stokAkhirOutlet + jumlahTerjual.
 */
class SenopatiSeedData
{
    public static function roles(): array
    {
        return ['manager', 'keeper'];
    }

    public static function permissions(): array
    {
        return ['create role', 'edit role', 'delete role', 'view role'];
    }

    public static function managerUser(): array
    {
        return [
            'name'  => 'Bayu Prasetyo',
            'email' => 'manager@senopaticoffee.id',
            'phone' => '081234560001',
            'photo' => '/assets/images/users/manager-1.svg',
        ];
    }

    public static function keeperUsers(): array
    {
        return [
            [
                'name'  => 'Rizky Aditya Ramadhan',
                'email' => 'keeper1@senopaticoffee.id',
                'phone' => '081234560002',
                'photo' => '/assets/images/users/keeper-1.svg',
            ],
            [
                'name'  => 'Salsabila Putri',
                'email' => 'keeper2@senopaticoffee.id',
                'phone' => '081234560003',
                'photo' => '/assets/images/users/keeper-2.svg',
            ],
            [
                'name'  => 'Fajar Nugroho',
                'email' => 'keeper3@senopaticoffee.id',
                'phone' => '081234560004',
                'photo' => '/assets/images/users/keeper-3.svg',
            ],
        ];
    }

    public static function categories(): array
    {
        return [
            ['name' => 'Coffee Beans',       'photo' => '/assets/images/categories/coffee-beans.svg',       'tagline' => 'Premium whole beans dari berbagai region Indonesia'],
            ['name' => 'Ground Coffee',      'photo' => '/assets/images/categories/ground-coffee.svg',      'tagline' => 'Kopi bubuk siap seduh untuk kebutuhan harian'],
            ['name' => 'Milk',               'photo' => '/assets/images/categories/milk.svg',               'tagline' => 'Susu segar dan alternatif plant-based'],
            ['name' => 'Syrup',              'photo' => '/assets/images/categories/syrup.svg',              'tagline' => 'Sirup rasa untuk flavored beverages'],
            ['name' => 'Powder',             'photo' => '/assets/images/categories/powder.svg',             'tagline' => 'Bubuk premium untuk minuman signature'],
            ['name' => 'Tea',                'photo' => '/assets/images/categories/tea.svg',                'tagline' => 'Daun teh pilihan untuk minuman teh'],
            ['name' => 'Chocolate',          'photo' => '/assets/images/categories/chocolate.svg',          'tagline' => 'Cokelat berkualitas untuk menu andalan'],
            ['name' => 'Topping',            'photo' => '/assets/images/categories/topping.svg',            'tagline' => 'Pelengkap topping untuk penyajian'],
            ['name' => 'Packaging',          'photo' => '/assets/images/categories/packaging.svg',          'tagline' => 'Kemasan dan perlengkapan penyajian'],
            ['name' => 'Cup',                'photo' => '/assets/images/categories/cup.svg',                'tagline' => 'Gelas minuman berbagai ukuran'],
            ['name' => 'Lid',                'photo' => '/assets/images/categories/lid.svg',                'tagline' => 'Tutup gelas universal'],
            ['name' => 'Straw',              'photo' => '/assets/images/categories/straw.svg',              'tagline' => 'Sedotan ramah lingkungan'],
            ['name' => 'Cleaning Supplies',  'photo' => '/assets/images/categories/cleaning-supplies.svg',  'tagline' => 'Perlengkapan kebersihan outlet'],
            ['name' => 'Snack',              'photo' => '/assets/images/categories/snack.svg',              'tagline' => 'Camilan, pastry, dan makanan beku'],
            ['name' => 'Sweetener',          'photo' => '/assets/images/categories/sweetener.svg',          'tagline' => 'Pemanis alami dan gula'],
        ];
    }

    public static function outlets(): array
    {
        return [
            [
                'name'    => 'Senopati Coffee Setia Budi',
                'address' => 'Jl. Setia Budi No. 12, Medan Selayang, Kota Medan, Sumatera Utara',
                'phone'   => '061-29080001',
                'photo'   => '/assets/images/merchants/outlet-setia-budi.svg',
            ],
            [
                'name'    => 'Senopati Coffee Medan Johor',
                'address' => 'Jl. Medan Johor No. 21, Medan Johor, Kota Medan, Sumatera Utara',
                'phone'   => '061-29080002',
                'photo'   => '/assets/images/merchants/outlet-medan-johor.svg',
            ],
            [
                'name'    => 'Senopati Coffee Ring Road',
                'address' => 'Jl. Ring Road No. 8, Medan Sunggal, Kota Medan, Sumatera Utara',
                'phone'   => '061-29080003',
                'photo'   => '/assets/images/merchants/outlet-ring-road.svg',
            ],
        ];
    }

    public static function warehouse(): array
    {
        return [
            'name'    => 'Gudang Pusat Senopati Coffee',
            'address' => 'Kawasan Industri Pulogadung, Jl. Rawa Sumur Timur II No. 5, Jakarta Timur, DKI Jakarta',
            'phone'   => '021-47000001',
            'photo'   => '/assets/images/warehouses/gudang-pusat-senopati.svg',
        ];
    }

    /**
     * Daftar produk.
     *
     * Setiap produk memiliki:
     * - wfinal : stok akhir yang diinginkan di Gudang Pusat (beberapa <= 5 untuk fitur "Stok Hampir Habis")
     * - min_qty / max_qty : rentang kuantitas per baris penjualan (agar transaksi realistis)
     * - outlets : [[stokAkhirOutlet, jumlahTerjual], ...] sejajar dengan outlets(); kosong jika produk tidak dijual di outlet
     */
    public static function products(): array
    {
        return [
            [
                'name' => 'Arabica Gayo',
                'category' => 'Coffee Beans',
                'unit' => 'kg',
                'price' => 180000,
                'about' => 'Single origin Arabika dari Aceh Gayo, cita rasa floral dan fruity dengan aftertaste bersih.',
                'is_popular' => true,
                'min_qty' => 2,
                'max_qty' => 5,
                'wfinal' => 25,
                'outlets' => [[18, 22], [20, 15], [16, 24]],
            ],
            [
                'name' => 'Arabica Toraja',
                'category' => 'Coffee Beans',
                'unit' => 'kg',
                'price' => 195000,
                'about' => 'Arabika Toraja Sapan dengan body tebal dan hints cokelat serta rempah.',
                'is_popular' => true,
                'min_qty' => 2,
                'max_qty' => 5,
                'wfinal' => 30,
                'outlets' => [[14, 18], [16, 12], [13, 20]],
            ],
            [
                'name' => 'Robusta Lampung',
                'category' => 'Coffee Beans',
                'unit' => 'kg',
                'price' => 120000,
                'about' => 'Robusta Lampung grade 1 dengan strong body, cocok untuk espresso blend.',
                'is_popular' => false,
                'min_qty' => 2,
                'max_qty' => 5,
                'wfinal' => 20,
                'outlets' => [[22, 20], [24, 14], [20, 25]],
            ],
            [
                'name' => 'House Blend',
                'category' => 'Coffee Beans',
                'unit' => 'kg',
                'price' => 150000,
                'about' => 'Signature blend Senopati Coffee, perpaduan Arabika dan Robusta yang seimbang.',
                'is_popular' => true,
                'min_qty' => 2,
                'max_qty' => 5,
                'wfinal' => 18,
                'outlets' => [[20, 25], [22, 18], [19, 28]],
            ],
            [
                'name' => 'Ground Arabica Gayo',
                'category' => 'Ground Coffee',
                'unit' => 'pcs',
                'price' => 85000,
                'about' => 'Kopi bubuk Arabika Gayo kemasan 250 gram, siap seduh untuk kebutuhan outlet.',
                'is_popular' => false,
                'min_qty' => 1,
                'max_qty' => 4,
                'wfinal' => 15,
                'outlets' => [[12, 30], [14, 24], [11, 34]],
            ],
            [
                'name' => 'Fresh Milk UHT',
                'category' => 'Milk',
                'unit' => 'liter',
                'price' => 28000,
                'about' => 'Susu segar UHT full cream untuk latte dan berbagai minuman berbasis susu.',
                'is_popular' => true,
                'min_qty' => 5,
                'max_qty' => 12,
                'wfinal' => 40,
                'outlets' => [[45, 75], [50, 60], [42, 82]],
            ],
            [
                'name' => 'Oat Milk Barista',
                'category' => 'Milk',
                'unit' => 'liter',
                'price' => 35000,
                'about' => 'Susu oat plant-based yang dirancang untuk latte art, alternatif susu sapi.',
                'is_popular' => true,
                'min_qty' => 5,
                'max_qty' => 12,
                'wfinal' => 35,
                'outlets' => [[40, 50], [4, 86], [38, 55]],
            ],
            [
                'name' => 'Vanilla Syrup',
                'category' => 'Syrup',
                'unit' => 'botol',
                'price' => 45000,
                'about' => 'Sirup vanilla premium untuk vanilla latte dan flavored beverages.',
                'is_popular' => false,
                'min_qty' => 2,
                'max_qty' => 6,
                'wfinal' => 25,
                'outlets' => [[3, 57], [30, 30], [34, 26]],
            ],
            [
                'name' => 'Caramel Syrup',
                'category' => 'Syrup',
                'unit' => 'botol',
                'price' => 45000,
                'about' => 'Sirup karamel untuk caramel macchiato dan minuman manis lainnya.',
                'is_popular' => false,
                'min_qty' => 2,
                'max_qty' => 6,
                'wfinal' => 20,
                'outlets' => [[28, 32], [31, 26], [26, 34]],
            ],
            [
                'name' => 'Hazelnut Syrup',
                'category' => 'Syrup',
                'unit' => 'botol',
                'price' => 48000,
                'about' => 'Sirup hazelnut untuk hazelnut latte dengan aroma kacang yang khas.',
                'is_popular' => false,
                'min_qty' => 1,
                'max_qty' => 4,
                'wfinal' => 4,
                'outlets' => [[15, 16], [2, 28], [14, 18]],
            ],
            [
                'name' => 'Brown Sugar Syrup',
                'category' => 'Syrup',
                'unit' => 'botol',
                'price' => 42000,
                'about' => 'Sirup gula merah untuk brown sugar latte dan es kopi susu.',
                'is_popular' => false,
                'min_qty' => 2,
                'max_qty' => 6,
                'wfinal' => 15,
                'outlets' => [[24, 28], [26, 22], [23, 30]],
            ],
            [
                'name' => 'Matcha Powder',
                'category' => 'Powder',
                'unit' => 'gram',
                'price' => 25000,
                'about' => 'Matcha premium grade dengan warna hijau cerah, untuk matcha latte.',
                'is_popular' => true,
                'min_qty' => 3,
                'max_qty' => 8,
                'wfinal' => 3,
                'outlets' => [[38, 42], [40, 32], [36, 46]],
            ],
            [
                'name' => 'Chocolate Powder',
                'category' => 'Powder',
                'unit' => 'gram',
                'price' => 18000,
                'about' => 'Cokelat bubuk premium untuk cokelat panas dan minuman cokelat.',
                'is_popular' => false,
                'min_qty' => 3,
                'max_qty' => 8,
                'wfinal' => 10,
                'outlets' => [[44, 46], [46, 36], [2, 88]],
            ],
            [
                'name' => 'Green Tea Powder',
                'category' => 'Powder',
                'unit' => 'gram',
                'price' => 22000,
                'about' => 'Teh hijau bubuk untuk green tea latte dan minuman teh hijau.',
                'is_popular' => false,
                'min_qty' => 3,
                'max_qty' => 8,
                'wfinal' => 5,
                'outlets' => [[28, 32], [5, 55], [30, 28]],
            ],
            [
                'name' => 'Green Tea Leaves',
                'category' => 'Tea',
                'unit' => 'gram',
                'price' => 30000,
                'about' => 'Daun teh hijau pilihan untuk penyajian teh tradisional dan teh tarik.',
                'is_popular' => false,
                'min_qty' => 2,
                'max_qty' => 5,
                'wfinal' => 8,
                'outlets' => [[18, 22], [20, 18], [17, 24]],
            ],
            [
                'name' => 'Whipped Cream',
                'category' => 'Topping',
                'unit' => 'can',
                'price' => 35000,
                'about' => 'Whipped cream siap pakai untuk topping minuman dan dessert.',
                'is_popular' => false,
                'min_qty' => 2,
                'max_qty' => 6,
                'wfinal' => 8,
                'outlets' => [[20, 22], [22, 16], [18, 24]],
            ],
            [
                'name' => 'Caramel Drizzle',
                'category' => 'Topping',
                'unit' => 'botol',
                'price' => 30000,
                'about' => 'Saus karamel untuk drizzle dan finishing penyajian minuman.',
                'is_popular' => false,
                'min_qty' => 2,
                'max_qty' => 6,
                'wfinal' => 6,
                'outlets' => [[16, 18], [18, 12], [4, 36]],
            ],
            [
                'name' => 'Takeaway Bag',
                'category' => 'Packaging',
                'unit' => 'pcs',
                'price' => 1500,
                'about' => 'Kantong kertas kraft untuk pesanan takeaway, ramah lingkungan.',
                'is_popular' => false,
                'min_qty' => 20,
                'max_qty' => 50,
                'wfinal' => 80,
                'outlets' => [[120, 130], [130, 110], [115, 140]],
            ],
            [
                'name' => 'Coffee Filter',
                'category' => 'Packaging',
                'unit' => 'pcs',
                'price' => 500,
                'about' => 'Filter kopi kertas untuk penyajian manual brew pour-over.',
                'is_popular' => false,
                'min_qty' => 20,
                'max_qty' => 50,
                'wfinal' => 60,
                'outlets' => [[90, 100], [100, 80], [85, 110]],
            ],
            [
                'name' => 'Paper Cup 12 oz',
                'category' => 'Cup',
                'unit' => 'pcs',
                'price' => 1500,
                'about' => 'Paper cup 12 oz untuk minuman dingin ukuran standar.',
                'is_popular' => false,
                'min_qty' => 30,
                'max_qty' => 70,
                'wfinal' => 200,
                'outlets' => [[180, 200], [200, 170], [5, 395]],
            ],
            [
                'name' => 'Paper Cup 16 oz',
                'category' => 'Cup',
                'unit' => 'pcs',
                'price' => 1800,
                'about' => 'Paper cup 16 oz untuk minuman dingin ukuran besar.',
                'is_popular' => false,
                'min_qty' => 30,
                'max_qty' => 70,
                'wfinal' => 180,
                'outlets' => [[170, 180], [185, 150], [160, 195]],
            ],
            [
                'name' => 'Cup Lid',
                'category' => 'Lid',
                'unit' => 'pcs',
                'price' => 800,
                'about' => 'Tutup paper cup universal yang cocok untuk cup 12 oz dan 16 oz.',
                'is_popular' => false,
                'min_qty' => 30,
                'max_qty' => 70,
                'wfinal' => 150,
                'outlets' => [[200, 190], [3, 367], [195, 180]],
            ],
            [
                'name' => 'Paper Straw',
                'category' => 'Straw',
                'unit' => 'pcs',
                'price' => 100,
                'about' => 'Sedotan kertas food grade, ramah lingkungan.',
                'is_popular' => false,
                'min_qty' => 40,
                'max_qty' => 90,
                'wfinal' => 300,
                'outlets' => [[5, 395], [210, 220], [225, 205]],
            ],
            [
                'name' => 'Frozen Croissant',
                'category' => 'Snack',
                'unit' => 'pcs',
                'price' => 15000,
                'about' => 'Croissant beku siap panggang untuk pastry pendamping kopi.',
                'is_popular' => false,
                'min_qty' => 2,
                'max_qty' => 8,
                'wfinal' => 4,
                'outlets' => [[30, 42], [32, 34], [28, 46]],
            ],
            [
                'name' => 'Salted Butter Cookies',
                'category' => 'Snack',
                'unit' => 'pcs',
                'price' => 18000,
                'about' => 'Cookies mentega dengan sentuhan garam, camilan klasik.',
                'is_popular' => false,
                'min_qty' => 3,
                'max_qty' => 10,
                'wfinal' => 20,
                'outlets' => [[40, 52], [44, 42], [38, 56]],
            ],
            [
                'name' => 'Banana Bread Slice',
                'category' => 'Snack',
                'unit' => 'pcs',
                'price' => 12000,
                'about' => 'Potongan banana bread lembut dan manis, pendamping kopi.',
                'is_popular' => false,
                'min_qty' => 3,
                'max_qty' => 10,
                'wfinal' => 15,
                'outlets' => [[36, 46], [40, 38], [34, 50]],
            ],
            [
                'name' => 'Palm Sugar',
                'category' => 'Sweetener',
                'unit' => 'gram',
                'price' => 12000,
                'about' => 'Gula aren asli untuk es kopi susu gula aren dan minuman tradisional.',
                'is_popular' => false,
                'min_qty' => 3,
                'max_qty' => 8,
                'wfinal' => 25,
                'outlets' => [[28, 34], [30, 28], [26, 36]],
            ],
            // --- Produk yang hanya disimpan di Gudang Pusat (tidak dijual di outlet) ---
            [
                'name' => 'Ground Robusta Lampung',
                'category' => 'Ground Coffee',
                'unit' => 'pcs',
                'price' => 70000,
                'about' => 'Kopi bubuk Robusta Lampung kemasan 250 gram, stok cadangan gudang.',
                'is_popular' => false,
                'min_qty' => null,
                'max_qty' => null,
                'wfinal' => 12,
                'outlets' => [],
            ],
            [
                'name' => 'Chamomile Tea',
                'category' => 'Tea',
                'unit' => 'gram',
                'price' => 40000,
                'about' => 'Bunga chamomile kering untuk teh herbal yang menenangkan.',
                'is_popular' => false,
                'min_qty' => null,
                'max_qty' => null,
                'wfinal' => 6,
                'outlets' => [],
            ],
            [
                'name' => 'Dark Chocolate Bar',
                'category' => 'Chocolate',
                'unit' => 'pcs',
                'price' => 45000,
                'about' => 'Dark chocolate bar 70% untuk menu dessert dan minuman cokelat.',
                'is_popular' => false,
                'min_qty' => null,
                'max_qty' => null,
                'wfinal' => 10,
                'outlets' => [],
            ],
            [
                'name' => 'White Chocolate Powder',
                'category' => 'Chocolate',
                'unit' => 'gram',
                'price' => 20000,
                'about' => 'Bubuk white chocolate untuk white chocolate mocha.',
                'is_popular' => false,
                'min_qty' => null,
                'max_qty' => null,
                'wfinal' => 5,
                'outlets' => [],
            ],
            [
                'name' => 'Sanitizer Solution',
                'category' => 'Cleaning Supplies',
                'unit' => 'liter',
                'price' => 25000,
                'about' => 'Larutan sanitizer food grade untuk kebersihan peralatan outlet.',
                'is_popular' => false,
                'min_qty' => null,
                'max_qty' => null,
                'wfinal' => 5,
                'outlets' => [],
            ],
            [
                'name' => 'Dish Soap',
                'category' => 'Cleaning Supplies',
                'unit' => 'botol',
                'price' => 20000,
                'about' => 'Sabun pencuci piring untuk kebutuhan operasional outlet.',
                'is_popular' => false,
                'min_qty' => null,
                'max_qty' => null,
                'wfinal' => 8,
                'outlets' => [],
            ],
        ];
    }

    public static function customerNames(): array
    {
        return [
            'Andi Pratama',
            'Lestari Wulan',
            'Fajar Nugroho',
            'Ratna Sari',
            'Dedi Kurniawan',
            'Salsa Balqis',
            'Hendra Gunawan',
            'Mega Oktaviani',
            'Taufik Hidayat',
            'Nina Agustina',
            'Reza Firmansyah',
            'Putri Rahmawati',
            'Gilang Ramadhan',
            'Angga Saputra',
            'Vina Melati',
            'Bayu Prasetyo',
            'Citra Dewi',
            'Rizky Aditya',
            'Lia Permata',
            'Dimas Putranto',
            'Intan Ayu',
            'Surya Wijaya',
            'Anisa Rahma',
            'Yoga Pratama',
        ];
    }

    /**
     * Daftar stock out (barang keluar/hilang dari stok outlet).
     *
     * Format tiap baris: [indexOutlets, namaProduk, kuantitas, alasan].
     * Kuantitas stock out diperhitungkan sebagai bagian dari jumlah yang
     * didistribusikan ke outlet sehingga rantai stok tetap konsisten:
     * stokAwalOutlet = stokAkhir + jumlahTerjual + jumlahStockOut.
     */
    public static function stockOuts(): array
    {
        return [
            // Outlet 0 - Setia Budi
            [0, 'Arabica Gayo', 2, 'Kemasan bocor'],
            [0, 'Arabica Toraja', 1, 'Barang rusak'],
            [0, 'Fresh Milk UHT', 3, 'Stok kadaluarsa'],
            [0, 'Oat Milk Barista', 2, 'Stok kadaluarsa'],
            [0, 'Vanilla Syrup', 1, 'Kemasan bocor'],
            [0, 'Hazelnut Syrup', 2, 'Kemasan bocor'],
            [0, 'Matcha Powder', 1, 'Kemasan bocor'],
            [0, 'Chocolate Powder', 2, 'Barang rusak'],
            [0, 'Green Tea Powder', 1, 'Stok kadaluarsa'],
            [0, 'Caramel Drizzle', 1, 'Kemasan bocor'],
            [0, 'Paper Cup 12 oz', 5, 'Barang rusak'],
            [0, 'Paper Cup 16 oz', 5, 'Barang rusak'],
            [0, 'Cup Lid', 10, 'Barang rusak'],
            [0, 'Paper Straw', 20, 'Barang rusak'],
            [0, 'Frozen Croissant', 3, 'Stok kadaluarsa'],
            [0, 'Salted Butter Cookies', 2, 'Stok kadaluarsa'],
            [0, 'Banana Bread Slice', 2, 'Barang rusak'],
            [0, 'Palm Sugar', 1, 'Kemasan bocor'],

            // Outlet 1 - Medan Johor
            [1, 'Robusta Lampung', 2, 'Barang rusak'],
            [1, 'House Blend', 1, 'Kemasan bocor'],
            [1, 'Ground Arabica Gayo', 1, 'Barang rusak'],
            [1, 'Fresh Milk UHT', 4, 'Stok kadaluarsa'],
            [1, 'Oat Milk Barista', 2, 'Stok kadaluarsa'],
            [1, 'Caramel Syrup', 1, 'Kemasan bocor'],
            [1, 'Brown Sugar Syrup', 1, 'Kemasan bocor'],
            [1, 'Matcha Powder', 1, 'Kemasan bocor'],
            [1, 'Chocolate Powder', 1, 'Barang rusak'],
            [1, 'Green Tea Leaves', 1, 'Barang rusak'],
            [1, 'Whipped Cream', 1, 'Stok kadaluarsa'],
            [1, 'Takeaway Bag', 10, 'Barang rusak'],
            [1, 'Paper Cup 12 oz', 5, 'Barang rusak'],
            [1, 'Paper Cup 16 oz', 5, 'Barang rusak'],
            [1, 'Paper Straw', 10, 'Barang rusak'],
            [1, 'Frozen Croissant', 2, 'Stok kadaluarsa'],
            [1, 'Salted Butter Cookies', 2, 'Stok kadaluarsa'],
            [1, 'Banana Bread Slice', 1, 'Barang rusak'],

            // Outlet 2 - Ring Road
            [2, 'Arabica Gayo', 1, 'Kemasan bocor'],
            [2, 'Arabica Toraja', 1, 'Barang rusak'],
            [2, 'Robusta Lampung', 1, 'Kemasan bocor'],
            [2, 'Fresh Milk UHT', 3, 'Stok kadaluarsa'],
            [2, 'Vanilla Syrup', 1, 'Kemasan bocor'],
            [2, 'Hazelnut Syrup', 1, 'Kemasan bocor'],
            [2, 'Matcha Powder', 2, 'Kemasan bocor'],
            [2, 'Chocolate Powder', 1, 'Barang rusak'],
            [2, 'Green Tea Powder', 1, 'Stok kadaluarsa'],
            [2, 'Green Tea Leaves', 1, 'Barang rusak'],
            [2, 'Whipped Cream', 1, 'Stok kadaluarsa'],
            [2, 'Takeaway Bag', 5, 'Barang rusak'],
            [2, 'Coffee Filter', 5, 'Barang rusak'],
            [2, 'Paper Cup 16 oz', 5, 'Barang rusak'],
            [2, 'Cup Lid', 5, 'Barang rusak'],
            [2, 'Paper Straw', 10, 'Barang rusak'],
            [2, 'Salted Butter Cookies', 2, 'Stok kadaluarsa'],
            [2, 'Banana Bread Slice', 1, 'Barang rusak'],
        ];
    }

    /**
     * Total kuantitas stock out per produk per outlet.
     *
     * @return array<string, array<int, int>>  [namaProduk => [indexOutlet => totalKuantitas]]
     */
    public static function stockOutTotals(): array
    {
        $totals = [];

        foreach (self::stockOuts() as [$outletIndex, $productName, $quantity]) {
            $totals[$productName][$outletIndex] = ($totals[$productName][$outletIndex] ?? 0) + $quantity;
        }

        return $totals;
    }
}
