## Game Registration Portal

### Overview
A web-based platform for UCM university students to register, showcase, and rate academic projects. Features team management with admin approval, project submissions with multimedia assets, and a modern dark-themed UI.

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

#### Team Approval Workflow
New teams are created with `pending` status:
- ⏳ **Pending Teams:** Cannot publish projects, invite code inactive, orange visual indicators
- ✅ **Active Teams:** Full functionality after admin approval
- 🚫 **Suspended Teams:** All actions disabled

See [TEAM_APPROVAL_WORKFLOW.md](TEAM_APPROVAL_WORKFLOW.md) for details.

#### Admin Panel
- Access at `/admin` (admin login required)
- View/approve/reject pending teams
- Manage all teams and projects
- Statistics dashboard

See [ADMIN_LOGIN.md](ADMIN_LOGIN.md) for admin credentials.

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
```

#### Admin
```
POST   /api/admin/login                 # Admin authentication
GET    /api/admin/stats                 # Dashboard statistics
GET    /api/admin/teams                 # List all teams
POST   /api/admin/teams/{id}/approve    # Approve pending team
POST   /api/admin/teams/{id}/reject     # Reject pending team
DELETE /api/admin/teams/{id}            # Delete team
```

### Project Categorization
Projects are categorized by:
- **School Type:** ZŠ (základná), SŠ (stredná), VŠ (vysoká)
- **Year of Study:** 1-9 (ZŠ) or 1-5 (SŠ/VŠ)
- **Subject:** Slovenský jazyk, Matematika, Informatika, etc.

### UI Design
Modern dark-themed interface with:
- Glass-morphism card effects
- Indigo-violet gradient accents
- Seamless background across all components
- Responsive design for all screen sizes

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
Migrations are now idempotent - they check for column existence before modifications.

### Documentation
- [ADMIN_LOGIN.md](ADMIN_LOGIN.md) - Admin authentication
- [TEAM_APPROVAL_WORKFLOW.md](TEAM_APPROVAL_WORKFLOW.md) - Team approval process
- [ARCHITECTURE.md](ARCHITECTURE.md) - System architecture
- [CHANGELOG.md](CHANGELOG.md) - Version history
