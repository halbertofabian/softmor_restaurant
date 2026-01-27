<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::all();
        return view('tables.index', compact('tables'));
    }

    public function create()
    {
        return view('tables.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'capacity' => 'nullable|integer',
            'zone' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['status'] = 'free'; // Default status

        Table::create($data);

        return redirect()->route('tables.index')->with('success', 'Mesa creada con éxito.');
    }

    public function edit(Table $table)
    {
        return view('tables.edit', compact('table'));
    }

    public function show(Table $table)
    {
        return redirect()->route('tables.index');
    }

    public function update(Request $request, Table $table)
    {
        $request->validate([
            'name' => 'required',
            'capacity' => 'nullable|integer',
            'zone' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');

        $table->update($data);

        return redirect()->route('tables.index')->with('success', 'Mesa actualizada con éxito.');
    }

    public function destroy(Table $table)
    {
        $table->delete();
        return redirect()->route('tables.index')->with('success', 'Mesa eliminada con éxito.');
    }

    public function occupy(Table $table)
    {
        if (!$table->is_active) {
            return redirect()->back()->with('error', 'La mesa no está activa.');
        }

        if ($table->status !== 'free') {
            return redirect()->back()->with('error', 'La mesa no está libre.');
        }

        $table->update(['status' => 'occupied']);

        // Auto-create order
        $order = \App\Models\Order::create([
            'table_id' => $table->id,
            'user_id' => auth()->id(),
            'status' => 'open',
        ]);

        return redirect()->route('orders.mobile', $order);
    }

    public function release(Table $table)
    {
        $table->update(['status' => 'free']);

        return redirect()->back()->with('success', 'Mesa liberada.');
    }
}
