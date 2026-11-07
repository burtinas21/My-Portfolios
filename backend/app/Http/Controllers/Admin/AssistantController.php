<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantSetting;
use App\Models\ChatUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssistantController extends Controller
{
    /**
     * Get admin assistant settings.
     * GET /api/admin/assistant
     */
    public function show()
    {
        $settings = AssistantSetting::firstOrCreate(
            ['id' => 1],
            [
                'enabled' => false,
                'greeting' => 'Hi! I\'m an AI assistant. Ask me anything about my background, skills, or projects.',
                'extra_knowledge' => null,
                'suggested_questions' => [
                    'What are your main skills?',
                    'Tell me about your latest project',
                    'What technologies do you work with?',
                    'What is your experience?',
                ],
            ]
        );

        return response()->json($settings);
    }

    /**
     * Update assistant settings.
     * PUT /api/admin/assistant
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'greeting' => 'required|string|max:255',
            'extra_knowledge' => 'nullable|string|max:3000',
            'suggested_questions' => 'nullable|array|max:6',
            'suggested_questions.*' => 'string|max:100',
        ]);

        $settings = AssistantSetting::firstOrCreate(['id' => 1]);
        $settings->update($data);

        return response()->json($settings);
    }

    /**
     * Get usage statistics.
     * GET /api/admin/assistant/usage
     */
    public function usage()
    {
        $today = $this->getUsageForPeriod(today(), now());
        $last7Days = $this->getUsageForPeriod(today()->subDays(6), now());
        $last30Days = $this->getUsageForPeriod(today()->subDays(29), now());

        return response()->json([
            'today' => $today,
            'last_7_days' => $last7Days,
            'last_30_days' => $last30Days,
        ]);
    }

    /**
     * Get usage statistics for a specific period.
     */
    private function getUsageForPeriod($start, $end): array
    {
        $stats = ChatUsage::whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(input_tokens) as input_tokens'),
                DB::raw('SUM(output_tokens) as output_tokens')
            )
            ->first();

        return [
            'requests' => (int) ($stats->requests ?? 0),
            'input_tokens' => (int) ($stats->input_tokens ?? 0),
            'output_tokens' => (int) ($stats->output_tokens ?? 0),
        ];
    }
}
