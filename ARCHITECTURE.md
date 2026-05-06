# Game Registration Portal - Architecture Overview

**Version:** 1.1  
**Last Updated:** February 2, 2026  
**Tech Stack:** Laravel 11 + Vue 3 + PrimeVue + Sanctum

---

## Table of Contents
1. [System Overview](#system-overview)
2. [Technology Stack](#technology-stack)
3. [Architecture Patterns](#architecture-patterns)
4. [Backend Structure](#backend-structure)
5. [Frontend Structure](#frontend-structure)
6. [Authentication & Authorization](#authentication--authorization)
7. [Database Schema](#database-schema)
8. [API Endpoints](#api-endpoints)
9. [File Storage](#file-storage)
10. [Security Features](#security-features)
11. [Development Workflow](#development-workflow)

---

## System Overview

A web-based platform for UCM university students to register, showcase, and rate academic projects (games, applications, libraries). Features team management, project submissions with multimedia assets, and a sophisticated authentication system.

### Key Features
- **User Authentication:** Email verification, password reset, temporary password recovery
- **Team Management:** Create teams, invite members via codes, Scrum Master roles
- **Project Registry:** Upload games/apps with trailers, screenshots, source code, executables
- **Rating System:** Star ratings (1-5), view counters, user feedback
- **Academic Years:** Projects organized by academic periods
- **Role-Based Access:** User vs Admin roles (extensible)

---

## Technology Stack

### Backend
- **Framework:** Laravel 11.x
- **PHP Version:** 8.2+
- **Authentication:** Laravel Sanctum (token-based API auth)
- **Database:** MySQL/MariaDB
- **Email:** Laravel Mail with MailMessage notifications
- **Storage:** Local filesystem (`storage/app/public`)

### Frontend
- **Framework:** Vue 3 (Composition API)
- **Build Tool:** Vite 7
- **UI Library:** PrimeVue (Aura theme)
- **Theming:** CSS custom properties with light/dark mode (`data-theme` attribute on `<html>`)
- **HTTP Client:** Axios
- **Router:** Vue Router 4
- **i18n:** vue-i18n 9 — 11 locales (sk, en, cs, pl, hu, de, fr, it, es, pt, nl)
- **Icons:** PrimeIcons

### Development Tools
- **Backend:** Artisan CLI, Tinker, scheduled tasks
- **Frontend:** Hot Module Replacement (HMR), ESLint-ready
- **Database:** Migrations, seeders, factories

---

## Architecture Patterns

### Backend Patterns

#### 1. **Service Layer Pattern**
- **AuthService:** Encapsulates login, registration, verification, avatar upload logic
- **GameService:** Handles game creation business logic
- **ProjectService:** Facade for multi-project type handling (future-ready)

**Example:**
```php
// Controller delegates to service
public function login(Request $request) {
    $result = $this->authService->attemptLogin($email, $password);
    // Return response based on result status
}
```

#### 2. **FormRequest Validation**
- Validation extracted from controllers into dedicated classes
- Centralized error messages
- Custom authorization logic per request

**Classes:**
- `LoginTemporaryRequest`
- `ResetPasswordRequest`
- `UpdatePasswordRequest`
- `GameStoreRequest`

#### 3. **API Resources (Optional Layer)**
- Standardize JSON response shapes
- `GameResource`, `TeamResource` created but not yet wired
- Avoids exposing raw Eloquent models

#### 4. **Enum-Based Constants**
- `ProjectType` enum: `game`, `application`, `library`, `other`
- `TeamRole` enum: `scrum_master`, `member`

#### 5. **DTO Pattern (Data Transfer Objects)**
- `ProjectData` DTO for type-safe project creation
- Immutable value objects reduce stringly-typed bugs

### Frontend Patterns

#### 1. **Composition API**
- `ref()`, `computed()`, `onMounted()` for reactive state
- Composables pattern ready (e.g., `useAuth()`, `useProjects()`)

#### 2. **Centralized HTTP Client**
- Axios with global base URL and auth token injection
- Interceptors for 401 auto-logout

#### 3. **Component Structure**
- **Views:** Page-level components (`HomeView`, `LoginView`, `ProjectView`)
- **Components:** Reusable UI (`Navbar.vue`)
- **Router Guards:** `requiresAuth` meta for protected routes

#### 4. **Localization (i18n)**
- All UI text is externalized to JSON locale files under `frontend/src/locales/`
- `vue-i18n 9` runs in **composition mode** (`legacy: false`) so `useI18n()` is the only supported API
- Locale detection order: `localStorage('locale_preference')` → browser `navigator.language` → default `sk`
- Fallback locale: `en` (used when a key is missing in the active locale)
- Locale is switched at runtime via `setLocale(code)` exported from `i18n.js`, which also updates `html[lang]`

**Required boilerplate in every `<script setup>` that calls `t()` in its template:**
```javascript
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
```
> ⚠️ **Critical:** Omitting either line while using `t()` in the template causes a silent runtime error and a completely blank view. The build succeeds with no warnings, making this hard to spot. See [Troubleshooting — Blank Screen After Login](#common-issues) for details.

**Adding a new locale:**
1. Create `frontend/src/locales/<code>.json` (copy `en.json` as template)
2. Add `import <code> from './locales/<code>.json'` in `i18n.js`
3. Add the entry to `SUPPORTED_LOCALES` array in `i18n.js`
4. Add the code to the `messages` object in `createI18n()`

---

## Backend Structure

```
backend/
├── app/
│   ├── Console/
│   │   ├── Kernel.php                    # Task scheduler (token pruning)
│   │   └── Commands/
│   │       └── PruneExpiredPasswordResetTokens.php
│   ├── DTO/
│   │   └── ProjectData.php               # Immutable project creation DTO
│   ├── Enums/
│   │   ├── ProjectType.php               # game|application|library|other
│   │   └── TeamRole.php                  # scrum_master|member
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php        # Login, register, verify, reset password
│   │   │   ├── GameController.php        # CRUD for games/projects
│   │   │   └── TeamController.php        # Team creation, join, status
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php        # Admin/user role checks
│   │   ├── Requests/
│   │   │   ├── LoginTemporaryRequest.php
│   │   │   ├── ResetPasswordRequest.php
│   │   │   ├── UpdatePasswordRequest.php
│   │   │   └── GameStoreRequest.php
│   │   ├── Resources/
│   │   │   ├── GameResource.php          # Standardized game JSON
│   │   │   └── TeamResource.php          # Standardized team JSON
│   │   └── Traits/
│   │       └── ApiResponse.php           # Helper for consistent responses
│   ├── Models/
│   │   ├── User.php                      # Sanctum tokens, teams relation
│   │   ├── Team.php                      # Scrum master, invite codes
│   │   ├── Game.php                      # Projects with multimedia
│   │   ├── AcademicYear.php              # Academic periods
│   │   ├── GameRating.php                # User ratings
│   │   ├── TeamUser.php                  # Pivot with role
│   │   └── PasswordResetToken.php        # Reset/temporary tokens
│   ├── Notifications/
│   │   ├── VerifyEmailNotification.php   # Email verification
│   │   ├── PasswordResetNotification.php # Forgot password
│   │   └── TemporaryPasswordNotification.php # 5 failed attempts
│   └── Services/
│       ├── AuthService.php               # Authentication logic
│       ├── GameService.php               # Game creation
│       └── ProjectService.php            # Multi-type facade
├── config/
│   ├── sanctum.php                       # Token expiry: 2 hours
│   ├── cors.php                          # Frontend origins
│   └── database.php
├── database/
│   ├── migrations/
│   │   ├── *_create_users_table.php
│   │   ├── *_create_teams_table.php
│   │   ├── *_create_games_table.php
│   │   ├── *_create_password_reset_tokens_table.php
│   │   └── ...
│   ├── seeders/
│   └── factories/
├── routes/
│   ├── api.php                           # API routes (Sanctum protected)
│   ├── auth.php                          # Public auth routes
│   └── web.php
└── storage/
    ├── app/public/                       # User uploads (avatars, games)
    └── logs/
```

### Key Backend Components

#### AuthController
- **Public Routes:** `register`, `login`, `verifyEmail`, `forgotPassword`, `resetPassword`, `loginWithTemporaryPassword`
- **Protected Routes:** `logout`, `user`, `updateProfile`, `updateAvatar`, `updatePassword`

**Login Flow:**
1. Validate credentials → Check email verified → Check failed attempts
2. 5th failed attempt → Send temporary password email
3. \>5 failed attempts → Block login, send re-verification email
4. Success → Reset attempts counter, return token

#### GameController
- **store():** Validate team ownership, Scrum Master check, file uploads (trailer, splash, source, export)
- **index():** Paginated list with eager loading (team, academicYear)
- **show():** Increment views counter
- **rate():** One rating per user per game

#### TeamController
- **store():** Generate 6-char invite code, assign Scrum Master
- **join():** Validate invite code, add member to team
- **getTeamStatus():** Return user's teams with role info

---

## Frontend Structure

```
frontend/
├── public/
├── src/
│   ├── assets/
│   │   └── main.css                      # Tailwind imports + centralised .steam-theme overrides
│   ├── components/
│   │   └── Navbar.vue                    # Header with auth state
│   ├── features/
│   │   └── projects/
│   │       └── useProjects.js            # Composable (placeholder)
│   ├── router/
│   │   └── index.js                      # Route definitions + guards
│   ├── services/
│   │   └── apiClient.js                  # Axios wrapper (placeholder)
│   ├── types/
│   │   └── entities.d.ts                 # TypeScript interfaces
│   ├── views/
│   │   ├── HomeView.vue                  # Project grid, team selector
│   │   ├── LoginView.vue                 # Login + temporary password inline
│   │   ├── RegisterView.vue              # Registration form
│   │   ├── VerifyEmail.vue               # Email verification handler
│   │   ├── ForgotPasswordView.vue        # Request reset link
│   │   ├── ResetPasswordView.vue         # Token-based password reset
│   │   ├── TemporaryLoginView.vue        # Redirect to login (legacy)
│   │   ├── ProjectView.vue               # Project detail page
│   │   ├── GameView.vue                  # Legacy game detail
│   │   ├── AddProjectView.vue            # Project upload form
│   │   ├── AddGameView.vue               # Legacy game upload
│   │   └── TeamView.vue                  # Team detail page
│   ├── i18n.js                           # vue-i18n setup, locale detection, setLocale()
│   ├── locales/                          # Translation JSON files (11 languages)
│   │   ├── sk.json                       # Slovak — default locale
│   │   ├── en.json                       # English — fallback locale
│   │   └── cs|pl|hu|de|fr|it|es|pt|nl.json
│   ├── App.vue                           # Root component + inactivity logout
│   └── main.js                           # Vue app, PrimeVue, vue-i18n, axios setup
├── index.html
├── package.json
├── vite.config.js
└── tailwind.config.js
```

### Key Frontend Components

#### HomeView
- **Features:** Project grid, search/filter, team selector, academic year filter
- **Team Management:** Create team, join team, view team status
- **Not Logged In:** Show login prompt instead of projects
- **Clickable Teams:** Navigate to team detail

#### ProjectView / GameView
- **Multimedia:** YouTube embeds, local video player with controls
- **Rating:** Star rating system (1-5), one vote per user
- **Downloads:** Source code, executable export
- **Team Navigation:** Click team badge → team detail page

#### Navbar
- **Logged Out:** Register, Login buttons
- **Logged In:** Avatar, username, logout, "Zmeniť heslo" dialog
- **Conditional Link:** "Pridať projekt" only for Scrum Masters

#### LoginView
- **Standard Login:** Email + password
- **Temporary Password Mode:** Switches to temporary password input after 5 failed attempts
- **Links:** Forgot password, register

---

## Authentication & Authorization

### Authentication Flow

#### Registration
1. User submits email (must match `^[0-9]{7}@ucm\.sk$`), name, password
2. Backend creates user with `verification_token`, sends email
3. User clicks email link → `verifyEmail` endpoint → sets `email_verified_at`

#### Login
1. Check credentials → Check email verified → Check failed attempts
2. **Success:** Reset attempts, create Sanctum token (2-hour expiry), return user + token
3. **Wrong Password (1-4 attempts):** Increment counter, return remaining attempts
4. **5th Wrong Password:** Send temporary password email, increment to 5
5. **6+ Wrong Passwords:** Block login, return "too many attempts"

#### Temporary Password Recovery
1. User receives email with `XXXX-XXXX-XXXX` format password
2. Login form switches to temporary password mode
3. Backend validates hashed password, clears rate limiter, creates session
4. Token marked as used (one-time)

#### Password Reset
1. User requests reset via "Zabudnuté heslo?" → enters email
2. Backend generates 64-char token, sends email with link
3. User clicks link → frontend shows reset form with token in URL
4. Submit new password → backend validates token, updates password, revokes all sessions

### Authorization

#### Roles
- **user:** Default role, can create teams, join teams, rate games
- **admin:** Future role for moderation (routes scaffolded in `RoleMiddleware`)

#### Team Permissions
- **Scrum Master:** Can upload projects for their team
- **Member:** Can view team info, cannot upload

**Check in Frontend:**
```javascript
// Navbar checks if user can add project
canAddGame.value = isLoggedIn && isScrumMaster
```

**Check in Backend:**
```php
// GameController verifies team ownership
$team = Team::findOrFail($teamId);
if ($team->scrum_master_id !== $user->id) {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

---

## Database Schema

### Core Tables

#### users
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | varchar(255) | Display name |
| email | varchar(255) | Unique, `*****@ucm.sk` |
| password | varchar(255) | Bcrypt hashed |
| role | enum('user','admin') | Default: user |
| team_id | bigint nullable | FK to teams (legacy single-team) |
| avatar_path | varchar nullable | `avatars/*.jpg` |
| verification_token | varchar(64) nullable | Email verification |
| email_verified_at | timestamp nullable | |
| failed_login_attempts | int | Default: 0 |

#### teams
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | varchar(255) | |
| invite_code | varchar(6) | Unique, e.g., `A1B2C3` |
| scrum_master_id | bigint | FK to users |
| academic_year_id | bigint | FK to academic_years |

#### team_user (pivot)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| team_id | bigint | FK |
| user_id | bigint | FK |
| role | enum('scrum_master','member') | |

#### games (LEGACY - replaced by projects table)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| title | varchar(255) | |
| description | text | |
| category | varchar | Akčná, RPG, etc. |
| type | varchar nullable | game/application/library |
| team_id | bigint | FK |
| academic_year_id | bigint | FK |
| trailer_path | varchar nullable | Local or YouTube URL |
| splash_screen_path | varchar nullable | Poster image |
| source_code_path | varchar nullable | .zip |
| export_path | varchar nullable | .exe, .apk, WebGL |
| release_date | date nullable | |
| rating | decimal(3,2) nullable | Avg rating |
| rating_count | int | Number of ratings |
| views | int | View counter |

#### projects (NEW - universal project table)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| team_id | bigint | FK to teams |
| academic_year_id | bigint nullable | FK to academic_years |
| title | varchar(255) | Project name |
| description | text nullable | Project description |
| type | enum | game, web_app, mobile_app, library, other |
| school_type | enum nullable | ZŠ, SŠ, VŠ (základná/stredná/vysoká) |
| year_of_study | int nullable | 1-9 for ZŠ, 1-5 for SŠ/VŠ |
| subject | varchar nullable | Subject name (Slovenský jazyk, Matematika, etc.) |
| predmet | varchar nullable | University course code |
| release_date | date nullable | |
| splash_screen_path | varchar nullable | Thumbnail image |
| video_path | varchar nullable | Local video file |
| video_url | varchar nullable | External video URL (YouTube, etc.) |
| **files** | **json nullable** | **Universal file storage** |
| **metadata** | **json nullable** | **Type-specific metadata** |
| rating | decimal(3,2) | Average rating (default 0) |
| rating_count | int | Number of ratings (default 0) |
| views | int | View counter (default 0) |

**files JSON structure:**
```json
{
  "splash": "/path/to/splash.jpg",
  "video": "/path/to/video.mp4",
  "documentation": "/path/to/docs.pdf",
  "presentation": "/path/to/slides.pptx",
  "source_code": "/path/to/source.zip",
  "export": "/path/to/build.zip",
  "apk_file": "/path/to/app.apk",  // legacy
  "ios_file": "/path/to/app.ipa"   // legacy
}
```

**metadata JSON structure:**
```json
{
  "export_type": "standalone|webgl|mobile|executable",
  "tech_stack": "Unity, C#, Blender",
  "github_url": "https://github.com/...",
  "live_url": "https://...",
  "platform": "android|ios|both",
  "package_name": "@scope/package",
  "npm_url": "https://npmjs.com/..."
}
```

**File Upload Limits:**
- documentation: PDF/DOCX/ZIP/RAR, 10MB max
- presentation: PDF/PPT/PPTX, 15MB max
- source_code: ZIP/RAR, 200MB max
- export: ZIP/RAR/EXE/APK/IPA, 500MB max
- project_folder: ZIP/RAR, 20MB max (contains project files/docs)

#### game_ratings
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK |
| project_id | bigint | FK to projects (replaces game_id) |
| rating | int | 1-5 stars |
| Unique(user_id, project_id) | | One vote per user |

#### password_reset_tokens
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK |
| token | varchar(255) | Hash for temporary, raw for reset |
| type | enum('reset','temporary') | |
| expires_at | timestamp | 15min (temp) or 1hr (reset) |
| used | boolean | Default: false |
| used_at | timestamp nullable | |
| ip_address | varchar(45) nullable | |

#### academic_years
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | varchar(255) | "2024/2025" (strict YYYY/YYYY) |

### Relationships

**User:**
- `hasMany(Project)` via team
- `belongsToMany(Team)` via `team_user` pivot
- `hasMany(GameRating)`
- `hasMany(PasswordResetToken)`

**Team:**
- `belongsTo(User, 'scrum_master_id')`
- `belongsToMany(User)` via `team_user`
- `hasMany(Project)`
- `belongsTo(AcademicYear)`

**Project:**
- `belongsTo(Team)`
- `belongsTo(AcademicYear)`
- `hasMany(GameRating, 'project_id')`
- **Casts:** `files` => array, `metadata` => array

**Game (LEGACY):**
- `belongsTo(Team)`
- `belongsTo(AcademicYear)`
- `hasMany(GameRating)`

---

## API Endpoints

### Public Endpoints

#### Authentication
```
POST   /api/register                # Create account
POST   /api/login                   # Get token
POST   /api/verify-email            # Confirm email
POST   /api/forgot-password         # Request reset
POST   /api/reset-password          # Reset with token
POST   /api/login-temporary         # Login with temp password
```

#### Games (Read-Only)
```
GET    /api/games                   # List all projects
GET    /api/games/{id}              # Project detail
```

### Protected Endpoints (Sanctum Token Required)

#### User
```
POST   /api/logout
GET    /api/user                    # Current user info
PUT    /api/user                    # Update profile
POST   /api/user/avatar             # Upload avatar
PUT    /api/user/password           # Change password
```

#### Teams
```
GET    /api/user/team               # User's team status
POST   /api/teams                   # Create team
POST   /api/teams/join              # Join with code
```

#### Projects
```
GET    /api/projects                # List all projects (with filters: type, school_type, year_of_study, subject, search, academic_year_id)
POST   /api/projects                # Create project (Scrum Master only, requires active team)
PUT    /api/projects/{id}           # Update project (Scrum Master of project's team only)
POST   /api/projects/{id}           # Update with _method=PUT (for FormData compatibility)
GET    /api/projects/{id}           # Get project detail
POST   /api/projects/{id}/views     # Increment view count
POST   /api/projects/{id}/rate      # Submit rating (once per user)
GET    /api/projects/{id}/user-rating # Get user's rating
GET    /api/projects/my?team_id={id} # Get team's projects
```

#### Academic Years
```
GET    /api/academic-years          # List academic years
```

**Project Creation/Update Fields:**
- Required: title, type, team_id (create only), school_type, subject, predmet
- Optional: description, year_of_study, release_date
- Files: splash_screen (image), video (file or video_url), documentation (PDF/DOCX/ZIP/RAR), presentation (PDF/PPT/PPTX), source_code (ZIP/RAR), export (ZIP/RAR/EXE/APK/IPA), project_folder (ZIP/RAR - contains project files)
- Metadata: export_type (when export uploaded), tech_stack, github_url, live_url, platform, package_name, npm_url

#### Games (LEGACY - Use Projects endpoints)
```
POST   /api/games                   # Upload project (Scrum Master only)
POST   /api/games/{id}/rate         # Submit rating
```

### Admin Endpoints (Admin Role Required)

#### Admin Academic Years
```
POST   /api/admin/academic-years    # Create academic year (YYYY/YYYY)
```

### Request/Response Examples

#### Login Success
```json
// POST /api/login
{
  "email": "1234567@ucm.sk",
  "password": "SecurePass123!"
}

// Response 200
{
  "access_token": "1|abcd1234...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "1234567@ucm.sk",
    "role": "user"
  },
  "role": "user"
}
```

#### Login Failed (3rd Attempt)
```json
// Response 401
{
  "message": "Nesprávne heslo. Zostávajúce pokusy: 2",
  "failed_attempts": 3,
  "remaining_attempts": 2,
  "account_verified": true,
  "max_attempts": 5
}
```

#### Create Team
```json
// POST /api/teams (Authorization: Bearer {token})
{
  "name": "Team Alpha",
  "academic_year_id": 1
}

// Response 200
{
  "message": "Tím vytvorený",
  "team": {
    "id": 5,
    "name": "Team Alpha",
    "invite_code": "XYZ789",
    "scrum_master_id": 1
  }
}
```

---

## File Storage

### Structure
```
storage/app/public/
├── avatars/                          # User profile images
│   └── {hash}.jpg
├── projects/                         # Project files (organized by type)
│   ├── game/
│   │   ├── splash_screens/          # Poster images
│   │   ├── videos/                  # Video files
│   │   ├── documentation/           # PDF/DOCX/ZIP/RAR docs
│   │   ├── presentations/           # PDF/PPT/PPTX files
│   │   ├── source/                  # Source code ZIP/RAR archives
│   │   ├── exports/                 # Game builds (ZIP/RAR/EXE/APK/IPA)
│   │   └── folders/                 # Project folders (ZIP/RAR)
│   ├── web_app/
│   │   └── (same structure)
│   ├── mobile_app/
│   │   └── (same structure)
│   ├── library/
│   │   └── (same structure)
│   └── other/
│       └── (same structure)
├── trailers/                         # LEGACY: Old video location
└── splash_screens/                   # LEGACY: Old splash location
```

### Upload Validation

**Universal Files (all project types):**
- **documentation:** `mimes:pdf,docx,zip,rar`, `max:10240` (10MB - single DOCX, PDF, or archive ZIP/RAR)
- **presentation:** `mimes:pdf,ppt,pptx`, `max:15360` (15MB)
- **source_code:** `mimes:zip,rar`, `max:204800` (200MB)
- **export:** `mimes:zip,rar,exe,apk,ipa`, `max:512000` (500MB)
- **project_folder:** `mimes:zip,rar`, `max:20480` (20MB - complete project folder as archive)
- **export_type:** Required with export: `standalone`, `webgl`, `mobile`, `executable`

**Media Files:**
- **splash_screen:** `image`, `max:2048` (2MB) - enforced in frontend
- **video:** `video/mp4`, `max:102400` (100MB) - enforced in frontend
- **video_url:** Alternative to video file, validated as URL

**Type-Specific Metadata:**
- **tech_stack:** String (optional)
- **github_url:** URL (optional)
- **live_url:** URL (web_app, other)
- **platform:** `android|ios|both` (mobile_app)
- **package_name:** String (library)
- **npm_url:** URL (library)

### File Access
- Public URL: `http://127.0.0.1:8000/storage/{path}`
- Served via symlink: `php artisan storage:link`
- Files stored in `files` JSON column as relative paths
- Metadata stored in `metadata` JSON column

**Example Project Record:**
```json
{
  "id": 1,
  "title": "Math Game",
  "type": "game",
  "files": {
    "documentation": "projects/game/documentation/abc123.rar",
    "presentation": "projects/game/presentations/xyz789.pptx",
    "source_code": "projects/game/source/code_2024.rar",
    "export": "projects/game/exports/build_v1.rar",
    "project_folder": "projects/game/folders/project_complete.zip"
  },
  "metadata": {
    "export_type": "standalone",
    "tech_stack": "Unity, C#",
    "github_url": "https://github.com/user/repo"
  }
}
```

**Future Enhancement:** Use signed URLs for downloads to prevent hotlinking.

### PHP Configuration for Large Uploads

The following PHP settings in `php.ini` must be configured to support large file uploads:

```ini
; Maximum allowed size of an uploaded file
upload_max_filesize = 600M

; Maximum size of POST data that PHP will accept
post_max_size = 650M

; Maximum memory a script may use
memory_limit = 1024M

; Seconds a script is allowed to run
max_execution_time = 300
```

**Note:** These settings allow uploads up to **500MB** (export field maximum). Adjust based on your server capacity and network conditions. For XAMPP, edit `C:\xampp\php\php.ini`. For other setups, check your PHP installation directory.

---

## Security Features

### Password Security
- **Hashing:** Bcrypt via Laravel's `Hash` facade
- **Rules:** Min 8 chars, letters, mixed case, numbers, symbols (enforced in registration)
- **Change Password:** Requires current password verification
- **Session Revocation:** All tokens deleted on password change

### Rate Limiting
- **Failed Login Attempts:** Tracked per user, max 5 attempts
- **Rate Limiter Key:** `{email}|{ip}`
- **Lockout:** After 5 failures, temporary password sent
- **Reset:** Counter resets on successful login or email verification

### Token Management
- **Sanctum Tokens:** 2-hour expiration
- **Temporary Passwords:** 15-minute expiration, one-time use
- **Reset Tokens:** 1-hour expiration, one-time use
- **Automatic Cleanup:** Hourly scheduled task (`tokens:prune`)

### CORS Configuration
- **Allowed Origins:** `localhost:5173`, `127.0.0.1:5173`, `FRONTEND_URL` env variable
- **Credentials:** Supported for cookie-based auth (future)
- **Methods/Headers:** All allowed (tighten in production)

### Input Validation
- **Email Domain:** Must be `@ucm.sk` with 7-digit prefix
- **File Types:** Strict mime validation for uploads
- **SQL Injection:** Eloquent ORM parameterized queries
- **XSS:** Vue escapes output by default

### Frontend Security
- **Token Storage:** `localStorage` (consider `httpOnly` cookies in production)
- **401 Interceptor:** Auto-logout on invalid token
- **Inactivity Timeout:** 5-minute auto-logout
- **Route Guards:** `requiresAuth` meta flag

---

## Development Workflow

### Backend Setup
```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Storage symlink
php artisan storage:link

# Start server
php artisan serve
```

### Frontend Setup
```bash
cd frontend
npm install
npm run dev
```

### Scheduled Tasks
```bash
# Run scheduler in development
php artisan schedule:work

# In production (crontab)
* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
```

### Useful Commands
```bash
# Reset failed login attempts
php artisan tinker --execute="User::query()->update(['failed_login_attempts' => 0]);"

# Prune expired tokens
php artisan tokens:prune

# List scheduled tasks
php artisan schedule:list

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Testing Accounts
Create via Tinker or seeders:
```php
User::factory()->create([
    'email' => '1234567@ucm.sk',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);
```

---

## Future Enhancements

### Immediate (1-2 weeks)
- [ ] Wire `GameResource`/`TeamResource` to endpoints
- [ ] Add Feature tests for auth flows
- [ ] Implement Policies for authorization
- [ ] Add file download tracking
- [ ] Queue notifications with Horizon

### Medium-term (1-2 months)
- [ ] Admin dashboard for user/team management
- [ ] Signed URLs for secure downloads
- [ ] Search/filter indexing (Algolia/Meilisearch)
- [ ] Websockets for real-time notifications
- [ ] Export analytics to CSV

### Long-term (3+ months)
- [x] Multi-language support (i18n) — **Implemented** (vue-i18n 9, 11 locales, browser autodetect)
- [ ] Project versioning/changelog
- [ ] Comment system on projects
- [ ] Team leaderboards
- [ ] Integration with university LMS

---

## Troubleshooting

### Common Issues

**"Token Mismatch" on Login:**
- Check CORS configuration
- Verify `FRONTEND_URL` in `.env`
- Clear browser cache/cookies

**"Vite requires Node.js 20.19+:"**
- Upgrade Node.js or downgrade Vite to 5.2.0

**"Storage files not accessible:"**
- Run `php artisan storage:link`
- Check file permissions on `storage/app/public`

**"Temporary password not working:"**
- Check `password_reset_tokens` table for expired entries
- Verify email was sent (check `storage/logs/laravel.log`)
- Ensure format is exactly `XXXX-XXXX-XXXX` (14 chars)

**"Failed login attempts stuck at 5+:"**
- Reset counter: `User::find($id)->update(['failed_login_attempts' => 0]);`

---

## Contributing Guidelines

### Code Style
- **Backend:** PSR-12, use PHP-CS-Fixer
- **Frontend:** ESLint + Prettier
- **Commits:** Conventional Commits format

### Pull Request Process
1. Create feature branch from `main`
2. Write tests for new features
3. Update relevant documentation
4. Ensure all tests pass
5. Request review from maintainer

### Security Reporting
Report vulnerabilities privately to project maintainer.

---

**Document Maintained By:** Development Team  
**Repository:** [SimonSchmidt1/Game_Registration_Portal](https://github.com/SimonSchmidt1/Game_Registration_Portal)
