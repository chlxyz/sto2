<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->email !== config('admin.email')) {
            abort(403, 'Admin access only.');
        }

        return $next($request);
    }
}
