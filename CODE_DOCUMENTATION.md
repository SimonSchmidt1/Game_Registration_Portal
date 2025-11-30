# Game Registration Portal - Code Documentation
**Simple guide for understanding every code file in the system**

---

## BACKEND (Laravel PHP)

### Models (Database Tables) - `backend/app/Models/`

#### `User.php`
- **Purpose**: Represents a user account in the system
- **Main Features**:
  - Stores user information (name, email, password, role, avatar)
  - Handles authentication and email verification
  - Tracks failed login attempts for security
  - Methods to check if user is admin or Scrum Master
- **Relationships**: Can belong to multiple teams

#### `Team.php`
- **Purpose**: Represents a team of students
- **Main Features**:
  - Stores team name, invite code, and academic year
  - Has one Scrum Master who leads the team
- **Relationships**: Has multiple members (users), has multiple games/projects

#### `Project.php`
- **Purpose**: Represents any type of project (game, web app, mobile app, library, or other)
- **Main Features**:
  - Stores project details (title, description, type)
  - Stores categorization: school type (ZŠ/SŠ/VŠ), year of study (1-9 for ZŠ, 1-5 for SŠ/VŠ), and subject
  - Stores file paths for splash screen, video, exports, source code
  - Tracks ratings, views, and release date
- **Relationships**: Belongs to one team and one academic year

#### `Game.php` (LEGACY - being replaced by Project)
- **Purpose**: Old model for games only
- **Note**: New projects use `Project.php` model instead

#### `TeamUser.php`
- **Purpose**: Junction table that connects users to teams
- **Main Features**:
  - Stores which users are in which teams
  - Stores each user's role in team (member or scrum_master)

#### `AcademicYear.php`
- **Purpose**: Represents a school year (e.g., "2024/2025")
- **Main Features**: Just stores the year name

#### `GameRating.php`
- **Purpose**: Stores user ratings for projects/games
- **Main Features**:
  - Each user can rate a project once (1-5 stars)
  - Links user, project/game, and rating number
- **Relationships**: Belongs to user and project/game

#### `PasswordResetToken.php`
- **Purpose**: Stores tokens for password reset and temporary passwords
- **Main Features**:
  - Creates secure tokens for resetting forgotten passwords
  - Creates temporary passwords after 5 failed login attempts
  - Tracks expiration and usage of tokens

---

### Controllers (Handle HTTP Requests) - `backend/app/Http/Controllers/Api/`

#### `AuthController.php`
- **Purpose**: Handles all authentication operations
- **Main Endpoints**:
  - `register()` - Creates new user account, sends verification email
  - `login()` - Logs in user, checks password, handles failed attempts
  - `logout()` - Logs out user by deleting their token
  - `verifyEmail()` - Confirms email address from verification link
  - `updateAvatar()` - Uploads and saves user profile picture
  - `updateProfile()` - Changes user's display name (email cannot be changed)
  - `updatePassword()` - Changes user password (requires current password)
  - `forgotPassword()` - Sends password reset link to email
  - `resetPassword()` - Resets password using token from email
  - `loginWithTemporaryPassword()` - Logs in using temporary password sent after 5 failed attempts

#### `TeamController.php`
- **Purpose**: Manages team operations
- **Main Endpoints**:
  - `getTeamStatus()` - Returns all teams user belongs to with their role
  - `store()` - Creates new team (user becomes Scrum Master)
  - `join()` - Joins team using 6-character invite code
  - `show()` - Shows team details with members
  - `removeMember()` - Scrum Master can remove members from team
  - `leave()` - User can leave team (unless they are Scrum Master)

