<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckAppsId
{
    public function handle(Request $request, Closure $next, $appsId)
    {
        if ($request->query('apps_id') !== $appsId) {
            throw new HttpException(403, 'Apps ID tidak sesuai');
        }

        return $next($request);
    }
}
