<?php

namespace Noop\Documents;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

final readonly class Document
{
    public function __construct(private string $bytes, private string $mimeType) {}

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function download(string $filename): Response
    {
        return new Response($this->bytes, 200, [
            'Content-Type' => $this->mimeType,
            // Symfony rejects non-ASCII filenames without an ASCII fallback.
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $filename, Str::ascii($filename)),
        ]);
    }
}
