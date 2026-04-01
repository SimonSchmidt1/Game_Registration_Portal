<template>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="auth-header">
        <h2 class="auth-title">
          {{ showTemporaryLogin ? t('auth.temp_login_title') : (isAdminEmail(email) ? t('auth.admin_login_title') : t('auth.login_title')) }}
        </h2>
        <p class="auth-subtitle">{{ t('auth.subtitle') }}</p>
      </div>

      <div v-if="showTemporaryLogin" class="auth-alert auth-alert-warn">
        <div>
          <strong>{{ t('auth.too_many_attempts_title') }}</strong>
          <p>{{ t('auth.too_many_attempts_desc') }}</p>        </div>
      </div>
      <form class="auth-form" @submit.prevent="showTemporaryLogin ? loginWithTemporaryPassword() : (isAdminEmail(email) ? adminLogin() : login())">
        <div class="auth-field">
          <label class="auth-label">{{ t('auth.email_label') }}</label>
          <InputText 
            v-model="email" 
            :type="isAdminEmail(email) ? 'text' : 'email'" 
            class="dlg-input" 
            placeholder="1234567@ucm.sk"
            :pattern="isAdminEmail(email) ? undefined : '[0-9]{7}@ucm\\.sk'"
            :title="isAdminEmail(email) ? 'Admin email - žiadne obmedzenia formátu' : 'Email musí byť v tvare: 7 číslic@ucm.sk (napr. 1234567@ucm.sk)'"
            required 
          />
          <span v-if="isAdminEmail(email)" class="auth-hint auth-hint-accent">{{ t('auth.admin_detected') }}</span>
        </div>

        <div class="auth-field">
          <label class="auth-label">{{ t('auth.password_label') }}</label>
          <InputText 
            v-if="!showTemporaryLogin"
            v-model="password" 
            type="password" 
            class="dlg-input" 
            required 
          />
          <InputText 
            v-else
            v-model="temporaryPassword" 
            type="text" 
            class="dlg-input" 
            placeholder="XXXX-XXXX-XXXX"
            required 
          />
          <span v-if="showTemporaryLogin" class="auth-hint">{{ t('auth.temp_format_hint') }}</span>
        </div>

        <Button type="submit" :label="t('auth.sign_in_btn')" class="steam-btn steam-btn-accent auth-btn-block" />

        <div v-if="!showTemporaryLogin" class="auth-links">
          <router-link to="/forgot-password" class="auth-link">
            {{ t('auth.forgot_password') }}
          </router-link>
          <div class="auth-text">
            {{ t('auth.no_account') }}
            <router-link to="/register" class="auth-link">{{ t('auth.register_link') }}</router-link>
          </div>
        </div>
      </form>
    </div>

    <Toast />
  </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════ */
/* AUTH SHELL (Steam-style)                                   */
/* ═══════════════════════════════════════════════════════════ */
.auth-shell {
  max-width: 520px;
  margin: 0 auto;
  padding: 84px 24px 120px;
}

.auth-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 28px 28px 32px;
  box-shadow: 0 14px 40px rgba(10, 18, 26, 0.35);
}

.auth-header {
  text-align: center;
  margin-bottom: 22px;
}

.auth-icon {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  margin: 0 auto 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(var(--color-accent-rgb), 0.12);
  color: var(--color-accent);
  font-size: 1.4rem;
}

.auth-title {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 6px;
}

