# Portfolio API Documentation

Base URL: `http://localhost:8000/api`

## Table of Contents
1. [Authentication](#authentication)
2. [Public Endpoints](#public-endpoints)
3. [Admin Endpoints](#admin-endpoints)
4. [Error Responses](#error-responses)

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
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

---

### Get Social Links
**GET** `/socials`

Get all social media links, ordered by sort_order.

**Success Response (200):**
```json
[
  {
    "id": 1,
    "name": "GitHub",
    "url": "https://github.com/yourusername",
    "sort_order": 1,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  {
    "id": 2,
    "name": "LinkedIn",
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

Get all work experiences, ordered by sort_order.

**Success Response (200):**
```json
[
  {
    "id": 1,
    "role": "Senior Full-Stack Developer",
    "organization": "Tech Solutions Inc.",
    "period": "2022 - Present",
    "description": "Leading development of enterprise web applications...",
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
  "name": "Twitter",
  "url": "https://twitter.com/username",
  "sort_order": 3
}
```

**Validation Rules:**
- `name`: required, string, max 50
- `url`: required, valid URL, max 255
- `sort_order`: nullable, integer, min 0

**Success Response (201):**
Returns created social link object.

---

#### Update Social Link
**PUT/PATCH** `/admin/socials/{id}`

**Authentication:** Required

**Request Body:**
Same as create, all fields required.

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
  "period": "2022 - Present",
  "description": "Job description...",
  "sort_order": 1
}
```

**Validation Rules:**
- `role`: required, string, max 120
- `organization`: required, string, max 120
- `period`: required, string, max 50
- `description`: nullable, string, max 1500
- `sort_order`: nullable, integer, min 0

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

### Image Upload

#### Upload Image
**POST** `/admin/uploads`

**Authentication:** Required

**Request:** multipart/form-data

**Form Fields:**
- `image`: Image file (jpg, jpeg, png, webp, max 2MB)

**Success Response (201):**
```json
{
  "url": "/storage/uploads/1234567890_abc123.png",
  "path": "uploads/1234567890_abc123.png"
}
```

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
