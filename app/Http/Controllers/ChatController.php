<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ChatRequest;
use App\Neuron\YouTubeAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use NeuronAI\Chat\Messages\UserMessage;

class ChatController extends Controller
{
    /**
     * Display the chat interface.
     */
    public function index(): View
    {
        return view('chat');
    }

    /**
     * Handle chat message submission.
     */
    public function send(ChatRequest $request): JsonResponse
    {
        try {
            $agent = YouTubeAgent::make();
            $userMessage = new UserMessage($request->validated()['message']);
            $response = $agent->chat($userMessage);

            return response()->json([
                'success' => true,
                'message' => $response->getContent(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your message. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
