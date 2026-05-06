## Game Registration Portal

**Current Version:** 1.5.0

### Overview
A web-based platform for UCM university students to register, showcase, and rate academic projects. Features team management with admin approval, project submissions with multimedia assets, international team support, and a modern dark-themed UI.

**Supported Project Types:**
- game
- web_app
- mobile_app
- library
- other

### Quick Start

#### Prerequisites
- PHP 8.2+
- Node.js 18+
- MySQL/MariaDB
- Composer

#### Backend Setup
```powershell
cd backend

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Add required environment variables to .env:
# ADMIN_EMAIL=admin@gameportal.local
# ADMIN_PASSWORD=qKyUtyz0kSOyJxLU5E09zMkKospW6XZ9

# Database setup
php artisan migrate
php artisan db:seed

# Storage symlink
php artisan storage:link

# Start server
php artisan serve
```

#### Frontend Setup
```powershell
cd frontend

# Install dependencies
npm install

# Create .env file with:
# VITE_API_URL=http://127.0.0.1:8000
# VITE_ADMIN_EMAIL=admin@gameportal.local

# Start development server
npm run dev
```

### Key Features

#### Team Management
- **Create Teams:** Users can create teams (requires admin approval)
- **Join Teams:** Members join via 6-character invite codes
- **Occupations:** All members select their role (Programátor, Grafik 2D/3D, Tester, Animátor)
- **Scrum Master:** Team creator becomes Scrum Master with project publishing rights

#### Project Submissions
Teams can upload comprehensive project materials:
- **Universal Files (all project types):**
  - **Documentation:** PDF, DOCX, ZIP, or RAR files (max 10MB) - project documentation, guides, manuals
  - **Presentation:** PDF, PPT, or PPTX files (max 15MB) - project presentations
  - **Source Code:** ZIP or RAR files (max 200MB) - full project source code
  - **Export:** ZIP, RAR, EXE, APK, or IPA files (max 500MB) - compiled builds/executables
    - Export Type: standalone app, WebGL build, mobile game, or executable
  - **Project Folder:** ZIP or RAR files (max 20MB) - complete project directory structure
- **Media Assets:**
  - **Splash Screen:** JPG/PNG images for project thumbnails
  - **Video:** Upload video files or provide YouTube/external URLs
- **Metadata:** School type, year of study, subject, academic year, release date

#### Team Approval Workflow
New teams are created with `pending` status:
- ⏳ **Pending Teams:** Cannot publish projects, invite code inactive, orange visual indicators
- ✅ **Active Teams:** Full functionality after admin approval
- 🚫 **Suspended Teams:** All actions disabled

See [TEAM_APPROVAL_WORKFLOW.md](TEAM_APPROVAL_WORKFLOW.md) for details.

#### Admin Panel
- Access at `/admin` (admin login required)
- **Full Admin Capabilities:**
  - Create teams directly (with name, academic year, type, status)
  - Add academic years (format YYYY/YYYY, auto-suggested)
  - Register verified student accounts
  - Move users between teams (automatic Scrum Master handling)
  - Remove team members and reassign Scrum Masters
  - Manage all projects (view, edit, delete)
  - View/approve/reject pending teams
  - Dashboard with team, project, and user statistics
- **Permissions:** Admin overrides all team status restrictions and permission checks

See [ADMIN_LOGIN.md](ADMIN_LOGIN.md) and [ADMIN_USER_MANAGEMENT.md](ADMIN_USER_MANAGEMENT.md) for detailed documentation.

### API Endpoints

#### Projects (auth:sanctum)
```
GET    /api/projects                    # List projects (filters: type, school_type, year_of_study, subject)
POST   /api/projects                    # Create project (Scrum Master only, active team required)
PUT    /api/projects/{id}               # Update project
GET    /api/projects/{id}               # Project detail
POST   /api/projects/{id}/views         # Increment views
POST   /api/projects/{id}/rate          # Rate (once per user)
GET    /api/projects/{id}/user-rating   # Current user's rating
GET    /api/projects/my?team_id={id}    # Team's projects
```

