<?php

namespace Database\Seeders;

use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    /**
     * Seed the social_links table. Safe to run multiple times.
     */
    public function run(): void
    {
        $links = [
            ['name' => 'GitHub', 'url' => 'https://github.com/yourusername', 'sort_order' => 1],
            ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/in/yourusername', 'sort_order' => 2],
            ['name' => 'Twitter', 'url' => 'https://twitter.com/yourusername', 'sort_order' => 3],
            ['name' => 'Email', 'url' => 'mailto:your.email@example.com', 'sort_order' => 4],
        ];

        foreach ($links as $link) {
            SocialLink::updateOrCreate(
                ['name' => $link['name']], // Match by name
                $link // Update or create with these values
            );
        }
    }
}
