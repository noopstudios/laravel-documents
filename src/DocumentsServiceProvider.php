<?php

namespace Noop\Documents;

use Illuminate\Support\ServiceProvider;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/documents.php', 'documents');

        $this->app->singleton(Gotenberg::class, function () {
            $url = config('documents.gotenberg.url');

            return new Gotenberg(new ServiceClient(
                'Gotenberg',
                'GOTENBERG_URL',
                is_string($url) ? $url : null,
                config()->integer('documents.gotenberg.timeout'),
            ));
        });

        $this->app->singleton(Word2Pdf::class, function () {
            $url = config('documents.word2pdf.url');
            $key = config('documents.word2pdf.api_key');

            return new Word2Pdf(new ServiceClient(
                'word2pdf',
                'WORD2PDF_URL',
                is_string($url) ? $url : null,
                config()->integer('documents.word2pdf.timeout'),
                // The service only checks the key when it has one configured.
                is_string($key) && $key !== '' ? ['X-API-Key' => $key] : [],
            ));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/documents.php' => config_path('documents.php'),
        ], 'documents-config');
    }
}
