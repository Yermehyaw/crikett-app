<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasRole([RoleEnum::ADMIN->name, RoleEnum::OWNER->name])) {
            return $next($request);
        }

        return response()->json([
            'code' => 403,
            'message' => 'You Are Unauthorized To Access This Route(s)',
            'success' => false,
        ], 403);
    }
}
