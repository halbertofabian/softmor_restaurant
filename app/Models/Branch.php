<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory, \Illuminate\Database\Eloquent\SoftDeletes, \App\Models\Traits\BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'phone', 'address', 'is_active'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'branch_user')->withPivot('is_active', 'assigned_at');
    }
}
