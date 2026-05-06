# Admin User & Team Management

**Last Updated:** February 6, 2026

## Overview
Comprehensive admin capabilities for managing users and teams with full system override permissions. Admins have complete control over team lifecycle, user placement, and team composition.

---

## Admin Endpoints Summary

### Dashboard & Statistics
- **GET** `/api/admin/stats` - System statistics (teams, projects, users, pending teams, media counts)

### Teams Management
- **GET** `/api/admin/teams` - List all teams with filtering and search
- **POST** `/api/admin/teams` - Create new team
- **GET** `/api/admin/teams/{team}` - Team detail with members and projects
- **PUT** `/api/admin/teams/{team}` - Update team (name, status)
- **DELETE** `/api/admin/teams/{team}` - Soft-delete team
- **POST** `/api/admin/teams/{team}/approve` - Approve pending team
- **POST** `/api/admin/teams/{team}/reject` - Reject pending team with optional reason
- **GET** `/api/admin/teams/{team}/projects` - Get team's projects with media indicators
- **GET** `/api/admin/teams/{team}/projects` - Get team's projects with media indicators

### Team Member Management
- **DELETE** `/api/admin/teams/{team}/members/{user}` - Remove member (auto-null SM if needed)
- **POST** `/api/admin/teams/{team}/scrum-master` - Change Scrum Master (auto-role updates)

### Projects Management
- **GET** `/api/admin/projects` - List all projects with filtering and search
- **DELETE** `/api/admin/projects/{project}` - Delete project and associated ratings

### Academic Years Management
- **POST** `/api/admin/academic-years` - Create academic year (format `YYYY/YYYY`)

### User Management
- **GET** `/api/admin/users` - List all non-admin users with status and `is_absolvent` flag
- **POST** `/api/admin/users` - Register verified student account
- **POST** `/api/admin/users/{user}/deactivate` - Deactivate user (prevents login)
- **POST** `/api/admin/users/{user}/activate` - Reactivate user (allows login)
- **POST** `/api/admin/users/{user}/move-team` - Move user between teams (auto-SM handling)

### Absolvent System (via CSV Import)
- **Trigger**: When admin imports a CSV via the authorized students feature
- **Marking logic**: After CSV rows are processed, all non-admin users whose email is NOT in the `authorized_students` table (active) are marked `is_absolvent = true`
- **Clearing logic**: Users whose email IS in the authorized students list have `is_absolvent` cleared to `false`
- **Admin excluded**: The admin account is never affected by absolvent marking
- **Visual indicators**:
  - Gray shade (`text-gray-500 opacity-60`) on absolvent names in all views
  - "Absolvent" badge label next to names in HomeView, TeamView, ProjectView, AdminView
  - Absolvent status row in TeamView member detail dialog
- **Data field**: `users.is_absolvent` boolean, default `false`
- **Response**: CSV import response includes `absolvents_marked` and `absolvents_cleared` counts

---

## Dashboard Statistics

The admin panel displays real-time statistics via `GET /api/admin/stats`:

**Response**:
```json
{
  "total_teams": 5,
  "total_projects": 12,
  "total_users": 25,
  "teams_with_projects": 4,
  "projects_with_video": 8,
  "projects_with_splash": 7,
  "pending_teams": 2
}
```

**Statistics Cards**:
- **Celkom tímov**: Total teams (all statuses)
- **Celkom projektov**: Total projects across all teams
- **Používatelia**: Non-admin users in system
- **Čakajúce tímy**: Pending teams with pulsing indicator (orange badge)

**Internal Metrics**:
- `teams_with_projects`: Teams with at least one project
- `projects_with_video`: Projects with video media
- `projects_with_splash`: Projects with splash screen

---

## User Registration

Admins can create verified student accounts directly without email verification.

**Location**: Admin Panel → "Registrovať používateľa" button

**Form Fields**:
- **Názov** (Name): Student name (required, max 255 chars)
- **Email**: Must be unique (required, valid email format)
- **Heslo** (Password): Minimum 8 characters (required)
- **Typ študenta** (Student Type): Dropdown - "Denný študent" or "Externý študent" (required)

