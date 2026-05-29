<?php

// config for Devskio/LaravelOhdearHealthCheck

use Devskio\LaravelOhdearHealthCheck\Health\Checks\DatabaseCheck;
use Devskio\LaravelOhdearHealthCheck\Health\Checks\ErrorLogCheck;
use Devskio\LaravelOhdearHealthCheck\Health\Checks\UsedDiskSpaceCheck;

// Route overrides
$healthPath = env('OHDEAR_HEALTH_CHECK_PATH', '/healthcheck');
$healthMiddleware = env('OHDEAR_HEALTH_CHECK_MIDDLEWARE');
$healthMiddleware = is_string($healthMiddleware) && $healthMiddleware !== ''
    ? array_values(array_filter(array_map('trim', explode(',', $healthMiddleware))))
    : ['web'];

// Check toggles (all enabled by default)
$dbEnabled   = filter_var(env('OHDEAR_HEALTH_CHECK_DB_ENABLED', true), FILTER_VALIDATE_BOOL);
$diskEnabled = filter_var(env('OHDEAR_HEALTH_CHECK_DISK_ENABLED', true), FILTER_VALIDATE_BOOL);
$logEnabled  = filter_var(env('OHDEAR_HEALTH_CHECK_ERROR_LOG_ENABLED', true), FILTER_VALIDATE_BOOL);

// Threshold overrides (env values are always respected)
$diskWarn  = (float) env('OHDEAR_HEALTH_CHECK_DISK_WARN_PCT', 70);
$diskFail  = (float) env('OHDEAR_HEALTH_CHECK_DISK_FAIL_PCT', 90);
$logWarnMb = env('OHDEAR_HEALTH_CHECK_ERROR_LOG_WARN_MB');
$logMaxMb  = (int) env('OHDEAR_HEALTH_CHECK_ERROR_LOG_MAX_MB', 50);
$diskPath  = env('OHDEAR_HEALTH_CHECK_DISK_PATH', '/');

$checks = [];

if ($dbEnabled) {
    $checks[] = DatabaseCheck::class;
}

if ($diskEnabled) {
    $checks[] = [
        'class' => UsedDiskSpaceCheck::class,
        'options' => [
            'warning_threshold_percentage' => $diskWarn,
            'error_threshold_percentage'   => $diskFail,
            'disk_path'                    => $diskPath,
        ],
    ];
}

if ($logEnabled) {
    $logOptions = ['max_log_size_mb' => $logMaxMb];

    if ($logWarnMb !== null && $logWarnMb !== '') {
        $logOptions['warning_log_size_mb'] = (int) $logWarnMb;
    }

    $checks[] = [
        'class'   => ErrorLogCheck::class,
        'options' => $logOptions,
    ];
}

return [
    /*
     | The path where Oh Dear (or your uptime monitor) calls this endpoint.
     | Override via env: OHDEAR_HEALTH_CHECK_PATH
     */
    'health_route' => [
        'path'       => $healthPath,
        'middleware' => $healthMiddleware,
    ],

    /*
     | Optional shared secret.
     | The endpoint will require it via header `X-OhDear-HealthCheck-Secret`
     | or query param `?secret=`.
     | Override via env: OHDEAR_HEALTH_CHECK_SECRET
     */
    'secret' => env('OHDEAR_HEALTH_CHECK_SECRET'),

    /*
     | JSON response format.
     |
     | Supported:
     | - "array"   — package-native format
     | - "ohdear"  — raw Oh Dear compatible JSON
     |
     | Override via env: OHDEAR_HEALTH_CHECK_RESPONSE_FORMAT
     */
    'response_format' => env('OHDEAR_HEALTH_CHECK_RESPONSE_FORMAT', 'array'),

    /*
     | The checks run when the health endpoint is hit.
     |
     | Each entry is either:
     | - a check class-string, or
     | - [ 'class' => SomeCheck::class, 'options' => [...] ]
     |
     | Toggle individual checks via env flags (see top of this file).
     */
    'checks' => $checks,

    /*
     | Optional additional checks appended by integrations (e.g. the Statamic addon).
     */
    'additional_checks' => [],
];
