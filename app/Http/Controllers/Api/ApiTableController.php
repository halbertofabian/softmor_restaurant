<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Table;

class ApiTableController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Require branch_id parameter
        $branchId = $request->input('branch_id');
        
        if (!$branchId) {
            return response()->json([
                'status' => 'error',
                'message' => 'branch_id es requerido'
            ], 400);
        }
        
        // Verify user has access to this branch
        $hasAccess = $user->branches()->where('branches.id', $branchId)->exists();
        
        if (!$hasAccess) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }
        
        // Filter tables by tenant AND branch
        $tables = Table::where('tenant_id', $user->tenant_id)
                      ->where('branch_id', $branchId)
                      ->where('is_active', true)
                      ->orderBy('name')
                      ->get()
                      ->map(function($table) {
                          $activeOrder = $table->activeOrder;
                          
                          // Use the table's actual status field (matches web behavior)
                          $status = $table->status ?? 'free';
                          $hasOrder = $activeOrder !== null;
                          
                          return [
                              'id' => $table->id,
                              'name' => $table->name,
                              'status' => $status,
                              'has_active_order' => $hasOrder,
                              'seats' => $table->capacity ?? 4,
                              'active_order_id' => $activeOrder ? $activeOrder->id : null
                          ];
                      });

        return response()->json([
            'status' => 'success',
            'data' => $tables
        ]);
    }
    
    public function occupy(Request $request, Table $table)
    {
        $user = $request->user();
        
        // Verify table belongs to user's tenant and accessible branch
        if ($table->tenant_id !== $user->tenant_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta mesa'
            ], 403);
        }
        
        // Check if user has access to this table's branch
        $hasAccess = $user->branches()->where('branches.id', $table->branch_id)->exists();
        if (!$hasAccess) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }
        
        if ($table->status !== 'free') {
            return response()->json([
                'status' => 'error',
                'message' => 'La mesa no está disponible'
            ], 400);
        }
        
        $table->update(['status' => 'occupied']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Mesa ocupada exitosamente',
            'table' => [
                'id' => $table->id,
                'name' => $table->name,
                'status' => $table->status
            ]
        ]);
    }
    
    public function release(Request $request, Table $table)
    {
        $user = $request->user();
        
        // Verify table belongs to user's tenant
        if ($table->tenant_id !== $user->tenant_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta mesa'
            ], 403);
        }
        
        // Check if table has active orders
        if ($table->activeOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede liberar una mesa con orden activa'
            ], 400);
        }
        
        $table->update(['status' => 'free']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Mesa liberada exitosamente',
            'table' => [
                'id' => $table->id,
                'name' => $table->name,
                'status' => $table->status
            ]
        ]);
    }
}
