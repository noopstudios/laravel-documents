<?php

namespace Noop\Documents;

use LogicException;

final readonly class PendingTemplate
{
    /**
     * @param  list<array{string, string, string}>  $files
     * @param  array<string, string>  $fields
     * @param  ?string  $pdfRoute  null when the service has no PDF route for this input
     */
    public function __construct(
        private ServiceClient $client,
        private string $docxRoute,
        private ?string $pdfRoute,
        private array $files,
        private array $fields = [],
    ) {}

    public function toDocx(): Document
    {
        return $this->client->post($this->docxRoute, $this->files, $this->fields, Format::Docx);
    }

    public function toPdf(?PdfA $pdfA = null): Document
    {
        if ($this->pdfRoute === null) {
            throw new LogicException('word2pdf cannot convert HTML to PDF. Use toDocx(), or Pdf::html() for a PDF.');
        }

        return $this->client->post($this->pdfRoute, $this->files, $this->fields, Format::Pdf, $pdfA ? ['pdfa' => $pdfA->value] : []);
    }
}
