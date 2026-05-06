# Game Registration Portal - Troubleshooting Guide

This guide helps you quickly diagnose and fix common issues in the Game Registration Portal.

**Last Updated:** February 1, 2026

## Table of Contents

- [Authentication Issues](#authentication-issues)
- [Team Management Issues](#team-management-issues)
- [Game/Project Upload Issues](#gameproject-upload-issues)
- [File Upload Issues](#file-upload-issues)
- [Database Issues](#database-issues)
- [Email Issues](#email-issues)
- [Performance Issues](#performance-issues)
- [Development Issues](#development-issues)
- [Admin Issues](#admin-issues)

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

### Problem: Password Reset Shows "Chyba pripojenia"

**Symptoms:**
- "Chyba pripojenia" error on password reset resend
- User thinks network is broken
- Actually hitting rate limit

**Solution:**
This is NOT a network error - it's the rate limiter working correctly!
1. Rate limiting: max 1 request per minute, 5 per hour per email+IP
2. Wait the displayed countdown (typically 60 seconds)
3. Error message now shows: "Príliš veľa pokusov. Skús znova o X sekúnd."
4. Countdown updates every second

**Prevention:**
- Don't click resend button multiple times
- Wait full cooldown before retrying
- Check email spam folder while waiting

**Explanation:**
- Rate limiter blocks rapid password reset requests
- Prevents account enumeration and brute force attacks
- Server returns HTTP 429 (Too Many Requests)
- Frontend displays countdown timer showing retry time

---

### Problem: Invalid Email Format for Account (Non-UCM)

**Symptoms:**
- "Neplatný email" error during registration
- Can't use non-UCM email (not 7digits@ucm.sk)

**Solution:**
1. For regular teams: Must use UCM email format (1234567@ucm.sk)
2. For international teams: Any email format allowed
   - Admin creates team with type "Medzinárodný tím (SPE)"
   - Invite code will have SPE prefix (e.g., SPEABC123)
   - Join using SPE code to allow non-UCM email

**Explanation:**
- UCM email validation only applies to regular teams
- International teams (SPE) accept any email format
- Allows partner institutions to join with their emails

---

### Problem: Cannot Join International Team

**Symptoms:**
- "Student type must match team type" error
- Can't join SPE team as external student

**Solution:**
1. Check team type is "international" (SPE prefix)
2. International teams bypass student type checking
3. Any student type can join international teams
4. If error persists:
   - Admin manually adds user to team
   - Or create new SPE team to verify it's configured correctly

---

### Problem: User Cannot Login After Deactivation

**Symptoms:**
- "Váš účet bol deaktivovaný" message
- Cannot login even with correct password

**Solution:**
1. Admin has deactivated your account
2. Wait for admin to reactivate
3. Check Admin Panel → Users Management for status
4. Contact administrator to request reactivation

**Prevention:**
- Ensure admin knows account is still needed
- Request reactivation immediately if deactivated unexpectedly

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

---

### Problem: Cannot Create Team

**Symptoms:**
- "Tím už existuje" error
- Team creation fails

**Solution:**
1. Team name must be unique
2. Check if you're already Scrum Master of another team
3. Ensure academic year is selected
4. Select team type (Denný/Externý/Medzinárodný)
5. Verify database connection is working

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

### Problem: Team Member Status Badge

**Symptoms:**
- User shows "Neaktívny" in team details
- User cannot login

**Solution:**
1. Check user status in Admin Panel → Users Management
2. If "Neaktívny" (red badge), admin has deactivated account
3. Request admin to activate user account
4. Or admin can click "Aktivovať" button next to user

**Admin Action**:
1. Go to Admin Panel → Team Detail
2. Find member with "Neaktívny" badge
3. Click "Aktivovať" to reactivate user
4. User can login again immediately

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
   - Trailer video: 100MB max
   - Splash screen: 5MB max
   - Source code: 100MB max
   - Export: 100MB max
2. Compress files before uploading
3. For videos, use YouTube link instead of uploading

**Backend Configuration (if limits need adjustment):**
```php
// config/validation.php or directly in request
'trailer' => 'file|max:102400', // in KB (100MB)
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
   - Trailer: `.mp4`, `.mov`, `.avi` (max 100MB)
   - Splash screen: `.jpg`, `.png`, `.gif`, `.jpeg` (max 5MB)
   - Source code: `.zip`, `.rar`, `.7z` (max 100MB)
   - Export: `.zip`, `.exe`, `.apk` (max 100MB)
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

## Admin Issues

### Problem: Admin Login Not Working

**Symptoms:**
- "Neplatné admin prihlasovacie údaje" error
- Admin panel not accessible

**Solution:**
1. Verify environment variables in `backend/.env`:
   ```env
   ADMIN_EMAIL=admin@gameportal.local
   ADMIN_PASSWORD=qKyUtyz0kSOyJxLU5E09zMkKospW6XZ9
   ```
2. Verify frontend `.env`:
   ```env
   VITE_ADMIN_EMAIL=admin@gameportal.local
   ```
3. Clear config and create admin user:
   ```bash
   cd backend
   php artisan config:clear
   php artisan db:seed
   ```
4. Verify admin user exists:
   ```bash
   php artisan tinker
   User::where('email', 'admin@gameportal.local')->first();
   ```

---

### Problem: Admin Panel Shows 0 Stats

**Symptoms:**
- Statistics cards show 0
- Teams/projects not loading

**Solution:**
1. Check database has data
2. Verify admin middleware is working:
   ```bash
   php artisan route:list | grep admin
   ```
3. Check Laravel logs for errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```
4. Verify `status` column exists in teams table:
   ```bash
   php artisan tinker
   Schema::hasColumn('teams', 'status');
   ```

---

### Problem: Cannot Approve/Reject Teams

**Symptoms:**
- 403 Forbidden error
- "Prístup zamietnutý" message

**Solution:**
1. Verify you're logged in as admin
2. Check user role in database:
   ```bash
   php artisan tinker
   User::where('email', 'your@email')->first()->role;
   ```
3. Re-login as admin

---

### Problem: "Pridať projekt" Button Not Visible

**Symptoms:**
- Button hidden even though user is Scrum Master

**Solution:**
1. Check team status is `active` (not `pending` or `suspended`)
2. Verify active team is selected in HomeView
3. Check localStorage:
   ```javascript
   console.log(localStorage.getItem('active_team_status'));
   ```
4. Ask admin to approve team if status is `pending`

---

### Problem: Cannot Deactivate/Activate Users

**Symptoms:**
- Deactivate/Activate buttons not visible
- 403 Forbidden error when clicking

**Solution:**
1. Verify you're logged in as admin (check user email matches ADMIN_EMAIL)
2. Check admin middleware is properly configured:
   ```bash
   php artisan route:list | grep admin
   ```
3. Re-login as admin to refresh permissions
4. Check database for user role:
   ```bash
   php artisan tinker
   User::where('email', 'admin@gameportal.local')->first()->role; // Should be 'admin'
   ```

**Admin User Management**:
- Locate Users Management section in Admin Panel (top right)
- Click "Registrovať používateľa" to add new users
- Click deactivate (red button) to disable user account
- Click activate (green button) to re-enable user account
- Changes take effect immediately

---

### Problem: Password Reset Rate Limit - "Príliš veľa pokusov"

**Symptoms:**
- Cannot resend password reset email
- Message shows "Príliš veľa pokusov. Skús znova o X sekúnd."
- Countdown timer visible

**Solution:**
1. This is working as designed - rate limiting is active
2. Wait for countdown timer to reach 0
3. Rate limits: 1 request per minute, 5 per hour per email+IP
4. After timer expires, resend button becomes active

**Why Rate Limiting?**
- Prevents brute force attacks
- Prevents account enumeration
- Protects against spam and abuse
- Standard security best practice

**If Stuck (More Than 5 Hours)**:
- Try resetting from different network/IP address
- Or contact admin for manual password reset
- Admin can use tinker to reset password:
  ```bash
  php artisan tinker
  $user = User::where('email', 'user@email.com')->first();
  $user->update(['password' => Hash::make('new-password')]);
  ```

---

## Localization (i18n) Issues

### Problem: Blank Screen After Login — No Content Visible

**Symptoms:**
- Login succeeds (token stored, redirected to `/`)
- Home page (or any view) renders completely blank
- No error message shown to the user
- Browser console shows: `TypeError: t is not a function` or `t is not defined`
- Vite build completes successfully with **no errors or warnings**

**Root Cause:**
A Vue view uses `t()` in its template but is missing the `useI18n` import and/or the `const { t } = useI18n()` declaration in its `<script setup>`. Because `vue-i18n 9` operates in composition mode (`legacy: false`), the `t` function is **not injected globally** — each component must explicitly call `useI18n()` to get it. When `t` is undefined, the first template expression that calls it throws a runtime error that silently kills the entire component render, leaving a blank screen.

**Fix:**
Add the following two lines to the `<script setup>` of every view or component that uses `t()` in its template:
```javascript
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
```

**Example — correct pattern (from `HomeView.vue` after fix):**
```javascript
<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'   // ← must be present
// ...
const { t } = useI18n()              // ← must be present
</script>
```

**How to Diagnose:**
1. Open browser DevTools → Console tab
2. Look for `t is not a function`, `t is not defined`, or a Vue rendering error
3. If blank screen only appears after login (not on public pages), the affected view is likely the post-login landing view (`HomeView.vue`)
4. Audit all views with this command from the project root:
   ```powershell
   # Find views that call t() in template but may lack useI18n
   Select-String -Path frontend\src\views\*.vue -Pattern "useI18n" | Select-Object Path
   # Cross-reference: find views that actually use t() in template
   Select-String -Path frontend\src\views\*.vue -Pattern "\bt\(" | Select-Object Path | Sort-Object -Unique
   ```
   Every path in the second list must also appear in the first.

**Why the Build Doesn't Catch It:**
- Vite/Rollup only checks syntax and module resolution — it does not track whether `t` is in scope at runtime
- `vue-i18n` does not provide a Vite plugin warning for missing `useI18n()` calls
- The error only surfaces at runtime when the component is mounted

**Known Affected File (Fixed Feb 26, 2026):**
- `frontend/src/views/HomeView.vue` — was missing `import { useI18n } from 'vue-i18n'` and `const { t } = useI18n()`. All other views had the correct pattern already.

**Prevention:**
- Use a snippet or template for new views that includes `useI18n` boilerplate
- After adding any `t('...')` call to a template, immediately verify the import and declaration exist in `<script setup>`
- When a blank screen occurs after login, check the console for `t is not a function` **before** investigating auth or API issues

---

### Problem: Translations Show Raw Keys (e.g., `team.active_team`)

**Symptoms:**
- Text like `team.active_team` or `filter.title` displayed literally on screen instead of translated text

**Solution:**
1. Verify the key exists in the active locale file (`frontend/src/locales/<code>.json`)
2. Verify the key exists in the fallback locale (`en.json`) — vue-i18n falls back to `en` before displaying the raw key
3. Check for typos (keys are case-sensitive and dot-notation is used for nesting)
4. If the key is new, add it to **all** locale files to avoid missing-translation warnings in the console

---

### Problem: Language Does Not Switch

**Symptoms:**
- Changing language in the UI has no effect

**Solution:**
1. Ensure `setLocale(code)` from `frontend/src/i18n.js` is called, not a direct mutation
2. Verify the locale code is in `SUPPORTED_LOCALES` (check `i18n.js`)
3. Check `localStorage.getItem('locale_preference')` in DevTools → Application → Local Storage
4. Clear localStorage and reload to reset to browser auto-detection

---

**Last Updated:** February 26, 2026  
**Version:** 1.6
