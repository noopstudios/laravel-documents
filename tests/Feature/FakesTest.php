<?php

use Illuminate\Support\Facades\Http;
use Noop\Documents\Facades\Pdf;
use Noop\Documents\Facades\WordTemplate;
use PHPUnit\Framework\AssertionFailedError;

it('fakes PDF generation without HTTP, a URL or the view', function () {
    Http::fake();
    $fake = Pdf::fake();
    $fake->assertNothingGenerated();

    /** @var view-string $missing */
    $missing = 'some.view';
    $bytes = Pdf::view($missing, ['a' => 1])->bytes();

    expect($bytes)->toStartWith('%PDF');
    Http::assertNothingSent();
    Pdf::assertGenerated('view', fn ($call) => $call['view'] === 'some.view' && $call['data'] === ['a' => 1]);
    expect(fn () => Pdf::assertGenerated('office'))->toThrow(AssertionFailedError::class);
});

it('fakes Word templates without HTTP or a template file', function () {
    Http::fake();
    WordTemplate::fake();

    $template = WordTemplate::fill('/does/not/exist.docx', ['x' => false]);

    expect($template->toPdf()->bytes())->toStartWith('%PDF')
        ->and($template->toDocx()->bytes())->toStartWith('PK');
    Http::assertNothingSent();
    WordTemplate::assertGenerated('fill', fn ($call) => $call['variables'] === ['x' => false]);
});
