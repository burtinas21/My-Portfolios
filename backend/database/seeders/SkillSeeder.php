<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Seed the skills table. Safe to run multiple times.
     */
    public function run(): void
    {
        $skills = [
            // Backend
            ['category' => 'Backend', 'name' => 'PHP', 'level' => 90, 'sort_order' => 1],
            ['category' => 'Backend', 'name' => 'Laravel', 'level' => 95, 'sort_order' => 2],
            ['category' => 'Backend', 'name' => 'Node.js', 'level' => 75, 'sort_order' => 3],
            ['category' => 'Backend', 'name' => 'PostgreSQL', 'level' => 85, 'sort_order' => 4],
            ['category' => 'Backend', 'name' => 'MySQL', 'level' => 85, 'sort_order' => 5],
            
            // Frontend
            ['category' => 'Frontend', 'name' => 'JavaScript', 'level' => 90, 'sort_order' => 6],
            ['category' => 'Frontend', 'name' => 'Vue.js', 'level' => 85, 'sort_order' => 7],
            ['category' => 'Frontend', 'name' => 'React', 'level' => 70, 'sort_order' => 8],
            ['category' => 'Frontend', 'name' => 'Tailwind CSS', 'level' => 90, 'sort_order' => 9],
            ['category' => 'Frontend', 'name' => 'HTML/CSS', 'level' => 95, 'sort_order' => 10],
            
            // DevOps & Tools
            ['category' => 'DevOps', 'name' => 'Git', 'level' => 85, 'sort_order' => 11],
            ['category' => 'DevOps', 'name' => 'Docker', 'level' => 75, 'sort_order' => 12],
            ['category' => 'DevOps', 'name' => 'Linux', 'level' => 80, 'sort_order' => 13],
            ['category' => 'DevOps', 'name' => 'AWS', 'level' => 65, 'sort_order' => 14],
        ];

        foreach ($skills as $skill) {
            Skill::updateOrCreate(
                ['category' => $skill['category'], 'name' => $skill['name']], // Match by category and name
                $skill // Update or create with these values
            );
        }
    }
}
