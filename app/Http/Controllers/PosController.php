<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

            return redirect()->route('pos.ticket', $order);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar el pago: ' . $e->getMessage());
        }
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
}
