<?php

use Devskio\LaravelOhdearHealthCheck\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

$healthPath = config('ohdear-health-check.health_route.path', '/healthcheck');
$healthMiddleware = config('ohdear-health-check.health_route.middleware', ['web']);

Route::middleware(array_merge($healthMiddleware, ['ohdear-health-check.secret']))
    ->get($healthPath, HealthCheckController::class);

