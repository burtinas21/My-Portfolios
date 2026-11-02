# Product Overview

## What is this project?

My Portfolio is a full-stack personal portfolio and resume application that showcases professional work, experience, skills, and contact information.

## Core Purpose

Provide a platform for professionals to display:
- Professional profile (name, headline, bio, location, availability status)
- Social media links
- Project showcase with detailed descriptions and technology stacks
- Work experience timeline
- Skill inventory organized by category
- Contact form for inquiries

## Key Features

### Public Website (Frontend)
- Browse-only access to profile, projects, skills, and experience
- Contact form to send messages
- Responsive design optimized for all devices
- Dark/light theme support
- Fast, SEO-friendly presentation

### Admin Dashboard (Backend)
- Secure authentication with token-based access
- CRUD operations for all portfolio content
- Project publishing control (draft/published)
- Image upload and management
- Message management and read tracking
- Content ordering/sorting

## Target Users

- **Primary:** Freelance professionals, developers, designers seeking portfolio solutions
- **Secondary:** Recruiters and potential clients viewing the portfolio

## Technology Stack

- **Backend:** Laravel 13 (PHP 8.3)
- **Frontend:** Vue.js 3 with Vite
- **Styling:** Tailwind CSS
- **Database:** SQLite (development), can switch to PostgreSQL/MySQL
- **Authentication:** Laravel Sanctum (API tokens)

## Deployment Model

- Backend API hosted separately from frontend
- Frontend served as static SPA
- CORS enabled for frontend communication
- File uploads stored in local/cloud storage
