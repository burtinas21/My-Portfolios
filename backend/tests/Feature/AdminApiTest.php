<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user and get token
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    public function test_admin_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/projects');
        $response->assertStatus(401);
    }

    public function test_authenticated_admin_can_access_admin_routes(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/admin/projects');

        $response->assertStatus(200);
    }

    public function test_admin_can_see_all_projects_including_unpublished(): void
    {
        Project::create([
            'title' => 'Published',
            'summary' => 'Test',
            'tag' => 'Web',
            'year' => 2024,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        Project::create([
            'title' => 'Draft',
            'summary' => 'Test',
            'tag' => 'Web',
            'year' => 2024,
            'is_published' => false,
            'sort_order' => 2,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/admin/projects');

        $response->assertStatus(200);
        $response->assertJsonCount(2); // Both published and unpublished
    }

    public function test_project_github_url_is_saved_and_returned_in_public_payload(): void
    {
        $githubUrl = 'https://github.com/example/project';

        $response = $this->withToken($this->token)
            ->postJson('/api/admin/projects', [
                'title' => 'Example Project',
                'summary' => 'A sample project',
                'tag' => 'Web App',
                'year' => 2024,
                'url' => 'https://example.com/demo',
                'github_url' => $githubUrl,
                'is_published' => true,
                'sort_order' => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('url', 'https://example.com/demo')
            ->assertJsonPath('github_url', $githubUrl);
        $this->assertDatabaseHas('projects', [
            'title' => 'Example Project',
            'github_url' => $githubUrl,
        ]);

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('projects.0.url', 'https://example.com/demo')
            ->assertJsonPath('projects.0.github_url', $githubUrl);
    }

    public function test_project_github_url_can_be_updated_or_left_null(): void
    {
        $project = Project::create([
            'title' => 'Example Project',
            'summary' => 'A sample project',
            'tag' => 'Web App',
            'year' => 2024,
            'url' => 'https://example.com/demo',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->withToken($this->token)
            ->putJson("/api/admin/projects/{$project->id}", [
                'title' => 'Example Project',
                'summary' => 'A sample project',
                'tag' => 'Web App',
                'year' => 2024,
                'url' => 'https://example.com/demo',
                'github_url' => 'https://github.com/example/updated-project',
                'is_published' => true,
                'sort_order' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('github_url', 'https://github.com/example/updated-project');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'github_url' => 'https://github.com/example/updated-project',
        ]);
    }

    public function test_project_github_url_must_be_a_github_repository_url(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/admin/projects', [
                'title' => 'Example Project',
                'summary' => 'A sample project',
                'tag' => 'Web App',
                'year' => 2024,
                'github_url' => 'https://example.com/not-github',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('github_url');
    }

    public function test_admin_can_create_skill(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/skills', [
            'category' => 'Backend',
            'name' => 'Laravel',
            'level' => 95,
            'sort_order' => 1,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('skills', ['name' => 'Laravel', 'level' => 95]);
    }

    public function test_admin_can_update_skill(): void
    {
        $skill = Skill::create([
            'category' => 'Backend',
            'name' => 'PHP',
            'level' => 80,
            'sort_order' => 1,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/admin/skills/{$skill->id}", [
            'category' => 'Backend',
            'name' => 'PHP',
            'level' => 90,
            'sort_order' => 1,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('skills', ['id' => $skill->id, 'level' => 90]);
    }

    public function test_admin_can_delete_skill(): void
    {
        $skill = Skill::create([
            'category' => 'Backend',
            'name' => 'PHP',
            'level' => 80,
            'sort_order' => 1,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/admin/skills/{$skill->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
    }

    public function test_admin_endpoints_validate_input(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/skills', [
            'category' => '', // Required
            'name' => '',     // Required
            'level' => 150,   // Max 100
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category', 'name', 'level']);
    }
}
