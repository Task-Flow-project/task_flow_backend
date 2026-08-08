<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession as Middleware;

class StartSession extends Middleware
{
    public function handle($request, \Closure $next)
    {
        if (! $this->sessionConfigured()) {
            return $next($request);
        }

        $session = $this->startSession($request);
        $request->setSession($session);

        $response = $next($request);

        $this->storeCurrentUrl($request, $session);
        $this->collectGarbage($session);

        $session->save();

        $this->addCookieToResponse($response, $session);

        return $response;
    }
}
