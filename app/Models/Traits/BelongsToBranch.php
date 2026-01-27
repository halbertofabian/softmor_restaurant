<?php

namespace App\Models\Traits;

use App\Models\Scopes\BranchScope;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;


trait BelongsToBranch
{
    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
                
                if (session()->has('branch_id')) {
                    $model->branch_id = session('branch_id');
                }
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                // Ensure tenant_id never changes
                $model->tenant_id = auth()->user()->tenant_id;
                
                // Ensure branch_id never changes (optional, but protects moving data between branches)
                // If we want to allow moving items between branches, we remove this.
                // For now, let's enforce integrity.
                // $model->branch_id = $model->getOriginal('branch_id'); 
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
