<?php

namespace Devskio\LaravelOhdearHealthCheck\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class UsedDiskSpaceCheck extends Check
{
    protected float $warningThreshold = 70;
    protected float $errorThreshold = 90;
    protected string $diskPath = '';

    public function warnWhenUsedSpaceIsAbovePercentage(float $percentage): self
    {
        $this->warningThreshold = $percentage;

        return $this;
    }

    public function failWhenUsedSpaceIsAbovePercentage(float $percentage): self
    {
        $this->errorThreshold = $percentage;

        return $this;
    }

    public function onDisk(string $path): self
    {
        $this->diskPath = $path;

        return $this;
    }

    public function run(): Result
    {
        $path = $this->diskPath !== '' ? $this->diskPath : base_path();

        try {
            $diskSpaceUsedPercentage = $this->getDiskUsagePercentage($path);
        } catch (\Throwable $e) {
            return Result::make()
                ->meta(['path' => $path])
                ->warning("Could not determine disk usage for path '{$path}': {$e->getMessage()}");
        }

        if ($diskSpaceUsedPercentage === null) {
            return Result::make()
                ->meta(['path' => $path])
                ->warning("Could not determine disk usage for path '{$path}'. The path may not be accessible.");
        }

        $result = Result::make()
            ->meta(['used_space_percentage' => $diskSpaceUsedPercentage, 'path' => $path])
            ->shortSummary("{$diskSpaceUsedPercentage}%");

        if ($diskSpaceUsedPercentage > $this->errorThreshold) {
            return $result->failed("The disk is almost full ({$diskSpaceUsedPercentage}% used).");
        }

        if ($diskSpaceUsedPercentage > $this->warningThreshold) {
            return $result->warning("The disk is getting full ({$diskSpaceUsedPercentage}% used).");
        }

        return $result->ok();
    }

    protected function getDiskUsagePercentage(string $path): ?float
    {
        $totalSpace = @disk_total_space($path);
        $freeSpace = @disk_free_space($path);

        if ($totalSpace === false || $freeSpace === false || $totalSpace <= 0) {
            return null;
        }

        $usedSpace = $totalSpace - $freeSpace;

        return round(($usedSpace / $totalSpace) * 100, 2);
    }
}

