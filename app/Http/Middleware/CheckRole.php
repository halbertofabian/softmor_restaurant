<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('login');
        }

        // If strict 'cocinero' isolation check logic is preferred:
        // Logic: Check if user has ANY of the passed roles
        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }

        // Specific business rule: If the user is a 'cocinero' and was denied access,
        // redirect them to their home screen instead of showing an error.
        if ($request->user()->hasRole('cocinero')) {
            return redirect()->route('kitchen.index');
        }

        // Redirect 'mesero' to tables if they try to access something unauthorized
        if ($request->user()->hasRole('mesero')) {
            return redirect()->route('tables.index');
        }

        abort(403, 'No tienes permisos para acceder a esta sección.');
    }
}
