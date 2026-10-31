<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the projects table. Safe to run multiple times.
     */
    public function run(): void
    {
        $projects = [
            [
                'title' => 'E-Commerce Platform',
                'summary' => 'A full-featured online store with payment integration and admin dashboard',
                'detail' => 'Built a complete e-commerce solution with product management, shopping cart, checkout flow, and Stripe payment integration. Features include inventory tracking, order management, and real-time notifications.',
                'tag' => 'Web App',
                'year' => 2024,
                'url' => 'https://example.com/ecommerce-demo',
                'image' => null,
                'stack' => ['Laravel', 'Vue.js', 'Tailwind CSS', 'PostgreSQL', 'Stripe'],
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Task Management System',
                'summary' => 'Collaborative project management tool with real-time updates',
                'detail' => 'A Trello-like application with drag-and-drop functionality, team collaboration features, and real-time synchronization using WebSockets.',
                'tag' => 'SaaS',
                'year' => 2023,
                'url' => 'https://example.com/task-manager',
                'image' => null,
                'stack' => ['Laravel', 'Livewire', 'Alpine.js', 'MySQL'],
                'is_published' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Portfolio Website',
                'summary' => 'Personal portfolio with blog and contact form',
                'detail' => 'A modern, responsive portfolio website showcasing projects and blog posts. Features include markdown blog posts, image optimization, and contact form with spam protection.',
                'tag' => 'Website',
                'year' => 2023,
                'url' => null,
                'image' => null,
                'stack' => ['Laravel', 'Vue.js', 'Tailwind CSS'],
                'is_published' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($projects as $project) {
            Project::updateOrCreate(
                ['title' => $project['title']], // Match by title
                $project // Update or create with these values
            );
        }
    }
}
