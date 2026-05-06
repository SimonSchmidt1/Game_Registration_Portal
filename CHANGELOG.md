# Changelog

All notable changes to the Game Registration Portal will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.2] - 2026-02-06

### Fixed - Complete Light Mode Support
- **Centralised theme overrides**: All `.steam-theme` Tailwind utility class overrides and PrimeVue component overrides moved from individual Vue scoped styles to `frontend/src/assets/main.css`
- **Comprehensive coverage**: Added overrides for ~100 hardcoded dark-colour Tailwind classes including backgrounds, text, borders, gradients, hovers, opacity variants, and semantic colours
- **GameView / AddGameView**: Added `steam-theme` class to deprecated views so they also render correctly in light mode
- **PrimeVue global overrides**: Inputs, dropdowns, calendars, buttons, file uploads, and dropdown panels now theme-aware globally
- **No more partial light mode**: All views (ProjectView, AdminView, AddProjectView, GameView, AddGameView) now fully respect the light/dark toggle

### Added - Admin Academic Year Management
- **Admin endpoint**: `POST /api/admin/academic-years` to create academic years
- **Strict format validation**: `YYYY/YYYY` with sequential year enforcement
- **Admin UI**: Add academic year dialog with safest auto-suggestion

### Added - Absolvent System
- **Database**: `is_absolvent` boolean column on `users` table (default: `false`)
- **CSV Import Integration**: When CSV is imported, users NOT in CSV are marked `is_absolvent = true`, users in CSV have the flag cleared
- **Admin account excluded**: Admin users are never marked as absolvents
- **Visual indicators**: Gray shade (`text-gray-500 opacity-60`) on absolvent names across all views
- **Absolvent badge**: "Absolvent" label shown next to absolvent names in HomeView, TeamView, ProjectView, AdminView
- **Member detail dialog**: Absolvent status row shown in TeamView member detail dialog
- **Backend responses**: `is_absolvent` included in admin users list, admin team detail members, team detail members

### Updated - Documentation
- Updated admin and API documentation to include academic year creation
- Updated documentation for absolvent system

## [1.5.1] - 2026-02-02

### Added - File Format Enhancements
- **RAR Archive Support**: All archive file fields now support RAR format
  - Documentation: `.pdf`, `.docx`, `.zip`, `.rar` (10MB)
  - Source Code: `.zip`, `.rar` (200MB)
  - Export: `.zip`, `.rar`, `.exe`, `.apk`, `.ipa` (500MB)
  - Project Folder: `.zip`, `.rar` (20MB)
  - Updated frontend FileUpload accept attributes and backend validation

### Fixed - Large File Upload Limits
- **PHP Configuration**: Updated upload limits to support large files
  - `upload_max_filesize = 600M`
  - `post_max_size = 650M`
  - `memory_limit = 1024M`
  - `max_execution_time = 300`
  - Fixed 413 Content Too Large errors on large uploads (160MB+)
  - Created `.user.ini` in `backend/public/` with same settings

### Updated - Documentation
- **ARCHITECTURE.md**: Added project_folder to file structure, RAR support details, PHP configuration section
- **FEATURES_IMPLEMENTED.md**: Updated to v1.5.0, added RAR archive feature, project folder, large file support
- **README.md**: Updated project submission documentation with RAR and project folder

## [1.5.0] - 2026-02-01

### Added - Project Folder Upload
- **Project Folder Field**: New optional upload field for projects
  - Accepts ZIP files containing project files and documentation
  - Max size: 20MB
  - Available in project creation and editing
  - Downloads as ZIP from project view
  - Stored in `files.project_folder` JSON column

### Added - User Account Management
- **User Deactivation/Reactivation**: Admins can deactivate user accounts preventing login
  - Deactivate button in Users Management section
  - Deactivate/Activate buttons in team member details
  - Status badge display (Aktívny/Neaktívny)
  - Backend check in AuthService.attemptLogin() returns 'inactive' response
  - Frontend AuthController handles 403 response with message "Váš účet bol deaktivovaný"
- **User Status Column**: `users.status` (active/inactive) with default 'active'
- **Status Filtering**: Users Management dialog shows all users with status badges

### Added - International Teams (SPE)
- **Team Type Support**: New team type "international" with SPE prefix
  - Admin can create teams with type: 'denny' (DEN), 'externy' (EXT), 'international' (SPE)
  - Invite code format: SPEXXXXXX (9 characters, auto-unique)
  - Example codes: SPEABC123, SPEXYZ789
- **Non-UCM Email Support**: International team members can use any email format
  - AuthController.register() detects SPE invite codes, allows non-UCM emails
  - AuthController.login() bypasses UCM email regex for SPE team members
- **Student Type Bypass**: International teams skip student_type matching validation
  - TeamService.joinTeamWithCode() checks for international team type
  - Members can have any student_type in international teams
- **Database Migration**: `2026_01_30_000001_add_team_type_to_teams_table.php`
  - Adds `team_type` column to teams table, default 'denny'

