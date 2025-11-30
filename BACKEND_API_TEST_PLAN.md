# Backend API Test Plan
**Date:** November 30, 2025  
**Scope:** All API endpoints for regular users

---

## 🔐 Authentication Endpoints

### ✅ Tested: Admin Login
- **Endpoint:** `POST /api/admin/login`
- **Status:** ✅ PASSED
- **Result:** Admin login works correctly

### ⚠️ Needs Testing: Regular User Login
- **Endpoint:** `POST /api/login`
- **Test Cases:**
  1. ✅ Valid UCM email format (1234567@ucm.sk) + correct password
  2. ✅ Invalid email format (should reject)
  3. ✅ Wrong password (should increment failed attempts)
  4. ✅ 5 failed attempts (should send temporary password)
  5. ✅ Temporary password login
  6. ✅ Unverified email (should block login)

### ⚠️ Needs Testing: Registration
- **Endpoint:** `POST /api/register`
- **Test Cases:**
  1. Valid registration with UCM email
  2. Invalid email format rejection
  3. Password strength validation
  4. Student type selection (denny/externy)

---

## 👥 Team Endpoints

### ⚠️ Needs Testing: Team Creation
- **Endpoint:** `POST /api/teams`
- **Required:** Authenticated user
- **Test Cases:**
  1. ✅ Create team with valid data
  2. ✅ Unique team name validation
  3. ✅ Academic year validation
  4. ✅ Occupation validation
  5. ✅ User becomes Scrum Master automatically

### ⚠️ Needs Testing: Join Team
- **Endpoint:** `POST /api/teams/join`
- **Test Cases:**
  1. ✅ Join with valid invite code
  2. ✅ Invalid invite code (404)
  3. ✅ Already a member (409)
  4. ✅ Team full (403)
  5. ✅ Occupation required

### ⚠️ Needs Testing: Get Team Status
- **Endpoint:** `GET /api/user/team`
- **Test Cases:**
  1. ✅ Returns all teams user is member of
  2. ✅ Includes `is_scrum_master` flag
  3. ✅ Includes team members
  4. ✅ Includes academic year

### ⚠️ Needs Testing: View Team Details
- **Endpoint:** `GET /api/teams/{id}`
- **Test Cases:**
  1. ✅ Returns team with members
  2. ✅ Includes pivot data (role, occupation)
  3. ✅ Includes student_type for members
  4. ✅ 404 for non-existent team

### ⚠️ Needs Testing: Remove Member
- **Endpoint:** `DELETE /api/teams/{team}/members/{user}`
- **Test Cases:**
  1. ✅ Scrum Master can remove members
  2. ✅ Cannot remove Scrum Master
  3. ✅ 403 for non-Scrum Master
  4. ✅ 404 if user not in team

### ⚠️ Needs Testing: Leave Team
- **Endpoint:** `POST /api/teams/{team}/leave`
- **Test Cases:**
  1. ✅ Regular member can leave
  2. ✅ Scrum Master cannot leave
  3. ✅ 404 if not a member

---

## 📁 Project Endpoints

### ✅ Partially Tested: List Projects
- **Endpoint:** `GET /api/projects`
- **Status:** ✅ Projects visible on home page
- **Needs Testing:**
  1. ✅ Filter by type
  2. ✅ Filter by school_type
  3. ✅ Filter by year_of_study
  4. ✅ Filter by subject
  5. ✅ Filter by academic_year_id
  6. ✅ Search by title/description

### ⚠️ Needs Testing: Create Project
- **Endpoint:** `POST /api/projects`
- **Required:** Authenticated user, Scrum Master role
- **Test Cases:**
  1. ✅ Create game project
  2. ✅ Create web_app project
  3. ✅ Create mobile_app project
  4. ✅ Create library project
  5. ✅ File uploads (splash, video, type-specific)
  6. ✅ YouTube video URL
  7. ✅ Metadata storage (tech_stack, URLs, etc.)
  8. ✅ Validation (school_type, subject required)
  9. ✅ 403 if not Scrum Master

### ⚠️ Needs Testing: View Project
- **Endpoint:** `GET /api/projects/{id}`
- **Test Cases:**
  1. ✅ Returns project with team and members
  2. ✅ Includes academic year
  3. ✅ 404 for non-existent project

### ⚠️ Needs Testing: Update Project
- **Endpoint:** `PUT /api/projects/{id}`
- **Required:** Scrum Master of project's team
- **Test Cases:**
  1. ✅ Update project details
  2. ✅ Replace files (old files deleted)
  3. ✅ Clear nullable fields (year_of_study, release_date)
  4. ✅ Change project type
  5. ✅ 403 if not Scrum Master
  6. ✅ Input sanitization (URLs, metadata)

