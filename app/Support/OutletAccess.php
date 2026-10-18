<?php

namespace App\Support;

use App\Models\User;

/**
 * Helper terpusat untuk menentukan outlet yang boleh diakses seorang user.
 *
 * - manager : semua outlet (tidak dibatasi)
 * - keeper  : outlet berdasarkan relasi keeper_id yang sudah ada
 * - kasir   : outlet berdasarkan users.merchant_id
 */
class OutletAccess
{
    /**
     * Merchant id yang boleh diakses user.
     * Mengembalikan null bila user tidak dibatasi (manager) atau tidak punya outlet.
     */
    public static function accessibleMerchantId(User $user): ?int
    {
        if ($user->hasRole('manager')) {
            return null;
        }

        if ($user->hasRole('keeper')) {
            return $user->merchant?->id;
        }

        if ($user->hasRole('kasir')) {
            return $user->assignedMerchant?->id;
        }

        return null;
    }

    public static function canManageMerchant(User $user, int $merchantId): bool
    {
        if ($user->hasRole('manager')) {
            return true;
        }

        if ($user->hasRole('keeper')) {
            return $user->merchant?->id === $merchantId;
        }

        if ($user->hasRole('kasir')) {
            return $user->assignedMerchant?->id === $merchantId;
        }

        return false;
    }
}
