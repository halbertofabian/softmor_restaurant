<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        // If we are already on the selection route, or logout, proceed to avoid loop
        if ($request->routeIs('branches.select') || $request->routeIs('branches.start') || $request->routeIs('logout')) {
            return $next($request);
        }

        // Check if session has branch
        if (!session()->has('branch_id')) {
            // Logic to auto select or redirect
            $branches = auth()->user()->active_branches;

            if ($branches->isEmpty()) {
                auth()->logout();
                return redirect()->route('login')->withErrors(['email' => 'Sin sucursales activas asignadas.']);
            }

            if ($branches->count() === 1) {
                // Auto select
                $branch = $branches->first();
                session(['branch_id' => $branch->id]);
                session(['branch_name' => $branch->name]);
                return $next($request);
            }

            // Multiple branches, redirect to select
            return redirect()->route('branches.select');
        }

        // Validate that current stored branch is still valid for user
        // (Expensive query per request? Just checking relation presence is fast if loaded)
        // For performance, we might skip this or cache it, but requirement asks for strictness.
        // auth()->user()->active_branches IS NOT cached on property access by default unless we eager load.
        // Let's assume session is trusted for the duration of the request, 
        // but if we want strictly "revoke access forces re-selection", we must check.
        
        // Quick check
        $currentBranchId = session('branch_id');
        // We can use the relation check directly without loading all models
        $hasAccess = auth()->user()->branches()
            ->where('branches.id', $currentBranchId)
            ->where('branches.is_active', true)
            ->exists(); // This is a DB query.

        if (!$hasAccess) {
             session()->forget(['branch_id', 'branch_name']);
             return redirect()->route('branches.select')->with('error', 'Acceso a sucursal revocado o sucursal inactiva.');
        }

        return $next($request);
    }
}
