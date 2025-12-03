# Admin Panel Implementation

**Date:** November 30, 2025  
**Status:** Implemented

---

## Overview

A comprehensive admin panel has been implemented with a single-view approach for managing teams and projects in the system.

## Features Implemented

### 1. Backend Components

#### AdminController (`backend/app/Http/Controllers/Api/AdminController.php`)
- **Stats endpoint** (`GET /api/admin/stats`): Returns system statistics
  - Total teams, projects, users
  - Teams with projects
  - Projects with video/splash/export
  - Pending teams count

- **Teams management**:
  - `GET /api/admin/teams` - List all teams with indicators
  - `GET /api/admin/teams/{id}` - Team details with members and projects
  - `PUT /api/admin/teams/{id}` - Update team (name, status)
  - `DELETE /api/admin/teams/{id}` - Delete team (soft delete)
  - `POST /api/admin/teams/{id}/approve` - Approve pending team
  - `POST /api/admin/teams/{id}/reject` - Reject pending team

- **Projects overview**:
  - `GET /api/admin/projects` - List all projects with filters

#### Database Changes
- **Migration:** `2025_11_30_add_status_to_teams_table.php`
  - Added `status` column to teams table (default: 'active')
  - Possible values: 'active', 'pending', 'suspended'

#### Model Updates
- **Team model:** Added `projects()` relationship and `status` to fillable
- **Project model:** Already has proper relationships

#### Routes
- Admin routes protected by `role:admin` middleware
- Prefix: `/api/admin/*`

### 2. Frontend Components

#### AdminView (`frontend/src/views/AdminView.vue`)
Features:
- **Statistics Dashboard**: 4 cards showing key metrics
- **Pending Teams Section**: Quick approve/reject for pending teams
- **Search & Filters**: Search by name, filter by status
- **Teams Overview Table**: Comprehensive table with:
  - Team name & invite code
  - Academic year
  - Members count
  - Projects count
  - Status indicators (✓/✗):
    - Has project
    - Has video
    - Has splash screen
    - Has export files
  - Status badge (active/pending/suspended)
  - Actions: View details, Edit, Delete

#### Dialogs
1. **Team Details**: Shows members, projects, and full team info
2. **Edit Team**: Change name and status
3. **Reject Team**: With optional reason input
4. **Delete Confirmation**: Warning before permanent deletion

#### Navbar Updates
- Admin button visible only to users with `role === 'admin'`
- Styled with red gradient to stand out
- Shield icon for visual distinction

#### Router
- Route: `/admin`
- Meta: `requiresAuth: true, requiresAdmin: true`
- Protected by route guard checking user role

### 3. Middleware & Security

- **EnsureUserIsAdmin**: Dedicated middleware for admin-only routes
  - Located: `app/Http/Middleware/EnsureUserIsAdmin.php`
  - Checks if authenticated user has `role === 'admin'`
  - Returns 401 if not authenticated, 403 if not admin
- **Routes**: Use `'admin'` middleware alias (registered in `Kernel.php`)
- **Frontend route guard**: Prevents non-admin access at router level
- **Component-level check**: Admin status verified on `AdminView` mount

---

## Usage

### For Admins:

1. **Login** with admin credentials
2. **Admin Panel button** appears in navbar (red button with shield icon)
3. **Click** to access the admin panel
4. **Manage teams**: 
   - View all teams and their status
   - Approve/reject pending teams
   - Edit team details
   - Delete teams if needed
5. **Monitor statistics** at the top of the page

### Team Status Workflow:

- **Active**: Normal, functional team
- **Pending**: Awaiting admin approval (future feature)
- **Suspended**: Temporarily disabled (future feature)

---

## Files Modified

### Backend:
- `app/Http/Controllers/Api/AdminController.php` (new)
- `app/Http/Middleware/EnsureUserIsAdmin.php` (new)
- `app/Http/Kernel.php` (added `admin` middleware alias)
- `app/Models/Team.php` (added `projects()` relationship, `status` fillable)
- `routes/api.php` (added admin routes with `admin` middleware)
- `database/migrations/2025_11_30_add_status_to_teams_table.php` (new)

### Frontend:
- `src/views/AdminView.vue` (new)
- `src/components/Navbar.vue` (added admin button)
- `src/router/index.js` (added admin route & guard)

---

## Future Enhancements

1. **Team Creation Approval Workflow**: Automatically set new teams to 'pending' status
2. **Notifications**: Real-time notifications for pending approvals
3. **User Management**: Add/edit/delete users
4. **Project Management**: Edit/delete projects from admin panel
5. **Analytics Dashboard**: Charts and graphs for system metrics
6. **Activity Logs**: Track admin actions
7. **Bulk Actions**: Approve/reject multiple teams at once
8. **Export Data**: Export teams/projects to CSV/Excel

---

## Troubleshooting

### Stats showing 0:
1. Check if migration ran: `php artisan migrate`
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify data exists in database
4. Check browser console for API errors

### Admin button not visible:
1. Ensure user has `role = 'admin'` in database
2. Check localStorage for correct user data
3. Clear browser cache and re-login

### Access denied errors:
1. Verify `admin` middleware is registered in `Kernel.php`
2. Check if user is properly authenticated
3. Ensure admin routes are under `auth:sanctum` middleware

### "Target class [role] does not exist" Error:
**Cause:** This error occurred because Laravel was trying to resolve `'role'` as a class name when using `'role:admin'` middleware syntax. The middleware parameter parsing wasn't working correctly.

**Solution:** Created a dedicated `EnsureUserIsAdmin` middleware that doesn't require parameters:
- Created `app/Http/Middleware/EnsureUserIsAdmin.php`
- Registered it as `'admin'` alias in `Kernel.php`
- Changed routes from `'role:admin'` to `'admin'`

**Prevention:** Always restart Laravel server after changing middleware registration:
```bash
# Stop server (Ctrl+C)
php artisan serve  # Start again
```

---

## API Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/stats` | System statistics |
| GET | `/api/admin/teams` | List all teams |
| GET | `/api/admin/teams/{id}` | Team details |
| PUT | `/api/admin/teams/{id}` | Update team |
| DELETE | `/api/admin/teams/{id}` | Delete team |
| POST | `/api/admin/teams/{id}/approve` | Approve team |
| POST | `/api/admin/teams/{id}/reject` | Reject team |
| GET | `/api/admin/projects` | List all projects |

---

## Testing Checklist

- [ ] Admin can access admin panel
- [ ] Regular users cannot access admin panel
- [ ] Stats display correctly
- [ ] Team list loads
- [ ] Search functionality works
- [ ] Status filter works
- [ ] View team details works
- [ ] Edit team works
- [ ] Delete team works
- [ ] Approve/reject pending teams works
- [ ] Admin button only visible to admin
- [ ] Non-admin redirected from /admin route

