# Team Approval Workflow

**Last Updated:** December 4, 2025  
**Status:** Fully Implemented

---

## Overview

When a user creates a new team, it is automatically set to **'pending'** status and requires admin approval before the team becomes fully functional. **Nothing can be done with a pending team** - this includes project publishing, member management, and accepting new members.

---

## Team Statuses

| Status | Description | Visual Indicator |
|--------|-------------|------------------|
| `pending` | Awaiting admin approval | Orange text, ⏳ badge |
| `active` | Fully functional | Normal/green indicators |
| `suspended` | Disabled by admin | Red text, 🚫 badge |

---

## Pending Team Restrictions

When a team has `status = 'pending'`:

### ❌ BLOCKED Actions
- **Create projects** - Backend returns 403
- **Edit projects** - Backend returns 403
- **Join team** (via invite code) - Backend returns 403 with message
- **Remove members** - Backend returns 403
- **Leave team** - Backend returns 403
- **Invite code** - Displayed but marked inactive (strikethrough)

### ✅ ALLOWED Actions
- View team details
- See team in user's team list
- Admin can approve/reject

---

## Visual Indicators

### Home Page (Team Selector)
- Team name displayed in **orange** for pending teams
- ⏳ badge next to team name
- Warning banner below team selector:
  > "Váš tím bol vytvorený a čaká na schválenie administrátorom. Kód pre pripojenie je neaktívny a nie je možné spravovať tím ani publikovať projekty."

### "Moje Tímy" Dialog
- Team card has orange border for pending teams
- Team name in orange
- Invite code shown with strikethrough and "(neaktívny)" label
- Warning message inside team card
- **Remove** and **Leave** buttons hidden for pending teams

### Add Project Page
- Shows warning banner if selected team is pending
- Project creation form hidden
- Button to return to home page

### Navbar
- "Pridať projekt" link hidden if active team is pending

---

## Backend Implementation

### TeamService.php

```php
// createTeam() - New teams start as pending
$teamData['status'] = 'pending';

// joinTeam() - Check team status
if ($team->status !== 'active') {
    return ['error' => 'team_not_active', 'status' => $team->status];
}

// removeMember() - Check team status
if ($team->status !== 'active') {
    return ['error' => 'team_' . $team->status];
}

// leaveTeam() - Check team status
if ($team->status !== 'active') {
    return ['error' => 'team_' . $team->status];
}
```

### TeamController.php

```php
// Handles team_pending and team_suspended errors
'team_pending' => response()->json([
    'message' => 'Tím čaká na schválenie. Táto akcia nie je povolená.'
], 403),
'team_suspended' => response()->json([
    'message' => 'Tím je pozastavený. Táto akcia nie je povolená.'
], 403),
```

### ProjectController.php

```php
// store() and update() check team status
if ($team->status !== 'active') {
    return response()->json([
        'message' => 'Váš tím nie je aktívny. Nie je možné publikovať projekty.',
        'team_status' => $team->status
    ], 403);
}
```

---

## Admin Panel

### Pending Teams Notification
When pending teams exist:
1. Statistics card shows count with pulsing animation
2. Prominent yellow notification section appears
3. Each pending team shows with Approve/Reject buttons

### Admin Actions

#### Approve Team
- `POST /api/admin/teams/{id}/approve`
- Changes status: `pending` → `active`
- Team gains full functionality

#### Reject Team
- `POST /api/admin/teams/{id}/reject`
- Optional reason parameter
- Team is soft-deleted

---

## User Flow

### Creating a New Team

1. User fills team creation form
2. Backend creates team with `status = 'pending'`
3. Success message: "Tím bol úspešne vytvorený a čaká na schválenie administrátorom."
4. Team appears in user's list with orange indicators
5. User cannot perform any team actions

### After Admin Approval

1. Admin clicks "Schváliť" in admin panel
2. Team status changes to `active`
3. User can now:
   - Publish projects
   - Share invite code
   - Manage members
   - Leave team

---

## Frontend Files

| File | Changes |
|------|---------|
| `HomeView.vue` | Orange team names, warning banners, disabled buttons |
| `AddProjectView.vue` | Warning message, hidden form for pending teams |
| `Navbar.vue` | Hidden "Pridať projekt" for pending/suspended teams |
| `AdminView.vue` | Pending teams notification section |

---

## Testing Checklist

- [ ] Create team → Shows as pending
- [ ] Try to publish project → Blocked with message
- [ ] Try to join pending team → Blocked with message  
- [ ] Try to remove member → Blocked with message
- [ ] Try to leave team → Blocked with message
- [ ] Admin approves team → Status becomes active
- [ ] All actions work after approval
