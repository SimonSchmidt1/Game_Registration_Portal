# Game Registration Portal - Backend

Laravel 11 backend API for the Game Registration Portal project. Provides authentication, team management, and game registration functionality.

## Architecture

See [ARCHITECTURE.md](../ARCHITECTURE.md) in the root directory for comprehensive system architecture documentation.

## Quick Start

### Requirements

- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Node.js (for asset compilation, optional)

### Installation

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env file
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=game_portal
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# (Optional) Seed database
php artisan db:seed

# Start development server
php artisan serve
```

The API will be available at `http://localhost:8000`.

## Configuration

### Sanctum Authentication

Token expiration is set to 2 hours in `config/sanctum.php`:

```php
'expiration' => 120,
```

### CORS Configuration

Configure allowed origins in `.env`:

```env
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173
```

### Email Configuration

Set up email for password recovery:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## Key Features

### Authentication System

- User registration with email verification
- Login with Sanctum token-based authentication
- Password reset via email token
- Temporary password system (triggered after 5 failed login attempts)
- Automatic token expiration (2 hours)
- Scheduled token pruning (runs hourly)

### API Patterns

- **FormRequest Validation**: Separate validation classes for clean controller logic
- **API Resources**: Standardized JSON response formatting (GameResource, TeamResource)
- **ApiResponse Trait**: Consistent response structure across endpoints
- **Middleware**: Sanctum auth, CORS, throttling

### Database Schema

Core models:
- `User` - User accounts with role-based access (učiteľ, žiak)
- `Team` - Student teams with members
- `Game` - Game projects with ratings
- `AcademicYear` - Academic year tracking

See [ARCHITECTURE.md](../ARCHITECTURE.md) for detailed schema documentation.

## API Endpoints

### Authentication

```
POST   /api/register                  - Register new user
POST   /api/login                     - Login and get token
POST   /api/logout                    - Logout and revoke token
GET    /api/user                      - Get authenticated user
POST   /api/forgot-password           - Request password reset
POST   /api/reset-password            - Reset password with token
POST   /api/login-temporary           - Login with temporary password
```

### User Management

```
POST   /api/user/update-avatar        - Upload avatar
PUT    /api/user/update-profile       - Update profile
PUT    /api/user/update-password      - Change password
GET    /api/email/verify/{id}/{hash}  - Verify email
```

### Teams, Games, Academic Years

See `routes/api.php` for complete endpoint listing.

## Scheduled Tasks

The application uses Laravel's task scheduler for maintenance:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('tokens:prune')->hourly();
}
```

To enable the scheduler, add this cron entry:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Testing

```bash
# Run PHPUnit tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php
```

## Artisan Commands

```bash
# Prune expired password reset tokens
php artisan tokens:prune

# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear
```

## Development Notes

### Code Quality Improvements

Recent stability enhancements:
- FormRequest classes for validation separation
- API Resources for response standardization
- ApiResponse helper trait for consistency
- Automated token pruning
- CORS environment configuration
- Enhanced security with token expiration

See [STABILITY.md](../STABILITY.md) for detailed improvement documentation.

## Troubleshooting

**Issue: Token not working**
- Check `config/sanctum.php` expiration setting
- Verify CORS configuration in `.env`
- Clear config cache: `php artisan config:clear`

**Issue: Email not sending**
- Check MAIL_* configuration in `.env`
- Use Mailpit for local development testing
- Verify queue worker is running if using queued notifications

**Issue: 401 Unauthorized**
- Token may be expired (2-hour limit)
- Check Authorization header format: `Bearer {token}`
- Verify token exists in `personal_access_tokens` table

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
