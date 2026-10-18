<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserRoleRepository
{

    public function assignRoleToUser(int $userId, int $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        if ($role->name === 'kasir' && empty($user->merchant_id)) {
            throw ValidationException::withMessages([
                'merchant_id' => ['User ini belum memiliki outlet. Tetapkan outlet pada data user sebelum memberikan role kasir.'],
            ]);
        }

        $user->assignRole($role->name);

        return $user;
    }

    public function removeRoleFromUser(int $userId, int $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        $user->removeRole($role->name);

        return $user;
    }

    public function getUserRoles(int $userId)
    {
        $user = User::findOrFail($userId);

        return $user->roles;
    }

}
