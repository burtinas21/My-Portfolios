<?php

namespace Tests\Feature;

use App\Models\AssistantSetting;
use App\Models\Certification;
use App\Models\ChatUsage;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for AI Assistant functionality.
 */
class AssistantTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->token = $this->admin->createToken('test')->plainTextToken;

        // Create a profile with email (to test it's excluded from system prompt)
        Profile::create([
            'name' => 'Test User',
            'initials' => 'TU',
            'headline' => 'Full-Stack Developer',
            'bio' => 'Passionate developer',
            'email' => 'test@example.com', // This should NEVER appear in system prompt
        ]);
    }

    /** GET /api/assistant returns settings when enabled AND API key is configured */
    public function test_get_assistant_settings_when_enabled(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        AssistantSetting::create([
            'enabled' => true,
            'greeting' => 'Hello there!',
            'suggested_questions' => ['Question 1', 'Question 2'],
        ]);

        $response = $this->getJson('/api/assistant');

        $response->assertStatus(200)
            ->assertJson([
                'enabled' => true,
                'greeting' => 'Hello there!',
                'suggested_questions' => ['Question 1', 'Question 2'],
            ])
            ->assertJsonMissing(['extra_knowledge']); // Never expose this publicly
    }

    /** GET /api/assistant returns enabled=false when API key is missing */
    public function test_get_assistant_returns_disabled_when_api_key_missing(): void
    {
        config(['services.anthropic.key' => '']); // No API key
        AssistantSetting::create([
            'enabled' => true,
            'greeting' => 'Hello there!',
            'suggested_questions' => ['Question 1', 'Question 2'],
        ]);

        $response = $this->getJson('/api/assistant');

        $response->assertStatus(200)
            ->assertJson([
                'enabled' => false, // Should be disabled despite setting enabled=true
                'greeting' => 'Hello there!',
                'suggested_questions' => ['Question 1', 'Question 2'],
            ]);
    }

    /** POST /api/chat returns 503 when assistant is disabled */
    public function test_chat_returns_503_when_disabled(): void
    {
        AssistantSetting::create(['enabled' => false]);

        $response = $this->postJson('/api/chat', [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);

        $response->assertStatus(503)
            ->assertJson(['message' => 'The AI assistant is currently unavailable. Please use the contact form to get in touch.']);
    }

    /** POST /api/chat returns 503 when API key is empty */
    public function test_chat_returns_503_when_api_key_missing(): void
    {
        config(['services.anthropic.key' => '']);
        AssistantSetting::create(['enabled' => true]);

        $response = $this->postJson('/api/chat', [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);

        $response->assertStatus(503)
            ->assertJson(['message' => 'The AI assistant is not configured. Please contact the site administrator.']);
    }

    /** Validation: 11 messages rejected */
    public function test_chat_rejects_more_than_10_messages(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        AssistantSetting::create(['enabled' => true]);

        $messages = array_fill(0, 11, ['role' => 'user', 'content' => 'test']);

        $response = $this->postJson('/api/chat', ['messages' => $messages]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('messages');
    }

    /** Validation: 501-character message rejected */
    public function test_chat_rejects_message_over_500_chars(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        AssistantSetting::create(['enabled' => true]);

        $response = $this->postJson('/api/chat', [
            'messages' => [['role' => 'user', 'content' => str_repeat('a', 501)]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('messages.0.content');
    }

    /** Validation: Last message must be from user */
    public function test_chat_rejects_when_last_message_not_from_user(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        AssistantSetting::create(['enabled' => true]);

        $response = $this->postJson('/api/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'assistant', 'content' => 'Hi there!'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('messages');
    }

    /** System prompt never contains email address */
    public function test_system_prompt_excludes_email(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        AssistantSetting::create(['enabled' => true]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'Test response']],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
            ]),
        ]);

        $this->postJson('/api/chat', [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);

        // Check that the system prompt sent to API doesn't contain the email
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            $systemPrompt = $body['system'] ?? '';

            // Email should NOT be in system prompt
            return !str_contains($systemPrompt, 'test@example.com');
        });
    }

    public function test_system_prompt_includes_public_certification_summary_without_credential_id(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        AssistantSetting::create(['enabled' => true]);
        Certification::create([
            'title' => 'Cloud Architecture Professional',
            'issuer' => 'Example Academy',
            'issue_date' => '2025-03-01',
            'credential_id' => 'PRIVATE-CREDENTIAL-987',
            'credential_url' => 'https://example.com/verify/PRIVATE-CREDENTIAL-987',
            'is_published' => true,
        ]);
        Certification::create([
            'title' => 'Unpublished Certification',
            'issuer' => 'Private Academy',
            'issue_date' => '2024-01-01',
            'credential_id' => 'DRAFT-CREDENTIAL-456',
            'is_published' => false,
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'Test response']],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
            ]),
        ]);

        $this->postJson('/api/chat', [
            'messages' => [['role' => 'user', 'content' => 'What certifications have you earned?']],
        ]);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            $systemPrompt = $body['system'] ?? '';

            return str_contains($systemPrompt, 'CERTIFICATIONS:')
                && str_contains($systemPrompt, 'Cloud Architecture Professional | Example Academy | 2025')
                && ! str_contains($systemPrompt, 'PRIVATE-CREDENTIAL-987')
                && ! str_contains($systemPrompt, 'example.com/verify')
                && ! str_contains($systemPrompt, 'Unpublished Certification')
                && ! str_contains($systemPrompt, 'DRAFT-CREDENTIAL-456');
        });
    }

    public function test_system_prompt_includes_compact_experience_details(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        AssistantSetting::create(['enabled' => true]);
        Experience::create([
            'role' => 'Software Engineer',
            'organization' => 'Example Studio',
            'period' => null,
            'description' => 'Delivered production software.',
            'start_date' => '2022-01-01',
            'end_date' => '2023-03-01',
            'is_current' => false,
            'employment_type' => 'Contract',
            'work_mode' => 'Hybrid',
            'location' => 'Addis Ababa',
            'highlights' => ['Built a service', 'Reduced latency'],
            'technologies' => ['Laravel', 'Vue'],
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'Test response']],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
            ]),
        ]);

        $this->postJson('/api/chat', [
            'messages' => [['role' => 'user', 'content' => 'Tell me about this role.']],
        ]);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            $systemPrompt = $body['system'] ?? '';

            return str_contains($systemPrompt, 'Jan 2022 – Mar 2023')
                && str_contains($systemPrompt, 'Type: Contract')
                && str_contains($systemPrompt, 'Mode: Hybrid')
                && str_contains($systemPrompt, 'Location: Addis Ababa')
                && str_contains($systemPrompt, 'Highlights: Built a service; Reduced latency')
                && str_contains($systemPrompt, 'Technologies: Laravel, Vue')
                && str_contains($systemPrompt, 'Delivered production software.');
        });
    }

    /** Email addresses are stripped from AI replies */
    public function test_emails_stripped_from_reply(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        AssistantSetting::create(['enabled' => true]);

        // Simulate API returning a response with an email
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'Contact me at test@example.com for more info']],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
            ]),
        ]);

        $response = $this->postJson('/api/chat', [
            'messages' => [['role' => 'user', 'content' => 'How do I contact you?']],
        ]);

        $response->assertStatus(200);

        // Email should be replaced
        $reply = $response->json('reply');
        $this->assertStringNotContainsString('test@example.com', $reply);
        $this->assertStringContainsString('[contact form]', $reply);
    }

    /** Usage is recorded after successful chat */
    public function test_usage_is_recorded(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        AssistantSetting::create(['enabled' => true]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'Test response']],
                'usage' => ['input_tokens' => 150, 'output_tokens' => 75],
            ]),
        ]);

        $this->assertDatabaseCount('chat_usage', 0);

        $this->postJson('/api/chat', [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);

        $this->assertDatabaseCount('chat_usage', 1);
        $this->assertDatabaseHas('chat_usage', [
            'input_tokens' => 150,
            'output_tokens' => 75,
        ]);
    }

    /** Admin routes require authentication */
    public function test_admin_assistant_requires_auth(): void
    {
        $this->getJson('/api/admin/assistant')->assertStatus(401);
        $this->putJson('/api/admin/assistant')->assertStatus(401);
        $this->getJson('/api/admin/assistant/usage')->assertStatus(401);
    }

    /** Admin can get and update settings */
    public function test_admin_can_update_settings(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->putJson('/api/admin/assistant', [
                'enabled' => true,
                'greeting' => 'Custom greeting',
                'extra_knowledge' => 'I charge $100/hour',
                'suggested_questions' => ['Q1', 'Q2'],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('assistant_settings', [
            'enabled' => true,
            'greeting' => 'Custom greeting',
            'extra_knowledge' => 'I charge $100/hour',
        ]);
    }

    /** Admin can get usage statistics */
    public function test_admin_can_get_usage_stats(): void
    {
        ChatUsage::create(['input_tokens' => 100, 'output_tokens' => 50, 'created_at' => now()]);
        ChatUsage::create(['input_tokens' => 200, 'output_tokens' => 100, 'created_at' => now()->subDays(2)]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->getJson('/api/admin/assistant/usage');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'today' => ['requests', 'input_tokens', 'output_tokens'],
                'last_7_days' => ['requests', 'input_tokens', 'output_tokens'],
                'last_30_days' => ['requests', 'input_tokens', 'output_tokens'],
            ]);
    }
}
