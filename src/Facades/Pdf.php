<?php

namespace Noop\Documents\Facades;

use Illuminate\Support\Facades\Facade;
use Noop\Documents\Gotenberg;

/**
 * @method static \Noop\Documents\PendingPdf view(view-string $view, array<string, mixed> $data = [])
 * @method static \Noop\Documents\PendingPdf html(string $html)
 * @method static \Noop\Documents\Document office(string $contents, string $filename)
 * @method static \Noop\Documents\Document merge(list<string|\Noop\Documents\Document> $pdfs)
 *
 * @see Gotenberg
 */
final class Pdf extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Gotenberg::class;
    }
}
