<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Models\AssistantSetting;
use App\Models\ChatUsage;
use App\Services\AssistantPrompt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Handle chat request.
     * POST /api/chat
     *
     * Sends messages to Claude API and returns the response.
     * Applies throttling and daily limits.
     */
    public function store(ChatRequest $request)
    {
        // Check if assistant is enabled
        $settings = AssistantSetting::first();
        if (!$settings || !$settings->enabled) {
            return response()->json([
                'message' => 'The AI assistant is currently unavailable. Please use the contact form to get in touch.',
            ], 503);
        }

        // Check if Anthropic API key is configured
        $apiKey = config('services.anthropic.key');
        if (empty($apiKey)) {
            return response()->json([
                'message' => 'The AI assistant is not configured. Please contact the site administrator.',
            ], 503);
        }

        // Check daily limit
        $dailyLimit = config('services.anthropic.daily_limit', 300);
        $todayCount = ChatUsage::whereDate('created_at', today())->count();
        
        if ($todayCount >= $dailyLimit) {
            return response()->json([
                'message' => 'The AI assistant has reached its daily limit. Please try again tomorrow or use the contact form.',
            ], 429);
        }

        // Build system prompt from database
        $systemPrompt = AssistantPrompt::build();

        // Prepare messages for API
        $messages = $request->validated()['messages'];

        try {
            // Call Claude API
            $response = Http::timeout(config('services.anthropic.timeout', 20))
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model', 'claude-3-haiku-20240307'),
                    'max_tokens' => 400,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                ]);

            if ($response->failed()) {
                $statusCode = $response->status();
                
                // Log the error without exposing the API key or message content
                Log::error('Anthropic API error', [
                    'status' => $statusCode,
                    'error' => $response->json()['error']['message'] ?? 'Unknown error',
                ]);

                if ($statusCode === 429) {
                    return response()->json([
                        'message' => 'The AI service is currently busy. Please try again in a moment.',
                    ], 503);
                }

                return response()->json([
                    'message' => 'The AI assistant encountered an error. Please try again or use the contact form.',
                ], 502);
            }

            $data = $response->json();
            $reply = $data['content'][0]['text'] ?? '';

            // Security: Strip any email addresses or mailto links from the response
            $reply = $this->sanitizeReply($reply);

            // Record usage
            ChatUsage::create([
                'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            ]);

            return response()->json([
                'reply' => $reply,
            ]);

        } catch (\Exception $e) {
            // Log error without sensitive data
            Log::error('Chat request failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'The AI assistant is temporarily unavailable. Please try again later or use the contact form.',
            ], 503);
        }
    }

    /**
     * Sanitize the AI reply by removing email addresses and mailto links.
     */
    private function sanitizeReply(string $reply): string
    {
        // Remove email addresses
        $reply = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[contact form]', $reply);
        
        // Remove mailto: links
        $reply = str_replace('mailto:', '', $reply);
        
        return $reply;
    }
}