### Added - Occupation Display Normalization
- **Valid Occupations Only**: Only 5 occupations now displayed (with standardized labels)
  - Programátor (Program)
  - Grafik 2D (2D Graphics)
  - Grafik 3D (3D Graphics)
  - Tester (QA Testing)
  - Animátor (Animation)
- **formatOccupation() Helper Function**: Added to all frontend views
  - Normalizes input (lowercase, trim, diacritics)
  - Returns canonical labels with proper Slovak diacritics
  - Returns `null` for invalid occupations (displays as "Neurčené")
- **Display Changes**:
  - Removed "Člen" label entirely
  - Scrum Masters show "SM • Occupation" (not "S")
  - Regular members show "[Occupation]" only
  - All invalid occupations display as "Neurčené"
- **Updated Views**: HomeView.vue, TeamView.vue, ProjectView.vue, GameView.vue, AdminView.vue

### Added - Password Reset Rate Limiting UX
- **Proper HTTP 429 Handling**: Frontend now correctly detects rate limit responses
  - Checks `response.status === 429` in resendResetEmail()
  - Extracts `retry-after` header from response
  - Falls back to 60 seconds if header missing
- **Countdown Timer**: Visual feedback for rate limit duration
  - Starts countdown from retry-after value
  - Updates every 1000ms
  - Button remains disabled during cooldown
  - Displays "Príliš veľa pokusov. Skús znova o X sekúnd." message
- **User-Friendly Error Messages**: Replaces confusing "Chyba pripojenia"
  - Toast notification with proper rate limit context
  - Clear explanation that user hit rate limit (not network error)
- **Backend Configuration**: Rate limiters already in place
  - 1 request per minute per email+IP
  - 5 requests per hour per email+IP

### Added - Authorized Students (CSV Import) System
- **Student Authorization Database**: New `authorized_students` table
  - Schema: student_id, name, email, student_type, is_active
  - Feature flag: `REQUIRE_AUTHORIZED_STUDENTS=false` (disabled by default)
- **CSV Import Functionality**: Admin endpoint for bulk student import
  - Validates CSV format (required columns: student_id, name, email, student_type)
  - Line-by-line error reporting with validation details
  - Bulk upsert - updates existing records, adds new ones
  - Max file size: 5MB
- **Admin Panel UI**: New "Importovať študentov (CSV)" button
  - CSV file upload dialog with real-time validation results
  - Error counter and detailed error list
  - Feature status indicator (enabled/disabled)
- **Registration Validation**: Authorization check during signup
  - Checks if email exists in authorized_students table
  - Only active students can register
  - International teams (SPE codes) always exempt from check
  - Clear error message: "Nie ste registrovaný v systéme UCM"
- **Management Endpoints**:
  - `POST /api/admin/authorized-students/import` - Import CSV
  - `GET /api/admin/authorized-students` - List all with pagination
  - `POST /api/admin/authorized-students/{id}/toggle` - Activate/deactivate
  - `GET /api/admin/config` - Check feature status

### Changed
- User status now visible throughout admin panel and team management
- Team type selection available in team creation dialog
- Occupation formatting applied consistently across all views
- Password reset error handling improved with proper 429 detection
- **Trailer video max size increased** from 20MB to 100MB

### Fixed
- "Chyba pripojenia" error on password reset resend (was actually HTTP 429 rate limit)
- Invalid occupations no longer displayed to users
- "Člen" label removed to avoid confusion with occupations
- Rate limiting now properly communicated to users

---

## [1.4.0] - 2025-12-20

### Added - Universal File Upload System
- **New File Types for All Projects:**
  - **Documentation:** PDF/ZIP files (10MB max) - project docs, manuals, guides
  - **Presentation:** PDF/PPT/PPTX files (15MB max) - project presentations
  - **Source Code:** ZIP files (200MB max) - complete project source
  - **Export:** ZIP/EXE/APK/IPA files (500MB max) - compiled builds
    - Export Type Selector: standalone, WebGL, mobile, executable
- **Backend Implementation:**
  - Files stored in `projects.files` JSON column
  - Export type stored in `projects.metadata` JSON column
  - Universal validation rules for all project types
  - Organized storage: `projects/{type}/documentation|presentations|source|exports/`
- **Frontend Implementation:**
  - New "Súbory projektu" section in AddProjectView
  - Export type dropdown with 4 options
  - Download buttons in ProjectView for all file types
  - Dynamic export label based on metadata
- **Admin Panel Updates:**
  - Added has_documentation, has_presentation, has_source_code indicators
  - Project cards show 8 file status indicators (was 5)
  - Green/red status with icons for each file type

### Added - Admin & Academic Year Setup
- **Permanent Admin Account:**
  - Admin user created in database via seeder
  - Email: `admin@gameportal.local`
  - Password from `ADMIN_PASSWORD` env variable
  - Auto-verified and always accessible
- **Academic Years Seeder:**
  - Automatically seeds 2023/2024, 2024/2025, 2025/2026
  - Called from DatabaseSeeder
  - Safe to re-run (uses updateOrCreate)

### Changed
- Project model casts `files` and `metadata` as arrays
- AdminController `teamProjects()` and `showTeam()` return new file indicators
- File validation increased: source_code (200MB), export (500MB)
- Admin panel grid changed from 5 to 4 columns to fit 8 indicators

