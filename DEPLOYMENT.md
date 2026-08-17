# Deployment Guide — Senopati Coffee Inventory & Distribution API

Panduan instalasi dan deployment untuk backend Laravel **Monday BWA Backend**
(Sistem Inventori & Distribusi Senopati Coffee).

## Persyaratan

- PHP 8.2+ dengan ekstensi `pdo_mysql`, `mbstring`, `gd` (atau `imagick`), `xml`, `zip`
- Composer 2.x
- MySQL 8.x (atau MariaDB)
- Nginx / Apache (untuk produksi)

## 1. Instalasi Pengembangan (*Development*)

```bash
# 1. Pasang dependensi
composer install

# 2. Buat berkas lingkungan
copy .env.example .env

# 3. Kunci aplikasi
php artisan key:generate

# 4. Konfigurasi database di .env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=<nama_database>
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Jalankan migrasi + seeder (data demo lengkap)
php artisan migrate:fresh --seed

# 6. Tautan storage (agar foto/thumbnail tampil)
php artisan storage:link

# 7. Jalankan server
php artisan serve
```

API tersedia di `http://localhost:8000`. Pastikan database sudah dibuat terlebih
dahulu (mis. `CREATE DATABASE gudang-backend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`).

## 2. Data Demo (Seeder)

`php artisan migrate:fresh --seed` menjalankan **11 seeder** secara berurutan:

| No | Seeder                  | Isi                                                          |
|----|-------------------------|--------------------------------------------------------------|
| 1  | `RoleSeeder`            | Role `manager` & `keeper` + 4 permission CRUD                |
| 2  | `UserSeeder`            | 1 manajer + 3 penjaga gudang                                 |
| 3  | `CategorySeeder`        | 15 kategori produk                                           |
| 4  | `ProductSeeder`         | 33 produk (nama, satuan, harga, kategori, foto)              |
| 5  | `WarehouseSeeder`       | 1 gudang pusat                                               |
| 6  | `WarehouseProductSeeder`| Stok awal gudang (termasuk stok yang akan didistribusikan)   |
| 7  | `OutletSeeder`          | 3 outlet Medan dengan `keeper_id`                            |
| 8  | `OutletProductSeeder`   | 81 baris distribusi (stok akhir outlet + qty terjual + stock out) |
| 9  | `DistributionSeeder`    | Mengurangi stok gudang sesuai total distribusi               |
| 10 | `StockOutSeeder`        | 54 record stock out + pengurangan stok outlet                |
| 11 | `SalesTransactionSeeder`| 150 transaksi (50 per outlet) + pengurangan stok outlet      |

Master data realistis (nama produk, harga, target stok, jumlah terjual, stock out,
nama pelanggan) terpusat di `database/seeders/Data/SenopatiSeedData.php`.

### Akun demo

Password semua akun: `password123`

| Role     | Email                              | Nama              | Telepon        |
|----------|------------------------------------|-------------------|----------------|
| manager   | `manager@senopaticoffee.id`        | Bayu Prasetyo     | `081234560001` |
| keeper   | `keeper1@senopaticoffee.id`        | Rizky Aditya Ramadhan | `081234560002` |
| keeper   | `keeper2@senopaticoffee.id`        | Salsabila Putri   | `081234560003` |
| keeper   | `keeper3@senopaticoffee.id`        | Fajar Nugroho     | `081234560004` |

### Jaminan konsistensi seeder

- Setiap transaksi menghitung `sub_total`, `tax_total` (10%), `grand_total` secara konsisten.
- Stok outlet berkurang sesuai kuantitas terjual + stock out; stok gudang berkurang
  sesuai total distribusi; **tidak ada stok negatif**.
- `SalesTransactionSeeder` mengakhiri proses dengan *assert*: stok gudang/outlet
  harus sama dengan target di `SenopatiSeedData`, dan jumlah transaksi ≥ 150.
  Jika gagal, seeder melempar `RuntimeException` dan proses berhenti.
- Sengaja disisakan produk stok rendah (≤5) agar laporan "Stok Hampir Habis"
  dapat diuji: Matcha, Hazelnut Syrup, Green Tea Powder, Frozen Croissant,
  White Chocolate Powder, Sanitizer Solution, dsb.

## 3. Role & Hak Akses

Otorisasi per role diterapkan di `routes/api.php` (middleware `role:...`) dan
diperkuat di `app/Services`/`app/Http/Controllers` untuk pemisahan data (scoping).

### Matriks endpoint

