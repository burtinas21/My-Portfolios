<?php

namespace Database\Seeders;

use App\Models\AssistantSetting;
use Illuminate\Database\Seeder;

class AssistantSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates the assistant settings row only if it doesn't exist.
     * Never overwrites existing settings.
     */
    public function run(): void
    {
        AssistantSetting::firstOrCreate(
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
    }
}
