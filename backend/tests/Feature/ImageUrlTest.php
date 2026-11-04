<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests for the image URL strategy introduced in Part 1:
 *
 * 1. POST /api/admin/uploads returns "path" ("/storage/...") and "url" (full URL).
 * 2. images:make-relative --dry-run reads rows but changes nothing.
 * 3. images:make-relative (real run) converts localhost absolute URLs to relative paths.
 */
class ImageUrlTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Use a fake public disk so no real files are written during tests
        Storage::fake('public');

        $this->admin = User::factory()->create([
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    // ── Upload endpoint ────────────────────────────────────────────────────

    /** The upload response must include "path" starting with "/storage/" and a "url" field. */
    public function test_upload_returns_path_and_url(): void
    {
        $file = UploadedFile::fake()->image('photo.png', 100, 100);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->postJson('/api/admin/uploads', ['image' => $file]);

        $response->assertStatus(201)
            ->assertJsonStructure(['path', 'url']);

        $data = $response->json();

        // "path" must be a relative /storage/... path — this is what gets saved in the DB
        $this->assertStringStartsWith('/storage/', $data['path'],
            '"path" must start with /storage/ so it works after deployment');

        // "url" must be an absolute URL (for preview convenience)
        $this->assertStringStartsWith('http', $data['url'],
            '"url" must be an absolute URL for immediate display');
    }

    /** The path value returned must be usable to store directly in the database. */
    public function test_upload_path_is_database_safe(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->postJson('/api/admin/uploads', ['image' => $file]);

        $response->assertStatus(201);
        $path = $response->json('path');

        // Saving the path to a profile should pass validation without errors
        $profile = Profile::create([
            'name'     => 'Test User',
            'initials' => 'TU',
            'headline' => 'Developer',
            'bio'      => 'A bio.',
            'email'    => 'test@example.com',
            'avatar'   => $path,
        ]);

        $this->assertDatabaseHas('profiles', ['avatar' => $path]);
        $this->assertStringStartsWith('/storage/', $profile->fresh()->avatar);
    }

    // ── images:make-relative --dry-run ─────────────────────────────────────

    /** --dry-run must leave every row unchanged. */
    public function test_make_images_relative_dry_run_changes_nothing(): void
    {
        $absoluteAvatar = 'http://localhost:8000/storage/uploads/avatar.png';
        $absoluteImage  = 'http://localhost:8000/storage/uploads/project.jpg';

        Profile::create([
            'name' => 'Dev', 'initials' => 'D', 'headline' => 'Dev',
            'bio' => 'Bio', 'email' => 'dev@example.com',
            'avatar' => $absoluteAvatar,
        ]);

        Project::create([
            'title' => 'Sample', 'summary' => 'A project', 'tag' => 'App',
            'year' => 2024, 'is_published' => true, 'sort_order' => 1,
            'image' => $absoluteImage,
        ]);

        // Run with --dry-run
        Artisan::call('images:make-relative', ['--dry-run' => true]);

        // Database must be untouched
        $this->assertDatabaseHas('profiles',  ['avatar' => $absoluteAvatar]);
        $this->assertDatabaseHas('projects',  ['image'  => $absoluteImage]);
    }

    // ── images:make-relative (real run) ───────────────────────────────────

    /** The real run must convert localhost absolute URLs to "/storage/..." paths. */
    public function test_make_images_relative_converts_absolute_urls(): void
    {
        Profile::create([
            'name' => 'Dev', 'initials' => 'D', 'headline' => 'Dev',
            'bio' => 'Bio', 'email' => 'dev@example.com',
            'avatar' => 'http://localhost:8000/storage/uploads/avatar.png',
        ]);

        Project::create([
            'title' => 'Sample', 'summary' => 'A project', 'tag' => 'App',
            'year' => 2024, 'is_published' => true, 'sort_order' => 1,
            'image' => 'https://my-server.example.com/storage/uploads/project.jpg',
        ]);

        // Run for real (no --dry-run flag)
        Artisan::call('images:make-relative');

        // Both rows should now have relative paths
        $this->assertDatabaseHas('profiles', ['avatar' => '/storage/uploads/avatar.png']);
        $this->assertDatabaseHas('projects', ['image'  => '/storage/uploads/project.jpg']);
    }

    /** Already-relative paths must not be modified. */
    public function test_make_images_relative_skips_already_relative_paths(): void
    {
        Profile::create([
            'name' => 'Dev', 'initials' => 'D', 'headline' => 'Dev',
            'bio' => 'Bio', 'email' => 'dev@example.com',
            'avatar' => '/storage/uploads/already-relative.png',
        ]);

        Artisan::call('images:make-relative');

        // Must remain unchanged
        $this->assertDatabaseHas('profiles', ['avatar' => '/storage/uploads/already-relative.png']);
    }
}