**Backend Implementation**:
- **Endpoint**: `POST /api/admin/users`
- **Controller**: `AdminController::createUser()`
- **Auto-verified**: `email_verified_at` is set immediately to current timestamp
- **Role**: Always created as `'student'` (admins cannot create other admins)
- **No verification token**: Users can log in immediately with provided password
- **Password hashing**: Secure hashing applied before storage
- **Failed attempts**: Initialized to 0

**Validation Rules**:
```
name: required, string, max:255
email: required, email, unique:users,email
password: required, string, min:8
student_type: required, in:denny,externy
```

**Response Example**:
```json
{
  "message": "Používateľ 'John Doe' bol úspešne registrovaný",
  "user": {
    "id": 15,
    "name": "John Doe",
    "email": "john@example.com",
    "student_type": "denny",
    "role": "student",
    "email_verified_at": "2025-12-25T10:30:00Z"
  }
}
```

**Key Differences from Regular Registration**:
| Feature | Admin Registration | Regular Registration |
|---------|-------------------|----------------------|
| Email Verification | Automatic (no email) | Requires verification link |
| Verification Token | None | Generated and emailed |
| Can Login Immediately | Yes | No (until verified) |
| Admin Only | Yes | No |

---
User Deactivation & Reactivation

### Overview
Admins can deactivate user accounts to prevent login while preserving all team and project data. Users remain in teams but cannot access the system.

### Deactivate User

**Location**: Admin Panel → Users Management → Deactivate button per user

**Backend**:
- **Endpoint**: `POST /api/admin/users/{user}/deactivate`
- **Controller**: `AdminController::deactivateUser()`
- **Action**: 
  1. Sets `users.status = 'inactive'`
  2. Revokes all active tokens (Sanctum logout)
  3. User data and team membership preserved
- **Response**:
  ```json
  {
    "message": "Používateľ 'John Doe' bol deaktivovaný",
    "status": "inactive"
  }
  ```

**Login Behavior When Inactive**:
- User attempts login with email and password
- AuthService.attemptLogin() checks `users.status`
- Returns 'inactive' response if status is not 'active'
- Frontend displays: "Váš účet bol deaktivovaný"
- User cannot proceed to dashboard

### Reactivate User

**Location**: Admin Panel → Users Man,internationalagement → Activate button per user

**Backend**:
- **Endpoint**: `POST /api/admin/users/{user}/activate`
- **Controller**: `AdminController::activateUser()`
- **Action**: Sets `users.status = 'active'`
- **Effect**: User can login again immediately
- **Response**:
  ```json
  {
    "message": "Používateľ 'John Doe' bol aktivovaný",
    "status": "active"
  }
  ```

### Mteam_type": "denny",
    "anage Team Member Status

Admins can also deactivate/activate users directly from team member details.

**Location**: Admin Panel → Team Detail → Team Members → Status Badge/Buttons

**UI**:
- Status badge shows "Aktívny" (green) or "Neaktívny" (red)
- Deactivate button appears for active members
- Activate button appears for inactive members
- Click button to toggle status with confirmation dialog

**Frontend Implementation** (AdminView.vue):
- `deactivateTeamMember(member)` - Sets status='inactive'
- `activateTeamMember(member)` - Sets status='active'
- Updates local team members array after API call
- Team detail dialog refreshes with new status

### Database Schema

**Migration**: `2026_01_30_000000_add_status_to_users_table.php`
```sql
ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active';
```

**User Model**:
```php
protected $fillable = [
    'name', 'email', 'password', 'student_type', 'status'
];
```

### Audit & Notes

- Deactivation preserves all data (teams, projects, ratings)
- User remains in team member lists
- User remains visible in admin panel
- Multiple deactivations/reactivations supported
- No cascading effects (teams/projects not affected)
- Tokens immediately revoked on deactivation (Sanctum logout)

---
## Team Management

### 1. Create New Teams

Admins can create empty teams for direct user assignment or invite code distribution.

**Location**: Admin Panel → "Vytvoriť tím" button

