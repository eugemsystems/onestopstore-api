<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voucher;
use App\Enums\RoleEnum;

class VoucherPolicy
{
    /**
     * Determine if the user can view any vouchers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('vouchers.index') || $user->hasRole(RoleEnum::ADMIN);
    }

    /**
     * Determine if the user can view the voucher.
     */
    public function view(User $user, ?Voucher $voucher = null): bool
    {
        return $user->hasPermissionTo('vouchers.show') || $user->hasRole(RoleEnum::ADMIN);
    }

    /**
     * Determine if the user can create vouchers.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('vouchers.create') || $user->hasRole(RoleEnum::ADMIN);
    }

    /**
     * Determine if the user can update the voucher.
     */
    public function update(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('vouchers.update') || $user->hasRole(RoleEnum::ADMIN);
    }

    /**
     * Determine if the user can delete the voucher.
     */
    public function delete(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('vouchers.delete') || $user->hasRole(RoleEnum::ADMIN);
    }
}

