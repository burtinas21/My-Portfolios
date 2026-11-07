<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CvTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        // Create admin user and get token
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->token = $this->admin->createToken('test-token')->plainTextToken;

        // Create profile for download filename test
        Profile::create([
            'name' => 'John Doe',
            'initials' => 'JD',
            'headline' => 'Developer',
            'bio' => 'Test bio',
            'email' => 'john@example.com',
        ]);
    }

    public function test_admin_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/cv');
        $response->assertStatus(401);
    }

    public function test_get_cv_when_none_exists(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/admin/cv');

        $response->assertStatus(200);
        $response->assertJson(['exists' => false]);
    }

    public function test_non_pdf_file_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_file_over_5mb_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 6000); // 6MB

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_fake_pdf_with_wrong_header_is_rejected(): void
    {
        // Create a fake file that claims to be PDF but doesn't have the PDF header
        $file = UploadedFile::fake()->createWithContent('document.pdf', 'This is not a real PDF file');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('valid PDF', $response->json('message'));
    }

    public function test_upload_then_replace_deletes_old_file(): void
    {
        // First upload
        $file1 = UploadedFile::fake()->createWithContent('cv1.pdf', '%PDF-1.4 fake pdf content');

        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', [
            'file' => $file1,
        ]);

        $response1->assertStatus(200);
        $this->assertDatabaseHas('resumes', ['original_name' => 'cv1']);

        $resume1 = Resume::first();
        $oldPath = $resume1->path;
        Storage::disk('private')->assertExists($oldPath);

        // Second upload (replace)
        $file2 = UploadedFile::fake()->createWithContent('cv2.pdf', '%PDF-1.4 another fake pdf');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', [
            'file' => $file2,
        ]);

        $response2->assertStatus(200);
        $this->assertDatabaseHas('resumes', ['original_name' => 'cv2']);

        // Verify old file was deleted
        Storage::disk('private')->assertMissing($oldPath);

        // Verify new file exists
        $resume2 = Resume::first();
        Storage::disk('private')->assertExists($resume2->path);

        // Verify download count was reset
        $this->assertEquals(0, $resume2->download_count);
    }

    public function test_is_public_false_makes_public_endpoint_unavailable(): void
    {
        // Upload a CV
        $file = UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 test content');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', ['file' => $file]);

        // Initially should be available
        $response = $this->getJson('/api/cv');
        $response->assertStatus(200);
        $response->assertJson(['available' => true]);

        // Toggle to hidden
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->patchJson('/api/admin/cv', ['is_public' => false]);

        // Now should be unavailable
        $response = $this->getJson('/api/cv');
        $response->assertStatus(200);
        $response->assertJson(['available' => false]);

        // Download should 404
        $response = $this->get('/api/cv/download');
        $response->assertStatus(404);
    }

    public function test_download_returns_correct_headers_and_increments_counter(): void
    {
        // Upload a CV
        $file = UploadedFile::fake()->createWithContent('my-cv.pdf', '%PDF-1.4 test content');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', ['file' => $file]);

        $resume = Resume::first();
        $this->assertEquals(0, $resume->download_count);

        // Download the file
        $response = $this->get('/api/cv/download');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="john-doe-CV.pdf"');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        // Laravel adds 'public' to cache-control for file responses
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));

        // Verify counter incremented
        $resume->refresh();
        $this->assertEquals(1, $resume->download_count);
    }

    public function test_inline_view_does_not_increment_counter(): void
    {
        // Upload a CV
        $file = UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 test content');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', ['file' => $file]);

        $resume = Resume::first();
        $this->assertEquals(0, $resume->download_count);

        // View inline
        $response = $this->get('/api/cv/download?inline=1');

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'inline; filename="john-doe-CV.pdf"');

        // Verify counter did NOT increment
        $resume->refresh();
        $this->assertEquals(0, $resume->download_count);
    }

    public function test_delete_removes_file_and_record(): void
    {
        // Upload a CV
        $file = UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 test content');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', ['file' => $file]);

        $resume = Resume::first();
        $path = $resume->path;

        Storage::disk('private')->assertExists($path);
        $this->assertDatabaseHas('resumes', ['id' => $resume->id]);

        // Delete
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/admin/cv');

        $response->assertStatus(204);

        // Verify file and record are gone
        Storage::disk('private')->assertMissing($path);
        $this->assertDatabaseMissing('resumes', ['id' => $resume->id]);
    }

    public function test_public_json_never_contains_path(): void
    {
        // Upload a CV
        $file = UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 test content');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', ['file' => $file]);

        // Check public endpoint
        $response = $this->getJson('/api/cv');

        $response->assertStatus(200);
        $response->assertJsonMissing(['path']);
        $response->assertJsonMissing(['original_name']);
        $response->assertJsonStructure([
            'available',
            'size',
            'updated_at',
        ]);
    }

    public function test_admin_can_preview_cv(): void
    {
        // Upload a CV
        $file = UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 test content');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', ['file' => $file]);

        // Preview
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/admin/cv/file');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="cv.pdf"');
    }

    public function test_toggle_public_works(): void
    {
        // Upload a CV
        $file = UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 test content');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', ['file' => $file]);

        $resume = Resume::first();
        $this->assertTrue($resume->is_public);

        // Toggle to false
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->patchJson('/api/admin/cv', ['is_public' => false]);

        $response->assertStatus(200);
        $response->assertJson(['is_public' => false]);

        $resume->refresh();
        $this->assertFalse($resume->is_public);

        // Toggle back to true
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->patchJson('/api/admin/cv', ['is_public' => true]);

        $response->assertStatus(200);
        $response->assertJson(['is_public' => true]);

        $resume->refresh();
        $this->assertTrue($resume->is_public);
    }

    public function test_download_rate_limited(): void
    {
        // Upload a CV
        $file = UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 test content');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/cv', ['file' => $file]);

        // Make 31 requests (rate limit is 30 per 1 minute)
        for ($i = 0; $i < 31; $i++) {
            $response = $this->get('/api/cv/download');
            
            if ($i < 30) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429); // Too many requests
            }
        }
    }
}
