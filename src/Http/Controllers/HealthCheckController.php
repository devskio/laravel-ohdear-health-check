<?php

namespace Devskio\LaravelOhdearHealthCheck\Http\Controllers;

use Devskio\LaravelOhdearHealthCheck\Core\CheckRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class HealthCheckController extends Controller
{
    public function __invoke(CheckRunner $runner): JsonResponse|Response
    {
        $checks = array_merge(
            config('ohdear-health-check.checks', []),
            config('ohdear-health-check.additional_checks', []),
        );

        $payload = $runner->run($checks);

        if (config('ohdear-health-check.response_format') === 'ohdear' && $payload->rawCheckResults !== null) {
            return response($payload->rawCheckResults->toJson(), $payload->httpStatus)
                ->header('Content-Type', 'application/json');
        }

        return response()->json($payload->toArray(), $payload->httpStatus);
    }
}
