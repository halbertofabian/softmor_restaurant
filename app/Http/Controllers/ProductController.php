<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\PreparationArea;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'preparationArea'])->get();
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('status', true)->get();
        $preparationAreas = PreparationArea::where('status', true)->get();
        return view('products.create', compact('categories', 'preparationAreas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:dish,drink,finished,extra',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'preparation_area_id' => 'required|exists:preparation_areas,id',
            'stock' => 'nullable|integer',
            'min_stock' => 'nullable|integer',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status'); // Handle checkbox
        $data['controls_inventory'] = $request->has('controls_inventory'); // Handle checkbox

        // Default stock to 0 if null
        $data['stock'] = $data['stock'] ?? 0;

        $product = Product::create($data);

        // Initial Inventory Movement if controls inventory and stock > 0
        if ($product->controls_inventory && $product->stock > 0) {
            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $product->stock,
                'previous_stock' => 0,
                'new_stock' => $product->stock,
                'notes' => 'Inventario inicial',
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Producto creado con éxito.');
    }

    public function edit(Product $product)
    {
        $categories = Category::where('status', true)->get();
        $preparationAreas = PreparationArea::where('status', true)->get();
        return view('products.edit', compact('product', 'categories', 'preparationAreas'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:dish,drink,finished,extra',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'preparation_area_id' => 'required|exists:preparation_areas,id',
            'stock' => 'nullable|integer',
            'min_stock' => 'nullable|integer',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status'); // Handle checkbox
        $data['controls_inventory'] = $request->has('controls_inventory'); // Handle checkbox
        $data['stock'] = $data['stock'] ?? 0;

        // Inventory Adjustment Logic
        if ($data['controls_inventory']) {
            $oldStock = $product->stock;
            $newStock = (int) $data['stock'];

            if ($oldStock !== $newStock) {
                $delta = $newStock - $oldStock;
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'quantity' => $delta,
                    'previous_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'notes' => 'Ajuste manual desde edición',
                    'user_id' => auth()->id(),
                ]);
            }
        }

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Producto actualizado con éxito.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Producto eliminado con éxito.');
    }
}
