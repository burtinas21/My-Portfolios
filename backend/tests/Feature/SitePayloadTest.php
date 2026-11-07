<?php

namespace Tests\Feature;

use App\Models\AssistantSetting;
use App\Models\Certification;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\User;
use App\Services\SitePayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SitePayloadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('array')->flush();
        Storage::fake('private');
        Http::fake();
        config()->set('services.anthropic.key', 'test-key');

        $this->admin = User::factory()->create();
        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    public function test_site_payload_contains_public_resources_and_social_icon(): void
    {
        Profile::create([
            'name' => 'Test User',
            'initials' => 'TU',
            'headline' => 'Developer',
            'bio' => 'Portfolio bio',
            'email' => 'test@example.com',
        ]);
        SocialLink::create([
            'name' => 'Telegram',
            'icon' => 'simple-icons:telegram',
            'url' => 'https://t.me/test',
        ]);
        Project::create([
            'title' => 'Published',
            'summary' => 'Public project',
            'year' => 2026,
            'is_published' => true,
        ]);
        Project::create([
            'title' => 'Draft',
            'summary' => 'Private project',
            'year' => 2026,
            'is_published' => false,
        ]);
        Skill::create(['category' => 'Backend', 'name' => 'Laravel', 'level' => 90]);
        Experience::create([
            'role' => 'Developer',
            'organization' => 'Example Co',
            'period' => '2024 - Present',
        ]);
        Certification::create([
            'title' => 'Published certificate',
            'issuer' => 'Example',
            'issue_date' => '2026-01-01',
            'is_published' => true,
        ]);
        Certification::create([
            'title' => 'Draft certificate',
            'issuer' => 'Example',
            'issue_date' => '2026-01-01',
            'is_published' => false,
        ]);

        Storage::disk('private')->put('cv/resume.pdf', '%PDF test');
        Resume::create([
            'original_name' => 'resume',
            'path' => 'cv/resume.pdf',
            'size' => 9,
            'is_public' => true,
        ]);
        AssistantSetting::create([
            'enabled' => true,
            'greeting' => 'Welcome',
            'suggested_questions' => ['What do you build?'],
        ]);

        $response = $this->getJson('/api/site');

        $response->assertOk()
            ->assertJsonStructure([
                'profile',
                'socials' => [['id', 'name', 'icon', 'url', 'sort_order']],
                'projects',
                'skills' => ['Backend'],
                'experiences',
                'certifications',
                'cv' => ['available', 'size', 'updated_at'],
                'assistant' => ['enabled', 'greeting', 'suggested_questions'],
            ])
            ->assertJsonPath('socials.0.icon', 'simple-icons:telegram')
            ->assertJsonPath('projects.0.title', 'Published')
            ->assertJsonMissing(['title' => 'Draft'])
            ->assertJsonPath('certifications.0.title', 'Published certificate')
            ->assertJsonMissing(['title' => 'Draft certificate'])
            ->assertJsonPath('cv.available', true)
            ->assertJsonPath('assistant.enabled', true)
            ->assertJsonPath('assistant.greeting', 'Welcome');
    }

    public function test_site_payload_uses_an_existing_optimized_avatar_variant(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/avatar.optimized.webp', 'optimized image');
        Storage::disk('public')->put('uploads/avatar.optimized-480.webp', 'small optimized image');
        Profile::create([
            'name' => 'Test User',
            'initials' => 'TU',
            'headline' => 'Developer',
            'bio' => 'Portfolio bio',
            'email' => 'test@example.com',
            'avatar' => 'http://localhost:8000/storage/uploads/avatar.png',
        ]);

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath(
                'profile.avatar_optimized',
                'http://localhost:8000/storage/uploads/avatar.optimized.webp',
            )
            ->assertJsonPath(
                'profile.avatar_optimized_srcset',
                'http://localhost:8000/storage/uploads/avatar.optimized-480.webp 480w, http://localhost:8000/storage/uploads/avatar.optimized.webp 800w',
            );
    }

    public function test_cached_profile_without_avatar_variants_is_rebuilt(): void
    {
        Profile::create([
            'name' => 'Current Profile',
            'initials' => 'CP',
            'headline' => 'Developer',
            'bio' => 'Portfolio bio',
            'email' => 'test@example.com',
        ]);
        Cache::forever(SitePayload::CACHE_KEY, [
            'profile' => ['name' => 'Stale Profile'],
            'socials' => [],
            'projects' => [],
            'skills' => [],
            'experiences' => [],
            'certifications' => [],
            'cv' => [],
            'assistant' => [],
        ]);

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('profile.name', 'Current Profile')
            ->assertJsonPath('profile.avatar_optimized', null)
            ->assertJsonPath('profile.avatar_optimized_srcset', null);
    }

    public function test_invalid_cached_payload_is_rebuilt_with_json_safe_arrays(): void
    {
        Cache::forever(SitePayload::CACHE_KEY, [
            'profile' => null,
            'socials' => collect(),
            'projects' => collect(),
            'skills' => collect(),
            'experiences' => collect(),
            'certifications' => [],
            'cv' => [],
            'assistant' => [],
        ]);

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('socials', [])
            ->assertJsonPath('projects', [])
            ->assertJsonPath('skills', [])
            ->assertJsonPath('experiences', []);
    }

    public function test_site_payload_supports_weak_etag_and_not_modified_response(): void
    {
        $response = $this->get('/api/site');

        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);
        $this->assertStringStartsWith('W/', $etag);
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));

        $notModified = $this->get('/api/site', ['If-None-Match' => $etag]);

        $notModified->assertStatus(304);
        $this->assertStringContainsString(
            'no-cache',
            $notModified->headers->get('Cache-Control'),
        );
    }

    public function test_social_and_project_changes_invalidate_cached_site_payload(): void
    {
        $this->getJson('/api/site')->assertJsonPath('socials', []);

        $social = SocialLink::create([
            'name' => 'GitHub',
            'icon' => 'simple-icons:github',
            'url' => 'https://github.com/test',
        ]);
        $this->getJson('/api/site')
            ->assertJsonPath('socials.0.name', 'GitHub')
            ->assertJsonPath('socials.0.icon', 'simple-icons:github');

        $social->update(['name' => 'GitLab']);
        $this->getJson('/api/site')->assertJsonPath('socials.0.name', 'GitLab');

        $social->delete();
        $this->getJson('/api/site')->assertJsonPath('socials', []);

        $this->getJson('/api/site')->assertJsonPath('projects', []);

        $project = Project::create([
            'title' => 'First title',
            'summary' => 'Project summary',
            'year' => 2026,
            'is_published' => true,
        ]);
        $this->getJson('/api/site')->assertJsonPath('projects.0.title', 'First title');

        $project->update(['title' => 'Updated title']);
        $this->getJson('/api/site')->assertJsonPath('projects.0.title', 'Updated title');

        $project->delete();
        $this->getJson('/api/site')->assertJsonPath('projects', []);
    }

    public function test_social_url_schemes_are_validated_and_empty_icon_is_stored_as_null(): void
    {
        foreach (['mailto:person@example.com', 'tel:+15551234567'] as $url) {
            $this->withToken($this->token)
                ->postJson('/api/admin/socials', [
                    'name' => 'Contact',
                    'url' => $url,
                    'icon' => '',
                ])
                ->assertCreated()
                ->assertJsonPath('icon', null);
        }

        $this->withToken($this->token)
            ->postJson('/api/admin/socials', [
                'name' => 'Unsafe',
                'url' => 'javascript:alert(1)',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);

        $social = SocialLink::create(['name' => 'Existing', 'url' => 'https://example.com']);
        $this->withToken($this->token)
            ->putJson("/api/admin/socials/{$social->id}", [
                'name' => 'Unsafe',
                'url' => 'javascript:alert(1)',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_invalid_icon_is_rejected_and_public_socials_are_ordered_by_order_then_id(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/admin/socials', [
                'name' => 'Invalid',
                'url' => 'https://example.com',
                'icon' => 'Simple Icons:GitHub',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['icon']);

        $first = SocialLink::create([
            'name' => 'First',
            'icon' => 'simple-icons:github',
            'url' => 'https://example.com/first',
            'sort_order' => 1,
        ]);
        $second = SocialLink::create(['name' => 'Second', 'url' => 'https://example.com/second', 'sort_order' => 1]);
        $earlier = SocialLink::create(['name' => 'Earlier', 'url' => 'https://example.com/earlier', 'sort_order' => 0]);

        $this->getJson('/api/socials')
            ->assertOk()
            ->assertJsonPath('0.id', $earlier->id)
            ->assertJsonPath('1.id', $first->id)
            ->assertJsonPath('2.id', $second->id)
            ->assertJsonPath('1.icon', 'simple-icons:github');

        $this->withToken($this->token)
            ->getJson('/api/admin/socials')
            ->assertOk()
            ->assertJsonPath('0.id', $earlier->id)
            ->assertJsonPath('1.id', $first->id)
            ->assertJsonPath('2.id', $second->id)
            ->assertJsonPath('1.icon', 'simple-icons:github');
    }
}
