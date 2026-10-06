<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesBranchAccess;
use App\Http\Controllers\Controller;
use App\Models\PreparationArea;
use Illuminate\Http\Request;

class ApiPreparationAreaController extends Controller
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

        $areas = PreparationArea::where('tenant_id', $user->tenant_id)
            ->where('branch_id', $branchId)
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $areas->map(fn (PreparationArea $area) => [
                'id' => $area->id,
                'name' => $area->name,
                'print_ticket' => (bool) $area->print_ticket,
                'sort_order' => (int) $area->sort_order,
                'status' => (bool) $area->status,
                'system_printer_name' => $area->printer_name,
            ])->values(),
        ]);
    }
}
