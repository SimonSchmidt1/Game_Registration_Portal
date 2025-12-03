# Team Approval Workflow

**Date:** November 30, 2025  
**Status:** Implemented

---

## Overview

When a user creates a new team, it is automatically set to **'pending'** status and requires admin approval before the team can publish projects.

---

## How It Works

### 1. Team Creation (User Side)

**When a user creates a team:**
1. Team is created with `status = 'pending'`
2. User receives a message: *"Tím bol úspešne vytvorený a čaká na schválenie administrátorom. Po schválení budete môcť publikovať projekty."*
3. Team appears in user's team list, but:
   - ❌ **Cannot publish projects** (blocked by backend validation)
   - ❌ **Cannot edit projects** (blocked by backend validation)
   - ✅ Can invite members
   - ✅ Can view team details

### 2. Admin Notification (Admin Side)

**When admin logs in:**
1. **Statistics Card** shows pending teams count (yellow card with pulsing animation if > 0)
2. **Prominent Notification Section** appears at the top of admin panel:
   - Yellow/orange gradient background
   - Bell icon with animation
   - Badge showing count of pending teams
   - List of all pending teams with:
     - Team name
     - Scrum Master name
     - Academic year
     - **Approve** button (green)
     - **Reject** button (red)

### 3. Admin Actions

#### Approve Team
- Click **"Schváliť"** button
- Team status changes from `'pending'` → `'active'`
- Team can now publish projects
- Notification disappears from admin panel

#### Reject Team
- Click **"Zamietnuť"** button
- Optional: Enter rejection reason
- Team is **soft-deleted** (removed from system)
- Scrum Master can create a new team if needed

---

## Visual Indicators

### Admin Panel

1. **Statistics Card (Top Right)**
   - Shows count of pending teams
   - **Pulsing animation** when count > 0
   - Yellow/orange gradient with glow effect

2. **Notification Section**
   - Appears below statistics
   - Yellow/orange gradient background
   - Animated bell icon
   - Badge with count
   - Each pending team in a card with approve/reject buttons

3. **Teams Table**
   - Pending teams show **"Čakajúci"** status badge
   - Yellow badge with border

---

## Backend Implementation

### Team Creation
**File:** `backend/app/Services/TeamService.php`

```php
// Set status to 'pending' if status column exists
if (Schema::hasColumn('teams', 'status')) {
    $teamData['status'] = 'pending';
}
```

### Project Publishing Block
**File:** `backend/app/Http/Controllers/Api/ProjectController.php`

- `store()` method checks team status before allowing project creation
- `update()` method checks team status before allowing project editing
- Returns 403 with message if team is not 'active'

### Admin Endpoints
**File:** `backend/app/Http/Controllers/Api/AdminController.php`

- `GET /api/admin/stats` - Returns `pending_teams` count
- `GET /api/admin/teams` - Returns teams with status indicators
- `POST /api/admin/teams/{id}/approve` - Approves pending team
- `POST /api/admin/teams/{id}/reject` - Rejects pending team

---

## Frontend Implementation

### Admin Panel
**File:** `frontend/src/views/AdminView.vue`

- **Pending Teams Section**: Shows at top when `pendingTeams.length > 0`
- **Statistics Card**: Pulsing animation when pending teams exist
- **Approve/Reject Buttons**: Direct actions on each pending team

### User Experience
**File:** `frontend/src/views/HomeView.vue`

- Shows info toast when team requires approval
- User can still see their team but cannot publish projects

---

## User Flow

### Scenario: New Team Creation

1. **User creates team** → Status set to `'pending'`
2. **User sees message**: "Tím čaká na schválenie"
3. **User tries to publish project** → Blocked with message: "Váš tím čaká na schválenie administrátorom"
4. **Admin logs in** → Sees notification with pending team
5. **Admin approves team** → Status changes to `'active'`
6. **User can now publish projects** → Validation passes

---

## Status Values

- **'active'**: Team is approved and can publish projects
- **'pending'**: Team is waiting for admin approval
- **'suspended'**: Team is temporarily disabled (future feature)

---

## Testing

### Test Case 1: Create Team as User
1. Login as regular user
2. Create a new team
3. ✅ Should see message about approval needed
4. ✅ Try to publish project → Should be blocked

### Test Case 2: Admin Sees Pending Team
1. Login as admin
2. Go to Admin Panel
3. ✅ Should see pending teams count in stats
4. ✅ Should see notification section with pending team
5. ✅ Should see team in table with "Čakajúci" badge

### Test Case 3: Admin Approves Team
1. Admin clicks "Schváliť"
2. ✅ Team status changes to 'active'
3. ✅ Notification disappears
4. ✅ User can now publish projects

### Test Case 4: Admin Rejects Team
1. Admin clicks "Zamietnuť"
2. ✅ Team is soft-deleted
3. ✅ Notification disappears
4. ✅ User can create new team

---

## Future Enhancements

1. **Email Notifications**: Send email to Scrum Master when team is approved/rejected
2. **Auto-approval**: Option to auto-approve teams from verified users
3. **Bulk Actions**: Approve/reject multiple teams at once
4. **Approval History**: Track who approved/rejected and when
5. **Rejection Reasons**: Store and display rejection reasons to users

