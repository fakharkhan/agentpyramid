<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirecrawlService
{
    public function __construct(
        protected string $apiKey,
        protected string $baseUrl = 'https://api.firecrawl.dev/v2'
    ) {}

    /**
     * Crawl a URL and extract content including categories, details, and images.
     *
     * @param  string  $url  The URL to crawl
     * @param  array<string, mixed>  $options  Additional crawl options
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function crawl(string $url, array $options = []): array
    {
        // Convert maxDepth to maxDiscoveryDepth for v2 API
        $maxDepth = $options['maxDepth'] ?? $options['maxDiscoveryDepth'] ?? 10;
        unset($options['maxDepth']);

        $defaultOptions = [
            'crawlEntireDomain' => true,
            'scrapeOptions' => [
                'formats' => [
                    ['type' => 'markdown'],
                    ['type' => 'html'],
                ],
            ],
            'maxDiscoveryDepth' => $maxDepth,
        ];

        $payload = array_merge($defaultOptions, $options, ['url' => $url]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/crawl", $payload);

            $response->throw();

            return $response->json();
        } catch (RequestException $e) {
            Log::error('Firecrawl API error', [
                'url' => $url,
                'error' => $e->getMessage(),
                'response' => $e->response?->json(),
            ]);

            throw $e;
        }
    }

    /**
     * Scrape a single URL without crawling.
     *
     * @param  string  $url  The URL to scrape
     * @param  array<string, mixed>  $options  Additional scrape options
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function scrape(string $url, array $options = []): array
    {
        $defaultOptions = [];

        // Convert simple format strings to v2 format objects if needed
        if (! isset($options['formats'])) {
            $defaultOptions = [
                'formats' => [
                    ['type' => 'markdown'],
                    ['type' => 'html'],
                ],
            ];
        } elseif (is_array($options['formats'])) {
            $formats = [];
            foreach ($options['formats'] as $format) {
                if (is_string($format)) {
                    $formats[] = ['type' => $format];
                } else {
                    $formats[] = $format;
                }
            }
            $options['formats'] = $formats;
        }

        $payload = array_merge($defaultOptions, $options, ['url' => $url]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/scrape", $payload);

            $response->throw();

            return $response->json();
        } catch (RequestException $e) {
            Log::error('Firecrawl scrape API error', [
                'url' => $url,
                'error' => $e->getMessage(),
                'response' => $e->response?->json(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the status and results of a crawl job.
     *
     * @param  string  $jobId  The crawl job ID
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function getCrawlStatus(string $jobId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/crawl/{$jobId}");

            $response->throw();

            return $response->json();
        } catch (RequestException $e) {
            Log::error('Firecrawl get crawl status error', [
                'jobId' => $jobId,
                'error' => $e->getMessage(),
                'response' => $e->response?->json(),
            ]);

            throw $e;
        }
    }
}
