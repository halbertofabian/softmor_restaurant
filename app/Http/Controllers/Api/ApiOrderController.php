<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductFlavor;
use App\Models\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ApiOrderController extends Controller
{
    // Get active order for a table or create one
    public function getOrCreate(Request $request)
    {
        \Log::info('API: getOrCreate called', ['request' => $request->all()]);
        
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'branch_id' => 'required|exists:branches,id'
        ]);

        $user = $request->user();
        $branchId = $request->branch_id;
        $tableId = $request->table_id;
        
        // Verify user has access to this branch
        $hasAccess = $user->branches()->where('branches.id', $branchId)->exists();
        
        if (!$hasAccess) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }
        
        // Verify table belongs to this branch and tenant
        $table = Table::where('id', $tableId)
                     ->where('branch_id', $branchId)
                     ->where('tenant_id', $user->tenant_id)
                     ->first();
        
        if (!$table) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mesa no encontrada en esta sucursal'
            ], 404);
        }

        $order = Order::where('table_id', $table->id)
            ->where('status', 'open')
            ->with(['details'])
            ->first();

        if (!$order) {
            $order = Order::create([
                'table_id' => $table->id,
                'user_id' => $user->id,
                'status' => 'open',
                'branch_id' => $branchId,
                'tenant_id' => $user->tenant_id
            ]);

            // Mark table as occupied
            $table->status = 'occupied';
            $table->save();
        }

        \Log::info('API: Order retrieved/created', ['order_id' => $order->id, 'table_id' => $table->id]);

        return response()->json([
            'status' => 'success',
            'order' => $order->load('details')
        ]);
    }

    public function addItem(Request $request, Order $order)
    {
        \Log::info('API: addItem called', ['order_id' => $order->id, 'request' => $request->all()]);
        
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_flavor_id' => 'nullable|exists:product_flavors,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        if ($order->status !== 'open') {
            return response()->json(['status' => 'error', 'message' => 'Orden cerrada'], 400);
        }

        $product = Product::find($request->product_id);
        $flavor = null;

        if ($request->filled('product_flavor_id')) {
            $flavor = ProductFlavor::where('id', $request->product_flavor_id)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (!$flavor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El sabor seleccionado no es válido para este producto'
                ], 422);
            }
        }

        $flavorDelta = $flavor ? (float) $flavor->additional_price : 0;
        $unitPrice = (float) $product->price + $flavorDelta;

        $detail = OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_flavor_id' => $flavor?->id,
            'product_name' => $product->name,
            'flavor_name' => $flavor?->name,
            'price' => $unitPrice,
            'flavor_price_delta' => $flavorDelta,
            'quantity' => $request->quantity,
            'preparation_area_id' => $product->preparation_area_id,
            'notes' => $request->notes,
            'status' => 'pending', // Default status for kitchen flow
            'is_printed' => false,
            'tenant_id' => $order->tenant_id,
            'branch_id' => $order->branch_id
        ]);
        
        $order->calculateTotal();

        return response()->json([
            'status' => 'success',
            'detail' => $detail,
            'order_total' => $order->total
        ]);
    }

    public function removeItem(Request $request, Order $order, OrderDetail $detail)
    {
        if($detail->order_id !== $order->id) {
             return response()->json(['status' => 'error', 'message' => 'Item no pertenece a esta orden'], 400);
        }
        
        // Only allow deleting pending items
        if ($detail->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo se pueden eliminar items pendientes'
            ], 400);
        }
        
        $detail->delete();
        $order->calculateTotal();

        return response()->json([
            'status' => 'success',
            'order_total' => $order->total
        ]);
    }

    public function sendToKitchen(Request $request, Order $order)
    {
        $pendingDetails = $order->details()->where('status', 'pending')->get();
        if ($pendingDetails->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => "0 items enviados a cocina",
                'updated_count' => 0
            ]);
        }

        // Mark all 'pending' items as 'sent'
        $updatedCount = $order->details()
            ->where('status', 'pending')
            ->update([
                'status' => 'sent',
                'updated_at' => now() // Touch updated_at to help kitchen monitor sort/detect
            ]);

        // Direct local print by preparation area (no monitor tab required)
        try {
            $settings = \App\Models\Setting::withoutGlobalScopes()
                ->where('branch_id', $order->branch_id)
                ->pluck('value', 'key')
                ->toArray();

            $bridgeUrl = $settings['local_bridge_url'] ?? 'http://localhost:8000/api/printer/raw';
            $defaultPrinter = $settings['ticket_printer_name'] ?? 'POS-80';

            $order->loadMissing(['table', 'user']);

            $detailsByArea = $pendingDetails->groupBy('preparation_area_id');
            $areas = \App\Models\PreparationArea::withoutGlobalScopes()
                ->whereIn('id', $detailsByArea->keys()->filter()->values())
                ->get()
                ->keyBy('id');

            foreach ($detailsByArea as $areaId => $items) {
                if (!$areaId) {
                    continue;
                }

                $area = $areas->get($areaId);
                if (!$area || !$area->print_ticket) {
                    continue;
                }

                $payload = [
                    'type' => 'kitchen',
                    'printer_name' => !empty($area->printer_name) ? $area->printer_name : $defaultPrinter,
                    'table_name' => $order->table->name ?? '?',
                    'waiter_name' => $order->user->name ?? 'Mesero',
                    'date' => now()->format('H:i'),
                    'items' => $items->map(function ($item) {
                        return [
                            'quantity' => $item->quantity,
                            'name' => $item->product_name . ($item->flavor_name ? ' (' . $item->flavor_name . ')' : ''),
                            'notes' => $item->notes ?? '',
                        ];
                    })->values()->all(),
                ];

                $response = Http::timeout(8)->post($bridgeUrl, $payload);
                if ($response->ok()) {
                    \App\Models\OrderDetail::withoutGlobalScopes()
                        ->whereIn('id', $items->pluck('id')->all())
                        ->update(['is_printed' => true]);
                }
            }
        } catch (\Throwable $e) {
            // Keep order flow running even if local printer is unavailable
        }

        return response()->json([
            'status' => 'success',
            'message' => "$updatedCount items enviados a cocina",
            'updated_count' => $updatedCount
        ]);
    }
    
    public function show(Order $order) {
        return response()->json([
            'status' => 'success',
            'order' => $order->load(['details', 'table'])
        ]);
    }
}
