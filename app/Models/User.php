<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'pais_whatsapp',
        'estado'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Secure Route Binding to prevent IDOR across tenants.
     * This ensures that when accessing /users/{id}, the user must belong to the same tenant.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // If the user is logged in
        if (auth()->check()) {
            // Allow Super Admin to access ANY user
            if (auth()->user()->hasRole('super_admin')) {
                return $this->where('id', $value)->firstOrFail();
            }

            // Restrict regular users to their tenant
            return $this->where('id', $value)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->firstOrFail();
        }

        // Fallback for non-authenticated contexts (unlikely for route restrictions)
        return parent::resolveRouteBinding($value, $field);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user')
                    ->withPivot('is_active', 'tenant_id');
    }

    public function getActiveBranchesAttribute()
    {
        return $this->branches()->where('branches.is_active', true)->wherePivot('is_active', true)->get();
    }
}
