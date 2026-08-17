<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'No tienes permisos para administrar usuarios.');
        }

        return view('users.index');
    }

    public function datatable()
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'No tienes permisos para administrar usuarios.');
        }

        $users = User::where('tenant_id', auth()->user()->tenant_id)->with('roles')->get();

        $data = $users->map(function ($user) {
            $nameCell = '<div class="d-flex flex-column"><span class="fw-bold">' . e($user->name) . '</span><small class="text-muted">' . e($user->email) . '</small></div>';

            $roles = '';
            foreach ($user->roles as $role) {
                $roles .= '<span class="badge bg-label-primary">' . e(ucfirst($role->name)) . '</span> ';
            }

            $status = $user->estado == 'activo'
                ? '<span class="badge bg-label-success">Activo</span>'
                : '<span class="badge bg-label-danger">Inactivo</span>';

            $actions = '<div class="dropdown">'
                . '<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">'
                . '<i class="ti tabler-dots-vertical"></i>'
                . '</button>'
                . '<div class="dropdown-menu">'
                . '<a class="dropdown-item" href="' . route('users.edit', $user) . '">Editar</a>';

            if ($user->id !== auth()->id()) {
                $actions .= '<form action="' . route('users.destroy', $user) . '" method="POST">'
                    . '<input type="hidden" name="_token" value="' . csrf_token() . '">'
                    . '<input type="hidden" name="_method" value="DELETE">'
                    . '<button type="submit" class="dropdown-item text-danger" data-gf-confirm="auto" data-gf-entity="el usuario" data-gf-name="' . e($user->name) . '">Eliminar</button>'
                    . '</form>';
            }

            $actions .= '</div></div>';

            return [
                'name' => $nameCell,
                'roles' => trim($roles),
                'status' => $status,
                'whatsapp' => e($user->pais_whatsapp),
                'actions' => $actions,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function create()
    {
        // Get global roles to assign
        $roles = Role::whereNull('tenant_id')->get();
        $branches = \App\Models\Branch::all();
        return view('users.create', compact('roles', 'branches'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'No tienes permisos para crear usuarios.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'pais_whatsapp' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        $data['password'] = Hash::make($data['password']);
        
        $data['tenant_id'] = auth()->user()->tenant_id;
        $user = User::create($data);
        
        $user->roles()->attach($data['role_id']);

        // Assign branches if provided, otherwise assign all? Or none?
        // For now, if provided in request
        if ($request->has('branches')) {
            $user->branches()->syncWithPivotValues($request->input('branches'), ['tenant_id' => auth()->user()->tenant_id]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Usuario creado correctamente.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        $roles = Role::whereNull('tenant_id')->get();
        $branches = \App\Models\Branch::all(); // TenantScope applies automatically
        return view('users.edit', compact('user', 'roles', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'No tienes permisos para editar usuarios.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'pais_whatsapp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'estado' => 'required|in:activo,inactivo',
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->roles()->sync([$data['role_id']]);

        if (isset($data['branches'])) {
             $user->branches()->syncWithPivotValues($data['branches'], ['tenant_id' => auth()->user()->tenant_id]);
        } else {
             $user->branches()->detach();
        }

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'No tienes permisos para eliminar usuarios.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
