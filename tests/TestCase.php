<?php

namespace Noop\Documents\Tests;

use Noop\Documents\DocumentsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [DocumentsServiceProvider::class];
    }

    /** Keeps a developer's shell environment out of the normal suite. */
    protected function defineEnvironment($app): void
    {
        $app['config']->set([
            'documents.gotenberg.url' => null,
            'documents.word2pdf.url' => null,
            'documents.word2pdf.api_key' => null,
        ]);
    }
}
