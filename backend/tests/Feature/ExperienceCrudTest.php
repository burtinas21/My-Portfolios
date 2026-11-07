<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\User;
use App\Services\SitePayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExperienceCrudTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('array')->flush();
        $admin = User::factory()->create();
        $this->token = $admin->createToken('test')->plainTextToken;
    }

    public function test_admin_can_create_experience_with_new_fields_and_normalized_lists(): void
    {
        $this->getJson('/api/site')->assertOk()->assertJsonPath('experiences', []);

        $role = str_repeat('R', 120);
        $highlights = array_map(static fn (int $number): string => "Achievement {$number}", range(1, 6));
        $highlights[0] = ' Delivered a platform ';
        $technologies = array_map(static fn (int $number): string => "Technology {$number}", range(1, 7));

        $response = $this->withToken($this->token)->postJson(
            '/api/admin/experiences',
            $this->validExperience([
                'role' => $role,
                'period' => '',
                'start_date' => '2024-01-15',
                'end_date' => '2025-02-01',
                'employment_type' => 'Full-time',
                'work_mode' => 'Hybrid',
                'location' => ' Addis Ababa ',
                'highlights' => $highlights,
                'technologies' => array_merge([' Laravel '], $technologies),
            ])
        );

        $response->assertCreated()
            ->assertJsonPath('role', $role)
            ->assertJsonPath('period', null)
            ->assertJsonPath('start_date', '2024-01-15')
            ->assertJsonPath('end_date', '2025-02-01')
            ->assertJsonPath('is_current', false)
            ->assertJsonPath('location', 'Addis Ababa')
            ->assertJsonPath('highlights', array_merge(['Delivered a platform'], array_slice($highlights, 1)))
            ->assertJsonPath('technologies', array_merge(['Laravel'], $technologies));

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('experiences.0.role', $role)
            ->assertJsonPath('experiences.0.start_date', '2024-01-15');
    }

    public function test_admin_can_update_experience_and_current_role_has_no_end_date(): void
    {
        $experience = Experience::create($this->validExperience([
            'period' => '2022 - 2024',
            'start_date' => '2022-01-01',
            'end_date' => '2024-01-01',
        ]));

        $this->withToken($this->token)
            ->putJson("/api/admin/experiences/{$experience->id}", $this->validExperience([
                'period' => null,
                'start_date' => '2022-01-01',
                'is_current' => true,
                'employment_type' => 'Contract',
                'work_mode' => 'Remote',
                'highlights' => ['Shipped the product'],
                'technologies' => ['PHP'],
            ]))
            ->assertOk()
            ->assertJsonPath('period', null)
            ->assertJsonPath('is_current', true)
            ->assertJsonPath('end_date', null)
            ->assertJsonPath('employment_type', 'Contract');

        $this->assertDatabaseHas('experiences', [
            'id' => $experience->id,
            'is_current' => true,
            'end_date' => null,
        ]);
    }

    public function test_experience_validation_rejects_invalid_dates_and_list_values(): void
    {
        $invalidValues = [
            ['data' => ['start_date' => today()->addDay()->toDateString()], 'field' => 'start_date'],
            [
                'data' => ['start_date' => '2024-06-01', 'end_date' => '2024-05-31'],
                'field' => 'end_date',
            ],
            [
                'data' => ['start_date' => '2024-01-01', 'end_date' => '2024-12-01', 'is_current' => true],
                'field' => 'end_date',
            ],
            ['data' => ['employment_type' => 'Permanent'], 'field' => 'employment_type'],
            ['data' => ['work_mode' => 'Flexible'], 'field' => 'work_mode'],
            ['data' => ['highlights' => array_fill(0, 7, 'Highlight')], 'field' => 'highlights'],
            ['data' => ['highlights' => [str_repeat('a', 201)]], 'field' => 'highlights.0'],
            ['data' => ['technologies' => array_fill(0, 9, 'Tool')], 'field' => 'technologies'],
            ['data' => ['technologies' => [str_repeat('a', 31)]], 'field' => 'technologies.0'],
        ];

        foreach ($invalidValues as $invalid) {
            $this->withToken($this->token)
                ->postJson('/api/admin/experiences', $this->validExperience($invalid['data']))
                ->assertUnprocessable()
                ->assertJsonValidationErrors($invalid['field']);
        }
    }

    public function test_legacy_experience_fields_remain_optional_and_are_returned(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/admin/experiences', [
                'role' => 'Legacy Developer',
                'organization' => 'Old Company',
                'period' => '2018 - 2020',
                'description' => 'Worked on existing projects.',
            ])
            ->assertCreated()
            ->assertJsonPath('period', '2018 - 2020')
            ->assertJsonPath('description', 'Worked on existing projects.')
            ->assertJsonPath('start_date', null)
            ->assertJsonPath('is_current', false)
            ->assertJsonPath('highlights', null)
            ->assertJsonPath('technologies', null);

        $this->getJson('/api/experiences')
            ->assertOk()
            ->assertJsonPath('0.role', 'Legacy Developer')
            ->assertJsonPath('0.period', '2018 - 2020')
            ->assertJsonPath('0.description', 'Worked on existing projects.');
    }

    public function test_cached_legacy_experience_payload_is_rebuilt_with_new_fields(): void
    {
        $experience = Experience::create($this->validExperience([
            'period' => '2018 - 2020',
            'description' => 'Legacy description',
        ]));

        Cache::forever(SitePayload::CACHE_KEY, [
            'profile' => null,
            'socials' => [],
            'projects' => [],
            'skills' => [],
            'experiences' => [[
                'id' => $experience->id,
                'role' => $experience->role,
                'organization' => $experience->organization,
                'period' => $experience->period,
                'description' => $experience->description,
                'sort_order' => $experience->sort_order,
            ]],
            'certifications' => [],
            'cv' => [],
            'assistant' => [],
        ]);

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('experiences.0.role', 'Developer')
            ->assertJsonPath('experiences.0.start_date', null)
            ->assertJsonPath('experiences.0.is_current', false);
    }

    public function test_experiences_are_ordered_consistently_in_admin_and_public_lists(): void
    {
        $roles = [
            ['role' => 'Current Recent', 'is_current' => true, 'start_date' => '2024-01-01', 'sort_order' => 99],
            ['role' => 'Current Older', 'is_current' => true, 'start_date' => '2020-01-01', 'sort_order' => 0],
            ['role' => 'Past Recent', 'is_current' => false, 'start_date' => '2025-01-01', 'sort_order' => 0],
            ['role' => 'Past Older', 'is_current' => false, 'start_date' => '2023-01-01', 'sort_order' => 0],
            ['role' => 'Undated First', 'is_current' => false, 'start_date' => null, 'sort_order' => 1],
            ['role' => 'Undated Second', 'is_current' => false, 'start_date' => null, 'sort_order' => 1],
        ];

        foreach ($roles as $role) {
            Experience::create($this->validExperience($role));
        }

        $expectedOrder = [
            'Current Recent',
            'Current Older',
            'Past Recent',
            'Past Older',
            'Undated Second',
            'Undated First',
        ];

        $adminResponse = $this->withToken($this->token)->getJson('/api/admin/experiences');
        $adminResponse->assertOk();
        $this->assertSame(
            $expectedOrder,
            array_column($adminResponse->json(), 'role'),
        );

        $publicResponse = $this->getJson('/api/experiences');
        $publicResponse->assertOk();
        $this->assertSame(
            $expectedOrder,
            array_column($publicResponse->json(), 'role'),
        );
    }

    public function test_admin_and_public_lists_support_zero_one_three_and_eight_experiences(): void
    {
        $this->getJson('/api/experiences')->assertOk()->assertJsonCount(0);

        foreach (range(1, 8) as $number) {
            Experience::create($this->validExperience([
                'role' => "Role {$number}",
                'start_date' => sprintf('202%d-01-01', $number % 10),
            ]));

            if (! in_array($number, [1, 3, 8], true)) {
                continue;
            }

            $this->getJson('/api/experiences')->assertOk()->assertJsonCount($number);
            $this->withToken($this->token)
                ->getJson('/api/admin/experiences')
                ->assertOk()
                ->assertJsonCount($number);
        }
    }

    private function validExperience(array $overrides = []): array
    {
        return array_merge([
            'role' => 'Developer',
            'organization' => 'Example Company',
            'period' => null,
            'description' => null,
            'start_date' => null,
            'end_date' => null,
            'is_current' => false,
            'employment_type' => null,
            'work_mode' => null,
            'location' => null,
            'highlights' => null,
            'technologies' => null,
            'sort_order' => 0,
        ], $overrides);
    }
}
