/**
 * Returns true if a user is considered deactivated:
 * - Admin has set status to anything other than 'active', OR
 * - The user joined more than 5 years ago (likely no longer a student)
 */
export function isUserDeactivated(user) {
  if (!user) return false
  if (user.status && user.status !== 'active') return true
  if (user.created_at) {
    const fiveYearsAgo = new Date()
    fiveYearsAgo.setFullYear(fiveYearsAgo.getFullYear() - 5)
    return new Date(user.created_at) < fiveYearsAgo
  }
  return false
}
