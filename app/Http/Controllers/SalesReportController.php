<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
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

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->applyFilters($request);
        $totalAmount = (clone $query)->sum('amount');
        return view('reports.sales.index', compact('totalAmount'));
    }

    public function datatable(Request $request)
    {
        $query = $this->applyFilters($request);
        $totalAmount = (clone $query)->sum('amount');
        $sales = $query->get();

        $data = $sales->map(function ($payment) {
            $folio = '<span class="fw-bold text-primary">#' . e($payment->order_id) . '</span>';
            $date = '<div class="d-flex flex-column"><span class="fw-medium">' . $payment->created_at->format('d/m/Y') . '</span><small class="text-muted">' . $payment->created_at->format('g:i A') . '</small></div>';
            $client = ($payment->order && $payment->order->name)
                ? e(\Illuminate\Support\Str::limit($payment->order->name, 20))
                : '<span class="text-muted fst-italic">Público General</span>';

            $method = match ($payment->method) {
                'cash' => '<span class="badge bg-label-success"><i class="ti tabler-cash me-1"></i> Efectivo</span>',
                'card' => '<span class="badge bg-label-info"><i class="ti tabler-credit-card me-1"></i> Tarjeta</span>',
                'transfer' => '<span class="badge bg-label-primary"><i class="ti tabler-building-bank me-1"></i> Transf.</span>',
                default => '<span class="badge bg-label-secondary">' . e(ucfirst($payment->method)) . '</span>',
            };

            $actions = '';
            if ($payment->order) {
                $actions .= '<a href="' . route('orders.pre-check.print-direct', ['order' => $payment->order, 'redirect' => route('reports.sales.index')]) . '" target="_blank" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="Reimprimir Ticket"><i class="ti tabler-printer"></i></a>';
                $actions .= '<a href="' . route('orders.show', $payment->order) . '" class="btn btn-sm btn-icon btn-text-primary rounded-pill" title="Ver Comanda"><i class="ti tabler-eye"></i></a>';
            }

            return [
                'folio' => $folio,
                'date' => $date,
                'client' => $client,
                'method' => $method,
                'reference' => e($payment->reference ?? '-'),
                'amount' => '$' . number_format($payment->amount, 2),
                'actions' => $actions,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'totalAmount' => number_format($totalAmount, 2, '.', ''),
        ]);
    }
}
