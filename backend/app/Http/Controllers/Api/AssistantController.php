<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SitePayload;

class AssistantController extends Controller
{
    /**
     * Get public assistant settings.
     * GET /api/assistant
     *
     * Returns only enabled, greeting, and suggested_questions.
     * Never returns extra_knowledge (that's for the system prompt only).
     * 
     * The assistant is only enabled if BOTH:
     * 1. The setting is enabled in the database
     * 2. The Anthropic API key is configured in .env
     */
    public function show(SitePayload $sitePayload)
    {
        return response()->json($sitePayload->payload()['assistant'] ?? [
            'enabled' => false,
            'greeting' => 'Hi! I\'m an AI assistant. Ask me anything about my background, skills, or projects.',
            'suggested_questions' => [],
        ]);
    }
}
