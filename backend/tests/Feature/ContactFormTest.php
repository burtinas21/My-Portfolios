<?php

namespace Tests\Feature;

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_accepts_valid_message(): void
    {
        $response = $this->postJson('/api/messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'body' => 'This is a test message.',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Thank you! I will reply soon.']);
        
        $this->assertDatabaseHas('messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'body' => 'This is a test message.',
        ]);
    }

    public function test_contact_form_requires_name(): void
    {
        $response = $this->postJson('/api/messages', [
            'email' => 'john@example.com',
            'body' => 'Message without name.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_contact_form_requires_valid_email(): void
    {
        $response = $this->postJson('/api/messages', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'body' => 'Message with invalid email.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_contact_form_requires_body(): void
    {
        $response = $this->postJson('/api/messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    public function test_contact_form_enforces_maximum_lengths(): void
    {
        $response = $this->postJson('/api/messages', [
            'name' => str_repeat('a', 101), // Max 100
            'email' => 'test@example.com',
            'body' => str_repeat('b', 3001), // Max 3000
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'body']);
    }
}
