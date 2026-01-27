<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['order', 'order.branch', 'order.user'])
            ->latest();

        // Filter by Date Range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        } else {
            // Default to today
            $request->merge(['start_date' => now()->format('Y-m-d')]);
            $query->whereDate('created_at', '>=', now()->format('Y-m-d'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        } else {
            $request->merge(['end_date' => now()->format('Y-m-d')]);
        }

        // Filter by Payment Method
        if ($request->filled('method') && $request->method != 'all') {
            $query->where('method', $request->method);
        }

        $sales = $query->paginate(20)->withQueryString();

        // Calculate Totals for the filtered view
        // Clone query for totals to avoid pagination limits
        $totalsQuery = clone $query;
        // We need to use base query builder for aggregate if we used paginate on eloquent builder? 
        // No, we can just use the builder instance before paginate.
        // Actually paginate() returns results, so $query is still the builder... wait, paginate executes.
        // So we need to clone BEFORE paginate.
        
        // Let's refactor to be safe
        $totalAmount = Payment::where(function($q) use ($request) {
             if ($request->filled('start_date')) $q->whereDate('created_at', '>=', $request->start_date);
             if ($request->filled('end_date')) $q->whereDate('created_at', '<=', $request->end_date);
             if ($request->filled('method') && $request->method != 'all') $q->where('method', $request->method);
        })->sum('amount');


        return view('reports.sales.index', compact('sales', 'totalAmount'));
    }
}
