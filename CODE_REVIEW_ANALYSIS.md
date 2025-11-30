# Code Review Analysis - Game Registration Portal

**Review Date:** November 30, 2025  
**Scope:** Full codebase analysis (Laravel backend + Vue frontend)

---

## Executive Summary

The application is generally well-structured with clear separation of concerns. However, several critical security issues, performance bottlenecks, and maintainability concerns require attention.

**Risk Level Summary:**
- 🔴 Critical: 5 issues (immediate action required)
- 🟠 High: 8 issues (should fix soon)
- 🟡 Medium: 12 issues (plan to address)
- 🟢 Low: 7 issues (nice to have)

---

## 🔴 CRITICAL ISSUES

### 1. Admin Password Hardcoded in Config File

**Location:** `backend/config/admin.php`
```php
'password' => env('ADMIN_PASSWORD', 'qKyUtyz0kSOyJxLU5E09zMkKospW6XZ9'),
```

**Problem:** Default admin password is committed to version control. Anyone with access to the repository can see it.

**Impact:** Complete system compromise if `.env` is not properly configured.

**Fix:**
```php
// Remove default value entirely
'password' => env('ADMIN_PASSWORD'),
```

Add validation in `AuthService::attemptAdminLogin()`:
```php
if (empty($adminPassword)) {
    \Log::critical('Admin password not configured');
    return ['status' => 'invalid_credentials'];
}
```

---

### 2. Temporary Password Logged in Plain Text

**Location:** `backend/app/Services/AuthService.php:237`
```php
\Log::info('Attempting to send temporary password email', [
    // ...
    'temporary_password' => $temporaryPassword  // ⚠️ SECURITY ISSUE
]);
```

**Problem:** Plain-text temporary passwords are written to log files. Anyone with log access can steal user credentials.

**Fix:** Remove this line immediately:
```php
\Log::info('Attempting to send temporary password email', [
    'user_id' => $user->id,
    'user_email' => $user->email,
    'mail_driver' => config('mail.default'),
    // Remove 'temporary_password' entirely
]);
```

---

### 3. Token Storage in localStorage (XSS Vulnerable)

**Location:** Multiple frontend files use `localStorage.getItem('access_token')`

**Problem:** Access tokens stored in localStorage are vulnerable to XSS attacks. Any malicious script can steal the token.

**Impact:** Account takeover via XSS.

**Recommended Fix:**
1. Use `httpOnly` cookies for token storage
2. Configure Laravel Sanctum for cookie-based authentication:

```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost:5173,127.0.0.1:5173')),
```

```javascript
// Frontend: Use withCredentials
axios.defaults.withCredentials = true
```

---

### 4. Missing CSRF Protection for SPA

**Location:** `backend/config/cors.php`

**Problem:** While CORS is configured, there's no CSRF token validation for state-changing requests.

**Fix:** Add CSRF middleware or use Sanctum's cookie-based auth which includes CSRF protection.

---

### 5. Admin Login Bypasses All Security Controls

**Location:** `backend/app/Services/AuthService.php:55-99`

**Problems:**
- No rate limiting on admin login attempts
- No failed attempt tracking
- Creates user automatically if not exists (auto-creation vulnerability)
- Config password compared directly (timing attack vulnerability)

**Fix:**
```php
public function attemptAdminLogin(string $email, string $password): array
{
    // Add rate limiting
    $key = 'admin-login-' . request()->ip();
    if (RateLimiter::tooManyAttempts($key, 5)) {
        return ['status' => 'too_many_attempts'];
    }
    
    // Use timing-safe comparison
    if (!hash_equals($adminPassword, $password)) {
        RateLimiter::hit($key);
        return ['status' => 'invalid_credentials'];
    }
    
    // Remove auto-creation - admin should be seeded
    $user = User::where('email', $adminEmail)->first();
    if (!$user) {
        return ['status' => 'invalid_credentials'];
    }
    // ...
}
```

---

## 🟠 HIGH PRIORITY ISSUES

### 6. No Try-Catch in Controllers

**Location:** Most controller methods in `ProjectController`, `AuthController`

**Problem:** Only 2 try-catch blocks found across all controllers. Unhandled exceptions expose stack traces in development and cause 500 errors in production.

**Example issue in** `ProjectController::store()`:
```php
// File operations can fail
$splashPath = $request->file('splash_screen')->store(...);
// If storage fails, returns 500 with no user feedback
```

**Fix:** Wrap operations in try-catch:
```php
try {
    // ... existing code
} catch (\Illuminate\Database\QueryException $e) {
    \Log::error('Database error', ['error' => $e->getMessage()]);
    return response()->json(['message' => 'Database error occurred'], 500);
} catch (\Exception $e) {
    \Log::error('Unexpected error', ['error' => $e->getMessage()]);
    return response()->json(['message' => 'An unexpected error occurred'], 500);
}
```

