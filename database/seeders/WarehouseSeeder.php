<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = SenopatiSeedData::warehouse();

        Warehouse::factory()->create([
            'name'    => $warehouse['name'],
            'address' => $warehouse['address'],
            'phone'   => $warehouse['phone'],
            'photo'   => $warehouse['photo'],
        ]);
    }
}
