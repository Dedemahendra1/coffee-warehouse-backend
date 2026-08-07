<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $manager = SenopatiSeedData::managerUser();
        User::factory()->create([
            'name'     => $manager['name'],
            'email'    => $manager['email'],
            'phone'    => $manager['phone'],
            'photo'    => $manager['photo'],
            'password' => 'password123',
        ])->assignRole('manager');

        foreach (SenopatiSeedData::keeperUsers() as $keeper) {
            User::factory()->create([
                'name'     => $keeper['name'],
                'email'    => $keeper['email'],
                'phone'    => $keeper['phone'],
                'photo'    => $keeper['photo'],
                'password' => 'password123',
            ])->assignRole('keeper');
        }
    }
}
