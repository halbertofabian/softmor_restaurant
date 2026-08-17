<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\User;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::orderBy('name')->get();

        // Waiters available in the current branch (to assign when an admin/cashier occupies a table)
        $waiters = User::where('tenant_id', auth()->user()->tenant_id)
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'mesero');
            })
            ->when(session()->has('branch_id'), function ($query) {
                $query->whereHas('branches', function ($branchQuery) {
                    $branchQuery->where('branches.id', session('branch_id'));
                });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $roles = \App\Models\Role::whereNull('tenant_id')->orderBy('name')->get(['id', 'name']);

        return view('tables.index', compact('tables', 'waiters', 'roles'));
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

    public function occupy(Request $request, Table $table)
    {
        if (!$table->is_active) {
            return redirect()->back()->with('error', 'La mesa no está activa.');
        }

        if ($table->status !== 'free') {
            return redirect()->back()->with('error', 'La mesa no está libre.');
        }

        $user = auth()->user();

        // Meseros occupy with their own account; others must assign the waiter
        $waiterId = $user->id;
        if (!$user->hasRole('mesero')) {
            $request->validate([
                'waiter_id' => 'required|exists:users,id',
            ]);

            $waiter = User::where('id', $request->waiter_id)
                ->where('tenant_id', $user->tenant_id)
                ->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'mesero');
                })
                ->when(session()->has('branch_id'), function ($query) {
                    $query->whereHas('branches', function ($branchQuery) {
                        $branchQuery->where('branches.id', session('branch_id'));
                    });
                })
                ->first();

            if (!$waiter) {
                return redirect()->back()->with('error', 'Selecciona un mesero válido de esta sucursal.');
            }

            $waiterId = $waiter->id;
        }

        $table->update(['status' => 'occupied']);

        // Auto-create order
        $order = \App\Models\Order::create([
            'table_id' => $table->id,
            'user_id' => $waiterId,
            'status' => 'open',
        ]);

        // Redirect based on role: mesero -> mobile, cashier/admin -> checkout
        if ($user->hasRole('mesero')) {
            return redirect()->route('orders.mobile', $order);
        }
        
        return redirect()->route('pos.checkout', $order);
    }

    public function release(Table $table)
    {
        $table->update(['status' => 'free']);

        return redirect()->back()->with('success', 'Mesa liberada.');
    }
}
