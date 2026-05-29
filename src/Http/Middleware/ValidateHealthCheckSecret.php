<?php

namespace Devskio\LaravelOhdearHealthCheck\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateHealthCheckSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredSecret = (string) config('ohdear-health-check.secret', '');

        // If not configured, do not block.
        if ($configuredSecret === '') {
            return $next($request);
        }

        $provided = (string) ($request->header('oh-dear-health-check-secret') ?: $request->query('secret', ''));

        if ($provided === '' || ! hash_equals($configuredSecret, $provided)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid secret.',
            ], 403);
        }

        return $next($request);
    }
}

