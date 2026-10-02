<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryItemMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryItemController extends Controller
{
    public function index()
    {
        $items = InventoryItem::withCount('recipeItems')
            ->orderByDesc('status')
            ->latest()
            ->paginate(25);
        return view('inventory-items.index', compact('items'));
    }

    public function create()
    {
        return view('inventory-items.create');
    }

    public function show(InventoryItem $inventoryItem)
    {
        $movements = $inventoryItem->movements()->latest()->paginate(50);
        return view('inventory-items.movements', compact('inventoryItem', 'movements'));
    }

    public function storeMovement(Request $request, InventoryItem $inventoryItem)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['receipt', 'waste', 'adjustment'])],
            'quantity' => ['required', 'numeric', 'min:0'],
            'quantity_unit' => ['required', Rule::in(['g', 'kg', 'ml', 'l', 'unit'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['type'] !== 'adjustment' && (float) $data['quantity'] <= 0) {
            throw ValidationException::withMessages(['quantity' => 'La cantidad debe ser mayor a cero.']);
        }

        $quantity = $this->toBaseUnit((float) $data['quantity'], $data['quantity_unit'], $inventoryItem->base_unit);

        DB::transaction(function () use ($inventoryItem, $data, $quantity) {
            $item = InventoryItem::whereKey($inventoryItem->id)->lockForUpdate()->firstOrFail();
            $previous = (float) $item->stock;

            $new = round(match ($data['type']) {
                'receipt' => $previous + $quantity,
                'waste' => $previous - $quantity,
                'adjustment' => $quantity,
            }, 3);

            $signed = round(match ($data['type']) {
                'receipt' => $quantity,
                'waste' => -$quantity,
                'adjustment' => $new - $previous,
            }, 3);

            $item->update(['stock' => $new]);

            InventoryItemMovement::create([
                'inventory_item_id' => $item->id,
                'type' => $data['type'],
                'quantity' => $signed,
                'previous_stock' => $previous,
                'new_stock' => $new,
                'notes' => $data['notes'] ?: match ($data['type']) {
                    'receipt' => 'Entrada manual',
                    'waste' => 'Salida manual',
                    'adjustment' => 'Ajuste de inventario',
                },
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('inventory-items.show', $inventoryItem)->with('success', 'Movimiento registrado con éxito.');
    }

    public function report()
    {
        return view('inventory-items.report', $this->reportData());
    }

    public function reportPdf()
    {
        return Pdf::loadView('inventory-items.report-pdf', $this->reportData())
            ->setPaper('letter', 'landscape')
            ->download('reporte-materias-primas-' . now()->format('Y-m-d') . '.pdf');
    }

    private function reportData(): array
    {
        $items = InventoryItem::withCount('recipeItems')
            ->withMax('movements', 'created_at')
            ->orderByDesc('status')
            ->orderBy('name')
            ->get();
        $branch = \App\Models\Branch::find(session('branch_id'));
        $summary = [
            'total' => $items->count(),
            'active' => $items->where('status', true)->count(),
            'inactive' => $items->where('status', false)->count(),
            'low_stock' => $items->filter(fn ($item) => $item->min_stock !== null && (float) $item->stock <= (float) $item->min_stock)->count(),
        ];

        return compact('items', 'branch', 'summary');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['stock'] = $this->toBaseUnit((float) $data['stock'], $request->input('stock_unit'), $data['base_unit']);
        unset($data['stock_unit']);
        $data['status'] = $request->boolean('status');

        DB::transaction(function () use ($data) {
            $item = InventoryItem::create($data);
            if ((float) $item->stock !== 0.0) {
                InventoryItemMovement::create([
                    'inventory_item_id' => $item->id,
                    'type' => 'initial',
                    'quantity' => $item->stock,
                    'previous_stock' => 0,
                    'new_stock' => $item->stock,
                    'notes' => 'Inventario inicial',
                    'user_id' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('inventory-items.index')->with('success', 'Materia prima creada con éxito.');
    }

    public function edit(InventoryItem $inventoryItem)
    {
        return view('inventory-items.edit', compact('inventoryItem'));
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $data = $this->validated($request, false);
        if ($data['base_unit'] !== $inventoryItem->base_unit
            && ($inventoryItem->recipeItems()->exists() || $inventoryItem->movements()->exists())) {
            throw ValidationException::withMessages(['base_unit' => 'No puedes cambiar la unidad base porque ya tiene recetas o movimientos.']);
        }
        $data['status'] = $request->boolean('status');
        $inventoryItem->update($data);

        return redirect()->route('inventory-items.index')->with('success', 'Materia prima actualizada con éxito.');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        if ($inventoryItem->recipeItems()->exists() || $inventoryItem->movements()->exists()) {
            $inventoryItem->update(['status' => false]);
            return back()->with('success', 'La materia prima tiene historial o recetas y fue desactivada.');
        }
        $inventoryItem->delete();
        return back()->with('success', 'Materia prima eliminada con éxito.');
    }

    private function validated(Request $request, bool $includeStock = true): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'base_unit' => ['required', Rule::in(['g', 'ml', 'unit'])],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
        ];
        if ($includeStock) {
            $rules['stock'] = ['required', 'numeric', 'min:0'];
            $rules['stock_unit'] = ['required', Rule::in(['g', 'kg', 'ml', 'l', 'unit'])];
        }
        return $request->validate($rules);
    }

    private function toBaseUnit(float $quantity, string $inputUnit, string $baseUnit): float
    {
        $dimensions = ['g' => 'mass', 'kg' => 'mass', 'ml' => 'volume', 'l' => 'volume', 'unit' => 'unit'];
        if (($dimensions[$inputUnit] ?? null) !== ($dimensions[$baseUnit] ?? null)) {
            throw ValidationException::withMessages(['stock_unit' => 'La unidad de entrada no es compatible con la unidad base.']);
        }
        return round($quantity * (in_array($inputUnit, ['kg', 'l'], true) ? 1000 : 1), 3);
    }
}
