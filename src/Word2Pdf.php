<?php

namespace Noop\Documents;

use InvalidArgumentException;

class Word2Pdf
{
    public function __construct(private readonly ServiceClient $client) {}

    /**
     * @param  array<array-key, mixed>  $variables
     */
    public function fill(string $path, array $variables = []): PendingTemplate
    {
        // The service rejects a template whose filename does not end in .docx.
        $files = [['file', self::read($path), 'template.docx']];

        return new PendingTemplate($this->client, '/generator_word_var', '/generator_word_to_pdf_var', $files, Variables::normalize($variables));
    }

    /**
     * @param  array<string, string>  $images  filename => contents; the HTML refers to them by filename
     */
    public function fromHtml(string $html, ?string $referencePath = null, array $images = []): PendingTemplate
    {
        $files = [['html', $html, 'body.html']];

        if ($referencePath !== null) {
            $files[] = ['reference', self::read($referencePath), 'reference.docx'];
        }

        foreach ($images as $filename => $contents) {
            $files[] = ['images', $contents, basename($filename)];
        }

        return new PendingTemplate($this->client, '/generator_html_to_word', null, $files);
    }

    private static function read(string $path): string
    {
        $contents = is_file($path) && is_readable($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new InvalidArgumentException("Cannot read the file [{$path}].");
        }

        return $contents;
    }
}