---

### 7. Missing Input Sanitization

**Location:** `ProjectController::extractMetadata()`, various form inputs

**Problem:** User inputs like `tech_stack`, `github_url`, `live_url` are not sanitized before storage.

**Fix:**
```php
private function extractMetadata(Request $request)
{
    $metadata = [];
    foreach (['live_url', 'github_url', 'npm_url'] as $field) {
        if ($request->has($field)) {
            $val = filter_var($request->input($field), FILTER_SANITIZE_URL);
            if ($val !== null && $val !== '') {
                $metadata[$field] = $val;
            }
        }
    }
    // ...
}
```

---

### 8. Race Condition in Rating System

**Location:** `ProjectController::rate()`

**Problem:** Check-then-act pattern vulnerable to race conditions:
```php
$existingRating = GameRating::where('project_id', $id)->where('user_id', $user->id)->first();
if ($existingRating) {
    return response()->json(['message' => 'Already rated'], 422);
}
GameRating::create([...]);  // User could submit twice simultaneously
```

**Fix:** Use database-level unique constraint and catch the exception:
```php
try {
    GameRating::create([
        'project_id' => $id,
        'user_id' => $user->id,
        'rating' => $request->rating
    ]);
} catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
    return response()->json(['message' => 'Projekt už bol hodnotený'], 422);
}
```

---

### 9. File Upload Arbitrary Path Traversal Risk

**Location:** `ProjectController::store()`, `update()`

**Problem:** Project type comes from user input and is used directly in file paths:
```php
$splashPath = $request->file('splash_screen')->store("projects/{$request->type}/...", 'public');
```

While Laravel's `store()` method is generally safe, the project type should be validated against allowed values before use.

**Fix:** Already using validation, but add explicit check:
```php
$allowedTypes = ['game', 'web_app', 'mobile_app', 'library', 'other'];
if (!in_array($projectType, $allowedTypes)) {
    return response()->json(['message' => 'Invalid project type'], 422);
}
```

---

### 10. No File Type Verification Beyond Extension

**Location:** File upload validation

**Problem:** Validation only checks MIME type from browser, not actual file contents.

**Fix:** Add server-side file content verification:
```php
// For images
$image = getimagesize($request->file('splash_screen')->getPathname());
if (!$image) {
    return response()->json(['message' => 'Invalid image file'], 422);
}

// For videos, use FFProbe or similar
```

---

### 11. Missing Authorization Policies

**Location:** Controllers handle authorization inline

**Problem:** Authorization logic is scattered across controllers instead of centralized in Policy classes.

**Fix:** Create Laravel Policies:
```bash
php artisan make:policy ProjectPolicy --model=Project
```

```php
class ProjectPolicy
{
    public function update(User $user, Project $project): bool
    {
        return $project->team->members()
            ->where('users.id', $user->id)
            ->where('team_user.role_in_team', 'scrum_master')
            ->exists();
    }
}
```

Then in controller:
```php
$this->authorize('update', $project);
```

---

### 12. Duplicate axios Configuration

**Location:** `main.js` and `services/apiClient.js`

**Problem:** Two separate axios configurations exist - one in `main.js` (lines 23-30) and one in `apiClient.js`. Only `main.js` version is used.

**Fix:** Consolidate to single apiClient:
```javascript
// main.js - remove axios configuration
// Use apiClient everywhere instead of direct axios
import { apiClient } from './services/apiClient'
```

---

### 13. Hard-coded API URL in main.js

**Location:** `frontend/src/main.js:23`
```javascript
axios.defaults.baseURL = 'http://127.0.0.1:8000/api'
```

**Problem:** Hardcoded URL overrides environment variable.

**Fix:**
```javascript
axios.defaults.baseURL = import.meta.env.VITE_API_URL + '/api'
```

---

## 🟡 MEDIUM PRIORITY ISSUES

### 14. N+1 Query Potential in Team Members Display

**Location:** `TeamController::show()`

**Problem:** While `team.members` is eager loaded, accessing pivot data may trigger additional queries.

**Current:**
```php
$team->load(['members', 'academicYear']);
```

**Better:**
```php
$team->load(['members' => function($query) {
    $query->select('users.id', 'users.name', 'users.email', 'users.avatar_path', 'users.student_type');
}, 'academicYear']);
```

---

### 15. Missing Database Indexes

**Locations to check:**
- `projects.school_type` - used in filters
- `projects.subject` - used in filters  
- `projects.year_of_study` - used in filters
- `game_ratings.project_id` + `user_id` - unique constraint exists?