**Form Fields**:
- **Názov tímu** (Team Name): Unique name (required, max 255 chars)
- **Akademický rok** (Academic Year): Dropdown from database (required)
- **Typ tímu** (Team Type): "Denný tím" or "Externý tím" (required)
- **Stav** (Status): "Aktívny", "Čakajúci", or "Pozastavený" (optional, defaults to active)

**Backend Implementation**:
- **Endpoint**: `POST /api/admin/teams`
- **Controller**: `AdminController::createTeam()`
- **Invite Code Generation**:
  - Prefix: `DEN` (denný) or `EXT` (externý)
  - Format: `DENXXXXXX` or `EXTXXXXXX` (9 chars total)
  - Auto-unique validation in loop
  - Example codes: `DENAHK7BJ`, `EXTXYZ123`, `DENJKL456`
- **Scrum Master**: Optional (nil by default, assign later via Change SM)
- **Members**: Team starts empty
- **Status Behavior**:
  - `active`: Team immediately accepts members via join code
  - `pending`: Team in approval queue (cannot accept members)
  - `suspended`: Team disabled (all actions blocked)

**Validation Rules**:
```
name: required, string, max:255, unique:teams,name
academic_year_id: required, exists:academic_years,id
team_type: required, in:denny,externy
status: nullable, in:active,pending,suspended
scrum_master_id: nullable, exists:users,id
scrum_master_occupation: nullable, in:Programátor,Grafik 2D,Grafik 3D,Tester,Animátor
```

---

## Academic Years

Admins can add academic years used across team creation and project filtering.

**Location**: Admin Panel → "Pridať akademický rok" button

**Backend Implementation**:
- **Endpoint**: `POST /api/admin/academic-years`
- **Controller**: `AdminController::createAcademicYear()`

**Validation Rules**:
```
name: required, string, max:255, unique:academic_years,name, regex:/^\d{4}\/\d{4}$/
```

**Additional Rule**:
- Second year must be exactly start year + 1 (e.g., 2026/2027)

**Response Example**:
```json
{
  "message": "Akademický rok bol úspešne vytvorený",
  "academic_year": {
    "id": 6,
    "name": "2026/2027"
  }
}
```

**UI Notes**:
- The dialog pre-fills the safest suggested next year based on the highest valid existing year.
- Admin can edit the suggestion before saving (format still validated).

**Response Example**:
```json
{
  "message": "Tím 'Team Alpha' bol úspešne vytvorený",
  "team": {
    "id": 5,
    "name": "Team Alpha",
    "invite_code": "DENAHK7BJ",
    "status": "active",
    "academic_year": {
      "id": 2,
      "name": "2024/2025"
    },
    "scrum_master_id": null
  }
}
```

### 2. Edit Teams

Admins can modify team name and status after creation.

**Location**: Admin Panel → Team detail → Pencil icon

**Fields**:
- **Názov tímu** (Team Name): Current name, editable
- **Stav** (Status): Status dropdown (active/pending/suspended)

**Backend**:
- **Endpoint**: `PUT /api/admin/teams/{team}`
- **Controller**: `AdminController::updateTeam()`
- **Atomic**: Uses database transaction

**Validation**:
```
name: sometimes, string, max:255
status: sometimes, in:active,pending,suspended
```

### 3. Delete Teams

Soft-deletes teams while preserving historical data.

**Location**: Admin Panel → Team table → Trash icon

**Backend**:
- **Endpoint**: `DELETE /api/admin/teams/{team}`
- **Controller**: `AdminController::deleteTeam()`
- **Process**:
  1. Detaches all members from pivot
  2. Sets `deleted_at` timestamp
  3. Team excluded from normal queries

**Response**:
```json
{
  "message": "Tím 'Team Alpha' bol úspešne zmazaný",
  "deleted": true,
  "team_id": 5
}
```

### 4. Approve/Reject Pending Teams

Manage teams awaiting admin approval.

**Location**: "Čakajúce na schválenie" section (top of dashboard)

