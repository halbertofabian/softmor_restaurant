<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesBranchAccess;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Table;
use Illuminate\Http\Request;

class ApiDashboardController extends Controller
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

        if (! $user->hasRole('administrador') && ! $user->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permiso para ver el dashboard',
            ], 403);
        }

        $ordersQuery = Order::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('branch_id', $branchId);

        $salesToday = (float) (clone $ordersQuery)
            ->whereDate('created_at', today())
            ->where('status', 'closed')
            ->sum('total');

        $ordersToday = (clone $ordersQuery)->whereDate('created_at', today())->count();

        $paidOrdersToday = (clone $ordersQuery)
            ->whereDate('created_at', today())
            ->where('status', 'closed')
            ->count();

        $activeTables = Table::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->count();

        $occupiedTables = Table::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('status', 'occupied')
            ->count();

        $year = (int) now()->year;
        $salesByMonth = array_fill(0, 12, 0.0);

        (clone $ordersQuery)
            ->where('status', 'closed')
            ->whereYear('created_at', $year)
            ->get(['created_at', 'total'])
            ->each(function (Order $order) use (&$salesByMonth) {
                $month = (int) $order->created_at->month;
                $salesByMonth[$month - 1] += (float) $order->total;
            });

        $latestOrders = (clone $ordersQuery)
            ->with('table:id,name')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'table_name' => $order->table?->name,
                'status' => $order->status,
                'total' => (float) $order->total,
                'created_at' => $order->created_at?->toIso8601String(),
            ])
            ->values();

        $topProducts = OrderDetail::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'canceled')
            ->selectRaw('product_name, SUM(quantity) as quantity, SUM(price * quantity) as total')
            ->groupBy('product_name')
            ->orderByDesc('quantity')
            ->take(5)
            ->get()
            ->map(fn ($row) => [
                'product_name' => $row->product_name,
                'quantity' => (int) $row->quantity,
                'total' => (float) $row->total,
            ])
            ->values();

        $totalOrdersValue = (float) (clone $ordersQuery)->sum('total');

        return response()->json([
            'status' => 'success',
            'data' => [
                'sales_today' => round($salesToday, 2),
                'orders_today' => $ordersToday,
                'occupied_tables' => $occupiedTables,
                'active_tables' => $activeTables,
                'avg_ticket' => $paidOrdersToday > 0 ? round($salesToday / $paidOrdersToday, 2) : 0.0,
                'total_orders_value' => round($totalOrdersValue, 2),
                'sales_year' => $year,
                'sales_by_month' => array_map(fn ($value) => round($value, 2), $salesByMonth),
                'latest_orders' => $latestOrders,
                'top_products' => $topProducts,
            ],
        ]);
    }

    public function waiter(Request $request)
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

        $ordersQuery = Order::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('branch_id', $branchId)
            ->where('user_id', $user->id);

        $salesToday = (float) (clone $ordersQuery)
            ->whereDate('created_at', today())
            ->where('status', 'closed')
            ->sum('total');

        $ordersToday = (clone $ordersQuery)->whereDate('created_at', today())->count();

        $openOrdersQuery = (clone $ordersQuery)->whereNotIn('status', ['closed', 'canceled']);

        $activeOrders = (clone $openOrdersQuery)->count();

        $pendingItems = OrderDetail::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('branch_id', $branchId)
            ->where('status', 'pending')
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['closed', 'canceled']))
            ->count();

        $openOrders = (clone $openOrdersQuery)
            ->with('table:id,name')
            ->withCount(['details as pending_items' => fn ($query) => $query->where('status', 'pending')])
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'table_name' => $order->table?->name,
                'status' => $order->status,
                'total' => (float) $order->total,
                'pending_items' => (int) $order->pending_items,
                'created_at' => $order->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'active_orders' => $activeOrders,
                'orders_today' => $ordersToday,
                'pending_items' => $pendingItems,
                'sales_today' => round($salesToday, 2),
                'open_orders' => $openOrders,
            ],
        ]);
    }
}
