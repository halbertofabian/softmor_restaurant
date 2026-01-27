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

        $users = User::where('tenant_id', auth()->user()->tenant_id)->with('roles')->paginate(10);
        return view('users.index', compact('users'));
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
            'pais_whatsapp' => 'required|string|max:20',
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
            'pais_whatsapp' => 'required|string|max:20',
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