**Approve**:
- **Endpoint**: `POST /api/admin/teams/{team}/approve`
- **Action**: Changes status from "pending" → "active"
- **Effect**: Team can now accept members via join code

**Reject**:
- **Endpoint**: `POST /api/admin/teams/{team}/reject`
- **Request Body**: `{ "reason": "Project type not suitable" }`
- **Action**: Soft-deletes team and detaches members
- **Logging**: Rejection reason recorded

---

## Team Member Management

### 1. Remove Members

Admins can remove any member from any team (bypassing all restrictions).

**Location**: Admin Panel → Team Detail → Members list → Trash icon per member

**Backend**:
- **Endpoint**: `DELETE /api/admin/teams/{team}/members/{user}`
- **Controller**: `AdminController::removeMember()`
- **Scrum Master Handling**: If removing a Scrum Master:
  - Sets team `scrum_master_id = null`
  - Member role changed to null in pivot
  - Team can still exist without SM
- **Restrictions**: None (admin override)

**Response**:
```json
{
  "message": "Člen 'Jane Smith' bol odstránený z tímu 'Team Alpha'",
  "removed": true
}
```

**Validation Rules**:
- User must be member of specified team

### 2. Change Scrum Master

Admins can reassign the Scrum Master role to any team member.

**Location**: Admin Panel → Team Detail → "Zmeniť Scrum Mastera" dropdown (in Members section)

**Backend**:
- **Endpoint**: `POST /api/admin/teams/{team}/scrum-master`
- **Controller**: `AdminController::changeScrumMaster()`
- **Request Body**:
  ```json
  {
    "user_id": 123
  }
  ```
- **Role Updates**:
  - Old SM: `role_in_team` changes to "member"
  - New SM: `role_in_team` changes to "scrum_master"
  - Team's `scrum_master_id` updated to new user ID

**Validation Rules**:
```
user_id: required, exists:users,id
```

**Additional Validation**:
- New SM must be member of the team

