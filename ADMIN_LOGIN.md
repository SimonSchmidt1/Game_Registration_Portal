# Admin Login Documentation

## Overview
Special admin login system that bypasses normal email validation and uses config-based credentials. This allows administrators to log in without needing a UCM email address.

## Admin Credentials

### Default Credentials
- **Email**: `admin@gameportal.local`
- **Password**: `qKyUtyz0kSOyJxLU5E09zMkKospW6XZ9`

### Changing Admin Credentials

#### Option 1: Environment Variables (Recommended)
Add to your `.env` file:
```env
ADMIN_EMAIL=your-admin@email.com
ADMIN_PASSWORD=your-strong-password-here
```

#### Option 2: Config File
Edit `backend/config/admin.php`:
```php
'email' => 'your-admin@email.com',
'password' => 'your-strong-password-here',
```

After changing credentials, clear config cache:
```bash
php artisan config:clear
```

## How to Use

### Frontend
1. Go to the login page (`/login`)
2. Click "Admin prihlásenie" link at the top
3. Enter admin email and password
4. Click "Prihlásiť sa"

### Backend API
```http
POST /api/admin/login
Content-Type: application/json

{
  "email": "admin@gameportal.local",
  "password": "qKyUtyz0kSOyJxLU5E09zMkKospW6XZ9"
}
```

**Response:**
```json
{
  "access_token": "1|...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Administrator",
    "email": "admin@gameportal.local",
    "role": "admin"
  },
  "message": "Admin prihlásenie úspešné"
}
```

## Security Features

1. **Separate Endpoint**: Admin login uses `/api/admin/login` instead of regular `/api/login`
2. **No Email Validation**: Bypasses UCM email format requirement
3. **No Failed Attempt Tracking**: Admin login doesn't increment failed attempts
4. **Auto-Verified**: Admin account is automatically verified (no email verification needed)
5. **Longer Token Expiration**: Admin tokens last 24 hours (vs 2 hours for regular users)
6. **Auto-Create**: Admin user is automatically created on first login if it doesn't exist
7. **Role Enforcement**: Existing user is automatically promoted to admin if they log in with admin credentials

## Implementation Details

### Backend Files Modified
- `backend/config/admin.php` - Admin credentials configuration
- `backend/app/Services/AuthService.php` - Added `attemptAdminLogin()` method
- `backend/app/Http/Controllers/Api/AuthController.php` - Added `adminLogin()` endpoint
- `backend/routes/api.php` - Added `/api/admin/login` route

### Frontend Files Modified
- `frontend/src/views/LoginView.vue` - Added admin login toggle and function

## Admin User Creation

The admin user is automatically created on first successful login with these defaults:
- **Name**: "Administrator"
- **Email**: From config (`ADMIN_EMAIL` or default)
- **Role**: `admin`
- **Email Verified**: Automatically set to current timestamp
- **Failed Login Attempts**: 0

## Token Expiration

- **Regular Users**: 2 hours
- **Admin Users**: 24 hours

This allows administrators to stay logged in longer for administrative tasks.

## Important Security Notes

⚠️ **CRITICAL**: Change the default password immediately in production!

1. The default password is visible in the config file
2. Use environment variables (`.env`) for production
3. Never commit `.env` file to version control
4. Use a strong, unique password (the default is 32 characters, base64-encoded random bytes)
5. Consider using a password manager to store the admin password

## Testing

### Test Admin Login
```bash
curl -X POST http://127.0.0.1:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gameportal.local","password":"qKyUtyz0kSOyJxLU5E09zMkKospW6XZ9"}'
```

### Verify Admin User
```bash
php artisan tinker
$admin = User::where('role', 'admin')->first();
echo $admin->email . "\n";
echo $admin->role . "\n";
```

## Troubleshooting

### Admin Login Not Working
1. Check config cache: `php artisan config:clear`
2. Verify credentials in `backend/config/admin.php` or `.env`
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify route exists: `php artisan route:list | grep admin`

### Admin User Not Created
- Check database connection
- Verify user table exists
- Check Laravel logs for errors

### Token Not Working
- Verify token expiration (24 hours for admin)
- Check if token was revoked
- Ensure `auth:sanctum` middleware is applied

---

**Default Admin Password**: `qKyUtyz0kSOyJxLU5E09zMkKospW6XZ9`

**⚠️ CHANGE THIS PASSWORD IN PRODUCTION!**



