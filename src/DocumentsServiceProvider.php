<?php

namespace Noop\Documents;

use Illuminate\Support\ServiceProvider;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/documents.php', 'documents');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/documents.php' => config_path('documents.php'),
        ], 'documents-config');
    }
}
