<?php

namespace Noop\Documents\Testing;

use Noop\Documents\Document;
use Noop\Documents\Gotenberg;
use Noop\Documents\PendingPdf;

final class FakeGotenberg extends Gotenberg
{
    use RecordsGenerations;

    /** Calls are recorded when the method is called, not when the document is produced. */
    public function __construct()
    {
        parent::__construct(new FakeClient);
    }

    public function view(string $view, array $data = []): PendingPdf
    {
        $this->record(['type' => 'view', 'view' => $view, 'data' => $data]);

        // The view is not rendered, so tests need no real templates.
        return parent::html('');
    }

    public function html(string $html): PendingPdf
    {
        $this->record(['type' => 'html', 'html' => $html]);

        return parent::html($html);
    }

    public function office(string $contents, string $filename): Document
    {
        $this->record(['type' => 'office', 'filename' => $filename]);

        return parent::office($contents, $filename);
    }

    public function merge(array $pdfs): Document
    {
        $this->record(['type' => 'merge', 'count' => count($pdfs)]);

        return parent::merge($pdfs);
    }
}
