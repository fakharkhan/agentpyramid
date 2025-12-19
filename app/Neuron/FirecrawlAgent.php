<?php

declare(strict_types=1);

namespace App\Neuron;

use App\Neuron\Tools\FirecrawlCrawlTool;
use App\Neuron\Tools\FirecrawlGetResultsTool;
use App\Services\FirecrawlService;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\SystemPrompt;

class FirecrawlAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Anthropic(
            key: config('services.anthropic.key'),
            model: config('services.anthropic.model'),
            max_tokens: 4096
        );
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a specialized web scraping agent powered by Firecrawl. Your primary function is to crawl websites and extract comprehensive data including categories, products, details, and images.',
                'When crawling a website, you MUST use the firecrawl_crawl tool with the url parameter set to the full URL (e.g., url: "https://www.aarons.com"). The tool requires the url parameter to be provided.',
                'The firecrawl_crawl tool returns a job ID. After calling firecrawl_crawl, you MUST use the firecrawl_get_results tool with the job_id parameter to retrieve the crawl results.',
                'CRITICAL: Crawls are asynchronous. If firecrawl_get_results returns status "scraping", you MUST wait 5-10 seconds and call firecrawl_get_results again with the same job_id. Keep checking repeatedly until the status is "completed" or "failed". Only when status is "completed" will the data be available.',
                'The tool will crawl the entire domain to get comprehensive data including all subpages and categories.',
                'The firecrawl_get_results tool returns a summary with URLs and metadata when completed, not full page content, to avoid message size limits. Use the summary to understand the site structure and identify categories. Summarize the findings based on the URLs and titles provided.',
            ],
        );
    }

    /**
     * @return \NeuronAI\Tools\ToolInterface[]
     */
    protected function tools(): array
    {
        $firecrawlService = new FirecrawlService(
            apiKey: config('services.firecrawl.key'),
            baseUrl: config('services.firecrawl.base_url')
        );

        // Increase max tries for tools since crawls can take time
        $this->toolMaxTries(50);

        return [
            new FirecrawlCrawlTool($firecrawlService),
            new FirecrawlGetResultsTool($firecrawlService),
        ];
    }
}
