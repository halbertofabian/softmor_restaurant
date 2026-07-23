<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    private function applyFilters(Request $request)
    {
        $query = Payment::with(['order', 'order.branch', 'order.user'])->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        } else {
            $request->merge(['start_date' => now()->format('Y-m-d')]);
            $query->whereDate('created_at', '>=', now()->format('Y-m-d'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        } else {
            $request->merge(['end_date' => now()->format('Y-m-d')]);
        }

        if ($request->filled('method') && $request->method != 'all') {
            $query->where('method', $request->method);
        }

        if ($request->filled('waiter_id')) {
            $query->whereHas('order', function ($orderQuery) use ($request) {
                $orderQuery->where('user_id', $request->waiter_id);
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->applyFilters($request);
        $totalAmount = (clone $query)->sum('amount');
        $waiters = User::where('tenant_id', auth()->user()->tenant_id)
            ->whereHas('branches', function ($branchQuery) {
                $branchQuery->where('branches.id', session('branch_id'));
            })
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'mesero');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reports.sales.index', compact('totalAmount', 'waiters'));
    }

    public function datatable(Request $request)
    {
        $query = $this->applyFilters($request);
        $totalAmount = (clone $query)->sum('amount');
        $sales = $query->get();

        $waiterTotals = $sales->groupBy(function ($payment) {
            return $payment->order?->user_id ?? 'unassigned';
        })->map(function ($payments) {
            $waiter = $payments->first()->order?->user;

            return [
                'name' => $waiter?->name ?? 'Sin asignar',
                'orders' => $payments->pluck('order_id')->unique()->count(),
                'amount' => number_format($payments->sum('amount'), 2, '.', ''),
            ];
        })->sortByDesc(function ($waiter) {
            return (float) $waiter['amount'];
        })->values();

        $data = $sales->map(function ($payment) {
            $folio = '<span class="fw-bold text-primary">#' . e($payment->order_id) . '</span>';
            $date = '<div class="d-flex flex-column"><span class="fw-medium">' . $payment->created_at->format('d/m/Y') . '</span><small class="text-muted">' . $payment->created_at->format('g:i A') . '</small></div>';
            $client = ($payment->order && $payment->order->name)
                ? e(\Illuminate\Support\Str::limit($payment->order->name, 20))
                : '<span class="text-muted fst-italic">Público General</span>';

            $waiter = $payment->order?->user
                ? e($payment->order->user->name)
                : '<span class="text-muted fst-italic">Sin asignar</span>';

            $method = match ($payment->method) {
                'cash' => '<span class="badge bg-label-success"><i class="ti tabler-cash me-1"></i> Efectivo</span>',
                'card' => '<span class="badge bg-label-info"><i class="ti tabler-credit-card me-1"></i> Tarjeta</span>',
                'transfer' => '<span class="badge bg-label-primary"><i class="ti tabler-building-bank me-1"></i> Transf.</span>',
                default => '<span class="badge bg-label-secondary">' . e(ucfirst($payment->method)) . '</span>',
            };

            $actions = '';
            if ($payment->order) {
                $actions .= '<a href="' . route('pos.ticket', $payment->order) . '" target="_blank" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="Reimprimir Ticket"><i class="ti tabler-printer"></i></a>';
                $actions .= '<a href="' . route('orders.show', $payment->order) . '" class="btn btn-sm btn-icon btn-text-primary rounded-pill" title="Ver Comanda"><i class="ti tabler-eye"></i></a>';
            }

            return [
                'folio' => $folio,
                'date' => $date,
                'client' => $client,
                'waiter' => $waiter,
                'method' => $method,
                'reference' => e($payment->reference ?? '-'),
                'amount' => '$' . number_format($payment->amount, 2),
                'actions' => $actions,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'totalAmount' => number_format($totalAmount, 2, '.', ''),
            'waiterTotals' => $waiterTotals,
        ]);
    }
}
