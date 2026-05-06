/**
 * Drop-in replacement for fetch() that handles 401 globally.
 * When a 401 is received (expired/invalid token), clears auth state
 * and redirects to /login — mirrors the existing axios interceptor in main.js.
 */
export async function apiFetch(url, options = {}) {
  const response = await fetch(url, { credentials: 'include', ...options })

  if (response.status === 401) {
    const hasToken = !!localStorage.getItem('access_token')
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')

    if (hasToken) {
      // Notify Navbar to update its login state
      window.dispatchEvent(new Event('logout'))

      // Redirect to login if not already there
      if (!window.location.pathname.startsWith('/login')) {
        window.location.href = '/login'
      }
    }
  }

  return response
}
