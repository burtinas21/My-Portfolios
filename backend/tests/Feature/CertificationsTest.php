<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    public function test_admin_certification_crud_and_image_cleanup(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/old.png', 'old image');
        Storage::disk('public')->put('uploads/new.png', 'new image');

        $created = $this->withToken($this->token)->postJson('/api/admin/certifications', $this->validCertification([
            'image' => '/storage/uploads/old.png',
            'credential_id' => '',
            'description' => '',
        ]));

        $created->assertCreated()
            ->assertJsonPath('image', '/storage/uploads/old.png')
            ->assertJsonPath('credential_id', null)
            ->assertJsonPath('description', null);

        $certificationId = $created->json('id');
        $this->withToken($this->token)
            ->getJson('/api/admin/certifications')
            ->assertOk()
            ->assertJsonCount(1);

        $this->withToken($this->token)
            ->putJson("/api/admin/certifications/{$certificationId}", $this->validCertification([
                'title' => 'Updated Credential',
                'image' => '/storage/uploads/new.png',
            ]))
            ->assertOk()
            ->assertJsonPath('title', 'Updated Credential');

        Storage::disk('public')->assertMissing('uploads/old.png');

        $this->withToken($this->token)
            ->deleteJson("/api/admin/certifications/{$certificationId}")
            ->assertNoContent();

        Storage::disk('public')->assertMissing('uploads/new.png');
        $this->assertDatabaseMissing('certifications', ['id' => $certificationId]);
    }

    public function test_admin_certification_routes_require_authentication(): void
    {
        $this->getJson('/api/admin/certifications')->assertUnauthorized();
    }

    public function test_admin_certification_validation_rejects_invalid_values(): void
    {
        $invalidValues = [
            ['data' => ['issue_date' => today()->addDay()->toDateString()], 'field' => 'issue_date'],
            [
                'data' => [
                    'issue_date' => today()->subDay()->toDateString(),
                    'expiry_date' => today()->subDays(2)->toDateString(),
                ],
                'field' => 'expiry_date',
            ],
            ['data' => ['credential_url' => 'http://example.com/verify'], 'field' => 'credential_url'],
            ['data' => ['image' => 'https://example.com/certificate.png'], 'field' => 'image'],
            ['data' => ['issuer_icon' => 'not-an-icon'], 'field' => 'issuer_icon'],
        ];

        foreach ($invalidValues as $invalid) {
            $response = $this->withToken($this->token)->postJson(
                '/api/admin/certifications',
                $this->validCertification($invalid['data'])
            );

            $response->assertUnprocessable()->assertJsonValidationErrors($invalid['field']);
        }

        $this->withToken($this->token)
            ->postJson('/api/admin/certifications', ['issue_date' => today()->toDateString()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'issuer']);
    }

    public function test_admin_can_reorder_certifications(): void
    {
        $first = Certification::create($this->validCertification(['title' => 'First', 'sort_order' => 1]));
        $second = Certification::create($this->validCertification(['title' => 'Second', 'sort_order' => 0]));

        $this->withToken($this->token)
            ->patchJson('/api/admin/certifications/reorder', [
                'certifications' => [
                    ['id' => $first->id, 'sort_order' => 0],
                    ['id' => $second->id, 'sort_order' => 1],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('0.id', $first->id)
            ->assertJsonPath('0.sort_order', 0)
            ->assertJsonPath('1.id', $second->id);

        $this->assertDatabaseHas('certifications', ['id' => $first->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('certifications', ['id' => $second->id, 'sort_order' => 1]);
    }

    public function test_public_certifications_hide_drafts_order_records_and_mark_expired(): void
    {
        Certification::create($this->validCertification([
            'title' => 'Older First Sort',
            'sort_order' => 0,
            'issue_date' => today()->subMonths(6)->toDateString(),
            'expiry_date' => today()->subDay()->toDateString(),
        ]));
        Certification::create($this->validCertification([
            'title' => 'Newer First Sort',
            'sort_order' => 0,
            'issue_date' => today()->subMonths(3)->toDateString(),
        ]));
        Certification::create($this->validCertification([
            'title' => 'Later Sort',
            'sort_order' => 1,
        ]));
        Certification::create($this->validCertification([
            'title' => 'Draft',
            'is_published' => false,
        ]));

        $this->getJson('/api/certifications')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonPath('0.title', 'Newer First Sort')
            ->assertJsonPath('0.is_expired', false)
            ->assertJsonPath('1.title', 'Older First Sort')
            ->assertJsonPath('1.is_expired', true)
            ->assertJsonPath('2.title', 'Later Sort')
            ->assertJsonPath('2.is_expired', false)
            ->assertJsonMissing(['title' => 'Draft'])
            ->assertJsonStructure([['id', 'title', 'issuer', 'issuer_icon', 'issue_date', 'expiry_date', 'credential_id', 'credential_url', 'image', 'description', 'is_expired']]);
    }

    public function test_deleting_certification_keeps_an_image_used_by_a_project(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/shared.png', 'shared image');

        $certification = Certification::create($this->validCertification([
            'image' => '/storage/uploads/shared.png',
        ]));
        Project::create([
            'title' => 'Existing Project',
            'summary' => 'A project with a shared upload',
            'tag' => 'Web',
            'year' => 2025,
            'image' => '/storage/uploads/shared.png',
        ]);

        $this->withToken($this->token)
            ->deleteJson("/api/admin/certifications/{$certification->id}")
            ->assertNoContent();

        Storage::disk('public')->assertExists('uploads/shared.png');
    }

    private function validCertification(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Cloud Architecture Professional',
            'issuer' => 'Example Academy',
            'issuer_icon' => 'simple-icons:coursera',
            'issue_date' => today()->subMonth()->toDateString(),
            'expiry_date' => null,
            'credential_id' => 'ABC-123',
            'credential_url' => 'https://example.com/verify/ABC-123',
            'image' => null,
            'description' => 'Advanced cloud architecture training.',
            'is_published' => true,
            'sort_order' => 0,
        ], $overrides);
    }
}
