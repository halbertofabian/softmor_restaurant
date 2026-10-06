<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Branch;
use App\Models\User;

trait AuthorizesBranchAccess
{
    protected function userHasBranchAccess(User $user, $branchId): bool
    {
        if ($user->branches()->where('branches.id', $branchId)->exists()) {
            return true;
        }

        if ($user->hasRole('administrador') || $user->hasRole('admin')) {
            return Branch::where('id', $branchId)
                ->where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->exists();
        }

        return false;
    }
}
