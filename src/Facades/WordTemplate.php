<?php

namespace Noop\Documents\Facades;

use Illuminate\Support\Facades\Facade;
use Noop\Documents\Word2Pdf;

/**
 * @method static \Noop\Documents\PendingTemplate fill(string $path, array<array-key, mixed> $variables = [])
 * @method static \Noop\Documents\PendingTemplate fromHtml(string $html, ?string $referenceDocx = null, array<string, string> $images = [])
 *
 * @see Word2Pdf
 */
final class WordTemplate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Word2Pdf::class;
    }
}