| Endpoint                                              | Manager | Keeper |
|-------------------------------------------------------|:-------:|:------:|
| CRUD master data (kategori, produk, gudang, outlet)   | ✔       | ✖      |
| `DELETE warehouses/{warehouse}/products/{product}`    | ✔       | ✖      |
| `GET transactions` (monitoring semua outlet)          | ✔       | ✖      |
| `POST transactions` (penjualan outlet sendiri)        | ✖       | ✔      |
| `POST/PUT merchants/{merchant}/products*` (outlet sendiri) | ✖ | ✔ |
| `POST/PUT warehouses/{warehouse}/products*` (stok)    | ✖       | ✔      |
| `POST stock-outs` (outlet sendiri)                    | ✖       | ✔      |
| `GET stock-outs` / `GET transactions/{id}` / `GET my-merchant*` | ✔ (semua) | ✔ (hanya miliknya) |
| `GET categories/products/warehouses*` (view)          | ✔       | ✔      |

Catatan scoping:
- Keeper hanya dapat mengubah stok/produk outlet miliknya sendiri
  (`authorizeKeeper` di `MerchantProductController`).
- Keeper hanya dapat melihat `GET transactions/{id}` bila transaksi tersebut
  milik merchant-nya (selain itu → 403).
- `StockOutService` memvalidasi kepemilikan outlet dan mencegah stok negatif
  dalam satu transaksi database.

### Endpoint baru — Stock Out

| Method | Endpoint           | Deskripsi                                           |
|--------|--------------------|-----------------------------------------------------|
| GET    | `api/stock-outs`   | Manager: semua outlet; Keeper: hanya outlet sendiri |
| POST   | `api/stock-outs`   | Catat pengeluaran stok & kurangi stok outlet (keeper) |

Contoh payload `POST api/stock-outs`:
```json
{
  "merchant_id": 1,
  "product_id": 12,
  "quantity": 3,
  "reason": "Kemasan rusak / kadaluarsa / gratis"
}
```

## 4. Pengujian

```bash
# Seluruh test suite (unit + fitur + otorisasi role)
php artisan test
```

Test otorisasi (14 kasus) ada di `tests/Feature/RoleAccessTest.php`. Test memakai
database terpisah `gudang-backend-test` (lihat `phpunit.xml`) sehingga data
development di `gudang-backend` tidak terganggu. Sebelum menjalankan test, buat
database test terlebih dahulu:
```sql
CREATE DATABASE gudang-backend-test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 5. Deployment Produksi

```bash
# 1. Salin proyek ke server, pasang dependensi tanpa dev
composer install --no-dev --optimize-autoloader

# 2. Atur .env (APP_ENV=production, APP_DEBUG=false, konfigurasi DB)
#    Jangan lupa: CACHE_STORE=file|redis, SESSION_DRIVER, QUEUE_CONNECTION

# 3. Kunci aplikasi (sekali saja)
php artisan key:generate

# 4. Migrasi + seeder (hanya pada deploy pertama)
php artisan migrate --force
php artisan db:seed --force
#    --force diperlukan karena APP_ENV=production

# 5. Tautan storage
php artisan storage:link

# 6. Optimasi produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Arahkan web server ke folder public/ dengan rewrite ke index.php
```

> **Catatan:** `db:seed` hanya boleh dijalankan **sekali** pada deploy pertama.
> Bila data produksi sudah terisi, jangan jalankan seeder ulang karena akan
> membuat data ganda (kecuali `fresh`, yang menghapus seluruh data).

## 6. Konfigurasi Web Server

### Nginx

```nginx
server {
    listen 80;
    server_name api.example.com;
    root /var/www/mondaybwabackend-main/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache

```apache
<VirtualHost *:80>
    ServerName api.example.com
    DocumentRoot /var/www/mondaybwabackend-main/public

    <Directory /var/www/mondaybwabackend-main/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/mondaybwa-error.log
    CustomLog ${APACHE_LOG_DIR}/mondaybwa-access.log combined
</VirtualHost>
```

## 7. Scheduler & Queue

- Jalankan scheduler setiap menit di cron:
  ```
  * * * * * cd /var/www/mondaybwabackend-main && php artisan schedule:run >> /dev/null 2>&1
  ```
- Untuk queue (bila `QUEUE_CONNECTION=database` dan ada antrian):
  ```
  php artisan queue:work
  ```

## 8. Pemecahan Masalah

| Gejala                              | Solusi                                                              |
|-------------------------------------|---------------------------------------------------------------------|
| `No application encryption key`     | Jalankan `php artisan key:generate`                                 |
| Foto tidak muncul                   | `php artisan storage:link`; pastikan folder `storage/app/public`    |
| 419 / CSRF                          | Sesi menggunakan driver `database`; jalankan `php artisan migrate`  |
| 500 setelah `config:cache`          | Jalankan ulang `config:cache` setelah mengubah `.env`               |
| `db:seed --force` meminta konfirmasi| Tidak terjadi; gunakan `--force` saat `APP_ENV=production`          |
