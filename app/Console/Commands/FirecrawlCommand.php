<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Neuron\FirecrawlAgent;
use Illuminate\Console\Command;
use NeuronAI\Chat\Messages\UserMessage;

class FirecrawlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firecrawl:crawl 
                            {url : The URL to crawl}
                            {--max-depth=10 : Maximum depth to crawl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl a website using Firecrawl to extract categories, products, details, and images';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = $this->argument('url');
        $maxDepth = (int) $this->option('max-depth');

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error("Invalid URL: {$url}");

            return self::FAILURE;
        }

        $this->info("Starting crawl for: {$url}");

        if ($maxDepth !== 10) {
            $this->info("Max depth set to: {$maxDepth}");
        }

        try {
            $agent = FirecrawlAgent::make();

            $message = "Please crawl {$url} and extract all categories with complete details and images.";

            if ($maxDepth !== 10) {
                $message .= " Use a maximum depth of {$maxDepth}.";
            }

            $this->info('Sending request to agent...');
            $this->newLine();

            $response = $agent->chat(new UserMessage($message));

            $this->info('Response from agent:');
            $this->newLine();
            $this->line($response->getContent());

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error occurred: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
