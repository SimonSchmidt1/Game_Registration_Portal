# Email Troubleshooting Guide

## Problem: Emails Not Reaching MailHog (Temporary Password & Password Reset)

### Root Cause Analysis

**Initial Issue**: Temporary password and password reset emails were not being sent to MailHog, even though the logs showed they were sent.

**Root Causes Identified**:

1. **Notifications were Queued**: Both `TemporaryPasswordNotification` and `PasswordResetNotification` classes originally implemented `ShouldQueue`, which means emails were queued instead of being sent immediately. If no queue worker was running, emails would never be sent.

2. **Queue Worker Not Running**: Laravel's queue system requires a worker process (`php artisan queue:work`) to process queued jobs. Without this worker running, queued emails remain in the database and are never sent.

3. **Configuration Issue**: The default mail driver was set to `log` in `config/mail.php`, which would write emails to log files instead of sending them via SMTP.

### Solutions Applied

#### Solution 1: Removed Queueing (Primary Fix)
- **Changed**: Removed `implements ShouldQueue` from both `TemporaryPasswordNotification` and `PasswordResetNotification`
- **Result**: Emails now send synchronously, no queue worker needed
- **Files**: 
  - `backend/app/Notifications/TemporaryPasswordNotification.php`
  - `backend/app/Notifications/PasswordResetNotification.php`

#### Solution 2: Direct Mail Sending (Fallback)
- **Changed**: Initially switched to `Mail::send()` directly instead of notification
- **Result**: Ensured emails send immediately without queue dependency
- **Note**: Later reverted to notification with proper blade template for better formatting

#### Solution 3: Enhanced Error Handling
- **Added**: Comprehensive logging to track email sending process
- **Added**: Check for valid temporary passwords when attempts > 5
- **Result**: Better debugging and automatic password regeneration when needed

### Current Implementation

The temporary password email now:
1. Uses the notification system (synchronous, no queue)
2. Uses a proper HTML blade template (`resources/views/emails/temporary-password.blade.php`)
3. Includes both HTML and proper email formatting
4. Logs all attempts and errors for debugging
5. **Always sends a new temporary password** when:
   - User reaches exactly 5 failed login attempts
   - User exceeds 5 failed attempts (previous passwords are invalidated)
6. Each new temporary password invalidates previous unused ones to prevent confusion
7. **Counter reset behavior**:
   - Counter resets to 0 on successful login (regular or temporary password)
   - Counter resets to 0 if user hasn't attempted login in 2+ hours (session expiration)
   - This gives users a fresh 5 attempts after their session expires
8. **No blocking**: After sending temporary password, users can continue attempting to login
9. **Email sending not blocked**: Sending temporary password does not prevent other email notifications (verification, password reset, etc.)

### Email Template Location

- **HTML Template**: `backend/resources/views/emails/temporary-password.blade.php`
- **Notification Class**: `backend/app/Notifications/TemporaryPasswordNotification.php`
- **Sending Logic**: `backend/app/Services/AuthService.php::sendTemporaryPassword()`

### Testing Email Sending

Use the test command to verify email configuration:

```bash
cd backend
php artisan test:email your-email@ucm.sk
```

This will:
- Show current mail configuration (driver, host, port)
- Send a test email
- Report success or errors

### Resetting Failed Login Attempts (for Testing)

To reset failed login attempts for a user:

```bash
cd backend
php artisan reset:login-attempts user-email@ucm.sk
```

### MailHog Configuration

For local development with MailHog, ensure your `.env` has:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="test@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Important**: MailHog typically runs on port **1025** for SMTP. Verify MailHog is running and listening on this port.

### Common Issues

#### Issue 1: Emails Still Not Appearing in MailHog

**Check**:
1. MailHog is running: Open `http://localhost:8025` (MailHog web UI)
2. Port matches: Verify `MAIL_PORT=1025` in `.env`
3. Config cache cleared: Run `php artisan config:clear`
4. Check logs: `tail -f backend/storage/logs/laravel.log | grep -i "temporary password"`

