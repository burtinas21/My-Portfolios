<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Safe to run multiple times - all seeders use updateOrCreate or check before inserting.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ProfileSeeder::class,
            SocialLinkSeeder::class,
            ProjectSeeder::class,
            SkillSeeder::class,
            ExperienceSeeder::class,
        ]);
    }
}
