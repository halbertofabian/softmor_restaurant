<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductFlavor;
use App\Models\Table;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        return view('orders.index');
    }

    public function datatable()
    {
        $orders = Order::where('status', '!=', 'closed')->with('table')->get();

        $data = $orders->map(function ($order) {
            $statusBadge = match ($order->status) {
                'open' => '<span class="badge bg-label-primary">Abierta</span>',
                'sent' => '<span class="badge bg-label-warning">Enviada</span>',
                'in_preparation' => '<span class="badge bg-label-info">En Prep.</span>',
                'closed' => '<span class="badge bg-label-success">Cerrada</span>',
                'canceled' => '<span class="badge bg-label-danger">Cancelada</span>',
                default => '',
            };

            $actions = '<a href="' . route('orders.show', $order) . '" class="btn btn-sm btn-icon btn-text-primary"><i class="ti tabler-eye"></i></a>';

            if (!auth()->user()->hasRole('mesero')) {
                $actions .= '<a href="' . route('pos.checkout', $order) . '" class="btn btn-sm btn-icon btn-text-success"><i class="ti tabler-cash"></i></a>';
            }

            return [
                'id' => (string) $order->id,
                'table' => e($order->table->name ?? 'N/A'),
                'status' => $statusBadge,
                'total' => '$' . number_format($order->total, 2),
                'created_at' => $order->created_at->format('d/m H:i'),
                'actions' => $actions,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function create()
    {
        // This might not be used if we start order from Table list
    }

    public function store(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
        ]);

        $table = Table::find($request->table_id);

        if ($table->status != 'occupied') {
            return redirect()->back()->with('error', 'La mesa debe estar ocupada para abrir comanda.');
        }

        $activeOrder = Order::where('table_id', $table->id)
            ->where('status', '!=', 'closed')
            ->where('status', '!=', 'canceled')
            ->first();

        if ($activeOrder) {
            return redirect()->route('orders.show', $activeOrder);
        }

        $order = Order::create([
            'table_id' => $table->id,
            'user_id' => auth()->id(),
            'status' => 'open',
        ]);

        return redirect()->route('orders.show', $order);
    }

    public function show(Order $order)
    {
        if ($order->status === 'closed') {
            return redirect()->route('orders.index')->with('warning', 'La orden ya está cerrada.');
        }

        $order->load(['details', 'table']);
        $categories = Category::where('status', true)->get();
        $products = Product::where('status', true)->with(['flavors' => function ($query) {
            $query->where('is_active', true);
        }, 'comboItems.componentProduct.flavors'])->get();

        $productFlavorsMap = $products->mapWithKeys(function ($product) {
            return [
                $product->id => $product->flavors->map(function ($flavor) {
                    return [
                        'id' => $flavor->id,
                        'name' => $flavor->name,
                        'additional_price' => (float) $flavor->additional_price,
                    ];
                })->values(),
            ];
        });

        $productCombosMap = $products->mapWithKeys(function ($product) {
            return [
                $product->id => $product->comboItems->map(function ($item) {
                    return [
                        'combo_item_id' => $item->id,
                        'component_product_id' => $item->component_product_id,
                        'component_name' => $item->componentProduct?->name,
                        'quantity' => (int) $item->quantity,
                        'default_flavor_id' => $item->default_flavor_id,
                        'flavors' => ($item->componentProduct?->flavors ?? collect())->where('is_active', true)->map(function ($flavor) {
                            return [
                                'id' => $flavor->id,
                                'name' => $flavor->name,
                                'additional_price' => (float) $flavor->additional_price,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return view('orders.pos', compact('order', 'categories', 'products', 'productFlavorsMap', 'productCombosMap'));
    }

    public function addItem(Request $request, Order $order)
    {
        if ($order->status == 'closed' || $order->status == 'canceled') {
            return redirect()->back()->with('error', 'La comanda está cerrada.');
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_flavor_id' => 'nullable|exists:product_flavors,id',
            'combo_components' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $product = Product::find($request->product_id);
        $flavor = null;

        if ($product->type === 'combo') {
            $comboItems = $product->comboItems()->with(['componentProduct', 'componentProduct.flavors'])->get();
            if ($comboItems->isEmpty()) {
                return redirect()->back()->with('error', 'El combo no tiene componentes configurados.');
            }

            $selected = [];
            if ($request->filled('combo_components')) {
                $selected = json_decode($request->combo_components, true) ?: [];
            }

            $comboDescriptionParts = [];
            $componentLines = [];

            foreach ($comboItems as $comboItem) {
                $component = $comboItem->componentProduct;
                if (!$component) {
                    continue;
                }

                $unitSelections = [];
                foreach ($selected as $itemSel) {
                    $sameComboItem = (int) ($itemSel['combo_item_id'] ?? 0) === (int) $comboItem->id;
                    $fallbackByProduct = (int) ($itemSel['component_product_id'] ?? 0) === (int) $comboItem->component_product_id;
                    if ($sameComboItem || $fallbackByProduct) {
                        $unitSelections[] = $itemSel;
                    }
                }

                $unitCount = max(1, (int) $comboItem->quantity);
                for ($unitIndex = 0; $unitIndex < $unitCount; $unitIndex++) {
                    $selectedFlavorId = null;
                    if (!empty($unitSelections)) {
                        $match = collect($unitSelections)->first(function ($sel) use ($unitIndex) {
                            return (int) ($sel['unit_index'] ?? 0) === $unitIndex;
                        }) ?? $unitSelections[0];
                        $selectedFlavorId = !empty($match['product_flavor_id']) ? (int) $match['product_flavor_id'] : null;
                    }

                    $componentFlavor = null;
                    if ($selectedFlavorId) {
                        $componentFlavor = ProductFlavor::where('id', $selectedFlavorId)
                            ->where('product_id', $component->id)
                            ->where('is_active', true)
                            ->first();
                    } elseif ($comboItem->default_flavor_id) {
                        $componentFlavor = ProductFlavor::where('id', $comboItem->default_flavor_id)
                            ->where('product_id', $component->id)
                            ->where('is_active', true)
                            ->first();
                    }

                    $flavorDelta = $componentFlavor ? (float) $componentFlavor->additional_price : 0;
                    $unitPrice = (float) $component->price + $flavorDelta;
                    $lineQty = (int) $request->quantity;

                    $comboDescriptionParts[] = $component->name . ($componentFlavor ? ' (' . $componentFlavor->name . ')' : '');

                    $componentLines[] = [
                        'order_id' => $order->id,
                        'product_id' => $component->id,
                        'product_flavor_id' => $componentFlavor?->id,
                        'product_name' => $component->name,
                        'flavor_name' => $componentFlavor?->name,
                        'price' => 0,
                        'flavor_price_delta' => $flavorDelta,
                        'quantity' => $lineQty,
                        'preparation_area_id' => $component->preparation_area_id,
                        'status' => 'pending',
                        'is_combo_component' => true,
                    ];
                }
            }

            $notesParts = [];
            if ($request->notes) {
                $notesParts[] = $request->notes;
            }
            $notesParts[] = 'Combo: ' . $product->name;
            if (!empty($comboDescriptionParts)) {
                $notesParts[] = 'Incluye: ' . implode(', ', $comboDescriptionParts);
            }
            $comboNotes = implode(' | ', $notesParts);

            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => (int) $request->quantity,
                'preparation_area_id' => $product->preparation_area_id,
                'notes' => $comboNotes,
                'status' => 'pending',
                'is_combo_component' => false,
            ]);

            foreach ($componentLines as $line) {
                $line['notes'] = $comboNotes;
                OrderDetail::create($line);
            }

            $order->calculateTotal();

            if ($request->has('from_checkout')) {
                return redirect()->route('pos.checkout', $order);
            }

            if ($request->has('is_mobile')) {
                return redirect()->route('orders.mobile', $order);
            }

            return redirect()->route('orders.show', $order);
        }

        if ($request->filled('product_flavor_id')) {
            $flavor = ProductFlavor::where('id', $request->product_flavor_id)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (!$flavor) {
                return redirect()->back()->with('error', 'El sabor seleccionado no es válido para este producto.');
            }
        }

        $flavorDelta = $flavor ? (float) $flavor->additional_price : 0;
        $unitPrice = (float) $product->price + $flavorDelta;

        OrderDetail::create([
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
            'status' => 'pending',
        ]);

        $order->calculateTotal();

        if ($request->has('from_checkout')) {
            return redirect()->route('pos.checkout', $order);
        }

        if ($request->has('is_mobile')) {
            return redirect()->route('orders.mobile', $order);
        }

        return redirect()->route('orders.show', $order);
    }

    public function removeItem(Request $request, Order $order, OrderDetail $detail)
    {
        if ($order->status == 'closed' || $order->status == 'canceled') {
            return redirect()->back()->with('error', 'La comanda está cerrada.');
        }

        $detail->delete();
        $order->calculateTotal();

        if ($request->has('from_checkout')) {
            return redirect()->route('pos.checkout', $order);
        }

        if ($request->has('is_mobile')) {
            return redirect()->route('orders.mobile', $order);
        }

        return redirect()->route('orders.show', $order);
    }

    public function close(Order $order)
    {
        if ($order->status == 'closed') {
            return redirect()->back()->with('error', 'Ya está cerrada.');
        }

        foreach ($order->details as $detail) {
            $product = $detail->product;
            if ($product->controls_inventory) {
                $newStock = $product->stock - $detail->quantity;

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'type' => 'sale',
                    'quantity' => -$detail->quantity,
                    'previous_stock' => $product->stock,
                    'new_stock' => $newStock,
                    'notes' => "Venta Comanda #{$order->id}",
                    'user_id' => auth()->id(),
                ]);

                $product->update(['stock' => $newStock]);
            }
        }

        $order->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $order->table->update(['status' => 'free']);

        return redirect()->route('tables.index')->with('success', 'Comanda cerrada y mesa liberada.');
    }

    public function sendToKitchen(Order $order)
    {
        $order->details()->where('status', 'pending')->update([
            'status' => 'sent',
            'updated_at' => now(),
        ]);
        return redirect()->route('orders.mobile', $order)->with('success', '¡Pedido enviado a cocina exitosamente!');
    }

    public function mobile(Order $order)
    {
        $order->load(['details', 'table']);
        $categories = Category::where('status', true)->get();
        $products = Product::where('status', true)->with(['flavors' => function ($query) {
            $query->where('is_active', true);
        }, 'comboItems.componentProduct.flavors'])->get();

        $productFlavorsMap = $products->mapWithKeys(function ($product) {
            return [
                $product->id => $product->flavors->map(function ($flavor) {
                    return [
                        'id' => $flavor->id,
                        'name' => $flavor->name,
                        'additional_price' => (float) $flavor->additional_price,
                    ];
                })->values(),
            ];
        });

        $productCombosMap = $products->mapWithKeys(function ($product) {
            return [
                $product->id => $product->comboItems->map(function ($item) {
                    return [
                        'combo_item_id' => $item->id,
                        'component_product_id' => $item->component_product_id,
                        'component_name' => $item->componentProduct?->name,
                        'quantity' => (int) $item->quantity,
                        'default_flavor_id' => $item->default_flavor_id,
                        'flavors' => ($item->componentProduct?->flavors ?? collect())->where('is_active', true)->map(function ($flavor) {
                            return [
                                'id' => $flavor->id,
                                'name' => $flavor->name,
                                'additional_price' => (float) $flavor->additional_price,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return view('orders.mobile', compact('order', 'categories', 'products', 'productFlavorsMap', 'productCombosMap'));
    }
}
