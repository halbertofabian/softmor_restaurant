<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PreparationArea;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function index()
    {
        $areas = PreparationArea::where('status', true)->orderBy('sort_order', 'asc')->get();
        return view('kitchen.index', compact('areas'));
    }

    public function monitor(PreparationArea $area)
    {
        // Fetch Open Orders that have items for this area AND are SENT to kitchen
        $orders = Order::where('status', 'open')
            ->whereHas('details', function($q) use ($area) {
                $q->where('preparation_area_id', $area->id)
                  ->where('status', 'sent');
            })
            ->with(['details' => function($q) use ($area) {
                $q->where('preparation_area_id', $area->id)
                  ->where('status', 'sent');
            }, 'table'])
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Get Settings (Printer & Bridge URL)
        $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
                        ->whereIn('key', ['ticket_printer_name', 'local_bridge_url'])
                        ->pluck('value', 'key');
        
        $defaultPrinter = $settings['ticket_printer_name'] ?? 'POS-80';
        $localBridgeUrl = $settings['local_bridge_url'] ?? 'http://localhost:8000/api/printer/raw';

        return view('kitchen.monitor', compact('area', 'orders', 'defaultPrinter', 'localBridgeUrl'));
    }
    public function checkNewItems(PreparationArea $area)
    {
        // 1. Find items for this area, that are SENT but NOT YET PRINTED
        // ONLY from OPEN orders and from the last 25 minutes (safety filter)
        $newItems = \App\Models\OrderDetail::where('preparation_area_id', $area->id)
            ->where('status', 'sent')
            ->where('is_printed', false)
            ->where('created_at', '>=', now()->subMinutes(25)) // Safety: Only last 25 minutes
            ->whereHas('order', function($q) {
                $q->where('status', 'open'); // Only from open orders
            })
            ->with(['order.table', 'order.user', 'product']) // Eager load relationships
            ->orderBy('created_at', 'asc')
            ->get();

        // DEBUG: Log query details
        \Log::info("Kitchen Check - Area: {$area->name} (ID: {$area->id})");
        \Log::info("Found items count: " . $newItems->count());
        
        // Check if there are ANY items for this area regardless of printed status
        $allItemsForArea = \App\Models\OrderDetail::where('preparation_area_id', $area->id)
            ->where('status', 'sent')
            ->count();
        \Log::info("Total sent items for area (including printed): " . $allItemsForArea);

        if ($newItems->isEmpty()) {
            return response()->json([
                'status' => 'empty',
                'debug' => [
                    'area_id' => $area->id,
                    'total_sent_items' => $allItemsForArea
                ]
            ]);
        }

        // 2. Group by Order to print per ticket
        $orders = $newItems->groupBy('order_id')->map(function ($items, $orderId) {
            $firstItem = $items->first();
            return [
                'order_id' => $orderId,
                'table_name' => $firstItem->order->table->name ?? '?',
                'waiter_name' => $firstItem->order->user->name ?? 'Mesero',
                'created_at' => $firstItem->created_at->format('H:i'),
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'notes' => $item->notes,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'orders' => $orders
        ]);
    }

    public function markAsPrinted(Request $request)
    {
        $request->validate([
            'item_ids' => 'required|array'
        ]);

        \App\Models\OrderDetail::whereIn('id', $request->item_ids)
            ->update(['is_printed' => true]);

        return response()->json(['status' => 'success']);
    }
}
