# Admin Panel Troubleshooting Guide

**Last Updated:** November 30, 2025

---

## Common Issues and Solutions

### 1. "Target class [role] does not exist" Error

**Symptoms:**
- 500 Internal Server Error on `/api/admin/*` endpoints
- Error in logs: `Target class [role] does not exist`
- Admin panel stats showing 0 or not loading

**Root Cause:**
Laravel was trying to resolve `'role'` as a class name when using `'role:admin'` middleware syntax. The middleware parameter parsing wasn't working correctly because:
- The `RoleMiddleware` expects parameters (`...$roles`)
- Laravel's middleware resolution was failing to parse the `:admin` parameter
- It tried to resolve `'role'` as a class instead of recognizing it as middleware

**Solution Applied:**
1. Created dedicated `EnsureUserIsAdmin` middleware (`app/Http/Middleware/EnsureUserIsAdmin.php`)
   - No parameters needed - directly checks `$user->role === 'admin'`
   - Simpler and more reliable
2. Registered as `'admin'` alias in `Kernel.php`
3. Updated routes to use `'admin'` instead of `'role:admin'`

**Prevention:**
- Always restart Laravel server after changing middleware registration
- Use dedicated middleware for single-purpose checks (like admin-only)
- Use parameterized middleware (`RoleMiddleware`) only when you need multiple roles

---

### 2. Stats Showing 0

**Possible Causes:**
1. Migration not run (`status` column missing)
2. Database queries failing silently
3. Projects relationship not working

**Solutions:**
1. **Run migration:**
   ```bash
   cd backend
   php artisan migrate
   ```

2. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Verify data exists:**
   - Check `teams` table has records
   - Check `projects` table has records
   - Check `users` table has non-admin users

4. **Check browser console:**
   - Open DevTools (F12)
   - Check Network tab for `/api/admin/stats` response
   - Check Console for error messages

---

### 3. Admin Button Not Visible

**Check:**
1. User role in database:
   ```sql
   SELECT id, email, role FROM users WHERE email = 'your-admin@email.com';
   ```
   Should show `role = 'admin'`

2. LocalStorage:
   - Open DevTools → Application → Local Storage
   - Check `user` key contains `{"role":"admin"}`

3. **Fix:**
   - Logout and login again as admin
   - Clear browser cache
   - Check `Navbar.vue` - `isAdmin` computed property

---

### 4. Access Denied (403) Errors

**Check:**
1. User is authenticated (has valid token)
2. User role is 'admin' in database
3. Middleware is properly registered in `Kernel.php`
4. Routes are using correct middleware: `['auth:sanctum', 'admin']`

**Debug:**
```php
// In AdminController, add temporarily:
Log::info('Admin access attempt', [
    'user_id' => auth()->id(),
    'user_role' => auth()->user()->role ?? 'null'
]);
```

---

### 5. Teams/Projects Not Loading

**Check:**
1. **Database relationships:**
   - `Team` model has `projects()` relationship
   - `Project` model has `team()` relationship

2. **Column existence:**
   - `status` column might not exist (migration not run)
   - Code handles this gracefully, but features won't work

3. **Check logs for SQL errors:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "sql\|error"
   ```

---

### 6. Server Restart Required

**When to restart:**
- After changing `Kernel.php` (middleware registration)
- After changing route files
- After changing service providers

**How to restart:**
```bash
# Stop server (Ctrl+C in terminal)
cd backend
php artisan serve
```

**Clear cache first:**
```bash
php artisan optimize:clear
```

---

## Debugging Steps

### Step 1: Check Logs
```bash
cd backend
tail -n 50 storage/logs/laravel.log
```

### Step 2: Check Browser Console
- Open DevTools (F12)
- Check Console tab for JavaScript errors
- Check Network tab for failed API requests

### Step 3: Test API Directly
```bash
# Get your admin token first (from browser localStorage)
curl -X GET http://127.0.0.1:8000/api/admin/stats \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Step 4: Verify Middleware
```bash
php artisan route:list | grep admin
```
Should show routes with `auth:sanctum,admin` middleware

---

## Quick Fixes Checklist

- [ ] Restart Laravel server
- [ ] Run `php artisan optimize:clear`
- [ ] Check user role in database
- [ ] Verify middleware registration in `Kernel.php`
- [ ] Check Laravel logs for errors
- [ ] Clear browser cache and re-login
- [ ] Verify routes are correct in `api.php`
- [ ] Check database migrations are run

---

## Still Not Working?

1. **Share error details:**
   - Last 20 lines from `storage/logs/laravel.log`
   - Browser console errors
   - Network tab response for failed requests

2. **Verify setup:**
   - Laravel version: `php artisan --version`
   - PHP version: `php -v`
   - Database connection working: `php artisan tinker` → `DB::connection()->getPdo();`

3. **Check file permissions:**
   - `storage/logs/` should be writable
   - `bootstrap/cache/` should be writable

