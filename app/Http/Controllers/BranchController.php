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
        return view('branches.index');
    }

    public function datatable()
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403);
        }

        $branches = Branch::get();
        $currentBranchId = (int) session('branch_id');

        $data = $branches->map(function ($branch) use ($currentBranchId) {
            $status = $branch->is_active
                ? '<span class="badge bg-label-success">Activa</span>'
                : '<span class="badge bg-label-secondary">Inactiva</span>';

            $actions = '<div class="dropdown">'
                . '<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">'
                . '<i class="ti tabler-dots-vertical"></i>'
                . '</button>'
                . '<div class="dropdown-menu">'
                . '<a class="dropdown-item" href="' . route('branches.qr', $branch) . '"><i class="ti tabler-qrcode me-1"></i> Código QR</a>'
                . '<a class="dropdown-item" href="' . route('branches.edit', $branch) . '"><i class="ti tabler-pencil me-1"></i> Editar</a>'
                . '</div></div>';

            if ((int) $branch->id !== $currentBranchId) {
                $actions = str_replace(
                    '</div></div>',
                    '<form action="' . route('branches.destroy', $branch) . '" method="POST">'
                    . '<input type="hidden" name="_token" value="' . csrf_token() . '">'
                    . '<input type="hidden" name="_method" value="DELETE">'
                    . '<button type="submit" class="dropdown-item text-danger" data-gf-confirm="auto" data-gf-entity="la sucursal" data-gf-name="' . e($branch->name) . '"><i class="ti tabler-trash me-1"></i> Eliminar</button>'
                    . '</form></div></div>',
                    $actions
                );
            }

            return [
                'name' => '<strong>' . e($branch->name) . '</strong>',
                'phone' => e($branch->phone ?? '-'),
                'address' => e($branch->address ?? '-'),
                'status' => $status,
                'actions' => $actions,
            ];
        })->values();

        return response()->json(['data' => $data]);
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
