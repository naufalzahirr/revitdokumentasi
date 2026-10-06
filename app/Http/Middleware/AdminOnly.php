<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()->isAdmin(), 403, 'Menu ini hanya dapat dibuka oleh admin.');

        return $next($request);
    }
}
