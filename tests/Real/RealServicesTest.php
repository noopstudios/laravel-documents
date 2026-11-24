<?php

use Illuminate\Support\Facades\File;
use Noop\Documents\Facades\Pdf;
use Noop\Documents\Facades\WordTemplate;
use Noop\Documents\PdfA;

// Calls the real services. Run with: vendor/bin/pest --group=real
// Reads GOTENBERG_URL, WORD2PDF_URL and WORD2PDF_API_KEY from the shell environment.

beforeEach(function () {
    config([
        'documents.gotenberg.url' => getenv('GOTENBERG_URL') ?: null,
        'documents.word2pdf.url' => getenv('WORD2PDF_URL') ?: null,
        'documents.word2pdf.api_key' => getenv('WORD2PDF_API_KEY') ?: null,
    ]);
});

function missing(string $service): bool
{
    return ! getenv($service === 'gotenberg' ? 'GOTENBERG_URL' : 'WORD2PDF_URL');
}

/** @param  list<string>  $paragraphs */
function docxTemplate(array $paragraphs): string
{
    $body = '';
    foreach ($paragraphs as $text) {
        $body .= '<w:p><w:r><w:t xml:space="preserve">'.$text.'</w:t></w:r></w:p>';
    }

    $path = tempnam(sys_get_temp_dir(), 'tpl');
    register_shutdown_function(fn () => @unlink($path));

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        .'<Default Extension="xml" ContentType="application/xml"/>'
        .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        .'</Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        .'</Relationships>');
    $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'</w:body></w:document>');
    $zip->close();

    return $path;
}

function sampleTemplate(): string
{
    return docxTemplate([
        'Name: {name}',
        'Flag: {#show}SHOWN{/show}',
        'Nothing: [{nothing}]',
        'Empty: [{#empty}X{/empty}]',
        'Items: {#items}{title};{/items}',
        'Nested: {#company}{name}{/company}',
    ]);
}

it('renders HTML with header, footer and margins through Gotenberg', function () {
    $pdf = Pdf::html('<h1>Body</h1>')
        ->header('<html><body><p style="font-size:10px">Header</p></body></html>')
        ->footer('<html><body><p style="font-size:10px">Footer</p></body></html>')
        ->margins(30, 15, 30, 15)
        ->bytes();

    expect($pdf)->toStartWith('%PDF');
})->skip(fn () => missing('gotenberg'), 'No gotenberg URL configured.');

it('merges PDFs through Gotenberg', function () {
    $merged = Pdf::merge([Pdf::html('<p>PageOne</p>')->get(), Pdf::html('<p>PageTwo</p>')->get()])->bytes();

    expect($merged)->toStartWith('%PDF')
        ->and(preg_match_all('/\/Type\s*\/Page[^s]/', $merged))->toBe(2);
})->skip(fn () => missing('gotenberg'), 'No gotenberg URL configured.');

it('converts a docx through Gotenberg', function () {
    $converted = Pdf::office(File::get(sampleTemplate()), 'anexo.docx');

    expect($converted->bytes())->toStartWith('%PDF');
})->skip(fn () => missing('gotenberg'), 'No gotenberg URL configured.');

it('fills a docx template with normalized variables', function () {
    $docx = WordTemplate::fill(sampleTemplate(), [
        'name' => 'Ana',
        'show' => false,
        'nothing' => null,
        'empty' => [],
        'items' => array_map(fn (int $i) => ['title' => "T{$i}"], range(1, 25)),
        'company' => ['name' => 'ACME'],
    ])->toDocx();

    $path = tempnam(sys_get_temp_dir(), 'out');
    register_shutdown_function(fn () => @unlink($path));
    file_put_contents($path, $docx->bytes());

    $zip = new ZipArchive;
    $zip->open($path);
    $text = strip_tags((string) $zip->getFromName('word/document.xml'));
    $zip->close();

    expect($text)
        ->toContain('Name: Ana')
        ->not()->toContain('SHOWN')
        ->toContain('Nothing: []')
        ->toContain('Empty: []')
        ->toContain('Items: '.implode('', array_map(fn (int $i) => "T{$i};", range(1, 25))))
        ->toContain('Nested: ACME')
        ->not()->toContain('undefined')
        ->not()->toContain('null')
        ->not()->toContain('false');
})->skip(fn () => missing('word2pdf'), 'No word2pdf URL configured.');

it('fills a docx template into a PDF/A', function () {
    $pdf = WordTemplate::fill(sampleTemplate(), [
        'name' => 'Ana', 'show' => true, 'nothing' => null, 'empty' => [],
        'items' => [['title' => 'T1']], 'company' => ['name' => 'ACME'],
    ])->toPdf(PdfA::A2b);

    expect($pdf->bytes())->toStartWith('%PDF')
        ->and($pdf->bytes())->toContain('pdfaid:part');
})->skip(fn () => missing('word2pdf'), 'No word2pdf URL configured.');

it('converts HTML into a docx', function () {
    expect(WordTemplate::fromHtml('<h1>Title</h1><p>Body</p>')->toDocx()->bytes())->toStartWith('PK');
})->skip(fn () => missing('word2pdf'), 'No word2pdf URL configured.');
