# Game Registration Portal - Troubleshooting Guide

This guide helps you quickly diagnose and fix common issues in the Game Registration Portal.

## Table of Contents

- [Authentication Issues](#authentication-issues)
- [Team Management Issues](#team-management-issues)
- [Game/Project Upload Issues](#gameproject-upload-issues)
- [File Upload Issues](#file-upload-issues)
- [Database Issues](#database-issues)
- [Email Issues](#email-issues)
- [Performance Issues](#performance-issues)
- [Development Issues](#development-issues)

---

## Authentication Issues

### Problem: Cannot Login (Wrong Password)

**Symptoms:**
- "Nesprávne heslo" message
- Failed login attempts counter increasing

**Solution:**
1. Check if email format is correct: `1234567@ucm.sk` (7 digits + @ucm.sk)
2. After 5 failed attempts, check email for temporary password
3. Use temporary password from email (format: XXXX-XXXX-XXXX)
4. Temporary password expires after 15 minutes

**Prevention:**
- Use password manager
- Write down password during registration
- Change password immediately after using temporary password

---

### Problem: Automatic Logout After Inactivity

**Symptoms:**
- Logged out after 5 minutes of no activity
- Redirected to login page unexpectedly

**Solution:**
- This is expected behavior for security
- Move mouse, press keys, or scroll to reset timer
- Token expires after 2 hours regardless of activity

**Prevention:**
- Save your work frequently
- Keep session active by interacting with page

---

### Problem: Email Verification Not Working

**Symptoms:**
- "Účet nie je overený" message on login
- Verification link doesn't work

**Solution:**
1. Check spam folder for verification email
2. Verification token may have expired - login 5 times to trigger new email
3. Contact administrator if issue persists

**Backend Check:**
```bash
cd backend
php artisan tinker
User::where('email', 'your@email.ucm.sk')->update(['email_verified_at' => now()]);
```

---

### Problem: 401 Unauthorized Errors

**Symptoms:**
- API requests return 401 status
- Automatic logout after every request

**Solution:**
1. Check if token expired (2-hour limit)
2. Clear localStorage and login again
3. Check backend CORS configuration in `.env`:
   ```
   CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173
   ```
4. Verify Sanctum configuration:
   ```bash
   cd backend
   php artisan config:clear
   php artisan cache:clear
   ```

---

## Team Management Issues

### Problem: Cannot Join Team with Code

**Symptoms:**
- "Tím s týmto kódom nebol nájdený"
- Invalid invite code error

**Solution:**
1. Verify code is exactly 6 characters (case-insensitive)
2. Check for extra spaces when copying code
3. Ensure team exists in database:
   ```bash
   php artisan tinker
   Team::where('invite_code', 'YOUR_CODE')->first();
   ```
4. Team may be full (max 4 members)

**Prevention:**
- Copy invite code directly (don't type manually)
- Scrum Master should verify code before sharing

---

### Problem: Cannot Create Team

**Symptoms:**
- "Tím už existuje" error
- Team creation fails

**Solution:**
1. Team name must be unique
2. Check if you're already Scrum Master of another team
3. Ensure academic year is selected
4. Verify database connection is working

**Backend Check:**
```bash
cd backend
php artisan tinker
Team::where('name', 'Team Name')->exists(); // Should be false
```

---

### Problem: Cannot Remove Team Member

**Symptoms:**
- "Nemáte oprávnenie" error
- Member removal fails

**Solution:**
1. Only Scrum Master can remove members
2. Cannot remove Scrum Master (transfer role first)
3. Verify your Scrum Master status:
   ```bash
   php artisan tinker
   $team = Team::find(TEAM_ID);
   $team->members()->where('users.id', YOUR_ID)->first()->pivot->role_in_team;
   ```

---

### Problem: Cannot Leave Team

**Symptoms:**
- "Scrum Master nemôže opustiť tím" error

**Solution:**
- Scrum Master must transfer role before leaving
- Or delete the entire team if no longer needed
- Regular members can leave without restrictions

---

## Game/Project Upload Issues

### Problem: "Iba Scrum Master môže pridať hru"

**Symptoms:**
- Cannot access add game/project form
- Authorization error when submitting

**Solution:**
1. Check if you're Scrum Master of the team:
   - Go to "Moje Tímy"
   - Look for "Scrum Master" badge next to your team
2. Verify active team is selected (check localStorage):
   ```javascript
   console.log(localStorage.getItem('active_team_id'));
   console.log(localStorage.getItem('active_team_is_scrum_master'));
   ```
3. Try switching to your team in HomeView

**Backend Fix (if pivot is wrong):**
```bash
php artisan tinker
$team = Team::find(TEAM_ID);
$team->members()->updateExistingPivot(USER_ID, ['role_in_team' => 'scrum_master']);
```

---

### Problem: Game Submission Returns "Tím už má hru"

**Symptoms:**
- Cannot add second game to team
- Error message about existing game

**Solution:**
- By design, each team can have ONE game per academic year
- Edit existing game instead of creating new one
- Or use Projects system (multi-project support) instead

**To Allow Multiple Games:**
- This is intentional limitation
- Contact administrator if business logic should change

---

## File Upload Issues

### Problem: File Upload Fails (Too Large)

**Symptoms:**
- "Súbor je príliš veľký" error
- Upload hangs or times out

**Solution:**
1. Check file size limits:
   - Trailer video: 20MB max
   - Splash screen: 5MB max
   - Source code: 50MB max
   - Export: 50MB max
2. Compress files before uploading
3. For videos, use YouTube link instead of uploading

**Backend Configuration (if limits need adjustment):**
```php
// config/validation.php or directly in request
'trailer' => 'file|max:20480', // in KB (20MB)
```

**Server Configuration:**
```ini
; php.ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

---

### Problem: Invalid File Type Error

**Symptoms:**
- "Neplatný typ súboru" error
- File upload rejected

**Solution:**
1. Check accepted formats:
   - Trailer: `.mp4`, `.mov`, `.avi`
   - Splash screen: `.jpg`, `.png`, `.gif`, `.jpeg`
   - Source code: `.zip`, `.rar`, `.7z`
   - Export: `.zip`, `.exe`, `.apk`
2. Convert file to accepted format
3. Check file extension matches actual file type

---

### Problem: YouTube Link Not Working

**Symptoms:**
- Video doesn't display
- "Neplatná URL" error

**Solution:**
1. Use full YouTube URL: `https://www.youtube.com/watch?v=VIDEO_ID`
2. Or short format: `https://youtu.be/VIDEO_ID`
3. Ensure video is public (not unlisted or private)
4. Test URL in browser first

---

## Database Issues

### Problem: "SQLSTATE[HY000]: General error"

**Symptoms:**
- Database operations fail
- Migration errors

**Solution:**
1. Check database connection in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   ```
2. Test connection:
   ```bash
   cd backend
   php artisan db:show
   ```
3. Run migrations if needed:
   ```bash
   php artisan migrate
   ```

---

### Problem: Foreign Key Constraint Violation

**Symptoms:**
- Cannot delete team (has games)
- Cannot remove user (has ratings)

**Solution:**
- These are intentional protections
- Delete child records first (games, ratings) before parent (team, user)
- Or use `onDelete('cascade')` in migrations if appropriate

---

### Problem: Token Pruning Not Running

**Symptoms:**
- Old password reset tokens not cleaned up
- Database growing unnecessarily

**Solution:**
1. Ensure scheduler is running (cron job):
   ```cron
   * * * * * cd /path-to-project/backend && php artisan schedule:run >> /dev/null 2>&1
   ```
2. Manual cleanup:
   ```bash
   cd backend
   php artisan tokens:prune
   ```
3. Check logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Email Issues

### Problem: Emails Not Sending

**Symptoms:**
- No verification email received
- No password reset email

**Solution:**
1. Check mail configuration in backend `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=your_smtp_host
   MAIL_PORT=587
   MAIL_USERNAME=your_email
   MAIL_PASSWORD=your_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@example.com
   ```
2. For local development, use Mailpit:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=127.0.0.1
   MAIL_PORT=1025
   ```
3. Check logs:
   ```bash
   tail -f backend/storage/logs/laravel.log | grep -i mail
   ```
4. Test email manually:
   ```bash
   php artisan tinker
   Mail::raw('Test email', function($msg) {
       $msg->to('test@ucm.sk')->subject('Test');
   });
   ```

---

### Problem: Emails Going to Spam

**Solution:**
1. Use authenticated SMTP server
2. Set proper FROM address matching domain
3. Add SPF and DKIM records to DNS
4. Whitelist sender in email client

---

## Performance Issues

### Problem: Slow API Responses

**Symptoms:**
- Page loads slowly
- Timeout errors

**Solution:**
1. Enable query caching:
   ```bash
   cd backend
   php artisan config:cache
   php artisan route:cache
   ```
2. Optimize database queries (add indexes):
   ```sql
   CREATE INDEX idx_games_team ON games(team_id);
   CREATE INDEX idx_game_ratings_game ON game_ratings(game_id);
   ```
3. Enable Redis for caching (optional)
4. Check for N+1 query problems in logs

---

### Problem: Frontend Slow/Unresponsive

**Solution:**
1. Clear browser cache
2. Check network tab for slow requests
3. Rebuild frontend:
   ```bash
   cd frontend
   npm run build
   ```
4. Check Node.js version (20.18+ recommended)

---

## Development Issues

### Problem: Vite "Outdated Optimized Dependencies" Warning

**Symptoms:**
- Dev server shows warning
- Need to restart after dependency changes

**Solution:**
```bash
cd frontend
rm -rf node_modules/.vite
npm run dev
```

---

### Problem: Node Version Warning

**Symptoms:**
- Vite shows Node.js version warning
- "Vite requires Node.js 20.19+"

**Solution:**
- Update Node.js: `nvm install 20` or download from nodejs.org
- Warning is cosmetic - app still works with 20.18.0
- Ignore unless experiencing actual issues

---

### Problem: CORS Errors in Browser Console

**Symptoms:**
- "Access-Control-Allow-Origin" error
- API requests blocked

**Solution:**
1. Backend `.env`:
   ```env
   CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173
   ```
2. Clear config:
   ```bash
   cd backend
   php artisan config:clear
   ```
3. Restart backend server
4. Check Sanctum middleware is applied in `bootstrap/app.php`

---

### Problem: Changes Not Reflecting

**Symptoms:**
- Code changes don't appear
- Old behavior persists

**Solution:**

**Backend:**
```bash
cd backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

**Frontend:**
```bash
cd frontend
rm -rf node_modules/.vite
npm run dev
```

**Browser:**
- Hard refresh: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)
- Clear localStorage: F12 → Application → Local Storage → Clear All

---

## Getting Additional Help

### Enable Debug Mode

**Backend (config/app.php):**
```php
'debug' => env('APP_DEBUG', true),
```

**Check Logs:**
```bash
# Backend
tail -f backend/storage/logs/laravel.log

# Frontend (browser console)
F12 → Console tab
```

### Useful Commands

```bash
# Backend health check
cd backend
php artisan about

# Database status
php artisan db:show

# Queue status (if using queues)
php artisan queue:work --stop-when-empty

# List all routes
php artisan route:list

# Frontend dev tools
cd frontend
npm run dev -- --debug
```

### Contact Support

If issues persist:
1. Check ARCHITECTURE.md for system overview
2. Check STABILITY.md for recent changes
3. Review GitHub issues (if public repo)
4. Contact system administrator with:
   - Error messages (full text)
   - Steps to reproduce
   - Browser/environment details
   - Relevant log excerpts

---

**Last Updated:** November 2025  
**Version:** 1.0
