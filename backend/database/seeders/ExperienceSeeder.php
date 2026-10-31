<?php

namespace Database\Seeders;

use App\Models\Experience;
use Illuminate\Database\Seeder;

class ExperienceSeeder extends Seeder
{
    /**
     * Seed the experiences table. Safe to run multiple times.
     */
    public function run(): void
    {
        $experiences = [
            [
                'role' => 'Senior Full-Stack Developer',
                'organization' => 'Tech Solutions Inc.',
                'period' => '2022 - Present',
                'description' => 'Leading development of enterprise web applications using Laravel and Vue.js. Mentoring junior developers and architecting scalable solutions.',
                'sort_order' => 1,
            ],
            [
                'role' => 'Full-Stack Developer',
                'organization' => 'Digital Agency Co.',
                'period' => '2020 - 2022',
                'description' => 'Developed custom web applications for clients across various industries. Implemented CI/CD pipelines and improved deployment processes.',
                'sort_order' => 2,
            ],
            [
                'role' => 'Junior Web Developer',
                'organization' => 'Startup Ventures',
                'period' => '2019 - 2020',
                'description' => 'Built responsive websites and maintained existing codebases. Learned modern web development best practices and agile methodologies.',
                'sort_order' => 3,
            ],
        ];

        foreach ($experiences as $experience) {
            Experience::updateOrCreate(
                ['role' => $experience['role'], 'organization' => $experience['organization']], // Match by role and org
                $experience // Update or create with these values
            );
        }
    }
}
