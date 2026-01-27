<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashRegisterOpen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hasOpenRegister = \App\Models\CashRegister::where('user_id', auth()->id())
            ->where('branch_id', session('branch_id'))
            ->where('status', 'open')
            ->exists();

        if (!$hasOpenRegister) {
            return redirect()->route('cash-registers.create')
                ->with('error', 'Debes abrir caja para realizar ventas.');
        }

        return $next($request);
    }
}
