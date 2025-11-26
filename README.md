## Game Registration Portal (Multi-Project Edition)

### Overview
This portal now supports registering multiple project types beyond games:
- game
- web_app
- mobile_app
- library
- other

Each project can include video (upload or YouTube), splash image, downloadable files, and rich metadata (live_url, github_url, npm_url, package_name, platform, tech_stack). Ratings are restricted to a single rating per authenticated user per project (enforced at DB and application layers).

### Key Endpoints (auth:sanctum)
`GET /api/projects` – list projects (filters: type, category, search, academic_year_id)
`POST /api/projects` – create project (Scrum Master only)
`GET /api/projects/{id}` – project detail
`POST /api/projects/{id}/views` – increment views
`POST /api/projects/{id}/rate` – rate once
`GET /api/projects/{id}/user-rating` – current user rating
`GET /api/projects/my?team_id={id}` – list projects for active team

Legacy game routes are commented out in `backend/routes/api.php` and legacy views (`GameView.vue`, `AddGameView.vue`) are marked deprecated.

### Setup
```powershell
cd backend
composer install
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```
In a second terminal:
```powershell
cd frontend
npm install
npm run dev
```

Set `VITE_API_URL` in frontend `.env` (or environment) to the backend URL (e.g. http://localhost:8000).

### Project Creation Rules
Scrum Master of a team may create projects. Team membership & role checked via pivot `team_user.role_in_team`.

### Rating Logic
- First POST to `/rate` creates rating.
- Subsequent attempt returns 422.
- Aggregates `rating` (rounded) and `rating_count` stored on project row.

### Metadata Parsing
`tech_stack` accepts comma or semicolon separated string; stored as array. Other optional fields copied verbatim if present.

### Testing
Feature tests added at `backend/tests/Feature/ProjectTest.php` covering:
- Scrum master creation success
- Non-scrum master forbidden
- Single rating enforcement
- Type filtering
- Metadata extraction (web_app)

Run tests:
```powershell
cd backend
php artisan test --filter=ProjectTest
```

### Frontend Filters
Home view includes type dropdown + team scoped button ("Moje Projekty"). Selecting a type triggers API query param `?type=`.

### Optional Follow-Up
- Remove deprecated game components entirely once confirmed.
- Add indexing (`projects.type`, `projects.team_id`) for performance.
- Extend tests for file uploads & view counting.

### Troubleshooting
- 403 on create: ensure user attached to team with role_in_team = scrum_master.
- 422 on second rating: expected (single rating rule).
- Empty tech stack display: ensure proper delimiters.

---
Multi-project functionality ready for manual and automated testing.
