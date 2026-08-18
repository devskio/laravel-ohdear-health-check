<?php

namespace Devskio\LaravelOhdearHealthCheck\Http\Controllers;

use Devskio\LaravelOhdearHealthCheck\Core\CheckRunner;
use Illuminate\Http\JsonResponse;

class HealthCheckController
{
    public function __invoke(CheckRunner $runner): JsonResponse
    {
        $checks = array_merge(
            config('ohdear-health-check.checks', []),
            config('ohdear-health-check.additional_checks', []),
        );

        $payload = $runner->run($checks);


        return response()->json($payload->toArray(), $payload->httpStatus);
    }
}
