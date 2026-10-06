<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesBranchAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ApiWaiterController extends Controller
{
    use AuthorizesBranchAccess;

    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');

        if (! $branchId) {
            return response()->json([
                'status' => 'error',
                'message' => 'branch_id es requerido',
            ], 400);
        }

        if (! $this->userHasBranchAccess($user, $branchId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal',
            ], 403);
        }

        $waiters = User::where('tenant_id', $user->tenant_id)
            ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'mesero'))
            ->whereHas('branches', fn ($branchQuery) => $branchQuery->where('branches.id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $waiters->map(fn (User $waiter) => [
                'id' => $waiter->id,
                'name' => $waiter->name,
            ])->values(),
        ]);
    }
}
