# Technology Stack & Build System

## Backend Stack

### Core Framework
- **Laravel:** 13.17+ (PHP 8.3+)
- **Server:** PHP built-in server or compatible web server
- **Authentication:** Laravel Sanctum 4.0+ (API token-based)

### Key Dependencies
- `laravel/framework` - Core framework
- `laravel/sanctum` - API authentication
- `laravel/tinker` - REPL for debugging
- `laravel/pail` - Log viewing (dev)

### Development Tools
- `laravel/pint` - Code formatter (PSR-12 standard)
- `phpunit/phpunit` - Testing framework
- `mockery/mockery` - Mocking library
- `fakerphp/faker` - Test data generation
- `nunomaduro/collision` - Error rendering

### Database
- **Default:** SQLite (development)
- **Supported:** PostgreSQL, MySQL/MariaDB
- **Migrations:** Laravel migration system
- **Seeders:** Database seeders for test data

## Frontend Stack

### Core Framework
- **Vue.js:** 3.5.42+ (Composition API)
- **Build Tool:** Vite 8.2.2+
- **Routing:** Vue Router 5.3.1+
- **State Management:** Pinia 4.0.3+

### UI & Styling
- **CSS:** Tailwind CSS 4.3.3+
- **Icons:** 
  - Lucide Vue Next 0.577.0+ (SVG icons)
  - Iconify Vue 5.0.4+ (icon library)

### HTTP & API
- **Axios:** 1.21.0+ (HTTP client)

### Development
- **Node:** 22.18.0 or >=24.12.0
- **Package Manager:** npm

### Dev Dependencies
- `@vitejs/plugin-vue` - Vue support in Vite
- `@tailwindcss/vite` - Tailwind integration
- `vite-plugin-vue-devtools` - Vue dev tools

## Common Commands

### Backend Setup
```bash
# Initial setup (installs dependencies, generates key, runs migrations)
composer setup

# Development server
composer run dev

# Run tests
composer run test

# Code formatting
./vendor/bin/pint

# Database migration
php artisan migrate

# Database rollback
php artisan migrate:rollback

# Database seeding
php artisan db:seed

# Clear all caches
php artisan config:clear
php artisan cache:clear

# View logs in real-time
php artisan pail
```

### Frontend Setup
```bash
# Install dependencies
npm install

# Development server (Vite HMR on localhost:5173)
npm run dev

# Production build
npm run build

# Preview production build
npm run preview
```

## Environment Configuration

### Backend (.env)
- `APP_URL` - API URL (default: http://localhost:8000)
- `APP_DEBUG` - Debug mode (false in production)
- `DB_CONNECTION` - Database driver (sqlite, mysql, pgsql)
- `SESSION_DRIVER` - Session storage (database)
- `FILESYSTEM_DISK` - File storage (local)
- `CACHE_STORE` - Cache driver (database, redis)
- `FRONTEND_URL` - Frontend URL for CORS
- `ADMIN_EMAIL` / `ADMIN_PASSWORD` - Seeder credentials

### Frontend (.env)
- `VITE_API_URL` - Backend API URL (default: http://localhost:8000/api)

## Architecture Notes

### API Design
- RESTful endpoints at `/api/*`
- Admin endpoints at `/api/admin/*`
- Public endpoints at `/api/*` (read-only)
- Bearer token authentication via `Authorization` header
- JSON request/response format

### File Structure
- Controllers organized by scope (Api/, Admin/)
- Models in `app/Models/`
- Policies in `app/Policies/` for authorization
- Routes defined in `routes/api.php`

### Frontend Routing
- Client-side routing via Vue Router
- Views in `src/views/`
- Reusable components in `src/components/`
- API integration in `src/api/`
- Global state in `src/stores/` (Pinia)
- Composables in `src/composables/`

## Database Models

Core models with relationships:
- `User` - Admin accounts (authentication)
- `Profile` - Portfolio information
- `Project` - Portfolio projects (with publish status)
- `Skill` - Skills (grouped by category)
- `Experience` - Work history
- `SocialLink` - Social media links
- `Message` - Contact form submissions

## Testing

- **Backend:** PHPUnit test suite
- **Run tests:** `composer run test`
- **Test database:** In-memory SQLite (isolated)
- **Coverage:** Tests use real models but isolated DB

## Deployment Considerations

- Backend and frontend deployed separately
- Static frontend served via CDN or web server
- Backend requires PHP 8.3+ with composer
- Database migrations run on deployment
- Environment variables configured via .env
- File uploads require writable storage directory
- CORS configured for frontend domain

## Security Features

- CSRF protection for form submissions
- Password hashing with Bcrypt (12 rounds)
- Rate limiting on authentication and contact endpoints
- SQL injection prevention via prepared queries
- CORS restricted to frontend URL
- API token-based authentication (stateless)
- Authorization policies for resource access
