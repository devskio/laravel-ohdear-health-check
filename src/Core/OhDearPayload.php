<?php

namespace Devskio\LaravelOhdearHealthCheck\Core;

class OhDearPayload
{
    public function __construct(
        public readonly string $status,
        public readonly string $finishedAtIso8601,
        /** @var array<int, array<string, mixed>> */
        public readonly array $checks,
        public readonly int $httpStatus,
    ) {
    }

    /**
     * @return array{status:string, finished_at:string, checks:array<int, array<string,mixed>>}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'finished_at' => $this->finishedAtIso8601,
            'checks' => $this->checks,
        ];
    }
}

