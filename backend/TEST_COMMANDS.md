# API Testing Commands (PowerShell)

## 1. Login and Get Token

```powershell
# Login
$body = '{"email":"burtinas21@gmail.com","password":"myweb122127"}'
$response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/login" -Method POST -Headers @{"Content-Type"="application/json"; "Accept"="application/json"} -Body $body
$token = $response.token
Write-Host "Token: $token"
```

## 2. Test Public Endpoints (No Auth Required)

```powershell
# Get profile
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/profile" -Headers @{"Accept"="application/json"}

# Get projects (published only)
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/projects" -Headers @{"Accept"="application/json"}

# Get skills (grouped by category)
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/skills" -Headers @{"Accept"="application/json"}

# Get experiences
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/experiences" -Headers @{"Accept"="application/json"}

# Get social links
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/socials" -Headers @{"Accept"="application/json"}
```

## 3. Test Protected Endpoints (Requires Auth)

```powershell
# First, set your token
$token = "YOUR_TOKEN_HERE"

# Get authenticated user info
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/me" -Headers @{"Accept"="application/json"; "Authorization"="Bearer $token"}

# Logout
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/logout" -Method POST -Headers @{"Accept"="application/json"; "Authorization"="Bearer $token"}
```

## 4. Test Admin Endpoints

```powershell
# Get admin profile
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/profile" -Headers @{"Accept"="application/json"; "Authorization"="Bearer $token"}

# Update admin profile
$profileData = @{
    name = "Birtukan Nigussie"
    initials = "BN"
    headline = "Full-Stack Developer"
    bio = "Passionate developer..."
    email = "burtinas21@gmail.com"
    location = "Ethiopia"
    status = "Available"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/profile" -Method PUT -Headers @{"Content-Type"="application/json"; "Accept"="application/json"; "Authorization"="Bearer $token"} -Body $profileData

# Get all projects (including unpublished)
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/projects" -Headers @{"Accept"="application/json"; "Authorization"="Bearer $token"}

# Create a new skill
$skillData = @{
    category = "Backend"
    name = "Python"
    level = 75
    sort_order = 20
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/skills" -Method POST -Headers @{"Content-Type"="application/json"; "Accept"="application/json"; "Authorization"="Bearer $token"} -Body $skillData

# Update a skill (replace {id} with actual ID)
$updateData = @{
    category = "Backend"
    name = "Python"
    level = 80
    sort_order = 20
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/skills/{id}" -Method PUT -Headers @{"Content-Type"="application/json"; "Accept"="application/json"; "Authorization"="Bearer $token"} -Body $updateData

# Delete a skill
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/skills/{id}" -Method DELETE -Headers @{"Accept"="application/json"; "Authorization"="Bearer $token"}

# Get all messages
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/messages" -Headers @{"Accept"="application/json"; "Authorization"="Bearer $token"}

# Mark message as read
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/messages/{id}/read" -Method PATCH -Headers @{"Accept"="application/json"; "Authorization"="Bearer $token"}
```

## 5. Send Contact Message (Public, Throttled)

```powershell
$messageData = @{
    name = "Test User"
    email = "test@example.com"
    body = "This is a test message from the contact form."
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/messages" -Method POST -Headers @{"Content-Type"="application/json"; "Accept"="application/json"} -Body $messageData
```

## Quick Reference

- **Base URL**: `http://127.0.0.1:8000/api`
- **Admin Email**: `burtinas21@gmail.com`
- **Admin Password**: `myweb122127`
- **Login Endpoint**: `POST /api/login`
- **All Admin Routes**: Prefix with `/api/admin/` and require Bearer token

## Common Headers

```powershell
# For GET requests
@{"Accept"="application/json"; "Authorization"="Bearer $token"}

# For POST/PUT/PATCH requests
@{"Content-Type"="application/json"; "Accept"="application/json"; "Authorization"="Bearer $token"}
```
