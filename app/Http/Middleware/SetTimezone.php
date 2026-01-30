<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class SetTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the branch ID from session
        $branchId = session('branch_id');
        
        // Fetch the timezone setting for this branch
        $timezone = Setting::where('key', 'app_timezone')
                          ->where('branch_id', $branchId)
                          ->value('value');
        
        // If no timezone is set, use UTC as default
        if (!$timezone) {
            $timezone = config('app.timezone', 'UTC');
        }
        
        // Set the timezone for PHP and Laravel
        if ($timezone) {
            date_default_timezone_set($timezone);
            config(['app.timezone' => $timezone]);
        }
        
        return $next($request);
    }
}