#### `ProjectController.php`
- **Purpose**: Manages all project types (games, web apps, mobile apps, libraries, other)
- **Main Endpoints**:
  - `index()` - Lists all projects with optional filters (type, school_type, year_of_study, subject, search, academic year)
  - `store()` - Creates new project (only Scrum Master can do this, multiple projects per team allowed)
  - `show()` - Shows single project details
  - `update()` - Updates existing project (only Scrum Master of the project's team can edit)
  - `incrementViews()` - Increases view count when someone views project
  - `rate()` - User rates project (1-5 stars, only once per user)
  - `getUserRating()` - Checks if user already rated this project
  - `my()` - Returns all projects for a specific team

#### `GameController.php` (LEGACY - still works but use ProjectController for new code)
- **Purpose**: Old controller for game-only operations
- **Note**: Kept for backward compatibility, new code should use ProjectController

---

### Services (Business Logic) - `backend/app/Services/`

#### `AuthService.php`
- **Purpose**: Contains all authentication business logic
- **Main Methods**:
  - `register()` - Creates user and sends verification email
  - `attemptLogin()` - Validates credentials, handles failed attempts, returns tokens
  - `verifyToken()` - Validates email verification token
  - `updateAvatar()` - Processes avatar file upload
  - `sendTemporaryPassword()` - Generates and emails temporary password after 5 failed login attempts

#### `TeamService.php`
- **Purpose**: Contains team management logic
- **Main Methods**:
  - `getTeamsStatusForUser()` - Gets all teams for user with their roles
  - `createTeam()` - Creates team and assigns creator as Scrum Master
  - `joinTeam()` - Validates invite code and adds user to team (max 4 members)
  - `removeMember()` - Removes member from team (validates permissions)
  - `leaveTeam()` - Removes user from team (validates they are not Scrum Master)

#### `GameService.php`
- **Purpose**: Contains game/project creation and management logic
- **Main Methods**:
  - `createGame()` - Creates game with file uploads, validates user is Scrum Master
  - `listGames()` - Returns all games with relationships
  - `findGameWithRelations()` - Gets single game with team and academic year data
  - `getUserTeamGames()` - Gets games for user's team
  - `incrementViews()` - Increases view count
  - `rateGame()` - Adds rating and recalculates average
  - `getUserRating()` - Checks if user rated the game

#### `ProjectService.php`
- **Purpose**: Facade for future multi-project handling
- **Note**: Currently delegates to GameService, ready for expansion

---

### Routes - `backend/routes/`

#### `api.php`
- **Purpose**: Defines all API endpoints
- **Public Routes** (no login required):
  - POST `/login` - Login
  - POST `/register` - Register new account
  - POST `/verify-email` - Verify email address
  - POST `/forgot-password` - Request password reset
  - POST `/reset-password` - Reset password with token
  - POST `/login-temporary` - Login with temporary password

- **Protected Routes** (login required):
  - GET `/user` - Get current user info
  - POST `/user/avatar` - Update avatar
  - PUT `/user` - Update profile
  - PUT `/user/password` - Change password
  - POST `/logout` - Logout
  - GET `/user/team` - Get user's teams
  - POST `/teams` - Create team
  - POST `/teams/join` - Join team with code
  - GET `/teams/{id}` - Get team details
  - DELETE `/teams/{team}/members/{user}` - Remove team member
  - POST `/teams/{team}/leave` - Leave team
  - GET `/projects` - List all projects
  - POST `/projects` - Create project
  - GET `/projects/my` - Get my team's projects
  - GET `/projects/{id}` - Get project details
  - POST `/projects/{id}/views` - Increment views
  - POST `/projects/{id}/rate` - Rate project
  - GET `/projects/{id}/user-rating` - Get my rating
  - GET `/academic-years` - Get all academic years

- **Admin Routes** (admin role required):
  - GET `/admin/dashboard` - Admin dashboard (placeholder for future features)

#### `web.php`
- **Purpose**: Defines web routes (currently minimal, API is primary)

#### `auth.php`
- **Purpose**: Authentication routes (integrated into api.php)

#### `console.php`
- **Purpose**: Defines console commands

---

### Middleware - `backend/app/Http/Middleware/`

#### `Authenticate.php`
- **Purpose**: Checks if user is logged in (has valid token)

#### `AdminMiddleware.php`
- **Purpose**: Checks if user has admin role

#### Other Laravel default middleware handle CORS, rate limiting, etc.

---

### Notifications - `backend/app/Notifications/`

#### `VerifyEmailNotification.php`
- **Purpose**: Sends email verification link to new users

#### `PasswordResetNotification.php`
- **Purpose**: Sends password reset link to users who forgot password

#### `TemporaryPasswordNotification.php`
- **Purpose**: Sends temporary password after 5 failed login attempts

---

### Enums - `backend/app/Enums/`

#### `ProjectType.php`
- **Purpose**: Defines valid project types (GAME, WEB_APP, MOBILE_APP, LIBRARY, OTHER)

#### `TeamRole.php`
- **Purpose**: Defines team roles (MEMBER, SCRUM_MASTER)

---

### Rules (Validation) - `backend/app/Rules/`

#### `VideoMaxResolution.php`
- **Purpose**: Custom validation rule to limit video resolution (e.g., max 1920x1080)

---

### Config Files - `backend/config/`

#### `app.php`
- **Purpose**: Main application configuration (name, environment, debug mode, URL, timezone)

#### `database.php`
- **Purpose**: Database connection settings (MySQL)

#### `mail.php`
- **Purpose**: Email sending configuration

#### `cors.php`
- **Purpose**: Cross-Origin Resource Sharing settings for frontend-backend communication

#### `sanctum.php`
- **Purpose**: API token authentication settings

#### Other config files handle caching, sessions, logging, etc.

---

## FRONTEND (Vue.js)

### Main Entry Point - `frontend/src/`

#### `main.js`
- **Purpose**: Initializes the Vue application
- **Main Features**:
  - Imports and configures Vue
  - Registers PrimeVue components globally
  - Sets up axios for API calls
  - Adds global error handler
  - Configures API interceptors for authentication errors
  - Mounts app to DOM

#### `App.vue`
- **Purpose**: Root component that wraps entire application
- **Main Features**:
  - Shows navigation bar (Navbar component)
  - Displays current page content (router-view)
  - Shows footer
  - Implements inactivity timeout (5 minutes)
  - Auto-logs out inactive users

---

### Router - `frontend/src/router/`

#### `index.js`
- **Purpose**: Defines all frontend routes (pages)
- **Routes**:
  - `/` - Home page (project list)
  - `/login` - Login page
  - `/register` - Registration page
  - `/verify-email` - Email verification page
  - `/forgot-password` - Forgot password page
  - `/reset-password` - Reset password page
  - `/add-project` - Add new project page (requires login)
  - `/project/:id` - Project detail page
  - `/team/:id` - Team detail page
  - `/add` - Legacy add game page (backward compatibility)
  - `/game/:id` - Legacy game detail page (backward compatibility)

---

### Components - `frontend/src/components/`

#### `Navbar.vue`
- **Purpose**: Top navigation bar shown on every page
- **Main Features**:
  - Shows "Home" and "Add Project" links
  - Shows login/register buttons when logged out
  - Shows user avatar, profile dialog, and logout button when logged in
  - User profile dialog allows:
    - View user info (name, email, role)
    - Upload/change avatar
    - Edit display name
    - Change password

---

### Views (Pages) - `frontend/src/views/`

#### `HomeView.vue`
- **Purpose**: Main page showing all projects
- **Main Features**:
  - Displays grid of project cards with splash screens
  - Search bar to filter by project name
  - Multi-purpose filter system with independent filters:
    - School type filter (ZŠ, SŠ, VŠ) - optional
    - Year of study filter (1-9) - optional
    - Subject filter (8 subjects available) - optional
    - Project type filter (game, web app, mobile app, library, other)
  - Filters work independently or in combination (teachers can filter by any combination)
  - "Reset filters" button to clear all filters
  - Team selector if user is in team
  - "My Projects" filter to show only team's projects
  - "Create Team" and "Join Team" buttons
  - Create Team dialog with invite code generation
  - Join Team dialog to enter 6-character code
  - Team status dialog showing user's teams
  - Requires login to view projects

#### `LoginView.vue`
- **Purpose**: User login page
- **Main Features**:
  - Email and password form (UCM email format required: 7digits@ucm.sk)
  - Handles normal login
  - Switches to temporary password login after 5 failed attempts
  - Shows error messages with remaining attempts
  - Links to register and forgot password pages
  - Redirects to verify email if account not verified

#### `RegisterView.vue`
- **Purpose**: New user registration page
- **Main Features**:
  - Form with name, email, password, password confirmation
  - Email must be UCM format (7digits@ucm.sk)
  - Password must meet security requirements
  - Sends verification email after registration
  - Redirects to verify email page

#### `VerifyEmail.vue`
- **Purpose**: Email verification confirmation page
- **Main Features**:
  - Shows success/error message after clicking verification link
  - Displays when verification is successful
  - Provides link to login

#### `ForgotPasswordView.vue`
- **Purpose**: Request password reset page
- **Main Features**:
  - User enters email address
  - Sends reset link to email
  - Shows confirmation message

#### `ResetPasswordView.vue`
- **Purpose**: Reset forgotten password page
- **Main Features**:
  - User enters new password (accessed from email link)
  - Validates password strength
  - Requires password confirmation
  - Updates password and logs out all sessions

#### `TemporaryLoginView.vue`
- **Purpose**: Login with temporary password page
- **Main Features**:
  - Shows after 5 failed login attempts
  - User enters email and temporary password from email
  - Temporary password format: XXXX-XXXX-XXXX
  - Expires after 15 minutes

#### `AddProjectView.vue`
- **Purpose**: Create new project page (only Scrum Master can access)
- **Main Features**:
  - Form with all project fields (title, description, release date)
  - Project type selector (game, web app, mobile app, library, other)
  - **Categorization system** (required for Slovak education system):
    - School type selector (Základná Škola/ZŠ, Stredná škola/SŠ, Vysoká Škola/VŠ) - required
    - Year of study selector (dynamic: 1-9 for ZŠ, 1-5 for SŠ/VŠ) - optional
    - Subject selector (Slovenský jazyk, Matematika, Dejepis, Geografia, Informatika, Grafika, Chémia, Fyzika) - required
  - Video upload or YouTube URL option
  - Splash screen (poster image) upload
  - Type-specific fields that show based on selected type:
    - **Game**: Export file, source code, GitHub URL, tech stack
    - **Web App**: Live URL, GitHub URL, tech stack, source code
    - **Mobile App**: Platform selector, APK/iOS files, GitHub URL, tech stack, source code
    - **Library**: Package name, NPM URL, GitHub URL, tech stack, documentation, source code
    - **Other**: Live URL, GitHub URL, tech stack, source code
  - Validates user is Scrum Master before allowing submission
  - Validates year of study matches school type (ZŠ: 1-9, SŠ/VŠ: 1-5)
  - Uploads all files to backend

#### `ProjectView.vue`
- **Purpose**: Single project detail page
- **Main Features**:
  - Shows project title, categorization (school type, year, subject), type, team, academic year
  - Rating system (1-5 stars, user can rate once)
  - View counter
  - Video player with custom controls (if video uploaded) or YouTube embed
  - Splash screen image
  - Full project description
  - Metadata section (GitHub links, live URLs, tech stack, etc.)
  - Team members list
  - Download section for files (exports, source code, documentation)
  - Custom video player features:
    - Play/pause
    - Seek bar
    - Volume control
    - Fullscreen toggle
    - Keyboard shortcuts (Space = play/pause, Arrow keys = skip)
    - 10-second skip forward/backward buttons

#### `GameView.vue` (LEGACY - kept for backward compatibility)
- **Purpose**: Old single game detail page
- **Note**: New code uses ProjectView.vue

#### `AddGameView.vue` (LEGACY - kept for backward compatibility)
- **Purpose**: Old add game page
- **Note**: New code uses AddProjectView.vue

#### `TeamView.vue`
- **Purpose**: Team detail page
- **Main Features**:
  - Shows team name and info
  - Academic year
  - Member count (max 4)
  - Project count
  - Invite code with copy button
  - List of team members with avatars and roles (Member or Scrum Master)
  - Grid of team's projects with cards
  - Links to project details

---

### Services - `frontend/src/services/`

#### `apiClient.js`
- **Purpose**: Centralized axios configuration for API calls
- **Main Features**:
  - Sets base API URL from environment variable
  - Automatically adds auth token to requests
  - Handles authentication errors globally
  - Provides timeout configuration

---

### Features - `frontend/src/features/`

#### `projects/useProjects.js`
- **Purpose**: Composable for project-related operations
- **Note**: Currently minimal, ready for expansion

---

### Types - `frontend/src/types/`

#### `entities.d.ts`
- **Purpose**: TypeScript type definitions for entities
- **Note**: Provides type safety for User, Team, Project, etc.

---

### Configuration Files - `frontend/`

#### `vite.config.js`
- **Purpose**: Vite build tool configuration
- **Main Features**: Defines build settings, dev server, plugins

#### `tailwind.config.js`
- **Purpose**: Tailwind CSS configuration
- **Main Features**: Defines custom colors, spacing, breakpoints

#### `postcss.config.js`
- **Purpose**: PostCSS configuration for CSS processing

#### `package.json`
- **Purpose**: Lists all frontend dependencies and scripts
- **Main Dependencies**:
  - Vue 3 - Frontend framework
  - PrimeVue - UI component library
  - Vue Router - Page routing
  - Axios - HTTP requests
  - Tailwind CSS - Styling

---

## KEY CONCEPTS

### Authentication Flow
1. User registers with UCM email
2. Backend sends verification email
3. User clicks link to verify
4. User can now login
5. Backend returns JWT token
6. Frontend stores token in localStorage
7. All API requests include token in headers

### Security Features
- Email verification required before login (prevents fake accounts)
- Failed login tracking (max 5 attempts before temporary password is sent)
- Temporary password sent after 5 failed attempts (15-minute expiration, one-time use)
- Password reset via email with secure tokens (1-hour expiration)
- Auto-logout after 5 minutes of inactivity (prevents unauthorized access)
- Tokens expire after 2 hours (forces re-authentication)
- Password hashing using bcrypt
- All tokens are stored securely and can be revoked on password change

### Team System
- Each team has 1 Scrum Master (leader)
- Teams can have up to 4 members
- Only Scrum Master can add projects (but can create multiple projects per team)
- Teams have unique 6-character invite code
- Users can join teams using invite code
- Scrum Master can remove members
- Members can leave (but Scrum Master cannot)

### Project System
- 5 project types: Game, Web App, Mobile App, Library, Other
- Each project belongs to one team
- **Categorization system** (designed for Slovak education system to help teachers find curriculum-relevant projects):
  - **School type** (required): Základná Škola (ZŠ), Stredná škola (SŠ), or Vysoká Škola (VŠ)
  - **Year of study** (optional): 
    - For ZŠ: 1-9 (grades 1 through 9)
    - For SŠ/VŠ: 1-5 (years 1 through 5)
  - **Subject** (required): One of 8 available subjects:
    - Slovenský jazyk, Matematika, Dejepis, Geografia, Informatika, Grafika, Chémia, Fyzika
  - This system allows teachers to filter projects by school level, grade/year, and subject to find relevant educational content
- Projects have files: splash screen, video, source code, exports
- Projects can be rated 1-5 stars (once per user)
- Projects track view count
- Projects have academic years
- Each project type has specific fields
- **Multiple projects per team allowed** (Scrum Master can create unlimited projects for their team)

### File Storage
- Files stored in `storage/app/public/`
- Accessible via `/storage/` URL path (requires `php artisan storage:link` to create symlink)
- Organized by type:
  - `avatars/` - User profile pictures
  - `projects/{type}/splash_screens/` - Project poster images
  - `projects/{type}/videos/` - Project video files
  - `projects/{type}/exports/` - Game/application builds
  - `projects/{type}/source/` - Source code archives
  - `projects/{type}/docs/` - Documentation files
- File size limits:
  - Videos: 50MB max
  - Exports: 500MB max
  - Source code: 200MB max
  - Splash screens: 10MB max

### API Communication
- Backend: Laravel REST API at `/api/`
- Frontend: Vue.js SPA (Single Page Application)
- Authentication: Laravel Sanctum (token-based, 2-hour expiration)
- CORS enabled for frontend-backend communication
- Rate limiting on sensitive endpoints (login, registration, rating)
- All API responses in JSON format
- Token stored in browser localStorage and sent in Authorization header

---

## HOW TO UNDERSTAND THE FLOW

### Creating a Project
1. User logs in → `LoginView.vue` → `AuthController.login()`
2. User joins/creates team → `HomeView.vue` or `TeamView.vue` → `TeamController.store/join()`
3. Scrum Master clicks "Add Project" → `AddProjectView.vue`
4. Form filled with project details and categorization (school type, year, subject)
5. Form submitted → `ProjectController.store()` validates categorization and creates project
6. Files uploaded to storage
7. Project saved to database with new categorization fields
8. Redirect to home → `HomeView.vue` shows new project with filters available

### Viewing a Project
1. User clicks project card → Router navigates to `/project/:id`
2. `ProjectView.vue` loads
3. Component calls `GET /api/projects/{id}` → `ProjectController.show()`
4. Backend returns project data with team, members, files, and categorization
5. Component displays data including categorization (school type, year, subject), video player, download links
6. View count incremented automatically
7. If user is Scrum Master of project's team, "Upraviť projekt" button is visible

### Editing a Project
1. Scrum Master clicks "Upraviť projekt" button on project detail page
2. Router navigates to `/edit-project/{id}` → `AddProjectView` in edit mode
3. Component detects edit mode from route parameter
4. `loadProjectForEdit()` calls `GET /api/projects/{id}` to load existing project
5. Form is pre-populated with all existing project data (title, description, files, metadata, etc.)
6. User makes changes (updates fields, uploads new files, modifies metadata)
7. Form submitted → `PUT /api/projects/{id}` → `ProjectController.update()`
8. Backend validates:
   - User is Scrum Master of project's team (403 if not)
   - All input data is valid
   - Project type changes are valid
9. Files handled:
   - New files uploaded → old files deleted, new files stored
   - Files not changed → existing files preserved
10. Project updated in database
11. User redirected back to project detail page with success message

### Rating a Project
1. User clicks star → `ProjectView.vue` calls `submitRating()`
2. API call `POST /api/projects/{id}/rate` → `ProjectController.rate()`
3. Backend checks if user already rated (rejects if yes)
4. Rating saved, average recalculated
5. Frontend updates display with new rating

---

## DATABASE STRUCTURE

### Main Tables
- `users` - User accounts
- `teams` - Student teams
- `team_user` - Links users to teams (with role)
- `projects` - All projects (games, apps, libraries)
  - Contains: `school_type` (enum: zs, ss, vs), `year_of_study` (integer, nullable), `subject` (string)
- `games` - LEGACY table (still exists but new code uses projects)
- `academic_years` - School years
- `game_ratings` - User ratings for projects/games
- `password_reset_tokens` - Tokens for password reset and temporary passwords

---

This documentation provides a complete map of the codebase. Each file has a clear purpose, and the flow between components is straightforward: Views handle UI, Controllers handle requests, Services handle business logic, and Models represent database data.