### Fixed
- Admin file indicators now check JSON files column correctly
- Registration now works (server properly started)
- DatabaseSeeder calls AcademicYearSeeder automatically

---

## [1.3.0] - 2025-12-04

### Added - Modern UI Design
- **Glass-morphism Effects:** Semi-transparent cards with backdrop blur
- **Gradient Backgrounds:** Deep slate to indigo gradient across entire app
- **Seamless Layout:** Navbar, content, and footer blend as one unified design
- **Custom Button Styles:**
  - Primary: Indigo-violet gradient with glow shadow
  - Secondary: Semi-transparent with subtle borders
  - Ghost: Transparent with hover fill
- **Improved Dialogs:** Centered titles with custom close buttons
- **Custom Scrollbars:** Styled to match dark theme
- **Project Cards:** Hover lift effect with border glow

### Added - Code Quality Improvements
- **Event Listener Cleanup:** Added `onUnmounted` hooks to properly clean up:
  - `Navbar.vue`: `login` and `team-changed` event listeners
  - `LoginView.vue`: Redirect timeouts
  - `RegisterView.vue`: Redirect timeout
  - `VerifyEmail.vue`: Redirect timeout
  - `TemporaryLoginView.vue`: Redirect timeout
- **Occupation Formatting:** Added `formatOccupation()` helper for proper Slovak diacritics display

### Fixed
- Dialog titles now perfectly centered (independent of close button)
- Occupation display with proper diacritics (Programátor, Animátor)
- Black gaps between navbar, content, and footer eliminated

---

## [1.2.0] - 2025-12-04

### Added - Complete Team Approval Workflow
- **Pending Team Restrictions:** Teams with `pending` status cannot:
  - Publish or edit projects (403 error)
  - Accept new members via invite code (403 error)
  - Have members removed (403 error)
  - Have members leave (403 error)
- **Visual Indicators:**
  - Orange team names for pending teams
  - ⏳ badge in team selector
  - Warning banners in HomeView and AddProjectView
  - Strikethrough invite codes for pending teams
  - Disabled action buttons in "Moje Tímy" dialog
- **Backend Enforcement:**
  - `TeamService.joinTeam()` checks team status
  - `TeamService.removeMember()` checks team status
  - `TeamService.leaveTeam()` checks team status
  - `TeamController` returns appropriate 403 responses

### Changed
- Team status now stored in localStorage for cross-component access
- Navbar "Pridať projekt" button hidden for pending/suspended teams
- Admin panel highlights pending teams with pulsing animation

---

## [1.1.1] - 2025-12-04

### Fixed - Environment Configuration
- **Frontend `.env`:** Added `VITE_API_URL` and `VITE_ADMIN_EMAIL`
- **Backend `.env`:** Added `ADMIN_EMAIL` and `ADMIN_PASSWORD`
- **Admin User:** Created via `DatabaseSeeder.php`
- **Migration Safety:** All migrations now use `Schema::hasColumn()` checks to prevent duplicate column errors

### Fixed - Admin Login
- Admin credentials loaded from environment variables
- Admin user auto-created on database seed
- Frontend detects admin email for admin login flow

---

## [1.1.0] - 2025-11-27

### Added - Student Type Classification
- Users must specify student type during registration:
  - `Denný študent` (daily)
  - `Externý študent` (external)
- Added `student_type` column to `users` table
- Display in user profile

### Added - Team Member Occupations
- All team members select occupation when creating/joining teams:
  - Programátor
  - Grafik 2D
  - Grafik 3D
  - Tester
  - Animátor
- Added `occupation` column to `team_user` pivot table
- Occupation displayed in team member lists

### Added - Project Categorization
- **School Type:** ZŠ, SŠ, VŠ
- **Year of Study:** Dynamic based on school type
- **Subject:** Slovenský jazyk, Matematika, Informatika, etc.
- Clickable filter badges on project cards

### Breaking Changes
- `POST /api/teams` requires `occupation` field
- `POST /api/teams/join` requires `occupation` field
- `POST /api/register` requires `student_type` field

---

## [1.0.0] - 2025-11-26

### Initial Release
- Multi-project support (games, web apps, mobile apps, libraries)
- User authentication with email verification
- Team management with Scrum Master roles
- Project uploads with multimedia support
- Rating system (1-5 stars)
- Academic year organization
- Password reset and temporary password recovery
- Failed login attempt tracking

---

## Migration Notes

### Upgrading to 1.2.0+
1. Ensure `teams.status` column exists (run migrations)
2. Existing teams default to `active` status
3. New teams will be created as `pending`

### Upgrading to 1.1.1+
1. Add to `backend/.env`:
   ```
   ADMIN_EMAIL=admin@gameportal.local
   ADMIN_PASSWORD=your-secure-password
   ```
2. Add to `frontend/.env`:
   ```
   VITE_API_URL=http://127.0.0.1:8000
   VITE_ADMIN_EMAIL=admin@gameportal.local
   ```
3. Run: `php artisan db:seed` to create admin user
4. Clear cache: `php artisan config:clear`
