<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\SocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed test data
        Profile::create([
            'name' => 'Test User',
            'initials' => 'TU',
            'headline' => 'Test Developer',
            'bio' => 'Test bio',
            'email' => 'test@example.com',
        ]);

        SocialLink::create(['name' => 'GitHub', 'url' => 'https://github.com/test', 'sort_order' => 1]);
        
        Project::create([
            'title' => 'Published Project',
            'summary' => 'A published project',
            'tag' => 'Web App',
            'year' => 2024,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        Project::create([
            'title' => 'Unpublished Project',
            'summary' => 'A draft project',
            'tag' => 'Web App',
            'year' => 2024,
            'is_published' => false,
            'sort_order' => 2,
        ]);

        Skill::create(['category' => 'Backend', 'name' => 'PHP', 'level' => 90, 'sort_order' => 1]);
        
        Experience::create([
            'role' => 'Developer',
            'organization' => 'Test Co',
            'period' => '2020-2024',
            'sort_order' => 1,
        ]);
    }

    public function test_profile_endpoint_returns_200(): void
    {
        $response = $this->getJson('/api/profile');
        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'name', 'headline', 'bio', 'email']);
    }

    public function test_socials_endpoint_returns_200(): void
    {
        $response = $this->getJson('/api/socials');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_projects_endpoint_returns_only_published(): void
    {
        $response = $this->getJson('/api/projects');
        $response->assertStatus(200);
        $response->assertJsonCount(1); // Only published project
        $response->assertJsonFragment(['title' => 'Published Project']);
        $response->assertJsonMissing(['title' => 'Unpublished Project']);
    }

    public function test_published_project_can_be_viewed(): void
    {
        $project = Project::where('is_published', true)->first();
        $response = $this->getJson("/api/projects/{$project->id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Published Project']);
    }

    public function test_unpublished_project_returns_404(): void
    {
        $project = Project::where('is_published', false)->first();
        $response = $this->getJson("/api/projects/{$project->id}");
        $response->assertStatus(404);
    }

    public function test_skills_endpoint_returns_grouped_by_category(): void
    {
        $response = $this->getJson('/api/skills');
        $response->assertStatus(200);
        $response->assertJsonStructure(['Backend']);
    }

    public function test_experiences_endpoint_returns_200(): void
    {
        $response = $this->getJson('/api/experiences');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }
}
