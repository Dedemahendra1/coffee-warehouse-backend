<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\User;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    public function run(): void
    {
        $keepers = User::role('keeper')->orderBy('id')->get();

        foreach (SenopatiSeedData::outlets() as $index => $outlet) {
            Merchant::factory()->create([
                'name'      => $outlet['name'],
                'address'   => $outlet['address'],
                'phone'     => $outlet['phone'],
                'photo'     => $outlet['photo'],
                'keeper_id' => $keepers[$index]->id,
            ]);
        }
    }
}
