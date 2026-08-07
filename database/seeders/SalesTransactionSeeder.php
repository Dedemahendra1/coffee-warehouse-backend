<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Models\WarehouseProduct;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

class SalesTransactionSeeder extends Seeder
{
    /**
     * Jumlah transaksi yang dihasilkan untuk setiap outlet.
     */
    private const TARGET_TRANSACTIONS_PER_OUTLET = 50;

    public function run(): void
    {
        $customerNames = SenopatiSeedData::customerNames();
        $outletNames = collect(SenopatiSeedData::outlets())->pluck('name')->all();
        $linesPerOutlet = $this->buildLineItems();

        foreach ($outletNames as $outletIndex => $outletName) {
            $merchant = Merchant::where('name', $outletName)->firstOrFail();
            $groups = $this->packLines($linesPerOutlet[$outletIndex], self::TARGET_TRANSACTIONS_PER_OUTLET);

            foreach ($groups as $groupIndex => $group) {
                $createdAt = $this->transactionDate($groupIndex, count($groups));

                $subTotal = array_sum(array_column($group, 'sub_total'));
                $taxTotal = (int) round($subTotal * 0.1);
                $grandTotal = $subTotal + $taxTotal;

                $transaction = Transaction::factory()->create([
                    'name'        => $customerNames[array_rand($customerNames)],
                    'phone'       => '08' . random_int(100000000, 999999999),
                    'sub_total'   => $subTotal,
                    'tax_total'   => $taxTotal,
                    'grand_total' => $grandTotal,
                    'merchant_id' => $merchant->id,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt,
                ]);

                foreach ($group as $line) {
                    TransactionProduct::factory()->create([
                        'transaction_id' => $transaction->id,
                        'product_id'     => $line['product_id'],
                        'quantity'       => $line['quantity'],
                        'price'          => $line['price'],
                        'sub_total'      => $line['sub_total'],
                    ]);

                    // Stok outlet otomatis berkurang mengikuti penjualan.
                    MerchantProduct::where('merchant_id', $merchant->id)
                        ->where('product_id', $line['product_id'])
                        ->decrement('stock', $line['quantity']);
                }
            }
        }

        $this->assertStocksMatchTarget();
    }

    /**
     * Membangun daftar baris penjualan (line items) dari total terjual per produk
     * per outlet, dipecah menjadi kuantitas-kuantitas yang realistis.
     *
     * @return array<int, array<int, array<string, int>>>  [outletIndex => [line, ...]]
     */
    private function buildLineItems(): array
    {
        $productByKey = Product::pluck('id', 'name')->all();
        $outletCount = count(SenopatiSeedData::outlets());

        $totalsPerOutlet = array_fill(0, $outletCount, []);

        foreach (SenopatiSeedData::products() as $productData) {
            if (empty($productData['outlets'])) {
                continue;
            }

            $productId = $productByKey[$productData['name']];
            $price = $productData['price'];

            foreach ($productData['outlets'] as $outletIndex => [$finalStock, $sold]) {
                if ($sold <= 0) {
                    continue;
                }

                $totalsPerOutlet[$outletIndex][] = [
                    'product_id' => $productId,
                    'price'      => $price,
                    'total'      => $sold,
                    'min_qty'    => $productData['min_qty'],
                    'max_qty'    => $productData['max_qty'],
                ];
            }
        }

        $linesPerOutlet = [];

        foreach ($totalsPerOutlet as $outletIndex => $totals) {
            $lines = [];

            foreach ($totals as $item) {
                $remaining = $item['total'];

                while ($remaining > 0) {
                    $max = min($item['max_qty'], $remaining);
                    $min = min($item['min_qty'], $max);
                    $quantity = random_int($min, $max);

                    $lines[] = [
                        'product_id' => $item['product_id'],
                        'price'      => $item['price'],
                        'quantity'   => $quantity,
                        'sub_total'  => $quantity * $item['price'],
                    ];

                    $remaining -= $quantity;
                }
            }

            $linesPerOutlet[$outletIndex] = $lines;
        }

        return $linesPerOutlet;
    }

    /**
     * Mengelompokkan baris penjualan menjadi $target transaksi yang masing-masing
     * berisi 1-5 produk.
     *
     * @param  array<int, array<string, int>>  $lines
     * @return array<int, array<int, array<string, int>>>
     */
    private function packLines(array $lines, int $target): array
    {
        shuffle($lines);

        // Pastikan jumlah grup selalu feasibel: 1..5 baris per grup.
        $target = max(1, min($target, count($lines)));
        $target = max($target, (int) ceil(count($lines) / 5));

        $groups = [];

        while (count($groups) < $target && !empty($lines)) {
            $groupsLeft = $target - count($groups) - 1;

            $maxTake = min(5, count($lines) - $groupsLeft);
            $minTake = max(1, count($lines) - $groupsLeft * 5);

            $groups[] = array_splice($lines, 0, random_int($minTake, $maxTake));
        }

        return $groups;
    }

    private function transactionDate(int $index, int $total): Carbon
    {
        // Beberapa transaksi terakhir dibuat "hari ini" agar statistik harian terisi.
        if ($index >= $total - 5) {
            return Carbon::today()->setTime(random_int(8, 21), random_int(0, 59), random_int(0, 59));
        }

        return Carbon::now()
            ->subDays(random_int(2, 90))
            ->setTime(random_int(8, 21), random_int(0, 59));
    }

    /**
     * Memverifikasi seluruh rantai stok konsisten dengan katalog target.
     */
    private function assertStocksMatchTarget(): void
    {
        $productByKey = Product::pluck('id', 'name')->all();
        $outletNames = collect(SenopatiSeedData::outlets())->pluck('name')->all();
        $outletIds = [];

        foreach ($outletNames as $index => $outletName) {
            $outletIds[$index] = Merchant::where('name', $outletName)->value('id');
        }

        foreach (SenopatiSeedData::products() as $productData) {
            $productId = $productByKey[$productData['name']];

            $warehouseStock = (int) WarehouseProduct::where('product_id', $productId)->value('stock');
            if ($warehouseStock !== (int) $productData['wfinal']) {
                throw new RuntimeException(
                    "Warehouse stock mismatch untuk {$productData['name']} (expected {$productData['wfinal']}, got {$warehouseStock})."
                );
            }

            foreach ($productData['outlets'] as $index => [$finalStock, $sold]) {
                $outletStock = (int) MerchantProduct::where('merchant_id', $outletIds[$index])
                    ->where('product_id', $productId)
                    ->value('stock');

                if ($outletStock !== (int) $finalStock) {
                    throw new RuntimeException(
                        "Outlet stock mismatch untuk {$productData['name']} di {$outletNames[$index]} (expected {$finalStock}, got {$outletStock})."
                    );
                }
            }
        }

        $transactionCount = Transaction::count();
        if ($transactionCount < 150) {
            throw new RuntimeException("Jumlah transaksi ({$transactionCount}) kurang dari 150.");
        }
    }
}