#### Issue 2: HTML Not Rendering (Plaintext Only)

**Possible Causes**:
- Email client preference (some clients default to plaintext)
- MailHog display mode (check if MailHog is showing HTML or plaintext view)
- Email template issues (verify blade template exists and is valid)

**Solution**: The email template uses proper HTML with inline styles for maximum compatibility. If one device shows HTML and another doesn't, it's likely an email client setting.

#### Issue 3: Temporary Password Not Sent After 5 Attempts

**Behavior**:
- **At exactly 5 failed attempts**: A temporary password is always sent
- **At 6+ failed attempts**: A new temporary password is always sent (old ones are invalidated)
- This ensures users always have a fresh temporary password available
- **Counter reset conditions**:
  - ✅ Resets to 0 on successful login (regular password)
  - ✅ Resets to 0 on successful temporary password login
  - ✅ Resets to 0 if user has no active tokens (session expired - 2+ hours since last token)
- **No blocking**: After sending temporary password, users can continue attempting to login
- **Email sending not blocked**: Sending temporary password does not prevent other email notifications

**Check**:
1. Failed attempts count: Check `failed_login_attempts` in database
2. Logs show "Attempting to send temporary password email"
3. Verify MailHog is receiving emails
4. Check if user has active tokens: `SELECT * FROM personal_access_tokens WHERE tokenable_id = {user_id}`

**Note**: Each time a new temporary password is sent, previous unused temporary passwords are automatically invalidated to prevent confusion.

#### Issue 4: Password Reset Email Not Sent

**Behavior**:
- Password reset emails are sent when user requests a password reset via `/api/forgot-password`
- Reset link is valid for 1 hour and can only be used once
- Old reset tokens are automatically invalidated when a new one is created

**Root Cause**: Same as temporary password - `PasswordResetNotification` was queued (`ShouldQueue`) but no queue worker was running.

**Solution**: Removed `implements ShouldQueue` from `PasswordResetNotification` class, making it send synchronously.

**Check**:
1. Verify MailHog is receiving emails
2. Check logs for "Password reset notification sent"
3. Test using: Request password reset from frontend (`/forgot-password`)

**Files Modified**:
- `backend/app/Notifications/PasswordResetNotification.php` - Removed `ShouldQueue`

### Debugging Steps

1. **Check if email is being attempted**:
   ```bash
   tail -f backend/storage/logs/laravel.log | grep -i "Attempting to send"
   ```

2. **Check for errors**:
   ```bash
   tail -f backend/storage/logs/laravel.log | grep -i "Failed to send"
   ```

3. **Test email directly**:
   ```bash
   php artisan test:email your-email@ucm.sk
   ```

4. **Verify MailHog is receiving**:
   - Open MailHog web UI: `http://localhost:8025`
   - Check if test emails appear

### Key Files Modified

1. `backend/app/Notifications/TemporaryPasswordNotification.php` - Removed `ShouldQueue`
2. `backend/app/Notifications/PasswordResetNotification.php` - Removed `ShouldQueue`
3. `backend/app/Services/AuthService.php` - Enhanced email sending with better error handling
4. `backend/app/Http/Controllers/Api/AuthController.php` - Updated response messages
5. `frontend/src/views/LoginView.vue` - Improved 429 error handling

### Summary

**Why emails weren't reaching MailHog**:
1. ✅ **Fixed**: Notifications were queued (`ShouldQueue`) but no queue worker was running
2. ✅ **Fixed**: Switched to synchronous sending (removed `ShouldQueue` from both notifications)
3. ✅ **Fixed**: Enhanced error handling and logging
4. ✅ **Fixed**: Added automatic password regeneration for attempts > 5

**Current Status**: Both temporary password and password reset emails now send synchronously and should appear in MailHog immediately.
