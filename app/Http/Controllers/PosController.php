<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Payment;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $products = Product::with('category')->where('status', true)->get();

        return view('pos.checkout', compact('order', 'categories', 'products'));
    }

    public function pay(Request $request, Order $order)
    {
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

            return view('pos.print-bridge', compact('order', 'printData', 'settings'));


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
        
        return back()->with('success', count($pendingDetails) . ' items enviados a cocina.');
    }

    public function ticket(Order $order)
    {
        $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
            ->pluck('value', 'key')->toArray();
            
        return view('pos.ticket', compact('order', 'settings'));
    }

    public function preCheck(Order $order)
    {
        $settings = \App\Models\Setting::where('branch_id', session('branch_id'))
            ->pluck('value', 'key')->toArray();

        return view('pos.ticket', ['order' => $order, 'isPreCheck' => true, 'settings' => $settings]);
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
