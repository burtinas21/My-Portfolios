# Laravel Portfolio API - Deployment Guide

## Quick Start Commands

```powershell
# 1. Install dependencies
composer install

# 2. Setup environment
copy .env.example .env
# Edit .env with your database credentials and admin user info

# 3. Generate application key
php artisan key:generate

# 4. Run migrations
php artisan migrate

# 5. Seed database with sample data
php artisan db:seed

# 6. Create storage symlink
php artisan storage:link

# 7. Start development server
php artisan serve
# API will be available at http://localhost:8000/api

# 8. Run tests
php artisan test
```

## Environment Configuration

### Required .env Variables

```env
# Application
APP_NAME=YourPortfolio
APP_ENV=local
APP_DEBUG=true  # Set to false in production!
APP_URL=http://localhost:8000

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=my-portfolio
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Admin User (for seeding)
ADMIN_NAME="Your Name"
ADMIN_EMAIL=your@email.com
ADMIN_PASSWORD=your_secure_password

# Frontend URL (for CORS)
FRONTEND_URL=http://localhost:5173
```

### Production Security

Before deploying to production, update:
1. `APP_DEBUG=false` - Hide error details
2. `APP_ENV=production` - Set environment
3. `ADMIN_PASSWORD` - Use strong password
4. `FRONTEND_URL` - Set to actual domain
5. `APP_URL` - Set to actual API URL

