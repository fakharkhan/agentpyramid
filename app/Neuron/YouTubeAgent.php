<?php

declare(strict_types=1);

namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\SystemPrompt;

class YouTubeAgent extends Agent
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
            background: ['You are a friendly AI Agent created with NeuronAI framework.'],
        );
    }
}
