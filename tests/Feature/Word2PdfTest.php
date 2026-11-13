<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Noop\Documents\Facades\WordTemplate;
use Noop\Documents\PdfA;
use Noop\Documents\Variables;

function withWord2Pdf(?string $key = null): void
{
    config(['documents.word2pdf.url' => 'https://word2pdf.test', 'documents.word2pdf.api_key' => $key]);
}

function docxFile(string $contents = 'PK-fake'): string
{
    $path = tempnam(sys_get_temp_dir(), 'tpl');
    file_put_contents($path, $contents);
    // Deleted when the process ends, so tests need no cleanup of their own.
    register_shutdown_function(fn () => @unlink($path));

    return $path;
}

it('normalizes variables into bracket-notation text fields', function () {
    expect(Variables::normalize([
        'nothing' => null,
        'off' => false,
        'on' => true,
        'count' => 3,
        'price' => 9.5,
        'client' => ['name' => 'Ana', 'address' => ['city' => 'Porto']],
        'points' => [['title' => 'A'], ['title' => 'B']],
        'empty' => [],
    ]))->toBe([
        'nothing' => '',
        'off' => '',
        'on' => '1',
        'count' => '3',
        'price' => '9.5',
        'client[name]' => 'Ana',
        'client[address][city]' => 'Porto',
        'points[0][title]' => 'A',
        'points[1][title]' => 'B',
        'empty' => '',
    ])->and(fn () => Variables::normalize(['rows' => [new stdClass]]))
        ->toThrow(InvalidArgumentException::class, 'rows[0]')
        ->and(fn () => Variables::normalize(['a' => ['b]' => 'x']]))
        ->toThrow(InvalidArgumentException::class, 'Template variable key [b]] must not contain brackets.');
});

it('fills a template into a PDF/A with the API key', function () {
    withWord2Pdf('fake-key');
    Http::fake(['*' => Http::response('%PDF-1.7 fake')]);

    WordTemplate::fill(docxFile(), ['signed' => false, 'client' => ['name' => 'Ana']])->toPdf(PdfA::A2b);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://word2pdf.test/generator_word_to_pdf_var?pdfa=PDF%2FA-2b'
        && $request->header('X-API-Key') === ['fake-key']
        && $request->hasFile('file', 'PK-fake', 'template.docx')
        && fields($request) === ['signed' => '', 'client[name]' => 'Ana']);
});

it('fills a template into a docx without PDF/A or API key', function () {
    withWord2Pdf();
    Http::fake(['*' => Http::response('PK fake docx')]);

    expect(WordTemplate::fill(docxFile())->toDocx()->bytes())->toBe('PK fake docx');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://word2pdf.test/generator_word_var'
        && ! $request->hasHeader('X-API-Key'));
});

it('converts HTML with a reference document and images into a docx', function () {
    withWord2Pdf();
    Http::fake(['*' => Http::response('PK fake docx')]);

    WordTemplate::fromHtml('<img src="logo.png">', docxFile('PK-ref'), ['logo.png' => 'png', 'img/photo.jpg' => 'jpg'])->toDocx();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://word2pdf.test/generator_html_to_word'
        && $request->hasFile('html', '<img src="logo.png">', 'body.html')
        && $request->hasFile('reference', 'PK-ref', 'reference.docx')
        && $request->hasFile('images', 'png', 'logo.png')
        && $request->hasFile('images', 'jpg', 'photo.jpg'));
});
