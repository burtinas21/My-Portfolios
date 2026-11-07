# Portfolio API Documentation

Base URL: `http://localhost:8000/api`

## Table of Contents
1. [Authentication](#authentication)
2. [Public Endpoints](#public-endpoints)
3. [AI Assistant](#ai-assistant)
4. [Admin Endpoints](#admin-endpoints)
5. [Error Responses](#error-responses)

---

## Authentication

### Login
**POST** `/login`

Get an authentication token.

**Rate Limit:** 5 requests per minute

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "your-password"
}
```

**Success Response (200):**
```json
{
  "token": "1|abcd1234...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  }
}
```

**Error Response (422):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

---

### Logout
**POST** `/logout`

Delete the current access token.

**Authentication:** Required (Bearer token)

**Success Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

### Get Current User
**GET** `/me`

Get authenticated user information.

**Authentication:** Required (Bearer token)

**Success Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  }
}
```

---

## Public Endpoints

All public endpoints are read-only and do not require authentication.

### Get Site Bootstrap
**GET** `/site`

Fetches the public portfolio data in one request. The response includes `profile`,
`socials`, published `projects`, category-grouped `skills`, `experiences`, and,
when those features are installed, published `certifications`, CV availability,
and public assistant settings.

Social links are ordered by `sort_order`, then `id`, and include the nullable
`icon` value selected in the admin. Experiences include the legacy `period` and
`description` fields plus nullable date, employment, location, highlight, and
technology fields. Their ordering is described under [Get Experiences](#get-experiences).

**Caching:** Responses include a weak `ETag` and `Cache-Control: no-cache`.
Send the received value in `If-None-Match`; unchanged data returns `304 Not Modified`.
Portfolio edits invalidate the cached payload.

**Success Response (200):**
```json
{
  "profile": {},
  "socials": [
    {
      "id": 1,
      "name": "GitHub",
      "icon": "simple-icons:github",
      "url": "https://github.com/yourusername",
      "sort_order": 1
    }
  ],
  "projects": [],
  "skills": {},
  "experiences": [],
  "certifications": [],
  "cv": {
    "available": false,
    "size": null,
    "updated_at": null
  },
  "assistant": {
    "enabled": false,
    "greeting": "Hello!",
    "suggested_questions": []
  }
}
```

### Get Profile
**GET** `/profile`

Get the portfolio profile information.

**Success Response (200):**
```json
{
  "id": 1,
  "name": "Your Name",
  "initials": "YN",
  "headline": "Full-Stack Developer | Laravel & Vue.js Specialist",
  "bio": "Passionate full-stack developer...",
  "email": "your.email@example.com",
  "location": "Your City, Country",
  "status": "Available for freelance work",
  "avatar": "/storage/uploads/avatar.png",
  "avatar_optimized": "/storage/uploads/avatar.optimized.webp",
  "avatar_optimized_srcset": "/storage/uploads/avatar.optimized-480.webp 480w, /storage/uploads/avatar.optimized.webp 800w",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

`avatar_optimized` is the square WebP avatar derivative when available; it is
`null` otherwise. `avatar_optimized_srcset` is populated when responsive 480px
and 800px variants are available. The original `avatar` path is retained as a
fallback.

---

### Get Social Links
**GET** `/socials`

Get all social media links, ordered by `sort_order`, then `id`. Each link includes
its nullable Iconify `icon` name.

**Success Response (200):**
```json
[
  {
    "id": 1,
    "name": "GitHub",
    "icon": "simple-icons:github",
    "url": "https://github.com/yourusername",
    "sort_order": 1,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  {
    "id": 2,
    "name": "LinkedIn",
    "icon": null,
    "url": "https://linkedin.com/in/yourusername",
    "sort_order": 2,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

### Get Projects
**GET** `/projects`

Get all published projects, ordered by sort_order.

**Success Response (200):**
```json
[
  {
    "id": 1,
    "title": "E-Commerce Platform",
    "summary": "A full-featured online store with payment integration",
    "detail": "Built a complete e-commerce solution...",
    "tag": "Web App",
    "year": 2024,
    "url": "https://example.com/demo",
    "github_url": "https://github.com/yourname/project",
    "image": "/storage/uploads/project1.png",
    "stack": ["Laravel", "Vue.js", "Tailwind CSS", "PostgreSQL"],
    "is_published": true,
    "sort_order": 1,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

### Get Single Project
**GET** `/projects/{id}`

Get a single published project by ID.

**Success Response (200):**
```json
{
  "id": 1,
  "title": "E-Commerce Platform",
  "summary": "A full-featured online store",
  "detail": "Built a complete e-commerce solution...",
  "tag": "Web App",
  "year": 2024,
  "url": "https://example.com/demo",
  "github_url": "https://github.com/yourname/project",
  "image": "/storage/uploads/project1.png",
  "stack": ["Laravel", "Vue.js", "Tailwind CSS"],
  "is_published": true,
  "sort_order": 1,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

**Error Response (404):**
Returns 404 if project is unpublished or doesn't exist.

---

### Get Skills
**GET** `/skills`

Get all skills grouped by category, ordered by sort_order.

**Success Response (200):**
```json
{
  "Backend": [
    {
      "id": 1,
      "category": "Backend",
      "name": "PHP",
      "level": 90,
      "sort_order": 1,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    {
      "id": 2,
      "category": "Backend",
      "name": "Laravel",
      "level": 95,
      "sort_order": 2,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "Frontend": [
    {
      "id": 6,
      "category": "Frontend",
      "name": "Vue.js",
      "level": 85,
      "sort_order": 7,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

### Get Experiences
**GET** `/experiences`

Get all work experiences, ordered with current roles first, then by start date
descending with undated roles last, sort_order ascending, and ID descending.

**Success Response (200):**
```json
[
  {
    "id": 1,
    "role": "Senior Full-Stack Developer",
    "organization": "Tech Solutions Inc.",
    "period": "2022 - Present",
    "description": "Leading development of enterprise web applications...",
    "start_date": "2022-01-01",
    "end_date": null,
    "is_current": true,
    "employment_type": "Full-time",
    "work_mode": "Hybrid",
    "location": "New York, NY",
    "highlights": ["Led a team of five developers"],
    "technologies": ["Laravel", "Vue.js"],
    "sort_order": 1,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

### Send Contact Message
**POST** `/messages`

Submit a contact form message.

**Rate Limit:** 5 requests per minute

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "body": "Hello, I would like to discuss a project..."
}
```

`period` and `description` remain available for older entries. Date-based
entries can omit `period`; the client formats the date range from `start_date`,
`end_date`, and `is_current`.

**Validation Rules:**
- `name`: required, string, max 100 characters
- `email`: required, valid email, max 150 characters
- `body`: required, string, max 3000 characters

**Success Response (201):**
```json
{
  "message": "Thank you! I will reply soon."
}
```

**Error Response (422):**
```json
{
  "message": "The name field is required. (and 1 more error)",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field must be a valid email address."]
  }
}
```

---

## CV/Resume

Public endpoints for CV/resume information and downloads.

### Get CV Availability
**GET** `/cv`

Get information about CV availability. Does not expose file path or internal details.

**Success Response (200):**
```json
{
  "available": true,
  "size": 245760,
  "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

Or when CV is not available (doesn't exist, is hidden, or file missing):
```json
{
  "available": false,
  "size": null,
  "updated_at": null
}
```

---

### Download CV
**GET** `/cv/download`

Download the CV as a PDF file. Returns 404 if CV is not available (not uploaded, hidden, or file missing).

**Rate Limit:** 30 requests per minute

**Query Parameters:**
- `inline=1` (optional): View in browser instead of downloading. Does not increment download counter.

**Success Response (200):**
Returns PDF file with headers:
- `Content-Type: application/pdf`
- `Content-Disposition: attachment; filename="john-doe-CV.pdf"` (or `inline` with `?inline=1`)
- `X-Content-Type-Options: nosniff`
- `Cache-Control: no-cache`

Filename is generated from profile name: `{slug}-CV.pdf` (fallback: `CV.pdf`)

**Implementation Notes:**
- Download counter increments only for actual downloads (not inline views)
- No visitor data or IP addresses are stored
- File is served from private storage (not publicly accessible)

**Error Response (404):**
Returned when CV is not available (not uploaded, hidden, or file missing).

---

### Get Certifications
**GET** `/certifications`

Get published certifications ordered by `sort_order` ascending, then `issue_date` descending. Each item includes `is_expired`, which is true when its expiry date is before today. Unpublished certifications are not returned.

**Success Response (200):**
```json
[
  {
    "id": 1,
    "title": "Cloud Architecture Professional",
    "issuer": "Example Academy",
    "issuer_icon": "simple-icons:coursera",
    "issue_date": "2025-03-01",
    "expiry_date": "2028-03-01",
    "credential_id": "ABC-123",
    "credential_url": "https://example.com/verify/ABC-123",
    "image": "/storage/uploads/certificate.png",
    "description": "Advanced cloud architecture training.",
    "is_expired": false
  }
]
```

---

## AI Assistant

Public endpoints for interacting with the AI portfolio assistant.

### Get Assistant Info
**GET** `/assistant`

Get assistant configuration and suggested questions. Returns 200 even if assistant is disabled (just with `enabled: false`).

**Success Response (200):**
```json
{
  "enabled": true,
  "greeting": "Hello! I'm an AI assistant trained on the portfolio data. Ask me anything about the background, skills, projects, and experience.",
  "suggested_questions": [
    "What are your main skills?",
    "Tell me about your latest project",
    "What's your work experience?"
  ]
}
```

**Note:** The `extra_knowledge` field is NEVER returned to public clients.

---

### Chat with Assistant
**POST** `/chat`

Send a message and receive an AI-generated response based on portfolio data.

**Rate Limit:** 10 requests per minute per IP, plus daily global limit (default 300)

**Request Body:**
```json
{
  "messages": [
    {
      "role": "user",
      "content": "What are your main skills?"
    },
    {
      "role": "assistant",
      "content": "I have strong skills in PHP, Laravel, Vue.js..."
    },
    {
      "role": "user",
      "content": "Tell me more about your backend experience"
    }
  ]
}
```

**Validation Rules:**
- `messages`: required, array, 1-10 items
- `messages.*.role`: required, either "user" or "assistant"
- `messages.*.content`: required, string, 1-500 characters (trimmed, control characters removed)
- Last message must have `role: "user"`

**Success Response (200):**
```json
{
  "reply": "I have extensive experience with Laravel, having worked with it for 5+ years. I specialize in building scalable APIs and full-stack web applications..."
}
```

**Error Response (422):**
Validation error with details about which fields failed.

```json
{
  "message": "The messages.0.content field is required.",
  "errors": {
    "messages.0.content": ["The content field is required."]
  }
}
```

**Error Response (429):**
Returned when rate limit is exceeded.

```json
{
  "message": "Too many questions. Please try again in a minute."
}
```

**Error Response (503):**
Returned when assistant is disabled or not configured.

```json
{
  "message": "The AI assistant is currently unavailable. Please use the contact form to get in touch."
}
```

Or when API key is missing:

```json
{
  "message": "The AI assistant is not configured. Please contact the site administrator."
}
```

**Implementation Notes:**
- Email addresses are automatically stripped from AI replies before returning
- System prompt is built from database (profile, projects, skills, experiences, and assistant settings)
- Email addresses NEVER appear in system prompt sent to Anthropic
- Daily token usage is tracked in `chat_usage` table
- No message content is logged; only token counts are recorded

---

## Admin Endpoints

All admin endpoints require authentication. Include the Bearer token in the Authorization header:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

### Profile Management

#### Get Admin Profile
**GET** `/admin/profile`

**Authentication:** Required

**Success Response (200):**
Same as public `/profile` endpoint.

---

#### Update Profile
**PUT** `/admin/profile`

**Authentication:** Required

**Request Body:**
```json
{
  "name": "Your Name",
  "initials": "YN",
  "headline": "Full-Stack Developer",
  "bio": "Passionate developer...",
  "email": "your.email@example.com",
  "location": "Your City",
  "status": "Available",
  "avatar": "/storage/uploads/avatar.png"
}
```

**Validation Rules:**
- `name`: required, string, max 100
- `initials`: required, string, max 4
- `headline`: required, string, max 150
- `bio`: required, string, max 2000
- `email`: required, valid email
- `location`: nullable, string, max 100
- `status`: nullable, string, max 100
- `avatar`: nullable, string, max 255

**Success Response (200):**
Returns updated profile object.

---

### Social Links Management

#### List All Social Links
**GET** `/admin/socials`

**Authentication:** Required

**Success Response (200):**
Returns array of all social links.

---

#### Create Social Link
**POST** `/admin/socials`

**Authentication:** Required

**Request Body:**
```json
{
  "name": "Telegram",
  "icon": "simple-icons:telegram",
  "url": "https://t.me/username",
  "sort_order": 3
}
```

**Validation Rules:**
- `name`: required, string, max 50
- `url`: required, string, max 255; must start with `http://`, `https://`, `mailto:`, or `tel:`
- `icon`: nullable, string, max 80; when provided, must match `collection:icon-name` using lowercase letters, digits, and hyphens
- `sort_order`: nullable, integer, min 0

**Success Response (201):**
Returns created social link object.

---

#### Update Social Link
**PUT/PATCH** `/admin/socials/{id}`

**Authentication:** Required

**Request Body:**
Same fields as create. `name` and `url` are required; `sort_order` and `icon` are optional.

`icon` is nullable and may be omitted to keep the current icon. Use an empty
string or `null` to clear it.

**Success Response (200):**
Returns updated social link object.

---

#### Delete Social Link
**DELETE** `/admin/socials/{id}`

**Authentication:** Required

**Success Response (204):**
No content.

---

### Projects Management

#### List All Projects
**GET** `/admin/projects`

**Authentication:** Required

Returns ALL projects including unpublished drafts.

**Success Response (200):**
Returns array of all projects.

---

#### Create Project
**POST** `/admin/projects`

**Authentication:** Required

**Request Body:**
```json
{
  "title": "New Project",
  "summary": "A brief description",
  "detail": "Full project description...",
  "tag": "Web App",
  "year": 2024,
  "url": "https://example.com",
  "github_url": "https://github.com/yourname/project",
  "image": "/storage/uploads/project.png",
  "stack": ["Laravel", "Vue.js"],
  "is_published": true,
  "sort_order": 1
}
```

**Validation Rules:**
- `title`: required, string, max 150
- `summary`: required, string, max 255
- `detail`: nullable, string, max 3000
- `tag`: required, string, max 50
- `year`: required, integer, 2000-2100
- `url`: nullable, string, max 255
- `github_url`: nullable HTTPS GitHub repository URL, max 255
- `image`: nullable, string, max 255
- `stack`: nullable, array
- `stack.*`: string, max 40
- `is_published`: boolean
- `sort_order`: nullable, integer, min 0

**Success Response (201):**
Returns created project object.

---

#### Update Project
**PUT/PATCH** `/admin/projects/{id}`

**Authentication:** Required

**Request Body:**
Same as create.

**Success Response (200):**
Returns updated project object.

**Note:** If image field is updated, old image is automatically deleted from storage.

---

#### Delete Project
**DELETE** `/admin/projects/{id}`

**Authentication:** Required

**Success Response (204):**
No content.

**Note:** Associated image is automatically deleted from storage.

---

### Certifications Management

All certification management endpoints require authentication.

#### List All Certifications
**GET** `/admin/certifications`

Returns all certifications, including drafts, ordered by `sort_order` and then newest issue date.

#### Create Certification
**POST** `/admin/certifications`

Required fields: `title` (string, max 150), `issuer` (string, max 120), and `issue_date` (date, not in the future).

Optional fields: `issuer_icon` (Iconify name matching `namespace:name`, max 80), `expiry_date` (date on or after issue date), `credential_id` (max 100), `credential_url` (HTTPS only, max 255), `image` (relative `/storage/...` path only, max 255), `description` (max 500), `is_published` (boolean, defaults to true), and `sort_order` (integer, min 0, defaults to 0). Empty optional strings are stored as null.

**Success Response (201):** Returns the created certification object.

#### Update Certification
**PUT/PATCH** `/admin/certifications/{id}`

Uses the same fields and validation as create. Replaced upload images are removed from the public disk only when they are under `/storage/uploads/` and are not referenced by another record.

#### Delete Certification
**DELETE** `/admin/certifications/{id}`

Returns 204 No Content. An unused image under `/storage/uploads/` is removed from the public disk.

#### Reorder Certifications
**PATCH** `/admin/certifications/reorder`

Request body:
```json
{
  "certifications": [
    { "id": 1, "sort_order": 0 },
    { "id": 2, "sort_order": 1 }
  ]
}
```

Updates the supplied certification sort orders atomically and returns all certifications in display order.

---

### Skills Management

#### List All Skills
**GET** `/admin/skills`

**Authentication:** Required

**Success Response (200):**
Returns array of all skills.

---

#### Create Skill
**POST** `/admin/skills`

**Authentication:** Required

**Request Body:**
```json
{
  "category": "Backend",
  "name": "Python",
  "level": 75,
  "sort_order": 15
}
```

**Validation Rules:**
- `category`: required, string, max 50
- `name`: required, string, max 80
- `level`: required, integer, 0-100
- `sort_order`: nullable, integer, min 0

**Success Response (201):**
Returns created skill object.

---

#### Update Skill
**PUT/PATCH** `/admin/skills/{id}`

**Authentication:** Required

**Request Body:**
Same as create.

**Success Response (200):**
Returns updated skill object.

---

#### Delete Skill
**DELETE** `/admin/skills/{id}`

**Authentication:** Required

**Success Response (204):**
No content.

---

### Experiences Management

#### List All Experiences
**GET** `/admin/experiences`

**Authentication:** Required

**Success Response (200):**
Returns array of all experiences.

---

#### Create Experience
**POST** `/admin/experiences`

**Authentication:** Required

**Request Body:**
```json
{
  "role": "Senior Developer",
  "organization": "Company Name",
  "period": null,
  "description": "Job description...",
  "start_date": "2022-01-01",
  "end_date": null,
  "is_current": true,
  "employment_type": "Full-time",
  "work_mode": "Remote",
  "location": "New York, NY",
  "highlights": ["Led a team of five developers"],
  "technologies": ["Laravel", "Vue.js"],
  "sort_order": 1
}
```

**Validation Rules:**
- `role`: required, string, max 120
- `organization`: required, string, max 120
- `period`: nullable, string, max 50 (legacy fallback when dates are empty)
- `description`: nullable, string, max 1500
- `start_date`: nullable date, not in the future
- `end_date`: nullable date, on or after `start_date`; prohibited when `is_current` is true
- `is_current`: optional boolean, defaults to false
- `employment_type`: nullable, one of Full-time, Part-time, Internship, Freelance, Contract, Volunteer
- `work_mode`: nullable, one of Remote, On-site, Hybrid
- `location`: nullable, string, max 100
- `highlights`: nullable array of up to 6 strings, max 200 characters each
- `technologies`: nullable array of up to 8 strings, max 30 characters each
- `sort_order`: nullable, integer, min 0

Empty array items are trimmed and removed. Experience lists use the same ordering
as the public endpoint. Existing `period` and `description` values remain intact.

**Success Response (201):**
Returns created experience object.

---

#### Update Experience
**PUT/PATCH** `/admin/experiences/{id}`

**Authentication:** Required

**Request Body:**
Same as create.

**Success Response (200):**
Returns updated experience object.

---

#### Delete Experience
**DELETE** `/admin/experiences/{id}`

**Authentication:** Required

**Success Response (204):**
No content.

---

### Messages Management

#### List All Messages
**GET** `/admin/messages`

**Authentication:** Required

Returns all contact messages, latest first.

**Success Response (200):**
```json
[
  {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "body": "Hello, I would like to discuss...",
    "read_at": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

#### Mark Message as Read
**PATCH** `/admin/messages/{id}/read`

**Authentication:** Required

**Success Response (200):**
Returns updated message object with `read_at` timestamp.

---

#### Delete Message
**DELETE** `/admin/messages/{id}`

**Authentication:** Required

**Success Response (204):**
No content.

---

### AI Assistant Management

#### Get Assistant Settings
**GET** `/admin/assistant`

**Authentication:** Required

Get current assistant configuration including the extra knowledge field.

**Success Response (200):**
```json
{
  "id": 1,
  "enabled": true,
  "greeting": "Hello! I'm an AI assistant...",
  "extra_knowledge": "I charge $100/hour for consulting. I'm available for full-time roles.",
  "suggested_questions": [
    "What are your main skills?",
    "Tell me about your latest project"
  ],
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

---

#### Update Assistant Settings
**PUT** `/admin/assistant`

**Authentication:** Required

Update assistant configuration.

**Request Body:**
```json
{
  "enabled": true,
  "greeting": "Hello! Ask me anything about my work.",
  "extra_knowledge": "I charge $100/hour. I'm available for full-time roles starting next month.",
  "suggested_questions": [
    "What are your main skills?",
    "Tell me about your latest project",
    "What's your work experience?"
  ]
}
```

**Validation Rules:**
- `enabled`: required, boolean
- `greeting`: required, string, max 200
- `extra_knowledge`: nullable, string, max 3000
- `suggested_questions`: nullable, array, max 6 items
- `suggested_questions.*`: string, max 100

**Success Response (200):**
Returns updated settings object.

**Error Response (422):**
Validation error with field details.

---

#### Get Assistant Usage Statistics
**GET** `/admin/assistant/usage`

**Authentication:** Required

Get token usage statistics across different time periods.

**Success Response (200):**
```json
{
  "today": {
    "requests": 12,
    "input_tokens": 1250,
    "output_tokens": 890
  },
  "last_7_days": {
    "requests": 85,
    "input_tokens": 9340,
    "output_tokens": 6120
  },
  "last_30_days": {
    "requests": 320,
    "input_tokens": 38500,
    "output_tokens": 24680
  }
}
```

**Note:** Token counts help you track Anthropic API costs. Refer to the Anthropic Console for actual billing.

---

---

### CV Management

Admin endpoints for managing the CV/resume file.

#### Get CV Info
**GET** `/admin/cv`

**Authentication:** Required

Get current CV information or `{ exists: false }` if none uploaded.

**Success Response (200):**
```json
{
  "exists": true,
  "original_name": "my-resume",
  "size": 245760,
  "is_public": true,
  "download_count": 42,
  "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

Or when no CV exists:
```json
{
  "exists": false
}
```

---

#### Upload or Replace CV
**POST** `/admin/cv`

**Authentication:** Required

Upload a new CV or replace the existing one. When replacing, the old file is deleted and download counter is reset to 0.

**Request:** multipart/form-data

**Form Fields:**
- `file`: PDF file (required)

**Validation Rules:**
- `file`: required, file, mimes:pdf, mimetypes:application/pdf, max:5120 (5MB)
- PDF header bytes must be `%PDF-`

**Success Response (200):**
Returns updated CV info (same structure as GET).

**Error Response (422):**
```json
{
  "message": "The file must be a valid PDF.",
  "errors": {
    "file": ["The file is not a valid PDF document."]
  }
}
```

**Implementation Notes:**
- File is stored on private disk with a random UUID filename
- Original uploaded filename is sanitized and stored for display only
- Operation is transactional: if anything fails, the new file is deleted and old file is kept
- Old file is deleted only after successful database update

---

#### Toggle CV Visibility
**PATCH** `/admin/cv`

**Authentication:** Required

Toggle whether the CV is visible on the public site.

**Request Body:**
```json
{
  "is_public": false
}
```

**Validation Rules:**
- `is_public`: required, boolean

**Success Response (200):**
Returns updated CV info (same structure as GET).

---

#### Delete CV
**DELETE** `/admin/cv`

**Authentication:** Required

Delete the CV file and database record.

**Success Response (204):**
No content.

**Error Response (404):**
Returned when no CV exists.

---

#### Preview CV
**GET** `/admin/cv/file`

**Authentication:** Required

Stream the CV file for preview in the admin panel. File is displayed inline (not downloaded).

**Success Response (200):**
Returns PDF file with headers:
- `Content-Type: application/pdf`
- `Content-Disposition: inline; filename="{original_name}.pdf"`

**Error Response (404):**
Returned when CV not found or file missing.

---

### Image Upload

#### Upload Image
**POST** `/admin/uploads`

**Authentication:** Required

**Request:** multipart/form-data

**Form Fields:**
- `image`: Image file (jpg, jpeg, png, webp, max 2MB)
- `optimize_avatar`: Optional boolean. Send `1` for a profile avatar to retain the original and create square 480px/800px WebP variants.

**Success Response (201):**
```json
{
  "url": "http://localhost:8000/storage/uploads/1234567890_abc123.png",
  "path": "/storage/uploads/1234567890_abc123.png",
  "optimized_path": "/storage/uploads/1234567890_abc123.optimized.webp"
}
```

`optimized_path` is `null` when avatar optimization was not requested.

**Error Response (422):**
```json
{
  "message": "The image field must be an image.",
  "errors": {
    "image": ["The image field must be an image."]
  }
}
```

**Note:** Use the returned URL in profile `avatar` or project `image` fields.

---

## Error Responses

### 401 Unauthorized
Returned when authentication is required but not provided or invalid.

```json
{
  "message": "Unauthenticated."
}
```

### 404 Not Found
Returned when a resource doesn't exist.

```json
{
  "message": "No query results for model [App\\Models\\Project] 999"
}
```

### 422 Validation Error
Returned when request data fails validation.

```json
{
  "message": "The name field is required. (and 2 more errors)",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field must be a valid email address."],
    "body": ["The body field is required."]
  }
}
```

### 429 Too Many Requests
Returned when rate limit is exceeded.

```json
{
  "message": "Too Many Attempts."
}
```

---

## Rate Limiting

- **Login endpoint:** 5 requests per minute
- **Contact form:** 5 requests per minute
- **Chat endpoint:** 10 requests per minute per IP, plus daily global limit (default 300)
- **All other endpoints:** No rate limiting

---

## CORS

The API allows cross-origin requests from:
- Development: `http://localhost:5173`
- Production: Set via `FRONTEND_URL` environment variable

Credentials are supported for authentication cookies/tokens.

---

## Testing

Run the test suite:
```bash
php artisan test
```

All tests use in-memory SQLite database and are safe to run multiple times.
