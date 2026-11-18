<?php

return [
    'gotenberg' => [
        'url' => env('GOTENBERG_URL'),
        'username' => env('GOTENBERG_USERNAME'),
        'password' => env('GOTENBERG_PASSWORD'),
        'timeout' => (int) env('GOTENBERG_TIMEOUT', 120),
    ],

    'word2pdf' => [
        'url' => env('WORD2PDF_URL'),
        'api_key' => env('WORD2PDF_API_KEY'),
        'timeout' => (int) env('WORD2PDF_TIMEOUT', 60),
    ],
];
