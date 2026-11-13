<?php

namespace Noop\Documents;

final readonly class Gotenberg
{
    public function __construct(private ServiceClient $client) {}

    /**
     * @param  view-string  $view
     * @param  array<string, mixed>  $data
     */
    public function view(string $view, array $data = []): PendingPdf
    {
        return $this->html(view($view, $data)->render());
    }

    public function html(string $html): PendingPdf
    {
        return new PendingPdf($this->client, $html);
    }

    public function office(string $contents, string $filename): Document
    {
        // Gotenberg detects the input format from the file extension.
        return $this->client->post('/forms/libreoffice/convert', [['files', $contents, basename($filename)]], [], Format::Pdf);
    }

    /**
     * @param  list<string|Document>  $pdfs
     */
    public function merge(array $pdfs): Document
    {
        $files = [];

        // Gotenberg merges in alphabetical filename order, so the names carry the order.
        foreach ($pdfs as $i => $pdf) {
            $files[] = ['files', $pdf instanceof Document ? $pdf->bytes() : $pdf, sprintf('%03d.pdf', $i + 1)];
        }

        return $this->client->post('/forms/pdfengines/merge', $files, [], Format::Pdf);
    }
}
