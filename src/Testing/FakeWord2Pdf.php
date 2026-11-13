<?php

namespace Noop\Documents\Testing;

use Noop\Documents\PendingTemplate;
use Noop\Documents\Variables;
use Noop\Documents\Word2Pdf;

final class FakeWord2Pdf extends Word2Pdf
{
    use RecordsGenerations;

    private readonly FakeClient $fakeClient;

    /** Calls are recorded when the method is called, not when the document is produced. */
    public function __construct()
    {
        $this->fakeClient = new FakeClient;

        parent::__construct($this->fakeClient);
    }

    public function fill(string $path, array $variables = []): PendingTemplate
    {
        $this->record(['type' => 'fill', 'path' => $path, 'variables' => $variables]);

        // No file is read, but the variables still go through the real validation.
        Variables::normalize($variables);

        return new PendingTemplate($this->fakeClient, '', '', []);
    }

    public function fromHtml(string $html, ?string $referencePath = null, array $images = []): PendingTemplate
    {
        $this->record([
            'type' => 'fromHtml',
            'html' => $html,
            'reference' => $referencePath,
            'images' => array_map(basename(...), array_keys($images)),
        ]);

        return new PendingTemplate($this->fakeClient, '', null, []);
    }
}
