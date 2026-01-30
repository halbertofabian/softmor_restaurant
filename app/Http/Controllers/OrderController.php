<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('status', '!=', 'closed')->with('table')->get();
        return view('orders.index', compact('orders'));
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

        // Check if table already has active order
        $activeOrder = Order::where('table_id', $table->id)
            ->where('status', '!=', 'closed')
            ->where('status', '!=', 'canceled')
            ->first();

        if ($activeOrder) {
            return redirect()->route('orders.show', $activeOrder);
        }

        $order = Order::create([
            'table_id' => $table->id,
            'user_id' => auth()->id(), // Assuming auth
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
        $products = Product::where('status', true)->get();

        return view('orders.pos', compact('order', 'categories', 'products'));
    }

    public function addItem(Request $request, Order $order)
    {
        if ($order->status == 'closed' || $order->status == 'canceled') {
            return redirect()->back()->with('error', 'La comanda está cerrada.');
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $product = Product::find($request->product_id);

        // Add Detail
        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => $request->quantity,
            'preparation_area_id' => $product->preparation_area_id,
            'notes' => $request->notes,
            'status' => 'pending', // New items start as pending
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

        // 1. Deduct Inventory
        foreach ($order->details as $detail) {
            $product = $detail->product;
            if ($product->controls_inventory) {
                // Check stock? For now assume negative allowed or check
                $newStock = $product->stock - $detail->quantity;
                
                // Record movement
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

        // 2. Close Order
        $order->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // 3. Free Table
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
        // Load products grouped by category for mobile view optimization
        $products = Product::where('status', true)->get();
        
        return view('orders.mobile', compact('order', 'categories', 'products'));
    }
}
