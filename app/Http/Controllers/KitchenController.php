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

        return view('kitchen.monitor', compact('area', 'orders'));
    }
}
