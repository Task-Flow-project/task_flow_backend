<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookieToken = $request->cookie('taskflow_token');
        $bearerToken = $request->bearerToken();

        if (! $bearerToken && $cookieToken) {
            $request->headers->set('Authorization', 'Bearer ' . $cookieToken);
        }

        return $next($request);
    }
}
