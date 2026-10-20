<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['result' => 'error', 'message' => 'Permission denied'], 403);
            }
            abort(403, 'Permission denied');
        }

        return $next($request);
    }
}
