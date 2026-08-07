<?php

namespace Database\Seeders;

use Database\Seeders\Data\SenopatiSeedData;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan role legacy 'customer' bila masih ada dari project lama
        Role::where('name', 'customer')->delete();

        foreach (SenopatiSeedData::roles() as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $permissionNames = [];
        foreach (SenopatiSeedData::permissions() as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            $permissionNames[] = $permissionName;
        }

        Role::findByName('manager', 'web')->givePermissionTo($permissionNames);
    }
}
