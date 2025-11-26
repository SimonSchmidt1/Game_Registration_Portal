# Game Registration Portal - Frontend

Vue 3 frontend application for the Game Registration Portal project. Provides UI for authentication, team management, and game registration.

## Architecture

See [ARCHITECTURE.md](../ARCHITECTURE.md) in the root directory for comprehensive system architecture documentation.

## Quick Start

### Requirements

- Node.js 20.19+ (works with 20.18.0 but Vite recommends 20.19+)
- npm or yarn

### Installation

```bash
# Install dependencies
npm install

# Create .env file
cp .env.example .env

# Configure API endpoint in .env
# VITE_API_BASE_URL=http://localhost:8000
```

### Development

```bash
# Start development server with hot-reload
npm run dev
```

The application will be available at `http://localhost:5173`.

### Production Build

```bash
# Build for production
npm run build

# Preview production build locally
npm run preview
```

## Technology Stack

- **Vue 3** - Composition API with `<script setup>`
- **Vue Router** - Client-side routing with navigation guards
- **Vite** - Fast build tool and dev server
- **PrimeVue** - UI component library
- **Axios** - HTTP client with interceptors
- **Tailwind CSS** - Utility-first styling

## Project Structure

```
src/
├── assets/          # Static assets (images, styles)
├── components/      # Reusable Vue components
├── router/          # Vue Router configuration
├── views/           # Page-level components
├── App.vue          # Root component
└── main.js          # Application entry point
```

## Key Features

### Authentication

- User registration with email verification
- Login with automatic token management
- Password reset via email
- Temporary password login (after 5 failed attempts)
- Automatic logout on token expiration (2 hours)
- 5-minute inactivity timeout

### Security Features

- Axios 401 interceptor for automatic token invalidation
- Inactivity tracking with auto-logout
- Protected routes with auth guards
- Secure token storage in localStorage

### User Experience

- Responsive navigation with PrimeVue Menubar
- Toast notifications for user feedback
- Loading states and error handling
- Non-authenticated user experience (hidden UI, login prompts)
- Clickable team navigation across views
- Dynamic form switching (regular/temporary password)

## Configuration

### Environment Variables

Create a `.env` file:

```env
VITE_API_BASE_URL=http://localhost:8000
```

### Axios Configuration

Configured in `main.js`:

```javascript
axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
axios.defaults.withCredentials = true;

// Automatic token injection
axios.interceptors.request.use(config => {
  const token = localStorage.getItem('access_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Automatic logout on 401
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('access_token');
      router.push('/login');
    }
    return Promise.reject(error);
  }
);
```

## Routing

### Public Routes

- `/` - Home page (shows login prompt when not authenticated)
- `/login` - Login page (supports both regular and temporary passwords)
- `/register` - Registration page
- `/forgot-password` - Request password reset

### Protected Routes

Navigation guards check for valid token:

- `/project/:id` - Project detail view
- `/team/:id` - Team detail view
- `/game/:id` - Game detail view
- `/create-project` - Create new project

## Component Overview

### Core Components

- **App.vue** - Root component with inactivity tracking
- **Navbar.vue** - Navigation with auth-dependent visibility
- **HomeView.vue** - Project list with team filters (hidden when not logged in)
- **LoginView.vue** - Dynamic login form (regular + temporary password modes)
- **GameView.vue** - Game details with clickable team names
- **ProjectView.vue** - Project details with clickable team names
- **TeamView.vue** - Team details with member list

### Inactivity Timeout

Implemented in `App.vue`:

```javascript
const INACTIVITY_TIMEOUT = 5 * 60 * 1000; // 5 minutes

function setupInactivityTimer() {
  const events = ['mousemove', 'keypress', 'scroll', 'touchstart'];
  events.forEach(event => window.addEventListener(event, resetTimer));
}
```

## API Integration

### Authentication Flow

```javascript
// Login
const response = await axios.post('/api/login', credentials);
localStorage.setItem('access_token', response.data.token);

// Logout
await axios.post('/api/logout');
localStorage.removeItem('access_token');
router.push('/login');
```

### Error Handling

```javascript
try {
  const response = await axios.get('/api/teams');
  // Handle success
} catch (error) {
  if (error.response?.status === 401) {
    // Handled by interceptor (auto-logout)
  } else {
    toast.add({
      severity: 'error',
      summary: 'Chyba',
      detail: error.response?.data?.message || 'Nastala chyba',
      life: 3000
    });
  }
}
```

## Development Best Practices

### Recent Improvements

- FormRequest validation on backend for cleaner error handling
- Axios interceptors for automatic auth management
- Inactivity timeout for improved security
- Dynamic UI visibility based on authentication state
- Clickable navigation elements across all views

See [STABILITY.md](../STABILITY.md) for detailed improvement documentation.

## IDE Setup

### Recommended Extensions

- [Vue - Official](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (formerly Volar)
- [Tailwind CSS IntelliSense](https://marketplace.visualstudio.com/items?itemName=bradlc.vscode-tailwindcss)
- [ESLint](https://marketplace.visualstudio.com/items?itemName=dbaeumer.vscode-eslint)

### Browser DevTools

- **Chrome/Edge**: [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
- **Firefox**: [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)

Enable Custom Object Formatters for better console logging.

## Troubleshooting

**Issue: API requests failing with CORS errors**
- Verify backend CORS configuration includes `http://localhost:5173`
- Check `VITE_API_BASE_URL` in `.env`
- Ensure backend is running on configured port

**Issue: Automatic logout after inactivity not working**
- Check browser console for errors
- Verify App.vue has inactivity timer setup
- Confirm token exists in localStorage before timeout

**Issue: Can't login with temporary password**
- Ensure you've failed login 5 times first
- Check email for temporary password (XXXX-XXXX-XXXX format)
- Temporary password expires after 15 minutes

**Issue: 401 errors even with valid token**
- Token may be expired (2-hour backend limit)
- Clear localStorage and login again
- Check backend `config/sanctum.php` expiration setting

**Issue: Node version warning from Vite**
- Vite recommends Node.js 20.19+ but works with 20.18.0
- Update Node.js: `nvm install 20` or download from nodejs.org
- Warning is cosmetic and doesn't affect functionality

## Build Configuration

Customize Vite configuration in `vite.config.js`:

```javascript
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  }
})
```

See [Vite Configuration Reference](https://vite.dev/config/) for more options.
