<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesBranchAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Table;

class ApiTableController extends Controller
{
    use AuthorizesBranchAccess;

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
        $hasAccess = $this->userHasBranchAccess($user, $branchId);
        
        if (!$hasAccess) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }
        
        // Filter tables by tenant AND branch
        $tables = Table::with('activeOrder.user')
                      ->where('tenant_id', $user->tenant_id)
                      ->where('branch_id', $branchId)
                      ->where('is_active', true)
                      ->orderBy('name')
                      ->get()
                      ->map(function($table) {
                          $activeOrder = $table->activeOrder;
                          
                          // Use the table's actual status field (matches web behavior)
                          $status = $table->status ?? 'free';
                          $hasOrder = $activeOrder !== null;

                          if ($hasOrder && $status === 'free') {
                              $status = 'occupied';
                              $table->update(['status' => 'occupied']);
                          }
                          
                          return [
                              'id' => $table->id,
                              'name' => $table->name,
                              'zone' => $table->zone,
                              'status' => $status,
                              'has_active_order' => $hasOrder,
                              'seats' => $table->capacity !== null ? (int) $table->capacity : null,
                              'active_order_id' => $activeOrder ? $activeOrder->id : null,
                              'active_order_waiter_id' => $activeOrder ? $activeOrder->user_id : null,
                              'active_order_waiter_name' => $activeOrder?->user?->name
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
        $hasAccess = $this->userHasBranchAccess($user, $table->branch_id);
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

        if (!$this->userHasBranchAccess($user, $table->branch_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }

        $openOrders = Order::where('table_id', $table->id)
            ->whereNotIn('status', ['closed', 'canceled'])
            ->get();

        foreach ($openOrders as $openOrder) {
            // Meseros can only release their own orders (same criterion as web)
            if ($user->hasRole('mesero') && (int) $openOrder->user_id !== (int) $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Esta comanda está siendo atendida por otro mesero'
                ], 403);
            }

            $hasActiveItems = $openOrder->details()
                ->where('status', '!=', 'canceled')
                ->where('is_combo_component', false)
                ->exists();

            if ($hasActiveItems) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'La comanda tiene productos activos. Cóbrala o cancélalos antes de desocupar.'
                ], 422);
            }
        }

        foreach ($openOrders as $openOrder) {
            $openOrder->update(['status' => 'closed', 'closed_at' => now()]);
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
