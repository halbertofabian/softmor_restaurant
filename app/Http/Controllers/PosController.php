<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Payment;
use App\Models\Category;
use App\Models\Product;
use App\Services\PrintJobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;


class PosController extends Controller
{
    public function show(Order $order)
    {
        // Ensure order belongs to current tenant/branch via Global Scope (already applied)
        
        if ($order->status === 'closed') {
            return redirect()->route('orders.index')->with('warning', 'La orden ya está cerrada.');
        }

        $categories = Category::all();
        $products = Product::with([
            'category',
            'flavors' => function ($query) {
                $query->where('is_active', true);
            },
            'comboItems.componentProduct.flavors',
        ])->where('status', true)->get();

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

        return view('pos.checkout', compact('order', 'categories', 'products', 'productFlavorsMap', 'productCombosMap'));
    }

    public function pay(Request $request, Order $order)
    {
        if ($order->details()->count() === 0) {
            return back()->with('error', 'No se puede cobrar una orden sin productos.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string',
            'reference' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // 0. Check Active Register
            $activeRegister = \App\Models\CashRegister::where('branch_id', session('branch_id'))
                ->where('user_id', auth()->id())
                ->where('status', 'open')
                ->first();

            // Create Payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'cash_register_id' => $activeRegister ? $activeRegister->id : null,
                'amount' => $order->total, 
                'method' => $request->method,
                'reference' => $request->reference,
            ]);

            // 2. Deduct Inventory (Logic from OrderController)
            foreach ($order->details as $detail) {
                $product = $detail->product;
                if ($product && $product->controls_inventory) {
                    $newStock = $product->stock - $detail->quantity;
                    
                    // Record movement
                    \App\Models\InventoryMovement::create([
                        'product_id' => $product->id,
                        'type' => 'sale',
                        'quantity' => -$detail->quantity,
                        'previous_stock' => $product->stock,
                        'new_stock' => $newStock,
                        'notes' => "Venta POS #{$order->id}",
                        'user_id' => auth()->id(),
                    ]);

                    $product->update(['stock' => $newStock]);
                }
            }

            // Update Order
            $order->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
            
            // 3. Free Table
            if($order->table) {
                $order->table->update(['status' => 'free']);
            }

            DB::commit();

            // Option 2: Redirect to intermediate view for JS-based local printing bridge
            // We prepare the data to send to the local agent
            $printData = [
                'header' => 'Gestional Food',
                'branch_name' => $order->branch->name ?? 'Principal',
                'ticket_id' => $order->id,
                'table_name' => $order->table->name ?? '?',
                'waiter_name' => $order->user->name ?? 'Mesero',
                'date' => now()->format('d/m/Y H:i A'),
                'total' => $order->total,
                'items' => $order->details->map(function($detail) {
                    return [
                        'quantity' => $detail->quantity,
                        'name' => $detail->product->name ?? 'Producto',
                        'price' => $detail->price
                    ];
                })
            ];

            $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
                ->pluck('value', 'key')->toArray();

            // Add the printer name configured in the cloud to the payload sent to local server
            $printData['printer_name'] = $settings['ticket_printer_name'] ?? 'POS-80';

            $redirectUrl = route('tables.index');
            return view('pos.print-bridge', compact('order', 'printData', 'settings', 'redirectUrl'));


        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar el pago: ' . $e->getMessage());
        }
    }

    public function sendToKitchen(Order $order)
    {
        // Get pending items
        $pendingDetails = $order->details()->where('status', 'pending')->get();
        
        if ($pendingDetails->isEmpty()) {
            return back()->with('warning', 'No hay items pendientes para enviar.');
        }
        
        // Update status to 'sent'
        foreach ($pendingDetails as $detail) {
            $detail->update(['status' => 'sent']);
        }

        // Direct local print by preparation area (no monitor tab required)
        try {
            $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
                ->pluck('value', 'key')
                ->toArray();

            $bridgeUrl = $settings['local_bridge_url'] ?? 'http://localhost:8000/api/printer/raw';
            $defaultPrinter = $settings['ticket_printer_name'] ?? 'POS-80';

            if (app(PrintJobService::class)->enqueueKitchen($order, $pendingDetails, $settings)) {
                return back()->with('success', count($pendingDetails) . ' items enviados a cocina.');
            }

            $order->loadMissing(['table', 'user']);

            $detailsByArea = $pendingDetails->groupBy('preparation_area_id');
            $areas = \App\Models\PreparationArea::whereIn('id', $detailsByArea->keys()->filter()->values())
                ->get()
                ->keyBy('id');

            foreach ($detailsByArea as $areaId => $items) {
                if (!$areaId) {
                    continue;
                }

                $area = $areas->get($areaId);
                if (!$area || !$area->print_ticket) {
                    continue;
                }

                $payload = [
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
                ];

                $response = Http::timeout(8)->post($bridgeUrl, $payload);

                if ($response->ok()) {
                    \App\Models\OrderDetail::whereIn('id', $items->pluck('id')->all())
                        ->update(['is_printed' => true]);
                }
            }
        } catch (\Throwable $e) {
            // Keep order flow running even if local printer is unavailable
        }
        
        return back()->with('success', count($pendingDetails) . ' items enviados a cocina.');
    }

    public function ticket(Order $order)
    {
        if ($order->details()->count() === 0) {
            return back()->with('error', 'No se puede imprimir una cuenta sin productos.');
        }

        $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
            ->pluck('value', 'key')->toArray();
            
        return view('pos.ticket', compact('order', 'settings'));
    }

    public function preCheck(Order $order)
    {
        if ($order->details()->count() === 0) {
            return back()->with('error', 'No se puede imprimir una cuenta sin productos.');
        }

        $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
            ->pluck('value', 'key')->toArray();

        return view('pos.ticket', ['order' => $order, 'isPreCheck' => true, 'settings' => $settings]);
    }

    public function preCheckPrintDirect(Order $order)
    {
        if ($order->details()->count() === 0) {
            return back()->with('error', 'No se puede imprimir una cuenta sin productos.');
        }

        $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
            ->pluck('value', 'key')->toArray();

        $tip1 = (float) ($settings['ticket_tip_1_percent'] ?? 10);
        $tip2 = (float) ($settings['ticket_tip_2_percent'] ?? 12);
        $tip3 = (float) ($settings['ticket_tip_3_percent'] ?? 15);
        $tip4 = (float) ($settings['ticket_tip_4_percent'] ?? 18);
        $tipsEnabled = !empty($settings['ticket_tips_enabled']);

        $printData = [
            'type' => 'pre_check',
            'header' => $settings['ticket_pre_check_header'] ?? '*** CUENTA DE CONSUMO ***',
            'pre_check_disclaimer' => $settings['ticket_pre_check_disclaimer'] ?? 'No válido como comprobante fiscal',
            'branch_name' => $order->branch->name ?? 'Principal',
            'ticket_id' => $order->id,
            'date' => now()->format('d/m/Y H:i A'),
            'total' => $order->total,
            'items' => $order->details->where('is_combo_component', false)->values()->map(function ($detail) {
                return [
                    'quantity' => $detail->quantity,
                    'name' => $detail->product->name ?? 'Producto',
                    'price' => $detail->price,
                ];
            })->all(),
            'tips_enabled' => $tipsEnabled,
            'tip_suggestions' => [
                ['percent' => $tip1, 'amount' => round($order->total * ($tip1 / 100), 2)],
                ['percent' => $tip2, 'amount' => round($order->total * ($tip2 / 100), 2)],
                ['percent' => $tip3, 'amount' => round($order->total * ($tip3 / 100), 2)],
                ['percent' => $tip4, 'amount' => round($order->total * ($tip4 / 100), 2)],
            ],
            'printer_name' => $settings['ticket_printer_name'] ?? 'POS-80',
        ];

        $redirectUrl = route('pos.checkout', $order);

        return view('pos.print-bridge', compact('order', 'printData', 'settings', 'redirectUrl'));
    }
    public function ticketPdf(Order $order)
    {
        $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
            ->pluck('value', 'key')->toArray();

        // 80mm is approx 227 points.
        // We set a long height to simulate a "roll" or let it page break if needed.
        // But for thermal, custom paper size is key.
        $width = 227; 
        $height = 1000; // Arbitrary long height, or auto calc if we could.

        $pdf = Pdf::loadView('pos.ticket', compact('order', 'settings') + ['isPdf' => true]);
        $pdf->setPaper([0, 0, $width, $height], 'portrait');

        return $pdf->stream('ticket-' . $order->id . '.pdf');
    }

    public function printDirect(Order $order)
    {
        if ($order->details()->count() === 0) {
            return back()->with('error', 'No se puede imprimir una cuenta sin productos.');
        }

        try {
            // Get printer name from env or settings. defaults to 'POS-80' (common name)
            // You should share your printer in windows and use that share name here.
            $printerName = env('PRINTER_NAME', 'POS-80'); 
            
            // Connect to printer
            $connector = new WindowsPrintConnector($printerName);
            $printer = new Printer($connector);

            // Basic Header
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Gestional Food\n");
            $printer->text("Sucursal: " . ($order->branch->name ?? 'Principal') . "\n");
            $printer->text(now()->format('d/m/Y H:i A') . "\n");
            $printer->text("Ticket #: " . $order->id . "\n");
            $printer->text("--------------------------------\n");

            // Items
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            foreach ($order->details as $detail) {
                $printer->text($detail->quantity . " x " . ($detail->product->name ?? 'Producto') . "\n");
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->text("$" . number_format($detail->price * $detail->quantity, 2) . "\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
            }
            $printer->text("--------------------------------\n");
            
            // Total
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->setEmphasis(true);
            $printer->text("TOTAL: $" . number_format($order->total, 2) . "\n");
            $printer->setEmphasis(false);
            $printer->text("\n\n");

            // Cut
            $printer->cut();
            $printer->close();

            return back()->with('success', 'Ticket enviado a la impresora.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error de impresión: ' . $e->getMessage() . '. Asegúrate de que la impresora esté COMPARTIDA en Windows con el nombre: ' . env('PRINTER_NAME', 'POS-80'));
        }
    }

    public function apiLocalPrint(Request $request)
    {
        try {
            // This endpoint is meant to run LOCALLY on the machine with the printer.
            // It accepts JSON data and prints it.
            
            $printerName = env('PRINTER_NAME', 'POS-80'); 
            $connector = new WindowsPrintConnector($printerName);
            $printer = new Printer($connector);

            $data = $request->validate([
                'header' => 'nullable|string',
                'items' => 'required|array',
                'total' => 'required|numeric',
                'branch_name' => 'nullable|string',
                'ticket_id' => 'nullable',
                'date' => 'nullable'
            ]);

            // Header
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            if(isset($data['header'])) {
                 $printer->text($data['header'] . "\n");
            } else {
                 $printer->text("Gestional Food\n");
            }
            
            $printer->text("Sucursal: " . ($data['branch_name'] ?? 'Principal') . "\n");
            $printer->text(($data['date'] ?? now()->format('d/m/Y H:i A')) . "\n");
            $printer->text("Ticket #: " . ($data['ticket_id'] ?? 'N/A') . "\n");
            $printer->text("--------------------------------\n");

            // Items
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            foreach ($data['items'] as $item) {
                $printer->text($item['quantity'] . " x " . $item['name'] . "\n");
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->text("$" . number_format($item['price'] * $item['quantity'], 2) . "\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
            }
            $printer->text("--------------------------------\n");
            
            // Total
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->setEmphasis(true);
            $printer->text("TOTAL: $" . number_format($data['total'], 2) . "\n");
            $printer->setEmphasis(false);
            $printer->text("\n\n");

            // Cut
            $printer->cut();
            $printer->close();

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
