# Admin Panel Fix - Middleware Error

## The Problem
Error: `Target class [role] does not exist`

This happens because:
1. Laravel cached the old middleware configuration
2. The property name changed from `$routeMiddleware` to `$middlewareAliases` in Laravel 10+

## The Solution

Run these commands to clear the cache:

```bash
cd backend
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## If That Doesn't Work

Also run:
```bash
php artisan optimize:clear
```

This will clear all cached files including:
- Configuration cache
- Route cache
- View cache
- Application cache

## After Clearing Cache

1. Refresh your browser
2. Try accessing the Admin Panel again
3. The stats should now load correctly

## Files Fixed
- `backend/app/Http/Kernel.php` - Changed `$routeMiddleware` to `$middlewareAliases`
- `backend/app/Http/Controllers/Api/AdminController.php` - Added safe column checking

