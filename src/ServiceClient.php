<?php

namespace Noop\Documents;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class ServiceClient
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        private string $service,
        private string $urlEnv,
        private ?string $url,
        private int $timeout,
        private array $headers = [],
    ) {}

    /**
     * @param  list<array{string, string, string}>  $files  [field name, contents, filename]
     * @param  array<string, string>  $fields
     * @param  array<string, string>  $query
     */
    public function post(string $path, array $files, array $fields, Format $expect, array $query = []): Document
    {
        if ($this->url === null || $this->url === '') {
            throw new DocumentException("{$this->service} URL is not configured. Set {$this->urlEnv} in .env.");
        }

        $request = Http::asMultipart()
            ->withHeaders($this->headers)
            ->withQueryParameters($query)
            ->timeout($this->timeout)
            ->connectTimeout(15)
            // Only 503 (busy) is retried: anything else, timeouts included, must not re-run a conversion.
            ->retry([500, 1000], when: fn (Throwable $e) => $e instanceof RequestException && $e->response->status() === 503, throw: false);

        foreach ($files as [$name, $contents, $filename]) {
            $request->attach($name, $contents, $filename);
        }

        try {
            $response = $request->post(rtrim($this->url, '/').$path, $fields);
        } catch (ConnectionException $e) {
            throw new DocumentException("{$this->service} could not be reached.", previous: $e);
        }

        if (! $response->successful()) {
            $message = $response->json('message');
            $message = is_string($message) ? $message : mb_substr($response->body(), 0, 200);

            throw new DocumentException("{$this->service} failed with status {$response->status()}: {$message}", $response->status());
        }

        if (! str_starts_with($response->body(), $expect->signature())) {
            throw new DocumentException("{$this->service} returned an unexpected response (not a {$expect->name}).", $response->status());
        }

        return new Document($response->body(), $expect->value);
    }
}
