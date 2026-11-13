<?php

namespace Noop\Documents\Facades;

use Illuminate\Support\Facades\Facade;
use Noop\Documents\Gotenberg;
use Noop\Documents\Testing\FakeGotenberg;

/**
 * @method static \Noop\Documents\PendingPdf view(view-string $view, array<string, mixed> $data = [])
 * @method static \Noop\Documents\PendingPdf html(string $html)
 * @method static \Noop\Documents\Document office(string $contents, string $filename)
 * @method static \Noop\Documents\Document merge(array<array-key, string|\Noop\Documents\Document> $pdfs)
 * @method static void assertGenerated(string $type, ?\Closure $filter = null)
 * @method static void assertNothingGenerated()
 *
 * @see Gotenberg
 */
final class Pdf extends Facade
{
    /** Replace the service with a fake that makes no HTTP calls and records what was generated. */
    public static function fake(): FakeGotenberg
    {
        self::swap($fake = new FakeGotenberg);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return Gotenberg::class;
    }
}
