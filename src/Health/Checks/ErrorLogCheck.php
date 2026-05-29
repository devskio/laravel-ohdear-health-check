<?php

namespace Devskio\LaravelOhdearHealthCheck\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Illuminate\Support\Facades\File;

class ErrorLogCheck extends Check
{
    protected ?int $warningLogSizeInMb = null;
    protected int $maxLogSizeInMb = 50;

    public function warnWhenLogSizeIsLargerInMb(int $warningLogSizeInMb): self
    {
        $this->warningLogSizeInMb = $warningLogSizeInMb;

        return $this;
    }

    public function failWhenLogSizeIsLargerInMb(int $maxLogSizeInMb): self
    {
        $this->maxLogSizeInMb = $maxLogSizeInMb;

        return $this;
    }

    public function run(): Result
    {
        $logFile = $this->getLogFile();

        if (! $logFile || ! File::exists($logFile)) {
            return Result::make()->ok()->shortSummary('Log file not found or driver not supported.');
        }

        $logSizeInMb = round(File::size($logFile) / 1024 / 1024, 2);

        $result = Result::make()->meta(['log_size_in_mb' => $logSizeInMb]);

        if ($logSizeInMb > $this->maxLogSizeInMb) {
            return $result->failed("The log file size is {$logSizeInMb} MB, which is larger than the allowed {$this->maxLogSizeInMb} MB.");
        }

        if ($this->warningLogSizeInMb !== null && $logSizeInMb > $this->warningLogSizeInMb) {
            return $result->warning("The log file size is {$logSizeInMb} MB, which is larger than the warning threshold of {$this->warningLogSizeInMb} MB.");
        }

        return $result->ok()->shortSummary("Log file size is {$logSizeInMb} MB.");
    }

    protected function getLogFile(): ?string
    {
        $channel = config('logging.default');

        return $this->resolveChannelPath((string) $channel);
    }

    protected function resolveChannelPath(string $channel, int $depth = 0): ?string
    {
        // Prevent infinite recursion from misconfigured channels.
        if ($depth > 5) {
            return null;
        }

        $driver = config("logging.channels.{$channel}.driver");

        if ($driver === 'single' || $driver === 'daily') {
            return config("logging.channels.{$channel}.path");
        }

        // The default Laravel log channel is "stack"; resolve its first writable channel.
        if ($driver === 'stack') {
            $channels = (array) config("logging.channels.{$channel}.channels", []);
            foreach ($channels as $stackChannel) {
                $path = $this->resolveChannelPath((string) $stackChannel, $depth + 1);
                if ($path !== null) {
                    return $path;
                }
            }
        }

        return null;
    }
}

