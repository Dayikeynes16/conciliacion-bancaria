<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetGlobalDateFilters
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Resolve Month
        if ($request->has('month')) {
            $month = $request->input('month');
            session(['global_month' => $month]);
        } else {
            $month = session('global_month', now()->month);
        }

        // 2. Resolve Year
        if ($request->has('year')) {
            $year = $request->input('year');
            session(['global_year' => $year]);
        } else {
            $year = session('global_year', now()->year);
        }

        // 3. Merge into request so Controllers and Views see the resolved value
        $request->merge([
            'month' => $month,
            'year' => $year,
        ]);

        return $next($request);
    }
}
