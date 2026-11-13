<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Noop\Documents\DocumentException;
use Noop\Documents\Facades\Pdf;

const PDF = '%PDF-1.7 fake';

/** @return array<string, string> */
function fields(Request $request): array
{
    return collect($request->data())->whereNull('filename')->pluck('contents', 'name')->all();
}

function withGotenberg(): void
{
    config(['documents.gotenberg.url' => 'https://gotenberg.test/']);
}

it('fails without sending anything when the URL is missing', function () {
    Http::fake();

    expect(fn () => Pdf::html('<p>x</p>')->bytes())
        ->toThrow(DocumentException::class, 'Set GOTENBERG_URL in .env.');

    Http::assertNothingSent();
});

it('retries a 503 until a PDF comes back', function () {
    withGotenberg();
    Sleep::fake();
    Http::fakeSequence()->push('busy', 503)->push('busy', 503)->push(PDF);

    expect(Pdf::html('<p>x</p>')->bytes())->toBe(PDF);

    Http::assertSentCount(3);
});

it('does not retry other errors and reports the service message', function () {
    withGotenberg();
    Http::fake(['*' => Http::response(['message' => 'Chromium crashed'], 500)]);

    $error = null;

    try {
        Pdf::html('<p>x</p>')->bytes();
    } catch (DocumentException $error) {
    }

    expect($error?->status)->toBe(500)
        ->and($error?->getMessage())->toContain('Gotenberg', '500', 'Chromium crashed');

    Http::assertSentCount(1);
});

it('rejects a successful response that is not a PDF', function () {
    withGotenberg();
    Http::fake(['*' => Http::response('<html>proxy page</html>')]);

    expect(fn () => Pdf::html('<p>x</p>')->bytes())
        ->toThrow(DocumentException::class, 'unexpected response');
});

it('sends HTML with header, footer, paper, orientation and margins', function () {
    withGotenberg();
    Http::fake(['*' => Http::response(PDF)]);

    Pdf::html('<p>body</p>')
        ->header('<p>head</p>')
        ->footer('<p>foot</p>')
        ->paper('letter')
        ->landscape()
        ->margins(15, 10, 20.5, 10)
        ->bytes();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://gotenberg.test/forms/chromium/convert/html'
        && $request->hasFile('files', '<p>body</p>', 'index.html')
        && $request->hasFile('files', '<p>head</p>', 'header.html')
        && $request->hasFile('files', '<p>foot</p>', 'footer.html')
        && fields($request) === [
            'paperWidth' => '8.5in',
            'paperHeight' => '11in',
            'printBackground' => 'true',
            'marginTop' => '15mm',
            'marginRight' => '10mm',
            'marginBottom' => '20.5mm',
            'marginLeft' => '10mm',
            'landscape' => 'true',
        ]);
});

it('converts office files and merges PDFs in the given order', function () {
    withGotenberg();
    Http::fake(['*' => Http::response(PDF)]);

    Pdf::office('docx bytes', '/tmp/uploads/anexo.docx');
    Pdf::merge(['%PDF-B', Pdf::office('a', 'a.docx')]);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://gotenberg.test/forms/libreoffice/convert'
        && $request->hasFile('files', 'docx bytes', 'anexo.docx'));

    Http::assertSent(fn (Request $request) => $request->url() === 'https://gotenberg.test/forms/pdfengines/merge'
        && collect($request->data())->map(fn ($part) => [$part['filename'], $part['contents']])->all() === [
            ['001.pdf', '%PDF-B'],
            ['002.pdf', PDF],
        ]);
});
