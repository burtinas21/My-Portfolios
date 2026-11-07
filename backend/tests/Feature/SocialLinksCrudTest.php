<?php

namespace Tests\Feature;

use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Social Links CRUD operations (Admin API).
 *
 * Covers:
 * 1. Create a new social link
 * 2. List all social links
 * 3. Update an existing social link
 * 4. Delete a social link
 * 5. Verify deletion persists across requests
 */
class SocialLinksCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    /** Admin can create a new social link */
    public function test_admin_can_create_social_link(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->postJson('/api/admin/socials', [
                'name'       => 'GitHub',
                'url'        => 'https://github.com/username',
                'sort_order' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'url', 'sort_order']);

        $this->assertDatabaseHas('social_links', [
            'name' => 'GitHub',
            'url'  => 'https://github.com/username',
        ]);
    }

    /** Admin can list all social links */
    public function test_admin_can_list_social_links(): void
    {
        SocialLink::create(['name' => 'GitHub', 'url' => 'https://github.com/user', 'sort_order' => 1]);
        SocialLink::create(['name' => 'LinkedIn', 'url' => 'https://linkedin.com/in/user', 'sort_order' => 2]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->getJson('/api/admin/socials');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** Admin can update an existing social link */
    public function test_admin_can_update_social_link(): void
    {
        $social = SocialLink::create([
            'name'       => 'Twitter',
            'url'        => 'https://twitter.com/oldname',
            'sort_order' => 1,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->putJson("/api/admin/socials/{$social->id}", [
                'name'       => 'X (Twitter)',
                'url'        => 'https://twitter.com/newname',
                'sort_order' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id'   => $social->id,
                'name' => 'X (Twitter)',
                'url'  => 'https://twitter.com/newname',
            ]);

        $this->assertDatabaseHas('social_links', [
            'id'   => $social->id,
            'name' => 'X (Twitter)',
            'url'  => 'https://twitter.com/newname',
        ]);
    }

    /** Admin can delete a social link */
    public function test_admin_can_delete_social_link(): void
    {
        $social = SocialLink::create([
            'name'       => 'Facebook',
            'url'        => 'https://facebook.com/user',
            'sort_order' => 1,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->deleteJson("/api/admin/socials/{$social->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('social_links', ['id' => $social->id]);
    }

    /** Deleted social link does NOT appear in subsequent requests */
    public function test_deleted_social_link_not_in_list(): void
    {
        $social1 = SocialLink::create(['name' => 'GitHub', 'url' => 'https://github.com/user', 'sort_order' => 1]);
        $social2 = SocialLink::create(['name' => 'LinkedIn', 'url' => 'https://linkedin.com/in/user', 'sort_order' => 2]);

        // Delete the first link
        $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->deleteJson("/api/admin/socials/{$social1->id}")
            ->assertStatus(204);

        // List again — should only have 1 item
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->getJson('/api/admin/socials');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'LinkedIn']);
    }

    /** Public API does NOT show deleted social links */
    public function test_deleted_social_link_not_in_public_api(): void
    {
        $social1 = SocialLink::create(['name' => 'GitHub', 'url' => 'https://github.com/user', 'sort_order' => 1]);
        $social2 = SocialLink::create(['name' => 'LinkedIn', 'url' => 'https://linkedin.com/in/user', 'sort_order' => 2]);

        // Delete the first link
        $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->deleteJson("/api/admin/socials/{$social1->id}")
            ->assertStatus(204);

        // Fetch from PUBLIC endpoint (no auth required)
        $response = $this->getJson('/api/socials');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'LinkedIn'])
            ->assertJsonMissing(['name' => 'GitHub']);
    }
}
