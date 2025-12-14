# Changelog

All notable changes to the Game Registration Portal will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
