<?php

namespace Noop\Documents;

use LogicException;

final readonly class PendingTemplate
{
    /**
     * @param  list<array{string, string, string}>  $files
     * @param  array<string, string>  $fields
     * @param  ?string  $pdfPath  null when the service has no PDF route for this input
     */
    public function __construct(
        private ServiceClient $client,
        private string $docxPath,
        private ?string $pdfPath,
        private array $files,
        private array $fields = [],
    ) {}

    public function toDocx(): Document
    {
        return $this->client->post($this->docxPath, $this->files, $this->fields, Format::Docx);
    }

    public function toPdf(?PdfA $pdfA = null): Document
    {
        if ($this->pdfPath === null) {
            throw new LogicException('word2pdf cannot convert HTML to PDF. Use toDocx(), or Pdf::html() for a PDF.');
        }

        return $this->client->post($this->pdfPath, $this->files, $this->fields, Format::Pdf, $pdfA ? ['pdfa' => $pdfA->value] : []);
    }
}