**Response**:
```json
{
  "message": "Scrum Master bol zmenený na 'John Doe'",
  "scrum_master": {
    "id": 10,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

### 3. Move Users Between Teams

Admins can reassign users from one team to another with automatic Scrum Master handling.

**Location**: Admin Panel → Team Detail → Members list → Arrows icon (↔) per member

**UI Flow**:
1. Click arrows icon next to member
2. Dialog opens showing:
   - User name and email (read-only)
   - Source team name (read-only)
   - Target team dropdown (all teams except source)
   - Occupation selector (required)
3. Warning message if user is Scrum Master
4. Click "Presunúť" to execute

**Form Fields**:
- **Používateľ** (User): Display name and email
- **Zdrojový tím** (Source Team): Display current team
- **Cieľový tím** (Target Team): Dropdown of available teams
- **Povolanie v novom tíme** (Occupation): Dropdown of occupations

**Backend**:
- **Endpoint**: `POST /api/admin/users/{user}/move-team`
- **Controller**: `AdminController::moveUserBetweenTeams()`
- **Request Body**:
  ```json
  {
    "from_team_id": 1,
    "to_team_id": 2,
    "occupation": "Programátor"
  }
  ```

**Validation Rules**:
```
from_team_id: required, integer, exists:teams,id
to_team_id: required, integer, exists:teams,id
occupation: required, in:Programátor,Grafik 2D,Grafik 3D,Tester,Animátor
```

**Additional Validations**:
- User must be in source team
- User must NOT be in target team
- Target team must have capacity (< 10 members)
- User's `student_type` must match target team type (DEN/EXT prefix)

**Scrum Master Auto-Handling**:
If user is Scrum Master in source team:
1. User is demoted to "member" role
2. Oldest team member (by join date) becomes new Scrum Master
3. If no other members exist, `scrum_master_id` set to null
4. Response includes new SM information

**Response Example**:
```json
{
  "message": "Používateľ 'John Doe' bol presunul z tímu 'Team Alpha' do tímu 'Team Beta'",
  "user": {
    "id": 10,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "was_scrum_master_in_source": true,
  "new_sm_in_source": {
    "id": 8,
    "name": "Jane Smith"
  }
}
```

**Error Cases**:
- `404`: User not in source team
- `409`: User already in target team
- `403`: Target team at capacity
- `400`: Student type mismatch

**Occupation Options**:
- `"Programátor"` - Programmer
- `"Grafik 2D"` - 2D Graphics Designer
- `"Grafik 3D"` - 3D Graphics Designer
- `"Tester"` - QA Tester
- `"Animátor"` - Animator

---

## Project Management

### 1. View Projects

Admins can browse and search all projects in the system.

**Location**: Embedded in team detail (expandable project list)

**Backend**:
- **Endpoint**: `GET /api/admin/projects`
- **Controller**: `AdminController::projects()`
- **Filters** (query parameters):
  - `search`: Search by title (case-insensitive)
  - `type`: Filter by project type

### 2. Delete Projects

Admins can delete any project from any team.

**Location**: Admin Panel → Team Detail → Projects list → Trash icon per project

**Backend**:
- **Endpoint**: `DELETE /api/admin/projects/{project}`
- **Controller**: `AdminController::deleteProject()`
- **Process**:
  1. Deletes all associated ratings
  2. Deletes project record
  3. Soft-delete (uses model's delete method)

**Response**:
```json
{
  "message": "Projekt 'Game XYZ' bol úspešne zmazaný",
  "deleted": true,
  "project_id": 25
}
```

---

## Admin Panel UI Components

### Main Dashboard
**Statistics Section**:
- 4-column grid of cards:
  1. **Celkom tímov** (blue) - Total teams count
  2. **Celkom projektov** (green) - Total projects count
  3. **Používatelia** (purple) - Total non-admin users
  4. **Čakajúce tímy** (orange/yellow, pulsing) - Pending teams with bell icon

**Action Buttons** (top bar):
- "Obnoviť" (Refresh) - Manual data refresh with loading spinner
- "Registrovať používateľa" (Register User) - Opens user registration dialog
- "Vytvoriť tím" (Create Team) - Opens team creation dialog

**Pending Teams Section** (if any):
- Card with list of pending teams
- Each team shows: name, scrum master, academic year
- Action buttons per team: "Schváliť" (Approve), "Zamietnuť" (Reject)

### Teams Overview Table
**Search & Filter**:
- Search input: Filter teams by name
- Status dropdown: Filter by status (All, Active, Pending, Suspended)

**Columns**:
1. Expand icon (toggles projects)
2. Team name and invite code
3. Academic year
4. Member count
5. Project count
6. Status badge
7. Actions (View, Edit, Delete)

### Dialogs

**Team Detail Modal**:
- Team info and invite code
- Members section with move/remove buttons
- Change Scrum Master dropdown
- Projects section with file/media indicators

**Edit Team Dialog**:
- Team name input
- Status selector dropdown
- Cancel and Save buttons

**Register User Dialog**:
- Name, email, password inputs
- Student type dropdown
- Info: "Email bude automaticky overený. Používateľ sa môže ihneď prihlásiť."
- Cancel and Register buttons

**Create Team Dialog**:
- Team name input
- Academic year dropdown (lazy-loaded)
- Team type dropdown (Denný/Externý)
- Status dropdown
- Info: "Typ tímu určuje, či sa môžu pripojiť len denní alebo externí študenti."
- Cancel and Create buttons

**Move to Team Dialog**:
- User display (name, email - read-only)
- Source team display (read-only)
- Target team dropdown
- Occupation dropdown
- Warning box (yellow): SM demotion info
- Cancel and Move buttons

---

## Security & Permissions

### Protected Routes
All admin endpoints require:
- Authenticated user: `auth:sanctum` middleware
- Admin role: Custom `admin` middleware

### Audit Logging
All admin actions logged with:
- Admin user ID
- Affected resource IDs and names
- Action type and timestamp

---

## Common Workflows

### Onboard New Team Manually
1. Register students via "Registrovať používateľa"
2. Create team via "Vytvoriť tím"
3. Assign Scrum Master via team detail
4. Manually move students to team via arrows icon

### Approve Pending Team
1. Find team in "Čakajúce na schválenie" section
2. Click "Schváliť"
3. Team status changes to "active"
4. Team can now accept members via join code

### Reorganize Team Membership
1. Open team detail
2. For each member needing reassignment:
   - Click arrows icon (Move to Team)
   - Select new team
   - Confirm move
3. System auto-promotes new SM if original was moved

---

## Testing Checklist

### User Registration
- [ ] Register student with all fields populated
- [ ] Verify email marked as verified in database
- [ ] Login with registered credentials works immediately
- [ ] Cannot register duplicate email
- [ ] Cannot create admin users (always student role)
- [ ] Password hashing verified

### Team Creation
- [ ] Create denný team (verify DEN prefix)
- [ ] Create externý team (verify EXT prefix)
- [ ] Verify invite code is unique
- [ ] Create with pending status (appears in approval section)
- [ ] Create with active status (team ready)
- [ ] Duplicate team name rejected
- [ ] Invite code displayed to admin

### Member Management
- [ ] Remove regular member from team
- [ ] Remove Scrum Master (verify scrum_master_id becomes null)
- [ ] Verify member count decreases after removal
- [ ] Attempt to remove non-existent member (error)

### Scrum Master Changes
- [ ] Change SM to different member
- [ ] Verify old SM gets "member" role
- [ ] Verify new SM gets "scrum_master" role
- [ ] Attempt to change to non-member (error)

### User Movement
- [ ] Move member between active teams
- [ ] Move Scrum Master (verify auto-demotion, next SM auto-promotion)
- [ ] Attempt to move to full team (error)
- [ ] Attempt to move to incompatible type (error)
- [ ] Verify member count updates in both teams
- [ ] Verify pivot occupation saves correctly

### Project Management
- [ ] Delete project with ratings (verify ratings deleted)
- [ ] Verify project count decreases
- [ ] Search projects by title
- [ ] Filter projects by type

### Data Consistency
- [ ] After removal, member not in any team list
- [ ] After move, member appears only in target team
- [ ] Only one SM per team at all times
- [ ] Pivot occupations persist correctly
- [ ] Team stats update in real-time

### UI/UX
- [ ] All dialogs open/close correctly
- [ ] Confirmation dialogs show correct data
- [ ] Toast notifications appear on success/error
- [ ] Search and filter work across all lists
- [ ] Refresh button updates data
- [ ] Loading spinners appear during operations
- [ ] Dropdowns lazy-load academic years
- [ ] Expand/collapse team projects smoothly

---

## Related Documentation
- [Admin Login](ADMIN_LOGIN.md) - Authentication and access setup
- [Team Approval Workflow](TEAM_APPROVAL_WORKFLOW.md) - Pending team handling
- [Architecture](ARCHITECTURE.md) - System design overview
- [README](README.md) - Quick start and feature overview
- [ ] Duplicate team name rejected

### Member Removal
- [ ] Remove regular member
- [ ] Remove Scrum Master (verify SM becomes null)
- [ ] Verify member count decreases
- [ ] Attempt to remove non-existent member (error)

### Scrum Master Change
- [ ] Change to different member
- [ ] Verify old SM becomes member
- [ ] Verify new SM gets badge
- [ ] Attempt to change to non-member (error)

### User Movement
- [ ] Move member between active teams
- [ ] Move Scrum Master (verify auto-demotion, SM promotion)
- [ ] Attempt to move to full team (error)
- [ ] Attempt to move to incompatible type (error)
- [ ] Verify occupation selector works with valid values

### Data Consistency
- [ ] After removal, member not in any team list
- [ ] After move, member appears only in target team
- [ ] After SM change, only one SM per team
- [ ] Pivot occupations persist correctly
- [ ] Team member counts update accurately

---

## Related Documentation
- [Admin Login](ADMIN_LOGIN.md) - Authentication and access
- [Team Approval Workflow](TEAM_APPROVAL_WORKFLOW.md) - Pending team handling
- [Architecture](ARCHITECTURE.md) - System design overview
