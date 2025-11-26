# Admin Foundation Setup - Quick Reference

## What Was Added

### ✅ Foundation for Any Admin Feature

**5 Core Components** that support ANY future admin functionality:

1. **Role Checking Methods** (User model)
2. **Admin Middleware** (Route protection)
3. **Soft Deletes** (Recovery capability)
4. **Database Indexes** (Fast queries)
5. **Admin Routes Structure** (Ready to expand)

---

## How to Use

### 1. Check if User is Admin

```php
// In controllers
if ($request->user()->isAdmin()) {
    // Admin-only logic
}

// In blade/views
@if(auth()->user()->isAdmin())
    <admin-button />
@endif
```

### 2. Protect Routes with Admin Middleware

```php
// In routes/api.php - already set up at bottom of file
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Any route here requires admin role
    Route::get('/whatever', [YourController::class, 'method']);
});
```

### 3. Access Soft Deleted Records

```php
// Include deleted records in query
$allGames = Game::withTrashed()->get();

// Get only deleted records
$deletedGames = Game::onlyTrashed()->get();

// Restore a deleted record
$game = Game::withTrashed()->find($id);
$game->restore();

// Permanently delete (use carefully!)
$game->forceDelete();
```

### 4. Common Admin Queries (Optimized with Indexes)

```php
// Get recent users (fast - indexed created_at)
$recentUsers = User::orderBy('created_at', 'desc')->paginate(20);

// Filter by role (fast - indexed role)
$admins = User::where('role', 'admin')->get();

// Find unverified users (fast - indexed email_verified_at)
$unverified = User::whereNull('email_verified_at')->get();

// Get team games timeline (fast - composite index)
$teamGames = Game::where('team_id', $teamId)
    ->orderBy('created_at', 'desc')
    ->get();
```

### 5. Test Admin Access

```bash
# Create admin user in tinker
php artisan tinker
$user = User::find(1);
$user->role = 'admin';
$user->save();

# Test endpoint
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/admin/dashboard
```

---

## Frontend Integration (When Ready)

```javascript
// In Vue composable or component
const user = computed(() => JSON.parse(localStorage.getItem('user') || '{}'))
const isAdmin = computed(() => user.value?.role === 'admin')

// Show admin link in navbar
<router-link v-if="isAdmin" to="/admin">Admin Panel</router-link>
```

---

## What's NOT Included (Intentionally)

❌ Specific admin features (user management, analytics, etc.)  
❌ Admin UI/dashboard  
❌ Audit logs  
❌ Permission granularity (just admin/user for now)  

**Why?** You said you don't know what functionality you'll need yet. This gives you the foundation to build ANYTHING without committing to specific features.

---

## Adding Your First Admin Feature (Example)

When you're ready, here's how easy it is:

```php
// 1. Create controller
php artisan make:controller Admin/UserController

// 2. Add methods
public function index() {
    return User::withTrashed()
        ->orderBy('created_at', 'desc')
        ->paginate(20);
}

public function destroy($id) {
    User::find($id)->delete(); // Soft delete
    return response()->json(['message' => 'User deleted']);
}

public function restore($id) {
    User::withTrashed()->find($id)->restore();
    return response()->json(['message' => 'User restored']);
}

// 3. Add routes (already protected by middleware)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/users/{id}/restore', [UserController::class, 'restore']);
});
```

That's it! The foundation handles auth, role checking, and safe deletion automatically.

---

## Database Changes

**Migrations Applied:**
- ✅ `deleted_at` column added to users, teams, games
- ✅ Indexes added for fast admin queries

**No breaking changes** - existing code continues working normally.

---

## Quick Commands

```bash
# See all migrations
php artisan migrate:status

# Create admin user
php artisan tinker
User::find(1)->update(['role' => 'admin']);

# View soft deleted records
php artisan tinker
Game::onlyTrashed()->count();
```

---

**Next Steps:** Build whatever admin features you need - the foundation is ready! 🚀
