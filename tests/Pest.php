<?php

use Illuminate\Http\Client\Request;
use Noop\Documents\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/** @return array<string, string> */
function fields(Request $request): array
{
    return collect($request->data())->whereNull('filename')->pluck('contents', 'name')->all();
}
