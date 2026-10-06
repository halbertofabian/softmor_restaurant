<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::with('roles')->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        if ($user->estado !== 'activo') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu cuenta no está activa. Contacta al administrador.',
            ], 403);
        }

        $tokenName = 'pwa-'.($credentials['device_name'] ?? 'device');
        $token = $user->createToken($tokenName)->plainTextToken;
        $role = $user->roles->first()?->name ?? 'user';

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'estado' => $user->estado,
                'role' => $role,
            ],
            'role' => $role,
            'tenant_id' => $user->tenant_id,
            'branches' => $this->accessibleBranches($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles');
        $role = $user->roles->first()?->name ?? 'user';

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'estado' => $user->estado,
                'role' => $role,
            ],
            'role' => $role,
            'tenant_id' => $user->tenant_id,
            'permissions' => $this->permissionsFor($role),
            'branches' => $this->accessibleBranches($user),
        ]);
    }

    private function accessibleBranches(User $user): array
    {
        if ($user->hasRole('administrador') || $user->hasRole('admin')) {
            $branches = Branch::where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $branches = $user->branches()
                ->where('branches.is_active', true)
                ->where('branches.tenant_id', $user->tenant_id)
                ->orderBy('branches.name')
                ->get();
        }

        return $branches->map(fn (Branch $branch) => [
            'id' => $branch->id,
            'name' => $branch->name,
            'address' => $branch->address ?? '',
            'is_active' => (bool) $branch->is_active,
        ])->values()->all();
    }

    private function permissionsFor(string $role): array
    {
        return [
            'take_orders' => in_array($role, ['administrador', 'admin', 'mesero', 'caja'], true),
            'reprint' => in_array($role, ['administrador', 'admin', 'mesero', 'caja', 'cocinero'], true),
            'manage_printers' => true,
        ];
    }
}
