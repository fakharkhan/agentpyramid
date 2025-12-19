<?php

declare(strict_types=1);

namespace App\Neuron\Tools;

use App\Services\FirecrawlService;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class FirecrawlCrawlTool extends Tool
{
    public function __construct(
        protected FirecrawlService $firecrawlService
    ) {
        parent::__construct(
            name: 'firecrawl_crawl',
            description: 'Crawl a website URL to extract all categories, products, details, and images. This tool will crawl the entire domain to get comprehensive data including all subpages and categories. You MUST provide the url parameter with the full URL to crawl.',
        );
    }

    /**
     * @return ToolProperty[]
     */
    protected function properties(): array
    {
        return [
            ToolProperty::make(
                name: 'url',
                type: PropertyType::STRING,
                description: 'REQUIRED: The full URL to crawl (e.g., https://www.aarons.com). Must be a valid HTTP/HTTPS URL starting with http:// or https://.',
                required: true
            ),
            ToolProperty::make(
                name: 'max_depth',
                type: PropertyType::INTEGER,
                description: 'Maximum depth to crawl. Default is 10. Higher values will crawl more pages but take longer.',
                required: false
            ),
        ];
    }

    /**
     * Execute the tool callback.
     *
     * @param  string  $url  The URL to crawl
     * @param  ?int  $max_depth  Maximum crawl depth
     * @return array<string, mixed>
     */
    public function __invoke(string $url, ?int $max_depth = null): array
    {
        $options = [];

        if ($max_depth !== null) {
            $options['maxDiscoveryDepth'] = $max_depth;
        }

        $result = $this->firecrawlService->crawl($url, $options);

        // v2 API returns async job with id - return the job info
        return [
            'success' => $result['success'] ?? true,
            'id' => $result['id'] ?? null,
            'url' => $result['url'] ?? $url,
            'message' => 'Crawl job started. Use the crawl ID to check status.',
        ];
    }
}
