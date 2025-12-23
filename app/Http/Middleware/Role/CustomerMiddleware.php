<?php

namespace App\Http\Middleware\Role;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            throw new HttpException(401, 'Unauthorized');
        }

        if (($user->role ?? null) !== 'CUSTOMER') {
            throw new HttpException(403, 'Akses khusus customer');
        }

        return $next($request);
    }
}