**Fix:** Add migration:
```php
Schema::table('projects', function (Blueprint $table) {
    $table->index('school_type');
    $table->index('subject');
    $table->index('year_of_study');
});
```

---

### 16. Inconsistent Error Response Format

**Problem:** Different controllers return errors in different formats:
```php
// Sometimes:
return response()->json(['message' => 'Error'], 400);

// Sometimes:
return response()->json(['error' => 'Error'], 400);

// Sometimes includes extra fields
```

**Fix:** Use the `ApiResponse` trait consistently:
```php
use App\Http\Traits\ApiResponse;

class ProjectController extends Controller
{
    use ApiResponse;
    
    // Then use: $this->errorResponse('Message', 400);
}
```

---

### 17. Frontend Code Duplication

**Location:** `HomeView.vue`, `TeamView.vue`, `ProjectView.vue`

**Problem:** Same helper functions duplicated across views:
- `getAvatarUrl()`
- `getSplashUrl()`
- `formatProjectType()`
- `getSchoolTypeLabel()`

**Fix:** Create composable:
```javascript
// composables/useFormatters.js
export function useFormatters() {
    const API_URL = import.meta.env.VITE_API_URL
    
    const getAvatarUrl = (path) => {
        if (!path) return ''
        if (path.startsWith('http')) return path
        return `${API_URL}/storage/${path}?t=${Date.now()}`
    }
    
    // ... other formatters
    
    return { getAvatarUrl, getSplashUrl, formatProjectType, getSchoolTypeLabel }
}
```

---

### 18. Missing Loading States in Frontend

**Location:** Various views

**Problem:** Some operations don't show loading indicators (e.g., profile updates, team member removal).

**Fix:** Add consistent loading states:
```javascript
const isLoading = ref(false)

async function saveProfile() {
    isLoading.value = true
    try {
        // ... operation
    } finally {
        isLoading.value = false
    }
}
```

---

### 19. Legacy Code Not Removed

**Location:** `GameView.vue`, `AddGameView.vue`, `GameController.php`, `GameService.php`

**Problem:** Legacy game-related files are kept for "backward compatibility" but add maintenance burden and confusion.

**Recommendation:**
1. Add deprecation warnings
2. Set a removal date
3. Migrate any remaining data
4. Remove legacy files in next major version

---

### 20. Missing Input Validation on Frontend

**Location:** Various form inputs

**Problem:** Frontend relies on backend validation only. No client-side validation for better UX.

**Fix:** Add validation before submission:
```javascript
function validateForm() {
    const errors = {}
    if (!name.value || name.value.length < 3) {
        errors.name = 'Názov musí mať aspoň 3 znaky'
    }
    if (!schoolType.value) {
        errors.schoolType = 'Vyberte typ školy'
    }
    return errors
}
```

---

### 21. No Request Timeout Handling

**Location:** Frontend API calls

**Problem:** API calls can hang indefinitely without timeout handling.

**Fix:** Add timeout to apiClient:
```javascript
export const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_URL + '/api',
    timeout: 30000, // 30 seconds
})
```

Handle timeout in interceptor:
```javascript
apiClient.interceptors.response.use(
    response => response,
    error => {
        if (error.code === 'ECONNABORTED') {
            return Promise.reject({ message: 'Požiadavka vypršala. Skúste znova.' })
        }
        // ...
    }
)
```

---

### 22. Missing Pagination

**Location:** `ProjectController::index()`

**Problem:** Returns all projects without pagination:
```php
$projects = $query->orderBy('created_at', 'desc')->get();
```

**Impact:** Performance degrades with many projects.

**Fix:**
```php
$perPage = min((int) $request->query('per_page', 12), 100);
$projects = $query->orderBy('created_at', 'desc')->paginate($perPage);
return response()->json($projects);
```

---

### 23. Views Counter Abuse

**Location:** `ProjectController::incrementViews()`

**Problem:** No protection against artificial view inflation:
```php
public function incrementViews($id)
{
    $project = Project::findOrFail($id);
    $project->increment('views');  // Anyone can spam this
}
```

**Fix:** Add rate limiting and session-based tracking:
```php
public function incrementViews(Request $request, $id)
{
    $key = 'project-view-' . $id . '-' . ($request->user()?->id ?? $request->ip());
    
    if (Cache::has($key)) {
        return response()->json(['views' => Project::find($id)->views]);
    }
    
    Cache::put($key, true, now()->addHour());
    
    $project = Project::findOrFail($id);
    $project->increment('views');
    return response()->json(['views' => $project->views]);
}
```

---

### 24. Missing Transaction Wrapper

**Location:** `ProjectController::store()`, `update()`

**Problem:** Multi-step operations (file upload + DB insert) are not wrapped in transactions.

