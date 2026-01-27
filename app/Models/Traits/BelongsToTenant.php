<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        static::updating(function ($model) {
            // Prevent tenant_id from being changed during updates
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function tenant()
    {
        // Optional relationship if we had a Tenant model, 
        // but for now we just use the ID from the user/session.
        // We can link to Subscription if needed.
    }
}
