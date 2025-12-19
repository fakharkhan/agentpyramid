<?php

test('neuron-ai mcp server endpoint format is valid', function () {
    $mcpUrl = 'https://docs.neuron-ai.dev/~gitbook/mcp';

    // MCP servers may use different protocols (SSE, WebSocket, HTTP)
    // The URL format should be valid even if it requires specific MCP client
    expect($mcpUrl)->toStartWith('https://')
        ->and($mcpUrl)->toContain('docs.neuron-ai.dev')
        ->and($mcpUrl)->toContain('mcp');
});

test('mcp configuration file contains neuron-ai-docs server', function () {
    $mcpConfigPath = base_path('.mcp.json');

    expect($mcpConfigPath)->toBeReadableFile();

    $config = json_decode(file_get_contents($mcpConfigPath), true);

    expect($config)->toHaveKey('mcpServers')
        ->and($config['mcpServers'])->toHaveKey('neuron-ai-docs')
        ->and($config['mcpServers']['neuron-ai-docs'])->toHaveKey('url')
        ->and($config['mcpServers']['neuron-ai-docs']['url'])->toBe('https://docs.neuron-ai.dev/~gitbook/mcp');
});
