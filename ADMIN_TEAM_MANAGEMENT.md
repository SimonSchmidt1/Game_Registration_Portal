# Admin Team Member Management

**⚠️ DEPRECATED:** This document has been superseded by [ADMIN_USER_MANAGEMENT.md](ADMIN_USER_MANAGEMENT.md) which contains comprehensive documentation of all admin capabilities including member management, user registration, team creation, and user movement between teams.

## Overview
Administrators can now manage team members with full override capabilities, bypassing all regular user restrictions.

## Implementation Date
December 24, 2025

For complete documentation, see [ADMIN_USER_MANAGEMENT.md](ADMIN_USER_MANAGEMENT.md).

## Features Implemented

### 1. Remove Team Members (Admin Bypass)
- **Endpoint**: `DELETE /api/admin/teams/{team}/members/{user}`
- **Controller**: `AdminController::removeMember()`
- **Capabilities**:
  - Remove any member from any team
  - Can remove Scrum Master (automatically sets `scrum_master_id` to null)
  - Works regardless of team status (pending/active/suspended)
  - No permission checks - admin has full control

### 2. Change Scrum Master
- **Endpoint**: `POST /api/admin/teams/{team}/scrum-master`
- **Controller**: `AdminController::changeScrumMaster()`
- **Request Body**: `{ "user_id": 123 }`
- **Capabilities**:
  - Change Scrum Master to any team member
  - Automatically updates old SM role to "member"
  - Updates new SM role to "scrum_master"
  - Updates `teams.scrum_master_id` column
  - Validates new SM is actually a team member

## Backend Implementation

### Routes Added
```php
// backend/routes/api.php (in admin middleware group)
Route::delete('/teams/{team}/members/{user}', [AdminController::class, 'removeMember']);
Route::post('/teams/{team}/scrum-master', [AdminController::class, 'changeScrumMaster']);
```

### AdminController Methods

#### removeMember()
- Validates user is team member
- Removes member from `team_user` pivot table
- If removed user was Scrum Master, sets `scrum_master_id` to null
- Uses database transaction for safety
- Logs admin action with team/user details
- Returns success message with member name

#### changeScrumMaster()
- Validates `user_id` exists
- Validates new SM is team member
- Updates old SM pivot role to "member"
- Updates new SM pivot role to "scrum_master"
- Updates `teams.scrum_master_id`
- Uses database transaction for safety
- Logs admin action with old/new SM details
- Returns success message with new SM info

## Frontend Implementation

### AdminView.vue Changes

#### UI Components Added
1. **Dropdown in Members Section**
   - Shows "Zmeniť Scrum Mastera" dropdown
   - Lists all team members
   - Triggers confirmation dialog on change

2. **Trash Button per Member**
   - Red trash icon next to each member
   - Tooltip: "Odstrániť člena"
   - Triggers confirmation dialog on click

3. **Remove Member Confirmation Dialog**
   - Shows member name
   - Warning if removing Scrum Master
   - Cancel/Remove buttons

4. **Change SM Confirmation Dialog**
   - Shows new SM name
   - Info message about role change
   - Cancel/Change buttons

#### Methods Added
- `confirmRemoveMember(member)` - Opens removal dialog
- `removeMemberFromTeam()` - Calls DELETE API, refreshes data
- `confirmChangeScrumMaster()` - Opens SM change dialog
- `changeScrumMasterInTeam()` - Calls POST API, refreshes data
- `getSelectedNewSMName()` - Helper to get selected member name

#### State Variables Added
```javascript
const showRemoveMemberDialog = ref(false)
const showChangeSMDialog = ref(false)
const selectedMemberToRemove = ref(null)
const selectedNewScrumMaster = ref(null)
```

## Database Operations

### Remove Member Transaction
1. Detach member from `team_user` pivot
2. If removed user was SM, set `teams.scrum_master_id = null`
3. Commit transaction

### Change SM Transaction
1. Update old SM pivot: `role_in_team = 'member'`
2. Update new SM pivot: `role_in_team = 'scrum_master'`
3. Update team: `scrum_master_id = new_user_id`
4. Commit transaction

## Error Handling

### Remove Member Errors
- `404`: User not member of team
- `500`: Database error during removal

### Change SM Errors
- `400`: New SM not member of team
- `404`: User ID not found
- `500`: Database error during change

## Logging
All admin actions are logged with:
- Admin user ID
- Team ID and name
- Target user ID and name
- Action timestamp
- Old/new values (for SM changes)

## Security
- Routes protected by `auth:sanctum` + `admin` middleware
- User must be authenticated admin
- Frontend checks admin role before allowing access
- Backend double-checks admin permissions

## Testing Checklist
- [ ] Remove regular member from team
- [ ] Remove Scrum Master from team (verify SM becomes null)
- [ ] Change Scrum Master (verify old SM becomes member)
- [ ] Try to remove non-existent member (should error)
- [ ] Try to change SM to non-member (should error)
- [ ] Verify data refresh after actions
- [ ] Check confirmation dialogs display correctly
- [ ] Verify toast notifications show success/error messages

## Future Considerations

### Minimum Team Size
Admin team member management has been expanded. See `ADMIN_USER_MANAGEMENT.md` for complete documentation on:
- User registration and creation
- Team creation and lifecycle
- User movement between teams
- Comprehensive workflows and testing checklists

## Quick Links
- [Full Admin User & Team Management Guide](ADMIN_USER_MANAGEMENT.md)
- [Admin Login & Access](ADMIN_LOGIN.md)
- [Team Approval Workflow](TEAM_APPROVAL_WORKFLOW.md)
Currently admin can remove all members. Consider:
- Prevent removing last member?
- Require separate team deletion action?
- Allow empty teams?

### Project Ownership
When Scrum Master changes:
- Current: Projects remain owned by team
- Future: Transfer project creator metadata?

### Audit Logging
Currently logged to Laravel log. Consider:
- Separate admin_actions table?
- UI for viewing admin action history?
- Export admin logs for compliance?

### Notifications
Consider notifying affected users:
- Email when removed from team
- Email when promoted to/demoted from SM
- In-app notifications

## Related Files
- `backend/routes/api.php` - Route definitions
- `backend/app/Http/Controllers/Api/AdminController.php` - Controller logic
- `frontend/src/views/AdminView.vue` - UI implementation
