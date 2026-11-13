<?php

it('loads the default config', function () {
    expect(config('documents.gotenberg.url'))->toBeNull()
        ->and(config('documents.gotenberg.timeout'))->toBe(120);
});
