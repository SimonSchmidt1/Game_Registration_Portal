# Security Fixes Applied - November 30, 2025

This document lists the security fixes applied based on the code review.

---

## ✅ CRITICAL FIXES APPLIED

### 1. Admin Password Hardcoded - FIXED
**File:** `backend/config/admin.php`
- Removed hardcoded default password
- Admin credentials must now be set via environment variables
- Added validation to check if credentials are configured

### 2. Temporary Password Logged in Plain Text - FIXED
**File:** `backend/app/Services/AuthService.php`
- Removed temporary password from log output
- Only user_id, email, and mail_driver are logged now

### 3. Admin Login Security - FIXED
**File:** `backend/app/Services/AuthService.php`
- Added rate limiting (max 5 attempts per minute per IP)
- Using timing-safe comparison (`hash_equals`) for credentials
- Removed auto-creation of admin users (security risk)
- Added proper error handling with try-catch

---

## ✅ HIGH PRIORITY FIXES APPLIED

### 4. Try-Catch in Controllers - FIXED
**File:** `backend/app/Http/Controllers/Api/ProjectController.php`
- Added try-catch around `store()` method with DB transaction
- Added try-catch around `rate()` method

**File:** `backend/app/Http/Controllers/Api/AuthController.php`
- Added try-catch around `adminLogin()` method

### 5. Race Condition in Rating System - FIXED
**File:** `backend/app/Http/Controllers/Api/ProjectController.php`
- Changed `rate()` to use database transaction with `lockForUpdate()`
- Added handling for unique constraint violation
- Database already has unique index on `(project_id, user_id)`

### 6. Input Sanitization - FIXED
**File:** `backend/app/Http/Controllers/Api/ProjectController.php`
- Added URL validation with `filter_var()` for `live_url`, `github_url`, `npm_url`
- Restricted URLs to `http://` and `https://` protocols only
- Added `htmlspecialchars()` and `strip_tags()` for string fields
- Sanitized `tech_stack` array items

### 7. File Content Verification - FIXED
**File:** `backend/app/Http/Controllers/Api/ProjectController.php`
- Added `getimagesize()` check for splash_screen uploads
- Validates that uploaded image files are actually images

### 8. Hardcoded API URL - FIXED
**File:** `frontend/src/main.js`
- Changed `axios.defaults.baseURL = 'http://127.0.0.1:8000/api'` to use environment variable
- Now uses `import.meta.env.VITE_API_URL` with fallback

### 9. Admin Email Hardcoded in Frontend - FIXED
**File:** `frontend/src/views/LoginView.vue`
- Changed hardcoded admin email to use environment variable `VITE_ADMIN_EMAIL`

### 10. Rate Limiting Response Handling - FIXED
**File:** `frontend/src/views/LoginView.vue`
- Added handling for 429 status code from admin login
- Shows user-friendly message with retry time

---

## ⚠️ REMAINING ISSUES (Require More Work)

### Token Storage in localStorage
- Still uses localStorage for token storage
- Would require significant refactoring to use httpOnly cookies
- Consider implementing in a future security update

### CSRF Protection for SPA
- Full CSRF protection requires architectural changes
- Consider implementing when moving to cookie-based auth

---

## Environment Variables to Configure

Add these to your `.env` files:

### Backend (.env)
```env
ADMIN_EMAIL=your-admin@email.com
ADMIN_PASSWORD=your-secure-password-here
```

### Frontend (.env)
```env
VITE_API_URL=http://127.0.0.1:8000
VITE_ADMIN_EMAIL=admin@gameportal.local
```
**Note:** The `VITE_ADMIN_EMAIL` must match exactly what you set in `backend/.env` as `ADMIN_EMAIL`. This is used by the frontend to detect when someone is typing the admin email and adjust the UI accordingly.

---

## Testing the Fixes

1. **Admin Login Rate Limiting:**
   - Try logging in 6 times with wrong password
   - Should get "Príliš veľa pokusov" message after 5 attempts

2. **Input Sanitization:**
   - Try submitting a project with javascript: URL
   - Should be rejected or sanitized

3. **Image Verification:**
   - Try uploading a non-image file with .jpg extension
   - Should get "Neplatný obrázok" error

4. **No Admin Password:**
   - Remove ADMIN_PASSWORD from .env
   - Admin login should fail silently

