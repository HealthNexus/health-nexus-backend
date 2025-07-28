<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication required'
            ], 401);
        }

        if (!in_array($request->user()->role->slug, ['admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin access required'
            ], 403);
        }

        return $next($request);
    }
}
