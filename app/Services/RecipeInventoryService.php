<?php

namespace App\Services;

use App\Exceptions\InsufficientRecipeInventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemMovement;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecipeInventoryService
{
    public function shortages(Order $order): array
    {
        $requirements = $this->requirements($order->details()->where('status', 'pending')->get());
        if ($requirements->isEmpty()) {
            return [];
        }

        return InventoryItem::whereIn('id', $requirements->keys())->get()
            ->filter(fn ($item) => (float) $item->stock < (float) $requirements[$item->id])
            ->map(fn ($item) => [
                'inventory_item_id' => $item->id,
                'name' => $item->name,
                'unit' => $item->base_unit,
                'available' => (float) $item->stock,
                'required' => (float) $requirements[$item->id],
                'resulting' => (float) $item->stock - (float) $requirements[$item->id],
            ])->values()->all();
    }

    public function sendPending(Order $order, ?int $userId, bool $allowNegative = false): Collection
    {
        return DB::transaction(function () use ($order, $userId, $allowNegative) {
            $details = OrderDetail::where('order_id', $order->id)
                ->where('status', 'pending')->lockForUpdate()->get();

            if ($details->isEmpty()) {
                return $details;
            }

            $details->load('product.recipeItems');
            $itemIds = $details->flatMap(fn ($detail) => $detail->product?->recipeItems->pluck('inventory_item_id') ?? collect())
                ->unique()->sort()->values();
            $items = InventoryItem::whereIn('id', $itemIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $requirements = $this->requirements($details);
            $shortages = $items->filter(fn ($item) => (float) $item->stock < (float) ($requirements[$item->id] ?? 0))
                ->map(fn ($item) => [
                    'inventory_item_id' => $item->id,
                    'name' => $item->name,
                    'unit' => $item->base_unit,
                    'available' => (float) $item->stock,
                    'required' => (float) $requirements[$item->id],
                    'resulting' => (float) $item->stock - (float) $requirements[$item->id],
                ])->values()->all();
            if ($shortages && !$allowNegative) {
                throw new InsufficientRecipeInventory($shortages);
            }

            foreach ($details as $detail) {
                if ($detail->product?->type === 'combo') {
                    continue;
                }

                foreach ($detail->product?->recipeItems ?? [] as $recipeItem) {
                    if (InventoryItemMovement::where('order_detail_id', $detail->id)
                        ->where('inventory_item_id', $recipeItem->inventory_item_id)->where('type', 'consumption')->exists()) {
                        continue;
                    }

                    $item = $items->get($recipeItem->inventory_item_id);
                    if (!$item) {
                        continue;
                    }

                    $quantity = round((float) $recipeItem->quantity * $detail->quantity, 3);
                    $previous = (float) $item->stock;
                    $new = round($previous - $quantity, 3);
                    $item->update(['stock' => $new]);
                    InventoryItemMovement::create([
                        'inventory_item_id' => $item->id,
                        'order_detail_id' => $detail->id,
                        'type' => 'consumption',
                        'quantity' => -$quantity,
                        'previous_stock' => $previous,
                        'new_stock' => $new,
                        'notes' => "Consumo comanda #{$order->id}",
                        'user_id' => $userId,
                        'tenant_id' => $order->tenant_id,
                        'branch_id' => $order->branch_id,
                    ]);
                }
            }

            OrderDetail::whereIn('id', $details->pluck('id'))->update(['status' => 'sent', 'updated_at' => now()]);
            return $details;
        });
    }

    public function cancel(OrderDetail $detail, ?int $userId): void
    {
        DB::transaction(function () use ($detail, $userId) {
            $locked = OrderDetail::whereKey($detail->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'canceled') {
                return;
            }
            if ($locked->status !== 'sent') {
                abort(422, 'Solo se pueden cancelar productos enviados.');
            }

            $movements = InventoryItemMovement::where('order_detail_id', $locked->id)
                ->where('type', 'consumption')->orderBy('inventory_item_id')->get();
            $items = InventoryItem::whereIn('id', $movements->pluck('inventory_item_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($movements as $movement) {
                if (InventoryItemMovement::where('reversed_movement_id', $movement->id)->exists()) {
                    continue;
                }
                $item = $items->get($movement->inventory_item_id);
                $quantity = abs((float) $movement->quantity);
                $previous = (float) $item->stock;
                $new = round($previous + $quantity, 3);
                $item->update(['stock' => $new]);
                InventoryItemMovement::create([
                    'inventory_item_id' => $item->id,
                    'order_detail_id' => $locked->id,
                    'reversed_movement_id' => $movement->id,
                    'type' => 'return',
                    'quantity' => $quantity,
                    'previous_stock' => $previous,
                    'new_stock' => $new,
                    'notes' => "Cancelación comanda #{$locked->order_id}",
                    'user_id' => $userId,
                    'tenant_id' => $locked->tenant_id,
                    'branch_id' => $locked->branch_id,
                ]);
            }
            $locked->update(['status' => 'canceled']);
        });
    }

    private function requirements(Collection $details): Collection
    {
        $details->load('product.recipeItems');
        $requirements = collect();
        foreach ($details as $detail) {
            if ($detail->product?->type === 'combo') {
                continue;
            }
            foreach ($detail->product?->recipeItems ?? [] as $recipeItem) {
                $required = (float) $recipeItem->quantity * $detail->quantity;
                $requirements[$recipeItem->inventory_item_id] = ($requirements[$recipeItem->inventory_item_id] ?? 0) + $required;
            }
        }
        return $requirements;
    }
}