#### Teams
```
GET    /api/user/team                   # User's teams with status
POST   /api/teams                       # Create team (becomes pending)
POST   /api/teams/join                  # Join via invite code
DELETE /api/teams/{id}/members/{userId} # Remove member (active teams only)
POST   /api/teams/{id}/leave            # Leave team (active teams only)
GET    /api/academic-years              # List academic years
```

#### Admin
```
POST   /api/admin/login                       # Admin authentication
GET    /api/admin/stats                       # Dashboard statistics
GET    /api/admin/teams                       # List all teams
POST   /api/admin/teams                       # Create team
GET    /api/admin/teams/{team}                # Team detail
PUT    /api/admin/teams/{team}                # Update team
DELETE /api/admin/teams/{team}                # Delete team
POST   /api/admin/teams/{team}/approve        # Approve pending team
POST   /api/admin/teams/{team}/reject         # Reject pending team
GET    /api/admin/teams/{team}/projects       # Team projects
DELETE /api/admin/teams/{team}/members/{user} # Remove member (admin bypass)
POST   /api/admin/teams/{team}/scrum-master   # Change Scrum Master
POST   /api/admin/academic-years              # Create academic year (YYYY/YYYY)
DELETE /api/admin/projects/{project}          # Delete project
POST   /api/admin/users                       # Register verified student
POST   /api/admin/users/{user}/move-team      # Move user between teams
```

### Project Categorization
Projects are categorized by:
- **School Type:** ZŠ (základná), SŠ (stredná), VŠ (vysoká)
- **Year of Study:** 1-9 (ZŠ) or 1-5 (SŠ/VŠ)
- **Subject:** Slovenský jazyk, Matematika, Informatika, etc.

### UI Design
Steam-inspired interface with full light/dark mode support:
- Slate/blue surfaces (dark) or clean white/gray surfaces (light)
- Muted green accents adapting to each mode
- Flat cards, subtle borders, and compact typography
- Minimal iconography (text-first UI)
- Responsive layouts for all screen sizes
- Theme toggle in the Navbar; preference stored in `localStorage`
- All Tailwind overrides centralised in `main.css` via `.steam-theme` class

### Environment Variables

#### Backend (.env)
```env
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=game_portal
ADMIN_EMAIL=admin@gameportal.local
ADMIN_PASSWORD=your-secure-password
```

#### Frontend (.env)
```env
VITE_API_URL=http://127.0.0.1:8000
VITE_ADMIN_EMAIL=admin@gameportal.local
```

### Troubleshooting

#### 404 Errors on API calls
1. Verify `VITE_API_URL` is set in `frontend/.env`
2. Ensure backend server is running on port 8000
3. Check CORS configuration in `backend/config/cors.php`

#### Admin Login Not Working
1. Verify `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `backend/.env`
2. Run `php artisan config:clear`
3. Ensure admin user exists: `php artisan db:seed`

#### Migration Errors (Duplicate Column)
#### Password Reset Email Issues
- Use the link in the reset email within 1 hour.
- If your reset token is invalid or expired, the reset page now lets you resend the email directly. Enter your school email and click "Poslať znovu".
- Resend is rate‑limited (e.g., once per minute). If you see a "Príliš veľa pokusov" message, wait for the countdown and try again.

Migrations are now idempotent - they check for column existence before modifications.

### Documentation
- [ADMIN_LOGIN.md](ADMIN_LOGIN.md) - Admin authentication
- [TEAM_APPROVAL_WORKFLOW.md](TEAM_APPROVAL_WORKFLOW.md) - Team approval process
- [ARCHITECTURE.md](ARCHITECTURE.md) - System architecture
- [CHANGELOG.md](CHANGELOG.md) - Version history
