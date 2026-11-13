<?php

namespace Noop\Documents\Testing;

use Noop\Documents\Document;
use Noop\Documents\Format;
use Noop\Documents\ServiceClient;

final class FakeClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct('fake', '', null, 0);
    }

    public function post(string $path, array $files, array $fields, Format $expect, array $query = []): Document
    {
        return new Document($expect === Format::Pdf ? '%PDF-1.4 fake' : 'PK fake', $expect->value);
    }
}