.auth-subtitle {
  color: var(--color-text-muted);
  font-size: 0.9rem;
  margin: 0;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.auth-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.auth-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.auth-hint {
  font-size: 0.78rem;
  color: var(--color-text-subtle);
}

.auth-hint-accent {
  color: var(--color-accent);
}

.auth-links {
  display: flex;
  flex-direction: column;
  gap: 8px;
  text-align: center;
  margin-top: 4px;
}

.auth-text {
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

.auth-link {
  color: var(--color-accent);
  font-size: 0.85rem;
  text-decoration: none;
  transition: color 0.15s;
}

.auth-link:hover {
  color: var(--color-accent-hover);
  text-decoration: underline;
}

.auth-btn-block {
  width: 100%;
}

.auth-alert {
  display: flex;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 4px;
  margin-bottom: 16px;
  font-size: 0.85rem;
}

.auth-alert strong {
  display: block;
  margin-bottom: 4px;
}

.auth-alert p {
  margin: 0;
  opacity: 0.85;
}

.auth-alert-warn {
  background: rgba(var(--color-warning-rgb), 0.08);
  border: 1px solid rgba(var(--color-warning-rgb), 0.25);
  color: var(--color-warning);
}

/* ═══════════════════════════════════════════════════════════ */
/* BUTTONS (Steam-flat style)                                 */
/* ═══════════════════════════════════════════════════════════ */
.steam-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 3px;
  border: none;
  cursor: pointer;
  transition: background 0.12s, color 0.12s, opacity 0.12s;
  white-space: nowrap;
  line-height: 1.4;
}

.steam-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.steam-btn-accent {
  background: var(--color-accent);
  color: var(--color-accent-contrast);
}

.steam-btn-accent:hover:not(:disabled) {
  background: var(--color-accent-hover);
}

/* ═══════════════════════════════════════════════════════════ */
/* PRIMEVUE OVERRIDES                                         */
/* ═══════════════════════════════════════════════════════════ */
:deep(.dlg-input) {
  background: var(--color-elevated) !important;
  border: 1px solid var(--color-border) !important;
  border-radius: 3px !important;
  color: var(--color-text) !important;
  width: 100%;
}

:deep(.dlg-input::placeholder) {
  color: var(--color-text-subtle) !important;
}

:deep(.dlg-input:enabled:hover) {
  border-color: var(--color-border-strong) !important;
}

:deep(.dlg-input:enabled:focus) {
  border-color: var(--color-accent) !important;
  box-shadow: none !important;
}

@media (max-width: 640px) {
  .auth-shell {
    padding: 64px 16px 96px;
  }

  .auth-card {
    padding: 22px 20px 26px;
  }
}
</style>

<script setup>
import { ref, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Toast from 'primevue/toast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const toast = useToast()
const { t } = useI18n()

const email = ref('')
const password = ref('')
const temporaryPassword = ref('')
const showTemporaryLogin = ref(false)

// Track timeouts for cleanup
let redirectTimer = null

onUnmounted(() => {
  if (redirectTimer) {
    clearTimeout(redirectTimer)
  }
})

// Admin email from environment variable (no hardcoding secrets)
const ADMIN_EMAIL = import.meta.env.VITE_ADMIN_EMAIL || ''

// Check if email matches admin email from config
function isAdminEmail(emailValue) {
  if (!emailValue || !ADMIN_EMAIL) return false
  return emailValue.toLowerCase().trim() === ADMIN_EMAIL.toLowerCase().trim()
}

async function login() {
  try {
    // 1. Dôrazne požiadame Sanctum o inicializáciu CSRF cookie
    await axios.get(`${API_URL}/sanctum/csrf-cookie`, { withCredentials: true })

    const response = await axios.post(`${API_URL}/api/login`, {
      email: email.value,
      password: password.value
    })

    const token = response.data.access_token
    localStorage.setItem('access_token', token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

    // Set axios default header immediately
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

    // Fetch full user profile (including avatar) before redirect
    try {
      const userResponse = await axios.get(`${API_URL}/api/user`)
      localStorage.setItem('user', JSON.stringify(userResponse.data))
    } catch (err) {
      console.error('Failed to fetch user profile:', err)
    }

    // Poslanie eventu pre Navbar
    window.dispatchEvent(new Event('login'))

    // Toast upozornenie
    toast.add({ 
      severity: 'success', 
      summary: t('auth.login_title'), 
      detail: t('toast.login_success'), 
      life: 3000 
    })

    // Redirect after user data is loaded
    router.push('/')

  } catch (error) {
    const data = error.response?.data || {}
    const status = error.response?.status
    
    // Debug logging
    console.log('Login error:', { status, data, fullError: error })
    console.log('Response data keys:', Object.keys(data))
    console.log('verification_resent:', data.verification_resent)
    console.log('temporary_password_sent:', data.temporary_password_sent)

    // Email not verified - block login
    if (status === 403 && data.requires_verification) {
      toast.add({
        severity: 'warn',
        summary: t('auth.verification_required'),
        detail: data.message || 'Účet nie je overený. Skontrolujte e-mail a dokončite overenie.',
        life: 8000
      })
      redirectTimer = setTimeout(() => router.push('/verify-email'), 1600)
      return
    }

    // 5+ attempts - account blocked (but check if temporary password was sent)
    if (data.too_many_attempts && !data.verification_resent && !data.temporary_password_sent) {
      toast.add({
        severity: 'info',
        summary: t('auth.restore_account'),
        detail: data.message || 'Príliš veľa pokusov. Skontrolujte e‑mail a obnovte účet.',
        life: 8000
      })
      redirectTimer = setTimeout(() => router.push('/verify-email?resent=true'), 1600)
      return
    }

    // 5th attempt - temporary password sent (status 429)
    // Check for 429 status first, then check for the flags
    if (status === 429) {
      console.log('429 status received - checking for temporary password flags', data)
      
      // Check if this is the 5th attempt (temporary password sent)
      if (data.verification_resent || data.temporary_password_sent) {
        console.log('Temporary password sent - switching to temporary password form', data)
        toast.add({
          severity: 'info',
          summary: 'Dočasné heslo odoslané',
          detail: data.message || 'Poslali sme ti e-mail s dočasným heslom. Skontroluj si schránku (vrátane priečinka SPAM).',
          life: 7000
        })
        showTemporaryLogin.value = true
        toast.add({
          severity: 'info',
          summary: 'Použi dočasné heslo',
          detail: 'Zadaj dočasné heslo z e-mailu v poli nižšie (formát: XXXX-XXXX-XXXX).',
          life: 5000
        })
        return
      }
      
      // If 429 but not temporary password, it might be too_many_attempts
      if (data.too_many_attempts) {
        toast.add({
          severity: 'info',
          summary: t('auth.restore_account'),
          detail: data.message || 'Príliš veľa pokusov. Skontrolujte e‑mail a obnovte účet.',
          life: 8000
        })
        redirectTimer = setTimeout(() => router.push('/verify-email?resent=true'), 1600)
        return
      }
      
      // Generic 429 handling
      console.warn('429 status but no recognized flags', data)
      toast.add({
        severity: 'warn',
        summary: 'Príliš veľa pokusov',
        detail: data.message || 'Príliš veľa neúspešných pokusov. Skúste to znova neskôr.',
        life: 5000
      })
      return
    }

    // Attempts 1-4 wrong password
    if (status === 401 && (data.remaining_attempts !== undefined || data.failed_attempts !== undefined || /Zostávajúce pokusy\s*:\s*\d+/.test(data.message || ''))) {
      let remaining = data.remaining_attempts
      if (remaining === undefined && data.failed_attempts !== undefined) {
        const max = data.max_attempts || 5
        remaining = max - data.failed_attempts
      }
      if (remaining === undefined) {
        const match = (data.message || '').match(/Zostávajúce pokusy\s*:\s*(\d+)/)
        remaining = match ? parseInt(match[1], 10) : undefined
      }
      toast.add({
        severity: 'warn',
        summary: 'Nesprávne heslo',
        detail: data.message || (remaining !== undefined ? `Zostávajúce pokusy: ${remaining}` : 'Nesprávne heslo'),
        life: 4000
      })
      return
    }

    // Generic fallback
    toast.add({
      severity: 'error',
      summary: 'Chyba',
      detail: status === 401 ? 'Nesprávny email alebo heslo ❌' : 'Chyba pri prihlásení ❌',
      life: 3000
    })
  }
}

async function loginWithTemporaryPassword() {
  try {
    const response = await axios.post(`${API_URL}/api/login-temporary`, {
      email: email.value,
      temporary_password: temporaryPassword.value,
    })

    const token = response.data.access_token
    localStorage.setItem('access_token', token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

    // Set axios default header immediately
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

    // Fetch full user profile (including avatar) before redirect
    try {
      const userResponse = await axios.get(`${API_URL}/api/user`)
      localStorage.setItem('user', JSON.stringify(userResponse.data))
    } catch (err) {
      console.error('Failed to fetch user profile:', err)
    }

    window.dispatchEvent(new Event('login'))

    toast.add({
      severity: 'success',
      summary: 'Prihlásenie úspešné',
      detail: response.data.message || 'Vitaj späť!',
      life: 4000,
    })

    if (response.data.should_change_password) {
      toast.add({
        severity: 'warn',
        summary: 'Odporúčame',
        detail: 'Zmeň si heslo v nastaveniach profilu',
        life: 6000,
      })
    }

    router.push('/')
  } catch (error) {
    const data = error.response?.data || {}
    
    toast.add({
      severity: 'error',
      summary: 'Chyba',
      detail: data.message || 'Neplatné dočasné heslo alebo email',
      life: 4000,
    })
  }
}

async function adminLogin() {
  try {
    // 1. Dôrazne požiadame Sanctum o inicializáciu CSRF cookie pre administrátorské prihlásenie
    await axios.get(`${API_URL}/sanctum/csrf-cookie`, { withCredentials: true })

    const response = await axios.post(`${API_URL}/api/admin/login`, {
      email: email.value,
      password: password.value
    })

    const token = response.data.access_token
    localStorage.setItem('access_token', token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

    // Set axios default header immediately
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

    // Fetch full user profile (including avatar) before redirect
    try {
      const userResponse = await axios.get(`${API_URL}/api/user`)
      localStorage.setItem('user', JSON.stringify(userResponse.data))
    } catch (err) {
      console.error('Failed to fetch user profile:', err)
    }

    // Poslanie eventu pre Navbar
    window.dispatchEvent(new Event('login'))

    // Toast upozornenie
    toast.add({ 
      severity: 'success', 
      summary: t('auth.admin_login_title'), 
      detail: t('toast.login_success'), 
      life: 3000 
    })

    // Redirect after user data is loaded
    router.push('/')
  } catch (error) {
    const data = error.response?.data || {}
    const status = error.response?.status
    
    // Handle rate limiting (429)
    if (status === 429) {
      const retryAfter = data.retry_after || 60
      toast.add({
        severity: 'warn',
        summary: 'Príliš veľa pokusov',
        detail: data.message || `Príliš veľa neúspešných pokusov. Skúste znova o ${retryAfter} sekúnd.`,
        life: 6000
      })
      return
    }
    
    toast.add({
      severity: 'error',
      summary: 'Chyba',
      detail: data.message || 'Neplatné admin prihlasovacie údaje',
      life: 4000
    })
  }
}
</script>
