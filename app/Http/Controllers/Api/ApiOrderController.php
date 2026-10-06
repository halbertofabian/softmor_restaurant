<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientRecipeInventory;
use App\Http\Controllers\Api\Concerns\AuthorizesBranchAccess;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PreparationArea;
use App\Models\Product;
use App\Models\ProductFlavor;
use App\Models\Table;
use App\Models\User;
use App\Services\PrintJobService;
use App\Services\RecipeInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ApiOrderController extends Controller
{
    use AuthorizesBranchAccess;

    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');

        if (! $branchId) {
            return response()->json([
                'status' => 'error',
                'message' => 'branch_id es requerido',
            ], 400);
        }

        if (! $this->userHasBranchAccess($user, $branchId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal',
            ], 403);
        }

        $request->validate([
            'status' => 'nullable|in:open,sent,in_preparation,closed,canceled',
            'table_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Order::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('branch_id', $branchId)
            ->with(['table:id,name', 'user:id,name'])
            ->withCount(['details as pending_details_count' => fn ($details) => $details->where('status', 'pending')])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('table_id')) {
            $query->where('table_id', $request->input('table_id'));
        }

        $orders = $query->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => collect($orders->items())->map(fn (Order $order) => [
                'id' => $order->id,
                'table_id' => $order->table_id,
                'table_name' => $order->table?->name,
                'status' => $order->status,
                'total' => (float) $order->total,
                'has_pending' => (int) $order->pending_details_count > 0,
                'waiter_name' => $order->user?->name,
                'created_at' => $order->created_at?->toIso8601String(),
                'closed_at' => $order->closed_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    // Get active order for a table or create one
    public function getOrCreate(Request $request)
    {
        \Log::info('API: getOrCreate called', ['request' => $request->all()]);

        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'branch_id' => 'required|exists:branches,id',
            'waiter_id' => 'nullable|integer|exists:users,id',
        ]);

        $user = $request->user();
        $branchId = $request->branch_id;
        $tableId = $request->table_id;

        // Verify user has access to this branch
        $hasAccess = $this->userHasBranchAccess($user, $branchId);

        if (! $hasAccess) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal',
            ], 403);
        }

        // Verify table belongs to this branch and tenant
        $table = Table::where('id', $tableId)
            ->where('branch_id', $branchId)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (! $table) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mesa no encontrada en esta sucursal',
            ], 404);
        }

        // Meseros self-assign; admin/caja must pick a valid waiter of this branch
        if ($user->hasRole('mesero')) {
            $waiterId = $user->id;
        } else {
            $request->validate([
                'waiter_id' => 'required|exists:users,id',
            ]);

            $waiter = User::where('id', $request->waiter_id)
                ->where('tenant_id', $user->tenant_id)
                ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'mesero'))
                ->whereHas('branches', fn ($branchQuery) => $branchQuery->where('branches.id', $branchId))
                ->first();

            if (! $waiter) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Selecciona un mesero válido de esta sucursal',
                ], 422);
            }

            $waiterId = $waiter->id;
        }

        $order = Order::where('table_id', $table->id)
            ->where('status', 'open')
            ->with(['details' => fn ($query) => $query->where('is_combo_component', false)->where('status', '!=', 'canceled')])
            ->first();

        if ($order && $user->hasRole('mesero') && (int) $order->user_id !== (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Esta comanda está siendo atendida por otro mesero',
            ], 403);
        }

        if (! $order) {
            $order = Order::create([
                'table_id' => $table->id,
                'user_id' => $waiterId,
                'status' => 'open',
                'branch_id' => $branchId,
                'tenant_id' => $user->tenant_id,
            ]);

            // Mark table as occupied
            $table->status = 'occupied';
            $table->save();
        }

        \Log::info('API: Order retrieved/created', ['order_id' => $order->id, 'table_id' => $table->id]);

        return response()->json([
            'status' => 'success',
            'order' => $order->load(['details' => fn ($query) => $query->where('is_combo_component', false)->where('status', '!=', 'canceled')]),
        ]);
    }

    public function addItem(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        \Log::info('API: addItem called', ['order_id' => $order->id, 'request' => $request->all()]);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_flavor_id' => 'nullable|exists:product_flavors,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($order->status !== 'open') {
            return response()->json(['status' => 'error', 'message' => 'Orden cerrada'], 400);
        }

        $product = Product::whereKey($request->product_id)
            ->where('tenant_id', $order->tenant_id)->where('branch_id', $order->branch_id)->firstOrFail();
        $flavor = null;

        if ($product->type === 'combo') {
            $comboItems = $product->comboItems()->with('componentProduct')->get();
            if ($comboItems->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'El combo no tiene componentes'], 422);
            }

            $detail = DB::transaction(function () use ($order, $product, $comboItems, $request) {
                $parent = OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $request->quantity,
                    'preparation_area_id' => $product->preparation_area_id,
                    'notes' => $request->notes,
                    'status' => 'pending',
                    'is_combo_component' => false,
                    'tenant_id' => $order->tenant_id,
                    'branch_id' => $order->branch_id,
                ]);
                foreach ($comboItems as $comboItem) {
                    $component = $comboItem->componentProduct;
                    if (! $component) {
                        continue;
                    }
                    OrderDetail::create([
                        'order_id' => $order->id,
                        'parent_order_detail_id' => $parent->id,
                        'product_id' => $component->id,
                        'product_name' => $component->name,
                        'price' => 0,
                        'quantity' => $request->quantity * $comboItem->quantity,
                        'preparation_area_id' => $component->preparation_area_id,
                        'notes' => 'Combo: '.$product->name,
                        'status' => 'pending',
                        'is_combo_component' => true,
                        'tenant_id' => $order->tenant_id,
                        'branch_id' => $order->branch_id,
                    ]);
                }
                $order->calculateTotal();

                return $parent;
            });

            return response()->json(['status' => 'success', 'detail' => $detail, 'order_total' => $order->fresh()->total]);
        }

        if ($request->filled('product_flavor_id')) {
            $flavor = ProductFlavor::where('id', $request->product_flavor_id)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (! $flavor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El sabor seleccionado no es válido para este producto',
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
            'branch_id' => $order->branch_id,
        ]);

        $order->calculateTotal();

        return response()->json([
            'status' => 'success',
            'detail' => $detail,
            'order_total' => $order->total,
        ]);
    }

    public function removeItem(Request $request, Order $order, OrderDetail $detail)
    {
        $this->authorizeOrder($request, $order);
        if ($detail->order_id !== $order->id) {
            return response()->json(['status' => 'error', 'message' => 'Item no pertenece a esta orden'], 400);
        }
        abort_if($detail->is_combo_component, 404);

        if ($detail->status === 'sent') {
            foreach ($detail->componentDetails()->where('status', 'sent')->get() as $component) {
                app(RecipeInventoryService::class)->cancel($component, $request->user()->id);
            }
            app(RecipeInventoryService::class)->cancel($detail, $request->user()->id);
        } elseif ($detail->status === 'pending') {
            $detail->delete();
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto ya fue cancelado',
            ], 400);
        }
        $order->calculateTotal();

        return response()->json([
            'status' => 'success',
            'order_total' => $order->total,
        ]);
    }

    public function updateItem(Request $request, Order $order, OrderDetail $detail)
    {
        $this->authorizeOrder($request, $order);

        if ((int) $detail->order_id !== (int) $order->id) {
            return response()->json(['status' => 'error', 'message' => 'Item no pertenece a esta orden'], 400);
        }

        if ($detail->is_combo_component) {
            return response()->json(['status' => 'error', 'message' => 'Los componentes de combo no se editan directamente'], 422);
        }

        if ($detail->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Solo se pueden editar productos pendientes'], 422);
        }

        if ($detail->product?->type === 'combo') {
            return response()->json(['status' => 'error', 'message' => 'Para modificar un combo, elimínalo y agrégalo de nuevo'], 422);
        }

        $data = $request->validate([
            'quantity' => 'sometimes|integer|min:1|max:99',
            'notes' => 'sometimes|nullable|string|max:255',
        ]);

        if (empty($data)) {
            return response()->json(['status' => 'error', 'message' => 'Nada que actualizar'], 422);
        }

        $detail->update($data);
        $order->calculateTotal();

        return response()->json([
            'status' => 'success',
            'detail' => $detail->fresh(),
            'order_total' => $order->total,
        ]);
    }

    public function sendToKitchen(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $inventory = app(RecipeInventoryService::class);
        try {
            $pendingDetails = $inventory->sendPending($order, $request->user()->id, $request->boolean('allow_negative_inventory'));
        } catch (InsufficientRecipeInventory $exception) {
            return response()->json([
                'status' => 'inventory_warning',
                'message' => 'Existencia insuficiente. Confirma para enviar de todos modos.',
                'shortages' => $exception->shortages,
            ], 409);
        }
        if ($pendingDetails->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => '0 ítems enviados',
                'updated_count' => 0,
            ]);
        }

        $updatedCount = $pendingDetails->count();
        $pendingDetails = $pendingDetails->filter(fn ($detail) => $detail->product?->type !== 'combo');

        // Direct local print by preparation area (no monitor tab required)
        try {
            $settings = \App\Models\Setting::withoutGlobalScopes()
                ->where('branch_id', $order->branch_id)
                ->pluck('value', 'key')
                ->toArray();

            $bridgeUrl = $settings['local_bridge_url'] ?? 'http://localhost:8000/api/printer/raw';
            $defaultPrinter = $settings['ticket_printer_name'] ?? 'POS-80';

            if (app(PrintJobService::class)->enqueueKitchen($order, $pendingDetails, $settings)) {
                return response()->json([
                    'status' => 'success',
                    'message' => "$updatedCount ítems enviados",
                    'updated_count' => $updatedCount,
                ]);
            }

            $order->loadMissing(['table', 'user']);

            $detailsByArea = $pendingDetails->groupBy('preparation_area_id');
            $areas = \App\Models\PreparationArea::withoutGlobalScopes()
                ->whereIn('id', $detailsByArea->keys()->filter()->values())
                ->get()
                ->keyBy('id');

            foreach ($detailsByArea as $areaId => $items) {
                if (! $areaId) {
                    continue;
                }

                $area = $areas->get($areaId);
                if (! $area || ! $area->print_ticket) {
                    continue;
                }

                $payload = [
                    'type' => 'kitchen',
                    'printer_name' => ! empty($area->printer_name) ? $area->printer_name : $defaultPrinter,
                    'table_name' => $order->table->name ?? '?',
                    'waiter_name' => $order->user->name ?? 'Mesero',
                    'date' => now()->format('H:i'),
                    'items' => $items->map(function ($item) {
                        return [
                            'quantity' => $item->quantity,
                            'name' => $item->product_name.($item->flavor_name ? ' ('.$item->flavor_name.')' : ''),
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
            'message' => "$updatedCount ítems enviados",
            'updated_count' => $updatedCount,
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        $order->load([
            'table:id,name',
            'user:id,name',
            'details' => fn ($query) => $query->where('is_combo_component', false)->where('status', '!=', 'canceled'),
            'details.preparationArea:id,name',
        ]);

        $order->details->each->append('preparation_area_name');

        return response()->json([
            'status' => 'success',
            'order' => $order,
        ]);
    }

    public function send(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        $request->validate([
            'print_mode' => 'nullable|in:client,server',
            'allow_negative_inventory' => 'nullable|boolean',
        ]);

        if ($request->input('print_mode', 'server') === 'server') {
            return $this->sendToKitchen($request, $order);
        }

        $inventory = app(RecipeInventoryService::class);

        try {
            $pendingDetails = $inventory->sendPending(
                $order,
                $request->user()->id,
                $request->boolean('allow_negative_inventory')
            );
        } catch (InsufficientRecipeInventory $exception) {
            return response()->json([
                'status' => 'inventory_warning',
                'message' => 'Existencia insuficiente. Confirma para enviar de todos modos.',
                'shortages' => $exception->shortages,
            ], 409);
        }

        if ($pendingDetails->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => '0 ítems enviados',
                'updated_count' => 0,
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'total' => (float) $order->total,
                ],
                'print' => [
                    'generated_at' => now()->toIso8601String(),
                    'table_name' => $order->table?->name,
                    'waiter_name' => $order->user?->name,
                    'areas' => [],
                ],
                'inventory_warnings' => [],
            ]);
        }

        $updatedCount = $pendingDetails->count();
        $order->loadMissing(['table:id,name', 'user:id,name']);

        return response()->json([
            'status' => 'success',
            'message' => "$updatedCount ítems enviados",
            'updated_count' => $updatedCount,
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'total' => (float) $order->total,
            ],
            'print' => $this->buildPrintPayload($order, $pendingDetails),
            'inventory_warnings' => [],
        ]);
    }

    public function markPrinted(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        $data = $request->validate([
            'detail_ids' => 'required|array|min:1',
            'detail_ids.*' => 'integer',
        ]);

        $updated = OrderDetail::where('order_id', $order->id)
            ->whereIn('id', $data['detail_ids'])
            ->update(['is_printed' => true, 'updated_at' => now()]);

        return response()->json([
            'status' => 'success',
            'updated' => $updated,
        ]);
    }

    public function printPayload(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        $data = $request->validate([
            'area_id' => 'nullable|integer',
        ]);

        $order->loadMissing(['table:id,name', 'user:id,name']);

        $details = OrderDetail::where('order_id', $order->id)
            ->where('status', 'sent')
            ->with('product:id,type')
            ->get();

        return response()->json([
            'status' => 'success',
            'print' => $this->buildPrintPayload($order, $details, $data['area_id'] ?? null),
        ]);
    }

    private function buildPrintPayload(Order $order, Collection $details, ?int $areaId = null): array
    {
        $printable = $details->filter(fn (OrderDetail $detail) => $detail->product?->type !== 'combo');

        if ($areaId !== null) {
            $printable = $printable->where('preparation_area_id', $areaId);
        }

        $grouped = $printable->groupBy('preparation_area_id');

        $areas = PreparationArea::withoutGlobalScopes()
            ->whereIn('id', $grouped->keys()->filter()->values())
            ->get()
            ->keyBy('id');

        $payloadAreas = [];

        foreach ($grouped as $preparationAreaId => $items) {
            $area = $areas->get($preparationAreaId);

            if (! $area || ! $area->print_ticket) {
                continue;
            }

            $payloadAreas[] = [
                'area_id' => $area->id,
                'area_name' => $area->name,
                'print_ticket' => (bool) $area->print_ticket,
                'detail_ids' => $items->pluck('id')->values()->all(),
                'items' => $items->map(fn (OrderDetail $item) => [
                    'detail_id' => $item->id,
                    'quantity' => (int) $item->quantity,
                    'name' => $item->product_name.($item->flavor_name ? ' ('.$item->flavor_name.')' : ''),
                    'notes' => $item->notes ?? '',
                ])->values()->all(),
            ];
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'table_name' => $order->table?->name,
            'waiter_name' => $order->user?->name,
            'areas' => $payloadAreas,
        ];
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        $user = $request->user();

        abort_unless(
            $order->tenant_id === $user->tenant_id
            && $this->userHasBranchAccess($user, $order->branch_id),
            403
        );

        abort_if(
            $user->hasRole('mesero') && (int) $order->user_id !== (int) $user->id,
            403,
            'Esta comanda está siendo atendida por otro mesero.'
        );
    }
}
