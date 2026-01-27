<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Table;
use App\Models\Order;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Vetnas Hoy (Sales Today)
        $salesToday = Order::whereDate('created_at', today())
            ->where('status', 'closed') // Assuming only closed orders count for sales
            ->sum('total');

        // 2. Pedidos (Orders Count)
        $ordersCount = Order::whereDate('created_at', today())->count();

        // 3. Mesas Ocupadas (Occupied / Total)
        $activeTables = Table::where('is_active', true)->count();
        $occupiedTables = Table::where('is_active', true)->where('status', 'occupied')->count();
        $tablesStat = "{$occupiedTables}/{$activeTables}";

        // 4. Ticket Promedio (Average Ticket)
        $paidOrdersCount = Order::whereDate('created_at', today())->where('status', 'closed')->count();
        $avgTicket = $paidOrdersCount > 0 ? $salesToday / $paidOrdersCount : 0;

        // 5. Ventas Mensuales (Chart Data - Current Year)
        $salesChart = Order::selectRaw('MONTH(created_at) as month, sum(total) as total')
            ->where('status', 'closed')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Fill 12 months
        $months = [];
        $salesData = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date('M', mktime(0, 0, 0, $i, 1));
            $salesData[] = $salesChart[$i] ?? 0;
        }

        // 6. Últimos Pedidos
        $latestOrders = Order::with('table')->latest()->take(5)->get();

        // 7. Productos Más Vendidos
        $topProducts = \App\Models\OrderDetail::select('product_name', \Illuminate\Support\Facades\DB::raw('sum(quantity) as qty'), \Illuminate\Support\Facades\DB::raw('sum(price * quantity) as total'))
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->take(5)
            ->get();

        // 8. Resumen Comandas (Total orders value maybe?)
        $totalOrdersValue = Order::sum('total'); // Or filter by timeframe

        return view('dashboard', compact(
            'salesToday', 
            'ordersCount', 
            'tablesStat', 
            'occupiedTables',
            'activeTables',
            'avgTicket', 
            'months', 
            'salesData', 
            'latestOrders', 
            'topProducts',
            'totalOrdersValue'
        ));
    }
}
