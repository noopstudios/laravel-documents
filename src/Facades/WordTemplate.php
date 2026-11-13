<?php

namespace Noop\Documents\Facades;

use Illuminate\Support\Facades\Facade;
use Noop\Documents\Testing\FakeWord2Pdf;
use Noop\Documents\Word2Pdf;

/**
 * @method static \Noop\Documents\PendingTemplate fill(string $path, array<array-key, mixed> $variables = [])
 * @method static \Noop\Documents\PendingTemplate fromHtml(string $html, ?string $referenceDocx = null, array<string, string> $images = [])
 * @method static void assertGenerated(string $type, ?\Closure $filter = null)
 * @method static void assertNothingGenerated()
 *
 * @see Word2Pdf
 */
final class WordTemplate extends Facade
{
    /** Replace the service with a fake that makes no HTTP calls and records what was generated. */
    public static function fake(): FakeWord2Pdf
    {
        self::swap($fake = new FakeWord2Pdf);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return Word2Pdf::class;
    }
}
