<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // 1. First, apply Tenant Scope (always redundant but safe)
        if (auth()->check()) {
            $builder->where($model->getTable() . '.tenant_id', auth()->user()->tenant_id);
        }

        // 2. Apply Branch Scope
        // Only if user is logged in AND has a selected branch in session
        if (auth()->check() && session()->has('branch_id')) {
            $builder->where($model->getTable() . '.branch_id', session('branch_id'));
        }
    }
}
