<?php

namespace Noop\Documents;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class PendingPdf
{
    private const array PAPER = [
        'A3' => ['297mm', '420mm'],
        'A4' => ['210mm', '297mm'],
        'A5' => ['148mm', '210mm'],
        'LETTER' => ['8.5in', '11in'],
        'LEGAL' => ['8.5in', '14in'],
    ];

    private ?string $header = null;

    private ?string $footer = null;

    private string $paper = 'A4';

    private bool $landscape = false;

    /** @var array<string, string> */
    private array $margins = [];

    public function __construct(private readonly ServiceClient $client, private readonly string $html) {}

    public function header(string $html): self
    {
        $this->header = $html;

        return $this;
    }

    public function footer(string $html): self
    {
        $this->footer = $html;

        return $this;
    }

    public function paper(string $size): self
    {
        $size = strtoupper($size);

        if (! isset(self::PAPER[$size])) {
            throw new InvalidArgumentException("Unknown paper size [{$size}].");
        }

        $this->paper = $size;

        return $this;
    }

    public function landscape(): self
    {
        $this->landscape = true;

        return $this;
    }

    /** All values in millimetres. */
    public function margins(float $top, float $right, float $bottom, float $left): self
    {
        $this->margins = [
            'marginTop' => $top.'mm',
            'marginRight' => $right.'mm',
            'marginBottom' => $bottom.'mm',
            'marginLeft' => $left.'mm',
        ];

        return $this;
    }

    public function get(): Document
    {
        $files = [['files', $this->html, 'index.html']];

        if ($this->header !== null) {
            $files[] = ['files', $this->header, 'header.html'];
        }

        if ($this->footer !== null) {
            $files[] = ['files', $this->footer, 'footer.html'];
        }

        [$width, $height] = self::PAPER[$this->paper];

        $fields = [
            'paperWidth' => $width,
            'paperHeight' => $height,
            'printBackground' => 'true',
            ...$this->margins,
        ];

        if ($this->landscape) {
            $fields['landscape'] = 'true';
        }

        return $this->client->post('/forms/chromium/convert/html', $files, $fields, Format::Pdf);
    }

    public function bytes(): string
    {
        return $this->get()->bytes();
    }

    public function download(string $filename): Response
    {
        return $this->get()->download($filename);
    }
}