**Fix:**
```php
return DB::transaction(function () use ($request, $validated) {
    // File uploads
    // Database insert
    // Return response
});
```

---

### 25. Inactivity Timeout Inconsistency

**Location:** `App.vue` (5 min) vs Sanctum token (2 hours)

**Problem:** Frontend logs out after 5 minutes inactivity, but token is valid for 2 hours. Tokens accumulate.

**Recommendation:** Either:
1. Reduce token expiry to match frontend timeout
2. Call logout API when inactivity detected
3. Use refresh token pattern

---

## 🟢 LOW PRIORITY ISSUES

### 26. Console.log Statements in Production

**Location:** Multiple frontend files

**Problem:** Debug logging remains in production code.

**Fix:** Use environment-aware logging:
```javascript
const isDev = import.meta.env.DEV

function log(...args) {
    if (isDev) console.log(...args)
}
```

---

### 27. Missing API Version

**Location:** `routes/api.php`

**Problem:** No API versioning. Breaking changes will affect all clients.

**Fix:** Add version prefix:
```php
Route::prefix('v1')->group(function () {
    // Current routes
});
```

---

### 28. TypeScript Types Not Used

**Location:** `frontend/src/types/entities.d.ts`

**Problem:** TypeScript types defined but not used anywhere (project uses JS).

**Recommendation:** Either:
1. Convert to TypeScript
2. Remove unused types file

---

### 29. Missing Environment Variable Validation

**Location:** Application bootstrap

**Problem:** App starts without required environment variables, fails at runtime.

**Fix:** Add to `AppServiceProvider::boot()`:
```php
if (app()->environment('production')) {
    $required = ['APP_KEY', 'DB_DATABASE', 'MAIL_HOST', 'FRONTEND_URL'];
    foreach ($required as $var) {
        if (empty(env($var))) {
            throw new \RuntimeException("Missing required env var: {$var}");
        }
    }
}
```

---

### 30. Unused Imports

**Location:** Various files

**Problem:** Unused imports increase bundle size and reduce readability.

**Fix:** Run linters and remove unused imports.

---

### 31. apiClient Not Used

**Location:** `frontend/src/services/apiClient.js`

**Problem:** Well-designed apiClient exists but is never imported. All views use inline fetch/axios.

**Fix:** Gradually refactor views to use apiClient.

---

### 32. Missing Health Check Endpoint

**Problem:** No endpoint to verify application is running correctly.

**Fix:**
```php
// routes/api.php
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => now()->toISOString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});
```

---

## Architecture Recommendations

### 1. Implement Repository Pattern

Current controllers directly query models. Consider adding repository layer for:
- Better testability
- Separation of data access logic
- Easier caching implementation

### 2. Add Form Request Classes

Move validation from controllers to dedicated FormRequest classes for:
- ProjectStoreRequest
- ProjectUpdateRequest
- TeamStoreRequest
- TeamJoinRequest

### 3. Implement Event/Listener Pattern

Replace inline notifications with events:
```php
// Instead of:
$user->notify(new TemporaryPasswordNotification($password));

// Use:
event(new TemporaryPasswordRequested($user, $password));

// Listener handles notification
```

### 4. Add Comprehensive Logging

Implement structured logging for:
- All authentication events
- All file uploads
- All authorization failures
- Performance metrics

### 5. Consider API Resource Classes

Use Laravel's API Resources for consistent response formatting:
```php
return new ProjectResource($project);
```

---

## Testing Recommendations

### Current State
- Limited test coverage
- Basic feature tests exist for auth

### Recommendations
1. Add unit tests for services
2. Add feature tests for all API endpoints
3. Add frontend component tests
4. Set up CI/CD with automated testing

---

## Performance Recommendations

1. **Add Redis caching** for:
   - Academic years list
   - Project counts
   - User session data

2. **Optimize file uploads**:
   - Use chunked uploads for large files
   - Generate thumbnails asynchronously
   - Consider CDN for static files

3. **Add database query logging** in development to catch N+1 issues

4. **Implement lazy loading** for project list images

---

## Summary Action Plan

### Immediate (This Week)
1. ⚠️ Remove hardcoded admin password
2. ⚠️ Remove temp password from logs
3. ⚠️ Add rate limiting to admin login
4. Add try-catch to controllers

### Short Term (This Month)
5. Migrate to httpOnly cookie auth
6. Add Laravel Policies
7. Fix race condition in ratings
8. Add pagination
9. Consolidate axios configuration

### Medium Term (Next Quarter)
10. Remove legacy game code
11. Add comprehensive tests
12. Implement repository pattern
13. Add Redis caching
14. Create composables for shared code

---

**Reviewed by:** AI Code Review  
**Next Review:** Recommend in 30 days after critical fixes

