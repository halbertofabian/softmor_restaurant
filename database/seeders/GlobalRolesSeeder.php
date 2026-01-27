<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GlobalRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'administrador', 'description' => 'Acceso total al tenant'],
            ['name' => 'mesero', 'description' => 'Toma de pedidos y gestión de mesas'],
            ['name' => 'cocinero', 'description' => 'Monitor de cocina'],
            ['name' => 'caja', 'description' => 'Cierre de cuentas y cobros'],
        ];

        foreach ($roles as $role) {
            \DB::table('roles')->insert([
                'name' => $role['name'],
                'description' => $role['description'],
                'tenant_id' => null, // Global
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
