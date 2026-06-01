<?php

namespace Devskio\LaravelOhdearHealthCheck\Core;

use Illuminate\Support\Arr;
use OhDear\HealthCheckResults\CheckResult as OhDearCheckResult;
use OhDear\HealthCheckResults\CheckResults;
use Spatie\Health\Checks\Check as SpatieCheck;
use Spatie\Health\Checks\Result as SpatieResult;

class CheckRunner
{
    /**
     * Run configured checks and return an Oh Dear compatible payload.
     *
     * Each check can be either:
     * - Spatie\Health\Checks\Check (run() => SpatieResult)
     * - OhDear\HealthCheckResults check (run() => OhDearCheckResult)
     * - class-string resolving to either of the above
     * - array{class: class-string, options?: array}
     *
     * @param array<int, mixed> $checks
     */
    public function run(array $checks): OhDearPayload
    {
        $results = [];
        $overallStatus = 'ok';

        // Keep a raw OhDear CheckResults for consumers that want it.
        $rawCheckResults = new CheckResults(new \DateTimeImmutable());

        foreach ($checks as $checkConfig) {
            [$check, $name, $options] = $this->makeCheck($checkConfig);

            try {
                if ($check instanceof SpatieCheck) {
                    $spatieResult = $check->run();
                    $status = $this->mapSpatieStatus($spatieResult);

                    $results[] = [
                        'name' => $name,
                        'label' => method_exists($check, 'getLabel') ? $check->getLabel() : $name,
                        'status' => $status,
                        'notification_message' => $spatieResult->notificationMessage ?? null,
                        'short_summary' => $spatieResult->shortSummary ?? null,
                        'meta' => $spatieResult->meta ?? [],
                    ];

                    $rawCheckResults->addCheckResult(new OhDearCheckResult(
                        $name,
                        $name,
                        (string) ($spatieResult->notificationMessage ?? $spatieResult->shortSummary ?? ''),
                        (string) ($spatieResult->shortSummary ?? ''),
                        $this->mapToOhDearStatus($status),
                        (array) ($spatieResult->meta ?? [])
                    ));

                    $overallStatus = $this->mergeOverallStatus($overallStatus, $status);

                    continue;
                }

                // OhDear-style check expected to return OhDearCheckResult.
                /** @var mixed $ohdearResult */
                $ohdearResult = $check->run();

                if (! $ohdearResult instanceof OhDearCheckResult) {
                    throw new \RuntimeException('Check did not return a supported result type.');
                }

                $rawCheckResults->addCheckResult($ohdearResult);

                $status = $this->mapOhDearStatus((string) $ohdearResult->status);

                $results[] = [
                    'name' => $ohdearResult->name !== '' ? $ohdearResult->name : $name,
                    'label' => $ohdearResult->label !== '' ? $ohdearResult->label : $name,
                    'status' => $status,
                    'notification_message' => $ohdearResult->notificationMessage,
                    'short_summary' => $ohdearResult->shortSummary,
                    'meta' => $ohdearResult->meta,
                ];

                $overallStatus = $this->mergeOverallStatus($overallStatus, $status);
            } catch (Throwable $e) {
                $results[] = [
                    'name' => $name,
                    'label' => $name,
                    'status' => 'failed',
                    'notification_message' => $e->getMessage(),
                    'short_summary' => null,
                    'meta' => [
                        'exception' => get_class($e),
                    ],
                ];

                $rawCheckResults->addCheckResult(new OhDearCheckResult(
                    $name,
                    $name,
                    $e->getMessage(),
                    $e->getMessage(),
                    OhDearCheckResult::STATUS_FAILED,
                    ['exception' => get_class($e)]
                ));

                $overallStatus = 'failed';
            }
        }

        $httpStatus =  200;

        return new OhDearPayload(
            status: $overallStatus,
            finishedAtIso8601: now()->toIso8601String(),
            checks: $results,
            httpStatus: $httpStatus,
            rawCheckResults: $rawCheckResults,
        );
    }

    /**
     * @param mixed $checkConfig
     * @return array{0: object, 1: string, 2: array<string,mixed>}
     */
    protected function makeCheck(mixed $checkConfig): array
    {
        $class = is_array($checkConfig) ? Arr::get($checkConfig, 'class') : $checkConfig;
        $options = is_array($checkConfig) ? Arr::get($checkConfig, 'options', []) : [];

        $check = is_object($class) ? $class : app($class);

        // Apply known options in a safe way (only if the check supports the method).
        if (isset($options['warning_threshold_percentage']) && method_exists($check, 'warnWhenUsedSpaceIsAbovePercentage')) {
            $check->warnWhenUsedSpaceIsAbovePercentage((float) $options['warning_threshold_percentage']);
        }

        if (isset($options['error_threshold_percentage']) && method_exists($check, 'failWhenUsedSpaceIsAbovePercentage')) {
            $check->failWhenUsedSpaceIsAbovePercentage((float) $options['error_threshold_percentage']);
        }

        if (isset($options['disk_path']) && method_exists($check, 'onDisk')) {
            $check->onDisk((string) $options['disk_path']);
        }

        if (isset($options['max_log_size_mb']) && method_exists($check, 'failWhenLogSizeIsLargerInMb')) {
            $check->failWhenLogSizeIsLargerInMb((int) $options['max_log_size_mb']);
        }

        if (isset($options['warning_log_size_mb']) && method_exists($check, 'warnWhenLogSizeIsLargerInMb')) {
            $check->warnWhenLogSizeIsLargerInMb((int) $options['warning_log_size_mb']);
        }

        $name = is_string($class) ? class_basename($class) : (is_object($check) ? class_basename($check) : 'UnknownCheck');

        return [$check, $name, $options];
    }

    protected function mapSpatieStatus(SpatieResult $result): string
    {
        $status = strtolower((string) ($result->status ?? 'ok'));

        return match ($status) {
            'ok', 'healthy' => 'ok',
            'warning' => 'warning',
            'failed', 'crashed', 'error' => 'failed',
            default => $status,
        };
    }

    protected function mapOhDearStatus(string $status): string
    {
        $status = strtolower($status);

        return match ($status) {
            'ok' => 'ok',
            'warning' => 'warning',
            'failed', 'crashed' => 'failed',
            default => $status,
        };
    }

    protected function mapToOhDearStatus(string $status): string
    {
        return match ($status) {
            'ok' => OhDearCheckResult::STATUS_OK,
            'warning' => OhDearCheckResult::STATUS_WARNING,
            default => OhDearCheckResult::STATUS_FAILED,
        };
    }

    protected function mergeOverallStatus(string $overall, string $current): string
    {
        if ($overall === 'failed' || $current === 'failed') {
            return 'failed';
        }

        if ($overall === 'warning' || $current === 'warning') {
            return 'warning';
        }

        return 'ok';
    }
}

