<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PreparationArea;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrintJobService
{
    public function __construct(private PrintJobSignal $signal) {}

    public function enqueueKitchen(Order $order, Collection $details, array $settings): bool
    {
        $agent = PrintAgent::withoutGlobalScopes()
            ->where('tenant_id', $order->tenant_id)
            ->where('branch_id', $order->branch_id)
            ->first();

        if (! $agent) {
            return false;
        }

        $defaultPrinter = $settings['ticket_printer_name'] ?? 'POS-80';
        $order->loadMissing(['table', 'user']);
        $detailsByArea = $details->groupBy('preparation_area_id');
        $areas = PreparationArea::withoutGlobalScopes()
            ->whereIn('id', $detailsByArea->keys()->filter()->values())
            ->get()
            ->keyBy('id');
        $queued = false;

        foreach ($detailsByArea as $areaId => $items) {
            $area = $areas->get($areaId);
            if (!$areaId || !$area || !$area->print_ticket) {
                continue;
            }

            PrintJob::create([
                'tenant_id' => $order->tenant_id,
                'branch_id' => $order->branch_id,
                'payload' => [
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
                ],
                'order_detail_ids' => $items->pluck('id')->values()->all(),
            ]);
            $queued = true;
        }

        if ($queued) {
            DB::afterCommit(function () use ($agent) {
                $this->signal->notify($agent);
            });
        }

        return $queued;
    }
}
