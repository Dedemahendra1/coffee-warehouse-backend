<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\User;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;

class KasirSeeder extends Seeder
{
    public function run(): void
    {
        $outlets = Merchant::orderBy('id')->get();

        if ($outlets->isEmpty()) {
            return;
        }

        foreach (SenopatiSeedData::kasirUsers() as $kasir) {
            $outlet = $outlets[$kasir['outletIndex']] ?? $outlets->first();

            $user = User::updateOrCreate(
                ['email' => $kasir['email']],
                [
                    'name'        => $kasir['name'],
                    'phone'       => $kasir['phone'],
                    'photo'       => $kasir['photo'],
                    'password'    => 'password123',
                    'merchant_id' => $outlet->id,
                ]
            );

            $user->assignRole('kasir');
        }
    }
}
