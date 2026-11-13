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
}