## Project Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # Admin CRUD controllers (auth required)
│   │   │   │   ├── ExperienceController.php
│   │   │   │   ├── MessageController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   ├── ProjectController.php
│   │   │   │   ├── SkillController.php
│   │   │   │   ├── SocialLinkController.php
│   │   │   │   └── UploadController.php
│   │   │   └── Api/             # Public API controllers
│   │   │       ├── AuthController.php
│   │   │       ├── ExperienceController.php
│   │   │       ├── MessageController.php
│   │   │       ├── ProfileController.php
│   │   │       ├── ProjectController.php
│   │   │       ├── SkillController.php
│   │   │       └── SocialLinkController.php
│   │   └── Middleware/
│   └── Models/
│       ├── Experience.php       # Singular, PascalCase
│       ├── Message.php
│       ├── Profile.php
│       ├── Project.php
│       ├── Skill.php
│       ├── SocialLink.php
│       └── User.php
├── database/
│   ├── migrations/              # Database schema
│   └── seeders/                 # Sample data
│       ├── AdminUserSeeder.php
│       ├── ExperienceSeeder.php
│       ├── ProfileSeeder.php
│       ├── ProjectSeeder.php
│       ├── SkillSeeder.php
│       └── SocialLinkSeeder.php
├── routes/
│   └── api.php                  # All API routes
├── storage/
│   └── app/
│       └── public/
│           └── uploads/         # Uploaded images
├── tests/
│   └── Feature/                 # Feature tests
│       ├── AdminApiTest.php
│       ├── AuthenticationTest.php
│       ├── ContactFormTest.php
│       └── PublicApiTest.php
├── API.md                       # API documentation
└── TEST_COMMANDS.md             # PowerShell test commands
```

## Database Schema

### Tables

1. **users** - Admin users (for authentication)
2. **personal_access_tokens** - Sanctum API tokens
3. **profiles** - Single profile record
4. **social_links** - Social media links
5. **projects** - Portfolio projects (with is_published flag)
6. **skills** - Technical skills (grouped by category)
7. **experiences** - Work experience entries
8. **messages** - Contact form submissions

### Key Features

- All content tables have `sort_order` for custom ordering
- Projects have `is_published` flag for drafts
- Messages have `read_at` timestamp for tracking
- Projects support JSON `stack` array for technologies

## API Routes Summary

### Public (No Auth)
- `GET /api/profile` - Get profile
- `GET /api/socials` - List social links
- `GET /api/projects` - List published projects only
- `GET /api/projects/{id}` - Get single published project
- `GET /api/skills` - List skills grouped by category
- `GET /api/experiences` - List experiences
- `POST /api/messages` - Submit contact form (throttled)

### Authentication
- `POST /api/login` - Get auth token (throttled)
- `POST /api/logout` - Revoke token (auth required)
- `GET /api/me` - Get current user (auth required)

### Admin (Auth Required)
- Profile: `GET`, `PUT /api/admin/profile`
- Socials: Full CRUD `/api/admin/socials`
- Projects: Full CRUD `/api/admin/projects` (sees all including drafts)
- Skills: Full CRUD `/api/admin/skills`
- Experiences: Full CRUD `/api/admin/experiences`
- Messages: `GET`, `PATCH /api/admin/messages/{id}/read`, `DELETE`
- Upload: `POST /api/admin/uploads` (image files)

**Total: 32 routes** (10 public, 3 auth, 19 admin)

## Authentication Flow

1. Frontend calls `POST /api/login` with credentials
2. Backend returns Bearer token
3. Frontend stores token (localStorage/cookie)
4. Frontend includes token in subsequent requests:
   ```
   Authorization: Bearer TOKEN_HERE
   ```
5. Backend validates token via Sanctum middleware
6. On logout, token is deleted from database

## Image Upload Flow

1. Admin uploads image via `POST /api/admin/uploads`
2. Image validated (jpg/jpeg/png/webp, max 2MB)
3. Stored in `storage/app/public/uploads/` with unique name
4. Returns public URL: `/storage/uploads/filename.ext`
5. Admin uses URL in profile avatar or project image field
6. Old images automatically deleted when updated/removed

## CORS Configuration

- Allows requests from `FRONTEND_URL` (.env)
- Supports credentials for Sanctum auth
- Applies to: `api/*`, `storage/*`, `sanctum/csrf-cookie`

## Security Features

1. **Authentication:** Laravel Sanctum with Bearer tokens
2. **Rate Limiting:** 5 req/min on login and contact form
3. **Validation:** All input validated via Form Requests
4. **CORS:** Restricted to specific frontend origin
5. **SQL Injection:** Protected by Eloquent ORM
6. **Password Hashing:** Bcrypt (12 rounds)
7. **Image Upload:** File type and size validation
8. **Admin Only:** All write operations require auth

## Testing

### Run All Tests
```powershell
php artisan test
```

### Test Coverage
- ✅ Public endpoints return 200
- ✅ Unpublished projects return 404
- ✅ Contact form validation
- ✅ Login success and failure
- ✅ Admin routes require auth
- ✅ Admin can see drafts
- ✅ CRUD operations work
- ✅ Input validation enforced

**28 tests, 76 assertions**

## Common Tasks

### Add New Admin User
```powershell
# Update .env
ADMIN_EMAIL=newadmin@example.com
ADMIN_PASSWORD=securepassword

# Re-run seeder
php artisan db:seed --class=AdminUserSeeder
```

### Reset Database (Development Only!)
```powershell
php artisan migrate:fresh --seed
```

### Clear All Caches
```powershell
php artisan optimize:clear
```

### View All Routes
```powershell
php artisan route:list --path=api
```

### Check Database Status
```powershell
php artisan migrate:status
```

## Production Deployment Checklist

- [ ] Set `APP_DEBUG=false` in .env
- [ ] Set `APP_ENV=production` in .env
- [ ] Use strong `ADMIN_PASSWORD`
- [ ] Update `FRONTEND_URL` to actual domain
- [ ] Update `APP_URL` to API URL
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan storage:link`
- [ ] Setup SSL certificate (HTTPS)
- [ ] Configure proper database backups
- [ ] Setup log rotation for `storage/logs`
- [ ] Consider using queue worker for emails
- [ ] Test all API endpoints in production
- [ ] Monitor error logs regularly

## Troubleshooting

### "Token Mismatch" Error
```powershell
php artisan config:clear
php artisan cache:clear
```

### Storage Link Not Working
```powershell
# Delete existing link (if any)
Remove-Item public\storage

# Recreate link
php artisan storage:link
```

### Database Connection Failed
- Check PostgreSQL is running
- Verify DB credentials in .env
- Test connection: `php artisan db:show`

### Tests Failing
```powershell
# Clear test cache
php artisan config:clear

# Run specific test
php artisan test --filter=AuthenticationTest
```

### CORS Errors
- Verify `FRONTEND_URL` in .env
- Check `config/cors.php` settings
- Clear config cache: `php artisan config:clear`

## Support Files

- **API.md** - Complete API documentation with examples
- **TEST_COMMANDS.md** - PowerShell commands for manual testing
- **.env.example** - Template with all required variables
- **tests/** - Automated test suite

## Notes

- Seeders are safe to run multiple times (use `updateOrCreate`)
- Only ONE admin user supported by design
- All timestamps in UTC (Laravel default)
- File uploads limited to 2MB
- API responses always JSON format
- Validation errors return 422 status
- Unauthorized requests return 401 status
