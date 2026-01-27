<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function createSubscription(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Generate Tenant ID (UUID)
            $tenantId = (string) Str::uuid();

            // Create Subscription
            $subscription = Subscription::create([
                'tenant_id' => $tenantId,
                'subscriber_email' => $data['email'],
                'subscription_date' => now(),
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'status' => 'active',
            ]);

            // Create Admin User
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'pais_whatsapp' => $data['pais_whatsapp'],
                'tenant_id' => $tenantId,
                'estado' => 'activo',
            ]);

            // Assign Admin Role
            $adminRole = Role::where('name', 'administrador')->whereNull('tenant_id')->first();
            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }

            // Create Default Branch "Matriz"
            $branch = \App\Models\Branch::create([
                'tenant_id' => $tenantId,
                'name' => 'Matriz',
                'address' => 'Dirección Principal',
                'is_active' => true
            ]);

            // Assign User to Branch
            // We must provide tenant_id manually for the pivot because user is not authenticated yet
            $user->branches()->attach($branch->id, ['tenant_id' => $tenantId, 'is_active' => true]);

            return $user;
        });
    }

    public function createSubscriptionWithInvitation(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Generate Tenant ID
            $tenantId = (string) Str::uuid();

            // Create Subscription
            $subscription = Subscription::create([
                'tenant_id' => $tenantId,
                'subscriber_email' => $data['email'],
                'subscription_date' => now(),
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'status' => 'pending_setup', // New status? Or just 'active'
            ]);

            // Create Admin User with random password (will be reset)
            $tempPassword = Str::random(16);
            
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($tempPassword),
                'pais_whatsapp' => $data['pais_whatsapp'],
                'tenant_id' => $tenantId,
                'estado' => 'pending', // Pending activation
            ]);

            // Assign Admin Role
            $adminRole = Role::where('name', 'administrador')->whereNull('tenant_id')->first();
            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }

            // Create Default Branch "Matriz"
            $branch = \App\Models\Branch::create([
                'tenant_id' => $tenantId,
                'name' => 'Matriz',
                'address' => 'Dirección Principal',
                'is_active' => true
            ]);

            // Assign User to Branch
            $user->branches()->attach($branch->id, ['tenant_id' => $tenantId, 'is_active' => true]);

            // Generate Invitation Token
            $token = Str::random(64);
            DB::table('setup_tokens')->insert([
                'email' => $data['email'],
                'token' => $token,
                'created_at' => now(),
                'expires_at' => now()->addHours(48),
            ]);

            return [
                'user' => $user,
                'token' => $token,
                'link' => route('setup-account.show', $token)
            ];
        });
    }
}
