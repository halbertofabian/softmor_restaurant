<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\PreparationArea;
use App\Models\Product;
use App\Models\ProductComboItem;
use App\Models\ProductFlavor;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index');
    }

    public function datatable()
    {
        $products = Product::with(['category', 'flavors'])->get();

        $data = $products->map(function ($product) {
            $editUrl = route('products.edit', $product);
            $duplicateUrl = route('products.duplicate', $product);
            $destroyUrl = route('products.destroy', $product);
            $token = csrf_token();

            $typeBadge = match ($product->type) {
                'dish' => '<span class="badge bg-label-primary">Platillo</span>',
                'drink' => '<span class="badge bg-label-info">Bebida</span>',
                'finished' => '<span class="badge bg-label-warning">Terminado</span>',
                'extra' => '<span class="badge bg-label-secondary">Extra</span>',
                'combo' => '<span class="badge bg-label-dark">Combo</span>',
                default => '',
            };

            $flavors = $product->flavors->count()
                ? e($product->flavors->pluck('name')->join(', '))
                : '<span class="text-muted">-</span>';

            $stock = $product->controls_inventory
                ? (string) $product->stock
                : '<span class="text-muted">-</span>';

            $statusBadge = $product->status
                ? '<span class="badge bg-label-success">Activo</span>'
                : '<span class="badge bg-label-secondary">Inactivo</span>';

            $actions = '<a href="' . $editUrl . '" class="btn btn-sm btn-icon btn-text-secondary" title="Editar"><i class="ti tabler-edit"></i></a>'
                . '<form action="' . $duplicateUrl . '" method="POST" class="d-inline">'
                . '<input type="hidden" name="_token" value="' . $token . '">'
                . '<button type="submit" class="btn btn-sm btn-icon btn-text-primary" title="Duplicar"><i class="ti tabler-copy"></i></button>'
                . '</form>'
                . '<form action="' . $destroyUrl . '" method="POST" class="d-inline">'
                . '<input type="hidden" name="_token" value="' . $token . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="btn btn-sm btn-icon btn-text-danger" data-gf-confirm="auto" data-gf-entity="el producto" data-gf-name="' . e($product->name) . '"><i class="ti tabler-trash"></i></button>'
                . '</form>';

            return [
                'name' => e($product->name),
                'type' => $typeBadge,
                'category' => e($product->category->name ?? 'N/A'),
                'price' => '$' . number_format($product->price, 2),
                'flavors' => $flavors,
                'stock' => $stock,
                'status' => $statusBadge,
                'actions' => $actions,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function create()
    {
        $categories = Category::where('status', true)->get();
        $preparationAreas = PreparationArea::where('status', true)->get();
        $comboProducts = Product::where('status', true)->where('type', '!=', 'combo')->with('flavors')->get();
        $comboProductsData = $comboProducts->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'flavors' => $p->flavors->map(function ($f) {
                    return ['id' => $f->id, 'name' => $f->name, 'additional_price' => (float) $f->additional_price];
                })->values(),
            ];
        })->values();
        return view('products.create', compact('categories', 'preparationAreas', 'comboProducts', 'comboProductsData'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:dish,drink,finished,extra,combo',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'preparation_area_id' => 'required|exists:preparation_areas,id',
            'stock' => 'nullable|integer',
            'min_stock' => 'nullable|integer',
            'flavor_name.*' => 'nullable|string|max:255',
            'flavor_price.*' => 'nullable|numeric|min:0',
            'combo_component_product_id.*' => 'nullable|exists:products,id',
            'combo_component_quantity.*' => 'nullable|integer|min:1',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status');
        $data['controls_inventory'] = $request->has('controls_inventory');
        $data['stock'] = $data['stock'] ?? 0;
        if (($data['type'] ?? null) === 'combo') {
            $componentIds = array_values(array_filter($request->input('combo_component_product_id', [])));
            if (empty($componentIds)) {
                return redirect()->back()->withInput()->withErrors(['combo_component_product_id' => 'Agrega al menos un componente para el combo.']);
            }
            $firstComponent = Product::find($componentIds[0]);
            if (!$firstComponent) {
                return redirect()->back()->withInput()->withErrors(['combo_component_product_id' => 'El componente seleccionado no es válido.']);
            }

            $data['category_id'] = $firstComponent->category_id;
            $data['preparation_area_id'] = $firstComponent->preparation_area_id;
            $data['controls_inventory'] = false;
            $data['stock'] = 0;
            $data['min_stock'] = null;
        }

        $product = null;

        DB::transaction(function () use (&$product, $data, $request) {
            $product = Product::create($data);
            $this->syncFlavors($product, $request);
            $this->syncComboItems($product, $request);

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
        });

        return redirect()->route('products.index')->with('success', 'Producto creado con éxito.');
    }

    public function edit(Product $product)
    {
        $product->load(['flavors', 'comboItems']);
        $comboProducts = Product::where('status', true)
            ->where('type', '!=', 'combo')
            ->where('id', '!=', $product->id)
            ->with('flavors')
            ->get();
        $categories = Category::where('status', true)->get();
        $preparationAreas = PreparationArea::where('status', true)->get();
        $comboProductsData = $comboProducts->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'flavors' => $p->flavors->map(function ($f) {
                    return ['id' => $f->id, 'name' => $f->name, 'additional_price' => (float) $f->additional_price];
                })->values(),
            ];
        })->values();
        $existingFlavorsData = $product->flavors->map(function ($flavor) {
            return ['name' => $flavor->name, 'additional_price' => (float) $flavor->additional_price];
        })->values();
        $existingComboItemsData = $product->comboItems->map(function ($item) {
            return [
                'component_product_id' => $item->component_product_id,
                'quantity' => $item->quantity,
                'default_flavor_id' => $item->default_flavor_id,
            ];
        })->values();
        return view('products.edit', compact('product', 'categories', 'preparationAreas', 'comboProducts', 'comboProductsData', 'existingFlavorsData', 'existingComboItemsData'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:dish,drink,finished,extra,combo',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'preparation_area_id' => 'required|exists:preparation_areas,id',
            'stock' => 'nullable|integer',
            'min_stock' => 'nullable|integer',
            'flavor_name.*' => 'nullable|string|max:255',
            'flavor_price.*' => 'nullable|numeric|min:0',
            'combo_component_product_id.*' => 'nullable|exists:products,id',
            'combo_component_quantity.*' => 'nullable|integer|min:1',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status');
        $data['controls_inventory'] = $request->has('controls_inventory');
        $data['stock'] = $data['stock'] ?? 0;
        if (($data['type'] ?? null) === 'combo') {
            $componentIds = array_values(array_filter($request->input('combo_component_product_id', [])));
            if (empty($componentIds)) {
                return redirect()->back()->withInput()->withErrors(['combo_component_product_id' => 'Agrega al menos un componente para el combo.']);
            }
            $firstComponent = Product::find($componentIds[0]);
            if (!$firstComponent) {
                return redirect()->back()->withInput()->withErrors(['combo_component_product_id' => 'El componente seleccionado no es válido.']);
            }

            $data['category_id'] = $firstComponent->category_id;
            $data['preparation_area_id'] = $firstComponent->preparation_area_id;
            $data['controls_inventory'] = false;
            $data['stock'] = 0;
            $data['min_stock'] = null;
        }

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

        DB::transaction(function () use ($product, $data, $request) {
            $product->update($data);
            $this->syncFlavors($product, $request);
            $this->syncComboItems($product, $request);
        });

        return redirect()->route('products.index')->with('success', 'Producto actualizado con éxito.');
    }


    public function duplicate(Product $product)
    {
        $newProduct = null;

        DB::transaction(function () use ($product, &$newProduct) {
            $baseName = trim((string) $product->name);
            $newName = $baseName . ' (copia)';
            $counter = 2;

            while (Product::where('name', $newName)->exists()) {
                $newName = $baseName . ' (copia ' . $counter . ')';
                $counter++;
            }

            $productColumns = Schema::getColumnListing('products');
            $newData = [
                'name' => $newName,
                'description' => $product->description,
                'type' => $product->type,
                'price' => $product->price,
                'category_id' => $product->category_id,
                'status' => $product->status,
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'preparation_area_id' => $product->preparation_area_id,
                'controls_inventory' => $product->controls_inventory,
            ];

            if (in_array('image', $productColumns, true)) {
                $newData['image'] = $product->image;
            }
            if (in_array('alert_stock', $productColumns, true)) {
                $newData['alert_stock'] = $product->alert_stock;
            }

            $newProduct = Product::create($newData);

            $product->loadMissing(['flavors', 'comboItems']);

            $flavorMap = [];
            foreach ($product->flavors as $flavor) {
                $newFlavor = ProductFlavor::create([
                    'product_id' => $newProduct->id,
                    'name' => $flavor->name,
                    'additional_price' => $flavor->additional_price,
                    'is_active' => $flavor->is_active,
                    'sort_order' => $flavor->sort_order,
                ]);
                $flavorMap[$flavor->id] = $newFlavor->id;
            }

            foreach ($product->comboItems as $item) {
                $componentProductId = (int) $item->component_product_id === (int) $product->id
                    ? $newProduct->id
                    : $item->component_product_id;

                $defaultFlavorId = $item->default_flavor_id;
                if ((int) $item->component_product_id === (int) $product->id && !empty($defaultFlavorId)) {
                    $defaultFlavorId = $flavorMap[$defaultFlavorId] ?? null;
                }

                ProductComboItem::create([
                    'combo_product_id' => $newProduct->id,
                    'component_product_id' => $componentProductId,
                    'default_flavor_id' => $defaultFlavorId,
                    'quantity' => $item->quantity,
                    'sort_order' => $item->sort_order,
                ]);
            }
        });

        return redirect()->route('products.edit', $newProduct)->with('success', 'Producto duplicado con éxito.');
    }

    public function destroy(Product $product)
    {
        $hasSalesHistory = OrderDetail::where('product_id', $product->id)->exists();

        if ($hasSalesHistory) {
            $product->update(['status' => false]);
            return redirect()->route('products.index')->with('success', 'El producto tiene historial de ventas. Se desactivó en lugar de eliminarse.');
        }

        $product->delete();
        return redirect()->route('products.index')->with('success', 'Producto eliminado con éxito.');
    }

    private function syncFlavors(Product $product, Request $request): void
    {
        $names = $request->input('flavor_name', []);
        $prices = $request->input('flavor_price', []);

        ProductFlavor::where('product_id', $product->id)->delete();

        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            ProductFlavor::create([
                'product_id' => $product->id,
                'name' => $name,
                'additional_price' => (float) ($prices[$index] ?? 0),
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }

    private function syncComboItems(Product $product, Request $request): void
    {
        ProductComboItem::where('combo_product_id', $product->id)->delete();

        if ($product->type !== 'combo') {
            return;
        }

        $componentIds = $request->input('combo_component_product_id', []);
        $quantities = $request->input('combo_component_quantity', []);

        foreach ($componentIds as $index => $componentId) {
            if (!$componentId) {
                continue;
            }

            ProductComboItem::create([
                'combo_product_id' => $product->id,
                'component_product_id' => (int) $componentId,
                'default_flavor_id' => null,
                'quantity' => max(1, (int) ($quantities[$index] ?? 1)),
                'sort_order' => $index,
            ]);
        }
    }
}
