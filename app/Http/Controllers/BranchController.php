<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403);
        }
        $branches = Branch::paginate(10);
        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403);
        }
        return view('branches.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        Branch::create($data);

        return redirect()->route('branches.index')->with('success', 'Sucursal creada con éxito.');
    }

    public function edit(Branch $branch)
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403);
        }
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $branch->update($data);

        return redirect()->route('branches.index')->with('success', 'Sucursal actualizada con éxito.');
    }

    public function destroy(Branch $branch)
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403);
        }
        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'Sucursal eliminada.');
    }
}
