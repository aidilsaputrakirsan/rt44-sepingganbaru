<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'demo' && !$request->isMethod('GET')) {
            if ($request->header('X-Inertia')) {
                return back()->with('error', 'Mode demo: fitur ini dinonaktifkan.');
            }

            return redirect()->back()->with('error', 'Mode demo: fitur ini dinonaktifkan.');
        }

        return $next($request);
    }
}
