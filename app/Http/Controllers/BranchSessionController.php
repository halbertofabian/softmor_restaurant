<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchSessionController extends Controller
{
    public function select()
    {
        // Get user's active branches
        $branches = auth()->user()->active_branches;

        // If user has no branches, error
        if ($branches->isEmpty()) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'No tienes sucursales asignadas. Contacta al administrador.']);
        }

        // If user has only one branch, auto select? 
        // Logic says yes, but if we are here it might be because of middleware redirect.
        // Middleware should handle auto-selection if only 1 exists, but strictly speaking checking here is safe.
        if ($branches->count() === 1) {
             return $this->setBranch($branches->first());
        }

        return view('auth.select-branch', compact('branches'));
    }

    public function start(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id'
        ]);

        $branch = auth()->user()->branches()->where('branches.id', $request->branch_id)->firstOrFail();

        if (!$branch->is_active) {
            return back()->with('error', 'La sucursal seleccionada no está activa.');
        }

        return $this->setBranch($branch);
    }

    public function switch(Branch $branch)
    {
        // Validate ownership
        if (!auth()->user()->branches->contains($branch->id)) {
            abort(403);
        }

        return $this->setBranch($branch);
    }

    private function setBranch($branch)
    {
        session(['branch_id' => $branch->id]);
        session(['branch_name' => $branch->name]); // Helper for UI
        
        return redirect()->intended('/dashboard'); // Go to dashboard or intended URL
    }
}
