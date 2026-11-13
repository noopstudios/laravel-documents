<?php

namespace Noop\Documents\Testing;

use Closure;
use PHPUnit\Framework\Assert;

trait RecordsGenerations
{
    /** @var list<array<string, mixed>> */
    private array $generated = [];

    /** @param  array<string, mixed>  $call */
    private function record(array $call): void
    {
        $this->generated[] = $call;
    }

    /** @param  ?Closure(array<string, mixed>): bool  $filter */
    public function assertGenerated(string $type, ?Closure $filter = null): void
    {
        $matches = array_filter(
            $this->generated,
            fn (array $call) => $call['type'] === $type && ($filter === null || $filter($call)),
        );

        Assert::assertNotEmpty($matches, "No [{$type}] document was generated.");
    }

    public function assertNothingGenerated(): void
    {
        Assert::assertEmpty($this->generated, 'Documents were generated unexpectedly.');
    }
}
