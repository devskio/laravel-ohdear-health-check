<?php

namespace Devskio\LaravelOhdearHealthCheck\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class UsedDiskSpaceCheck extends Check
{
    protected float $warningThreshold = 70;
    protected float $errorThreshold = 90;
    protected string $diskPath = '/';

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
        $diskSpaceUsedPercentage = $this->getDiskUsagePercentage();

        $result = Result::make()
            ->meta(['used_space_percentage' => $diskSpaceUsedPercentage])
            ->shortSummary("{$diskSpaceUsedPercentage}%");

        if ($diskSpaceUsedPercentage > $this->errorThreshold) {
            return $result->failed("The disk is almost full ({$diskSpaceUsedPercentage}% used).");
        }

        if ($diskSpaceUsedPercentage > $this->warningThreshold) {
            return $result->warning("The disk is getting full ({$diskSpaceUsedPercentage}% used).");
        }

        return $result->ok();
    }

    protected function getDiskUsagePercentage(): float
    {
        $totalSpace = disk_total_space($this->diskPath);
        $freeSpace = disk_free_space($this->diskPath);

        if (! $totalSpace || ! $freeSpace) {
            return 0.0;
        }

        $usedSpace = $totalSpace - $freeSpace;

        return round(($usedSpace / $totalSpace) * 100, 2);
    }
}

