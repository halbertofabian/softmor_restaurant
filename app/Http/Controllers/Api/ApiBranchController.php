<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;

class ApiBranchController extends Controller
{
    /**
     * Get branches accessible by the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Si el usuario es administrador, ve todas las sucursales ACTIVAS de su tenant
        if ($user->hasRole('administrador') || $user->hasRole('admin')) {
            $branches = Branch::where('tenant_id', $user->tenant_id)
                             ->where('is_active', true)
                             ->get();
        } else {
            // Si es usuario normal (mesero, cocinero, etc), ve solo las asignadas
            // Usamos la relación definida en el modelo User
            $branches = $user->branches()
                            ->where('branches.is_active', true)
                            ->get(); // Laravel automáticamente filtra por la tabla pivote si está bien configurada
            
            // Si la relación trae branches de otros tenants (por error en BD), filtramos
            $branches = $branches->where('tenant_id', $user->tenant_id)->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => $branches->map(function($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address ?? ''
                ];
            })
        ]);
    }

    /**
     * Verify user has access to a specific branch
     */
    public function verifyAccess(Request $request, $branchId)
    {
        $user = $request->user();
        
        $branch = Branch::where('id', $branchId)
                       ->where('tenant_id', $user->tenant_id)
                       ->where('is_active', true)
                       ->first();
        
        if (!$branch) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sucursal no encontrada'
            ], 404);
        }
        
        // Check if user has access
        $hasAccess = $user->branches()->where('branches.id', $branchId)->exists();
        
        if (!$hasAccess) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Acceso verificado',
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address ?? ''
            ]
        ]);
    }
}