### ⚠️ Needs Testing: Rate Project
- **Endpoint:** `POST /api/projects/{id}/rate`
- **Test Cases:**
  1. ✅ Submit rating (1-5)
  2. ✅ Duplicate rating prevention (422)
  3. ✅ Race condition handling (DB lock)
  4. ✅ Rating average calculation
  5. ✅ Rating count update

### ⚠️ Needs Testing: Get User Rating
- **Endpoint:** `GET /api/projects/{id}/user-rating`
- **Test Cases:**
  1. ✅ Returns user's rating if exists
  2. ✅ Returns null if not rated

### ⚠️ Needs Testing: Increment Views
- **Endpoint:** `POST /api/projects/{id}/views`
- **Test Cases:**
  1. ✅ Increments view count
  2. ✅ Returns updated count

### ⚠️ Needs Testing: My Projects
- **Endpoint:** `GET /api/projects/my?team_id={id}`
- **Test Cases:**
  1. ✅ Returns projects for specific team
  2. ✅ Requires team_id parameter
  3. ✅ 422 if team_id missing

---

## 👤 User Endpoints

### ⚠️ Needs Testing: Get Current User
- **Endpoint:** `GET /api/user`
- **Test Cases:**
  1. ✅ Returns authenticated user data
  2. ✅ 401 if not authenticated

### ⚠️ Needs Testing: Update Profile
- **Endpoint:** `PUT /api/user`
- **Test Cases:**
  1. ✅ Update name
  2. ✅ Email change blocked (422)
  3. ✅ Validation

### ⚠️ Needs Testing: Update Avatar
- **Endpoint:** `POST /api/user/avatar`
- **Test Cases:**
  1. ✅ Upload image
  2. ✅ Old avatar deleted
  3. ✅ Image validation (type, size)
  4. ✅ File content verification

### ⚠️ Needs Testing: Update Password
- **Endpoint:** `PUT /api/user/password`
- **Test Cases:**
  1. ✅ Change password with current password
  2. ✅ Wrong current password (422)
  3. ✅ Revokes other tokens

---

## 🔒 Security Tests

### ⚠️ Needs Testing: Rate Limiting
- **Admin Login:** ✅ 5 attempts per minute (tested in code)
- **Regular Login:** ⚠️ Should test failed attempt counter
- **API Endpoints:** ⚠️ Check throttle middleware

### ⚠️ Needs Testing: Authorization
- ✅ Scrum Master checks (tested in code)
- ⚠️ Team membership validation
- ⚠️ Project ownership validation

### ⚠️ Needs Testing: Input Sanitization
- ✅ URL validation (tested in code)
- ✅ HTML sanitization (tested in code)
- ⚠️ SQL injection attempts
- ⚠️ XSS attempts

### ⚠️ Needs Testing: File Upload Security
- ✅ Image content verification (tested in code)
- ⚠️ File size limits
- ⚠️ File type validation
- ⚠️ Malicious file detection

---

## 📊 Test Coverage Summary

| Category | Tested | Needs Testing | Total |
|----------|--------|---------------|-------|
| Authentication | 1 | 6 | 7 |
| Teams | 0 | 15 | 15 |
| Projects | 1 | 20 | 21 |
| User | 0 | 8 | 8 |
| Security | 3 | 10 | 13 |
| **TOTAL** | **5** | **59** | **64** |

---

## 🚀 Quick Test Commands

### Using curl (if you have a test user token):

```bash
# Get projects
curl -H "Authorization: Bearer YOUR_TOKEN" http://127.0.0.1:8000/api/projects

# Get user teams
curl -H "Authorization: Bearer YOUR_TOKEN" http://127.0.0.1:8000/api/user/team

# Get project details
curl -H "Authorization: Bearer YOUR_TOKEN" http://127.0.0.1:8000/api/projects/1
```

### Using Laravel Tinker:

```php
php artisan tinker

// Get a test user
$user = User::first();

// Create token
$token = $user->createToken('test')->plainTextToken;

// Test team creation
$team = Team::factory()->create(['scrum_master_id' => $user->id]);
```

---

## ✅ Recommendations

1. **Create Test Users:** Set up seeders with test accounts
2. **API Testing Tool:** Use Postman or Insomnia for endpoint testing
3. **Automated Tests:** Consider PHPUnit for backend tests
4. **Integration Tests:** Test full user flows end-to-end

---

## 📝 Notes

- Most endpoints require authentication (Bearer token)
- Team operations require user to be team member
- Project creation/editing requires Scrum Master role
- File uploads require proper Content-Type headers

