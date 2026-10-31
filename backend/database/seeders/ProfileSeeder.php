<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Seed the profile table. Safe to run multiple times.
     */
    public function run(): void
    {
        // Only seed if the profile doesn't exist yet (safe to run multiple times)
        if (Profile::count() === 0) {
            Profile::create([
                'name' => 'Your Name',
                'initials' => 'YN',
                'headline' => 'Full-Stack Developer | Laravel & Vue.js Specialist',
                'bio' => 'Passionate full-stack developer with expertise in building modern web applications. I specialize in creating elegant, performant solutions using Laravel, Vue.js, and other cutting-edge technologies. I love turning complex problems into simple, beautiful interfaces.',
                'email' => 'your.email@example.com',
                'location' => 'Your City, Country',
                'status' => 'Available for freelance work',
                'avatar' => null,
            ]);
        }
    }
}
