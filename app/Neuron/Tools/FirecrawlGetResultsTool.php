<?php

declare(strict_types=1);

namespace App\Neuron\Tools;

use App\Services\FirecrawlService;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class FirecrawlGetResultsTool extends Tool
{
    public function __construct(
        protected FirecrawlService $firecrawlService
    ) {
        parent::__construct(
            name: 'firecrawl_get_results',
            description: 'Get the status and results of a crawl job. IMPORTANT: If status is "scraping" or "pending", you MUST call this tool again after a few seconds to check again. Only when status is "completed" will the data be available. Keep calling this tool with the same job_id until status is "completed".',
        );

        // Set higher max tries since crawls can take time
        $this->setMaxTries(50);
    }

    /**
     * @return ToolProperty[]
     */
    protected function properties(): array
    {
        return [
            ToolProperty::make(
                name: 'job_id',
                type: PropertyType::STRING,
                description: 'REQUIRED: The crawl job ID returned by the firecrawl_crawl tool.',
                required: true
            ),
        ];
    }

    /**
     * Execute the tool callback.
     *
     * @param  string  $job_id  The crawl job ID
     * @return array<string, mixed>
     */
    public function __invoke(string $job_id): array
    {
        $result = $this->firecrawlService->getCrawlStatus($job_id);
        $status = $result['status'] ?? 'unknown';
        $data = $result['data'] ?? [];
        $total = $result['total'] ?? 0;
        $completed = $result['completed'] ?? count($data);
        $totalPages = count($data);

        // If still in progress, return early with clear instructions
        // API status can be: 'scraping', 'completed', or 'failed'
        if ($status === 'scraping') {
            return [
                'success' => true,
                'status' => $status,
                'job_id' => $job_id,
                'total_pages' => $total,
                'completed_pages' => $completed,
                'message' => "Crawl is still in progress (status: {$status}, {$completed}/{$total} pages completed). Wait 5-10 seconds, then call this tool again with the same job_id to check status. Large crawls can take several minutes. Keep checking until status is 'completed'.",
            ];
        }

        // Handle failed status
        if ($status === 'failed') {
            return [
                'success' => false,
                'status' => $status,
                'job_id' => $job_id,
                'message' => 'Crawl job failed. Please check the error details or try starting a new crawl.',
            ];
        }

        // Summarize the data to avoid overwhelming the chat history
        // Only return URLs and metadata, not full content
        $summary = [];
        if (is_array($data)) {
            foreach ($data as $index => $page) {
                if ($index >= 50) {
                    // Limit to first 50 pages for summary
                    break;
                }
                $summary[] = [
                    'url' => $page['url'] ?? $page['sourceURL'] ?? 'unknown',
                    'title' => $page['metadata']['title'] ?? $page['title'] ?? null,
                    'has_content' => isset($page['markdown']) || isset($page['html']),
                ];
            }
        }

        return [
            'success' => true,
            'status' => $status,
            'total_pages' => $total,
            'completed_pages' => $completed,
            'summary' => $summary,
            'has_more_data' => isset($result['next']),
            'next_url' => $result['next'] ?? null,
            'message' => $totalPages > 50
                ? "Crawl completed with {$total} pages. Showing first 50 URLs. Full content available but not included in this summary to avoid message size limits."
                : "Crawl completed with {$total} pages. Full content not included in summary to avoid message size limits.",
        ];
    }
}
