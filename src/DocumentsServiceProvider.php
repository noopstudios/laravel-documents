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
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/documents.php' => config_path('documents.php'),
        ], 'documents-config');
    }
}
