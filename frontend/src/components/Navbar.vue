<template>
  <nav ref="navRef" class="nav-bar">
    <div class="nav-inner">
      <!-- Left: Navigation links -->
      <div class="nav-left">
        <RouterLink to="/" class="nav-link">{{ t('nav.home') }}</RouterLink>
        <RouterLink v-if="canAddGame && !isAdmin" to="/add-project" class="nav-link">{{ t('nav.add_project') }}</RouterLink>
        <RouterLink v-if="isAdmin" to="/admin" class="nav-link nav-link-admin">{{ t('nav.admin_panel') }}</RouterLink>
      </div>

      <!-- Center: Logo -->
      <RouterLink to="/" class="nav-brand">
        <img src="@/assets/logo/logo_fpv.png" alt="FPV UCM" class="nav-logo" />
      </RouterLink>

      <!-- Right: Auth / Profile -->
      <div class="nav-right">
        <!-- Language Switcher -->
        <div class="nav-lang-switcher" v-click-outside="() => showLangMenu = false">
          <button class="nav-btn-ghost nav-lang-btn" @click="showLangMenu = !showLangMenu" :title="currentLocaleInfo.label">
            <span class="nav-lang-flag">{{ currentLocaleInfo.flag }}</span>
            <span class="nav-lang-code">{{ currentLocaleInfo.code.toUpperCase() }}</span>
          </button>
          <div v-if="showLangMenu" class="nav-lang-menu">
            <button
              v-for="loc in SUPPORTED_LOCALES"
              :key="loc.code"
              class="nav-lang-option"
              :class="{ active: loc.code === locale }"
              @click="switchLocale(loc.code)"
            >
              <span>{{ loc.flag }}</span>
              <span>{{ loc.label }}</span>
            </button>
          </div>
        </div>

        <button class="nav-btn-ghost nav-theme-toggle" @click="toggleTheme">
          <span class="nav-theme-label">{{ isLightTheme ? t('nav.dark_mode') : t('nav.light_mode') }}</span>
        </button>
        <template v-if="!isLoggedIn">
          <RouterLink to="/register">
            <button class="nav-btn-ghost">{{ t('nav.register') }}</button>
          </RouterLink>
          <RouterLink to="/login">
            <button class="nav-btn-accent">{{ t('nav.login') }}</button>
          </RouterLink>
        </template>

        <template v-else>
          <button @click="showUserProfileDialog = true" class="nav-user-btn">
            <img
              v-if="currentUser?.avatar_path"
              :key="currentUser.avatar_path"
              :src="getAvatarUrl(currentUser.avatar_path)"
              alt="Avatar"
              class="nav-avatar"
            />
            <div v-else class="nav-avatar-placeholder" :style="{ background: avatarColor }">{{ userName?.charAt(0).toUpperCase() }}</div>
            <span class="nav-user-name">{{ userName }}</span>
          </button>
          <button @click="logout" class="nav-btn-ghost nav-btn-logout">
            <span>{{ t('nav.logout') }}</span>
          </button>
        </template>
      </div>
    </div>
  </nav>

  <Toast ref="toast" />

  <!-- USER PROFILE DIALOG -->
  <Dialog 
    v-model:visible="showUserProfileDialog" 
    :modal="true" 
    :closable="false" 
    :draggable="false"
    class="w-11/12 md:w-[450px]"
    :contentStyle="{ backgroundColor: 'var(--color-surface)', color: 'var(--color-text)', padding: '0', border: '1px solid var(--color-border)', borderRadius: '16px' }"
    :showHeader="false"
    :style="{ borderRadius: '16px', overflow: 'hidden' }"
  >
    <div v-if="currentUser" class="relative flex flex-col pt-10">
      <button 
        class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-black/5 hover:bg-black/10 dark:bg-white/10 dark:hover:bg-white/20 text-gray-800 dark:text-white transition-colors z-10" 
        @click="showUserProfileDialog = false"
      >
        <span class="text-lg font-bold">✕</span>
      </button>

      <!-- Content Area -->
      <div class="px-8 pb-8 relative flex flex-col items-center">
        <!-- Avatar Section -->
        <div class="relative mb-4 group cursor-pointer shadow-lg rounded-full">
          <img 
            v-if="currentUser.avatar_path" 
            :key="currentUser.avatar_path"
            :src="getAvatarUrl(currentUser.avatar_path)" 
            alt="Avatar"
            class="w-32 h-32 rounded-full object-cover"
            style="background-color: var(--color-surface);"
          />
          <div 
            v-else 
            class="w-32 h-32 rounded-full flex items-center justify-center text-white font-bold text-5xl transition-transform duration-300 group-hover:scale-105"
            :style="{ background: avatarColor }"
          >
            {{ currentUser.name?.charAt(0).toUpperCase() }}
          </div>
          
          <!-- Upload overlay -->
          <label class="absolute inset-x-0 bottom-0 top-0 m-auto w-32 h-32 flex items-center justify-center bg-black/60 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer backdrop-blur-sm">
            <div class="flex flex-col items-center gap-1 text-white">
              <span class="text-xs font-medium tracking-wide">{{ t('profile.change_btn') }}</span>
            </div>
            <input 
              type="file" 
              accept="image/*" 
              class="hidden" 
              @change="handleAvatarUpload"
            />
          </label>
        </div>
        
        <h2 class="text-2xl font-bold mb-1 tracking-tight" style="color: var(--color-text-strong)">
          <span v-if="!editMode">{{ currentUser.name }}</span>
          <input 
            v-else 
            v-model="editName" 
            type="text" 
            class="text-center bg-transparent border-b-2 focus:outline-none w-full pb-1 transition-colors"
            style="color: var(--color-text-strong); border-bottom-color: rgb(91, 189, 138);"
            placeholder="Vaše meno"
          />
        </h2>
        <p class="text-sm font-medium mb-6" style="color: var(--color-text-muted)">
          {{ currentUser.email }}
        </p>

        <!-- User Role & Type Badges -->
        <div class="flex gap-3 w-full justify-center mb-8">
          <div class="px-4 py-2 rounded-xl border flex flex-col items-center flex-1" style="background: var(--color-elevated); border-color: var(--color-border)">
            <span class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-subtle)">Rola</span>
            <span class="text-sm font-bold capitalize" style="color: var(--color-text)">{{ currentUser.role }}</span>
          </div>
          <div class="px-4 py-2 rounded-xl border flex flex-col items-center flex-1" style="background: var(--color-elevated); border-color: var(--color-border)">
            <span class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-subtle)">Štúdium</span>
            <span class="text-sm font-bold capitalize" style="color: var(--color-text)">{{ getStudentTypeLabel(currentUser.student_type) }}</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col gap-3 w-full">
          <template v-if="!editMode">
            <button 
              class="w-full py-3 rounded-xl font-bold transition-all duration-200 flex items-center justify-center gap-2"
              style="background: var(--color-accent); color: var(--color-accent-contrast);"
              @click="startEdit" 
            >
              Zmeniť meno
            </button>
            <button 
              class="w-full py-3 rounded-xl font-semibold transition-all duration-200 hover:bg-black/5 flex items-center justify-center gap-2 border"
              style="border-color: var(--color-border); color: var(--color-text);"
              @click="openPasswordDialog" 
            >
              <i class="pi pi-lock"></i> {{ t('profile.change_password_btn') }}
            </button>
          </template>
          
          <template v-else>
            <button 
              class="w-full py-3 rounded-xl font-bold transition-all duration-200 flex items-center justify-center gap-2"
              style="background: var(--color-accent); color: var(--color-accent-contrast);"
              @click="saveProfile" 
            >
              {{ t('profile.save_btn') }}
            </button>
            <button 
              class="w-full py-3 rounded-xl font-semibold transition-all duration-200 hover:bg-black/5 flex items-center justify-center gap-2 border"
              style="border-color: var(--color-border); color: var(--color-text-muted);"
              @click="cancelEdit" 
            >
              {{ t('profile.cancel_btn') }}
            </button>
          </template>
        </div>
      </div>
    </div>
  </Dialog>

  <!-- Password Change Dialog -->
  <Dialog 
    v-model:visible="showPasswordDialog" 
    :modal="true"
    :closable="false"
    :draggable="false"
    class="w-11/12 md:w-[420px]"
    :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem', border: 'none' }" 
    :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)', padding: '1rem 1.5rem', position: 'relative' }"
    :style="{ borderRadius: '4px', overflow: 'hidden' }"
  >
    <template #header>
      <div class="dialog-header-custom">
        <span class="dialog-title-centered">{{ t('profile.change_password_title') }}</span>
        <button class="dialog-close-btn" @click="showPasswordDialog = false">
          ×
        </button>
      </div>
    </template>
    
    <div class="flex flex-col gap-4">
      <div>
        <label class="block mb-2 font-medium text-slate-300 text-sm">{{ t('profile.current_password_label') }}</label>
        <div class="relative">
          <input 
            v-model="currentPassword" 
            type="password" 
            class="password-input" 
            placeholder="Zadaj aktuálne heslo"
          />
        </div>
      </div>
      <div>
        <label class="block mb-2 font-medium text-slate-300 text-sm">{{ t('profile.new_password_label') }}</label>
        <div class="relative">
          <input 
            v-model="newPassword" 
            type="password" 
            class="password-input" 
            placeholder="Aspoň 8 znakov"
          />
        </div>
      </div>
      <div>
        <label class="block mb-2 font-medium text-slate-300 text-sm">{{ t('profile.confirm_password_label') }}</label>
        <div class="relative">
          <input 
            v-model="newPasswordConfirm" 
            type="password" 
            class="password-input" 
            placeholder="Zadaj heslo znova"
          />
        </div>
      </div>
      <div class="flex gap-2 mt-2">
        <button 
          class="btn-ghost-dialog flex-1"
          @click="showPasswordDialog = false" 
        >
          {{ t('profile.cancel_btn') }}
        </button>
        <button 
          class="btn-primary-dialog flex-1"
          @click="savePassword" 
        >
          {{ t('profile.save_password_btn') }}
        </button>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { RouterLink, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import axios from 'axios'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { SUPPORTED_LOCALES, setLocale } from '@/i18n'

const { t, locale } = useI18n()
const showLangMenu = ref(false)
const currentLocaleInfo = computed(() => SUPPORTED_LOCALES.find(l => l.code === locale.value) || SUPPORTED_LOCALES[0])
function switchLocale(code) {
  setLocale(code)
  showLangMenu.value = false
}

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const THEME_KEY = 'theme_preference'

// Navbar scroll-up logo shine
const navRef = ref(null)
let lastScrollY = 0
let glowTimer = null
let shineTimer = null
let lastShineAt = 0
function handleScroll() {
  const sy = window.scrollY
  const now = Date.now()
  if (sy < lastScrollY - 6 && now - lastShineAt > 900) {
    lastShineAt = now
    navRef.value?.classList.add('nav-glow')
    navRef.value?.classList.add('nav-shine')
    clearTimeout(glowTimer)
    glowTimer = setTimeout(() => {
      navRef.value?.classList.remove('nav-glow')
    }, 500)
    clearTimeout(shineTimer)
    shineTimer = setTimeout(() => {
      navRef.value?.classList.remove('nav-shine')
    }, 700)
  }
  lastScrollY = sy
}
const isLoggedIn = ref(!!localStorage.getItem('access_token'))
const canAddGame = ref(false) // Derived from active team scrum master status (now for projects)
const isAdmin = ref(false) // Whether current user is admin
const userName = ref('')
const currentUser = ref(null)
const showUserProfileDialog = ref(false)
const showPasswordDialog = ref(false)
const toast = ref(null)
const toastService = useToast()
const editMode = ref(false)
const editName = ref('')
const editEmail = ref('')
const currentPassword = ref('')
const newPassword = ref('')
const newPasswordConfirm = ref('')
function generateRandomColor() {
  const h = Math.floor(Math.random() * 360)
  return `hsl(${h}, 65%, 45%)`
}
const avatarColor = ref(generateRandomColor())
const themePreference = ref('dark')
const isLightTheme = computed(() => themePreference.value === 'light')

function applyTheme(theme) {
  const resolved = theme === 'light' ? 'light' : 'dark'
  themePreference.value = resolved
  document.documentElement.setAttribute('data-theme', resolved)
  localStorage.setItem(THEME_KEY, resolved)
}

function toggleTheme() {
  applyTheme(isLightTheme.value ? 'dark' : 'light')
}

async function loadCurrentUser() {
  const token = localStorage.getItem('access_token')
  if (!token) return
  
  try {
    const response = await axios.get(`${API_URL}/api/user`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    currentUser.value = response.data
    userName.value = response.data.name
  } catch (err) {
    console.error('Error loading current user:', err)
  }
}

function getAvatarUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  // Add cache-busting parameter to force browser to reload image
  return `${API_URL}/storage/${path}?t=${Date.now()}`
}

async function handleAvatarUpload(event) {
  const file = event.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('avatar', file)

  try {
    const token = localStorage.getItem('access_token')
    const res = await fetch(`${API_URL}/api/user/avatar`, {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token },
      body: formData
    })

    const data = await res.json()
    
    console.log('Avatar upload response:', data)

    if (res.ok) {
      currentUser.value = data.user
      userName.value = data.user.name
      console.log('Avatar updated successfully:', data.user.avatar_path)
      toastService.add({ severity: 'success', summary: t('profile.updated'), detail: t('profile.updated_desc'), life: 3000 })
      
      // Reset input to allow re-selecting the same file
      event.target.value = ''
    } else {
      console.error('Avatar upload failed:', data)
      toastService.add({ severity: 'error', summary: t('toast.error'), detail: data.message || t('profile.update_error_desc'), life: 3000 })
    }
  } catch (err) {
    console.error('Error uploading avatar:', err)
    toastService.add({ severity: 'error', summary: t('toast.error'), detail: t('profile.update_error_desc'), life: 3000 })
  }
}

function openPasswordDialog() {
  currentPassword.value = ''
  newPassword.value = ''
  newPasswordConfirm.value = ''
  showPasswordDialog.value = true
  showUserProfileDialog.value = false
}

async function savePassword() {
  if (newPassword.value !== newPasswordConfirm.value) {
    toastService.add({ 
      severity: 'error', 
      summary: t('toast.error'), 
      detail: t('auth.passwords_mismatch'), 
      life: 3000 
    })
    return
  }

  if (newPassword.value.length < 8) {
    toastService.add({ 
      severity: 'error', 
      summary: t('toast.error'), 
      detail: t('auth.password_too_short'), 
      life: 3000 
    })
    return
  }

  const token = localStorage.getItem('access_token')
  try {
    const res = await fetch(`${API_URL}/api/user/password`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        current_password: currentPassword.value,
        new_password: newPassword.value,
        new_password_confirmation: newPasswordConfirm.value,
      }),
    })

    const data = await res.json()

    if (res.ok) {
      toastService.add({ 
        severity: 'success', 
        summary: t('profile.password_changed'), 
        detail: data.message || t('profile.password_changed_desc'),
        life: 4000 
      })
      showPasswordDialog.value = false
    } else {
      toastService.add({ 
        severity: 'error', 
        summary: t('toast.error'), 
        detail: data.message || t('profile.password_error_desc'), 
        life: 4000 
      })
    }
  } catch (err) {
    toastService.add({ 
      severity: 'error', 
      summary: t('common.network_error'), 
      detail: t('common.try_again'), 
      life: 4000 
    })
  }
}

async function logout() {
  try {
    await axios.post(`${API_URL}/api/logout`, {}, {
      headers: { Authorization: `Bearer ${localStorage.getItem('access_token')}` }
    })
  } catch (error) {
    // Even if API call fails, still logout locally
    console.log('Logout API error (continuing with local logout):', error)
  } finally {
    // Always clear local storage and logout
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')
    isLoggedIn.value = false
    isAdmin.value = false
    currentUser.value = null

    toast.value.add({
      severity: 'success',
      summary: t('toast.success'),
      detail: t('toast.logout_success'),
      life: 3000
    })

    router.push('/login')
  }
}

// Event handler references for proper cleanup
const handleLoginEvent = async () => {
  isLoggedIn.value = true
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  userName.value = user.name || ''
  isAdmin.value = user.role === 'admin'
  // Admins should not see "Pridať projekt" button
  if (isAdmin.value) {
    canAddGame.value = false
  }
  await loadCurrentUser()
}

const handleTeamChangedEvent = (e) => {
  refreshActiveTeamStatus(e.detail)
}

onMounted(async () => {
  const storedTheme = localStorage.getItem(THEME_KEY)
  applyTheme(storedTheme === 'light' ? 'light' : 'dark')

  const token = localStorage.getItem('access_token')
  if (token) {
    try {
      const response = await axios.get(`${API_URL}/api/user`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      isLoggedIn.value = true
      currentUser.value = response.data
      userName.value = response.data.name
      // Check admin status
      isAdmin.value = response.data.role === 'admin'
      // Admins should not see "Pridať projekt" button
      if (isAdmin.value) {
        canAddGame.value = false
      }
    } catch {
      localStorage.removeItem('access_token')
      localStorage.removeItem('user')
      isLoggedIn.value = false
      isAdmin.value = false
      if (router.currentRoute.value.meta.requiresAuth) {
        router.push('/login')
      }
    }
  }

  // Add event listeners
  window.addEventListener('login', handleLoginEvent)
  window.addEventListener('team-changed', handleTeamChangedEvent)
  window.addEventListener('scroll', handleScroll, { passive: true })

  // Initialize scrum master flag from persisted active team
  refreshActiveTeamStatus()
})

// Cleanup event listeners on unmount to prevent memory leaks
onUnmounted(() => {
  window.removeEventListener('login', handleLoginEvent)
  window.removeEventListener('team-changed', handleTeamChangedEvent)
  window.removeEventListener('scroll', handleScroll)
})

function refreshActiveTeamStatus(detail) {
  const token = localStorage.getItem('access_token')
  const stored = localStorage.getItem('active_team_is_scrum_master')
  const storedStatus = localStorage.getItem('active_team_status')
  
  // Must be logged in to add games
  if (!token) {
    canAddGame.value = false
    return
  }
  
  // Admins should not see "Pridať projekt" button
  if (isAdmin.value) {
    canAddGame.value = false
    return
  }
  
  // Prefer event detail if available
  if (detail && typeof detail.isScrumMaster === 'boolean') {
    // Only allow adding projects if scrum master AND team is active
    const isActive = detail.status === 'active' || !detail.status
    canAddGame.value = detail.isScrumMaster && isActive
  } else if (stored !== null) {
    // Only allow adding projects if scrum master AND team is active
    const isActive = storedStatus === 'active' || !storedStatus
    canAddGame.value = stored === '1' && isActive
  } else {
    canAddGame.value = false
  }
}

function startEdit() {
  editName.value = currentUser.value.name
  editMode.value = true
}

function cancelEdit() {
  editMode.value = false
  editName.value = ''
}

function getStudentTypeLabel(type) {
  if (!type) return 'Neuvedené'
  const map = {
    'denny': 'Denný študent',
    'externy': 'Externý študent'
  }
  return map[type] || type
}

async function saveProfile() {
  const token = localStorage.getItem('access_token')
  if (!token) return

  try {
    const res = await fetch(`${API_URL}/api/user`, {
      method: 'PUT',
      headers: { 
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        name: editName.value
      })
    })

    const data = await res.json()

    if (res.ok) {
      currentUser.value = data.user
      userName.value = data.user.name
      editMode.value = false
      toastService.add({ severity: 'success', summary: t('profile.updated'), detail: t('profile.updated_desc'), life: 3000 })
    } else {
      toastService.add({ severity: 'error', summary: t('toast.error'), detail: data.message || t('profile.update_error_desc'), life: 3000 })
    }
  } catch (err) {
    console.error('Error updating profile:', err)
    toastService.add({ severity: 'error', summary: t('toast.error'), detail: t('profile.update_error_desc'), life: 3000 })
  }
}
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════
   SINGLE UNIFIED NAVBAR
   ═══════════════════════════════════════════════════════════ */
.nav-bar {
  background: var(--color-nav-bg);
  border-bottom: 2px solid var(--color-border);
  position: relative;
  z-index: 100;
  overflow: visible;
}
.nav-bar::after {
  content: '';
  position: absolute;
  left: 0; right: 0; bottom: -2px;
  height: 2px;
  background: linear-gradient(90deg, transparent 0%, var(--color-accent) 50%, transparent 100%);
  opacity: 0;
  transition: opacity 0.4s ease;
}
.nav-bar.nav-glow::after {
  opacity: 0.7;
}
.nav-bar.nav-glow .nav-logo {
  filter: drop-shadow(0 4px 18px rgba(0,0,0,0.5)) drop-shadow(0 2px 10px rgba(var(--color-accent-rgb), 0.3));
}
.nav-bar.nav-shine .nav-logo {
  animation: nav-logo-shine 0.7s ease-out;
}
.nav-bar.nav-shine::after {
  opacity: 0.95;
}
.nav-bar.nav-shine .nav-logo,
.nav-bar.nav-glow .nav-logo {
  will-change: filter;
}
@keyframes nav-logo-shine {
  0% {
    filter: drop-shadow(0 4px 14px rgba(0,0,0,0.45)) drop-shadow(0 1px 4px rgba(var(--color-accent-rgb), 0.15));
  }
  45% {
    filter: drop-shadow(0 8px 26px rgba(0,0,0,0.55)) drop-shadow(0 0 14px rgba(var(--color-accent-rgb), 0.55));
  }
  100% {
    filter: drop-shadow(0 4px 14px rgba(0,0,0,0.45)) drop-shadow(0 1px 4px rgba(var(--color-accent-rgb), 0.15));
  }
}
.nav-inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 32px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  position: relative;
}

/* ── Left: Links ── */
.nav-left {
  display: flex;
  align-items: center;
  gap: 2px;
  flex: 1;
}

/* ── Center: Logo ── */
.nav-brand {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  text-decoration: none;
  z-index: 2;
}
.nav-logo {
  height: 72px;
  width: auto;
  object-fit: contain;
  position: relative;
  top: 10px;
  filter: drop-shadow(0 4px 14px rgba(0,0,0,0.45)) drop-shadow(0 1px 4px rgba(var(--color-accent-rgb), 0.15));
  transition: filter 0.2s;
}
.nav-brand:hover .nav-logo {
  filter: drop-shadow(0 6px 20px rgba(0,0,0,0.55)) drop-shadow(0 2px 8px rgba(var(--color-accent-rgb), 0.25));
}
.nav-brand-text-wrap {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}
.nav-brand-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text);
  white-space: nowrap;
  letter-spacing: -0.01em;
}
.nav-brand-accent { color: var(--color-accent); }
.nav-brand-sub {
  font-size: 0.65rem;
  color: var(--color-text-subtle);
  white-space: nowrap;
  letter-spacing: 0.01em;
}
.nav-brand:hover .nav-brand-title { color: var(--color-text); }


.nav-link {
  padding: 8px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-decoration: none;
  border-radius: 3px;
  transition: color 0.12s, background 0.12s;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  white-space: nowrap;
}
.nav-link:hover { color: var(--color-text); background: var(--color-hover-bg); }
.nav-link.router-link-active { color: var(--color-accent); }
.nav-link-admin { color: var(--color-danger); }
.nav-link-admin:hover { color: var(--color-danger-hover); background: rgba(var(--color-danger-rgb), 0.1); }

/* ── Right: Auth ── */
.nav-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  justify-content: flex-end;
}

.nav-btn-ghost {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 12px; font-size: 0.82rem; font-weight: 600;
  color: var(--color-text-muted); background: transparent; border: none;
  border-radius: 3px; cursor: pointer; transition: color 0.12s, background 0.12s;
}
.nav-btn-ghost:hover { color: var(--color-text); background: var(--color-hover-bg-soft); }
.nav-btn-logout:hover { color: var(--color-danger); background: rgba(var(--color-danger-rgb), 0.1); }

.nav-btn-accent {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 14px; font-size: 0.82rem; font-weight: 600;
  color: var(--color-accent-contrast); background: var(--color-accent); border: none;
  border-radius: 3px; cursor: pointer; transition: background 0.12s;
}
.nav-btn-accent:hover { background: var(--color-accent-hover); }

/* User button */
.nav-user-btn {
  display: flex; align-items: center; gap: 8px;
  padding: 4px 10px 4px 4px; background: var(--color-user-btn-bg);
  border: 1px solid var(--color-border); border-radius: 3px;
  cursor: pointer; transition: background 0.12s, border-color 0.12s;
}
.nav-user-btn:hover { background: var(--color-user-btn-bg-hover); border-color: var(--color-accent); }

.nav-avatar { width: 28px; height: 28px; border-radius: 3px; object-fit: cover; }
.nav-avatar-placeholder {
  width: 28px; height: 28px; border-radius: 3px;
  display: flex; align-items: center; justify-content: center;
  line-height: 1;
  color: #fff; font-weight: 700; font-size: 0.8rem;
}
.nav-user-name {
  font-size: 0.82rem; font-weight: 600; color: var(--color-text);
  display: none;
}
@media (min-width: 640px) { .nav-user-name { display: inline; } }

.nav-theme-toggle {
  min-width: 120px;
  justify-content: center;
}
.nav-theme-label {
  letter-spacing: 0.01em;
}

/* ═══ Language Switcher ═══ */
.nav-lang-switcher {
  position: relative;
}
.nav-lang-btn {
  display: flex; align-items: center; gap: 5px;
  padding: 6px 10px; min-width: unset;
}
.nav-lang-flag { font-size: 1.1rem; line-height: 1; }
.nav-lang-code { font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em; }
.nav-lang-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  z-index: 9999;
  background: var(--color-surface, #1e2535);
  border: 1px solid var(--color-border, rgba(255,255,255,0.1));
  border-radius: 8px;
  padding: 6px;
  min-width: 160px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4);
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.nav-lang-option {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 10px;
  border: none; border-radius: 5px; cursor: pointer;
  font-size: 0.82rem; font-weight: 500;
  background: transparent; color: var(--color-text);
  text-align: left; width: 100%;
  transition: background 0.12s, color 0.12s;
}
.nav-lang-option:hover { background: var(--color-border, rgba(255,255,255,0.08)); }
.nav-lang-option.active {
  background: var(--color-accent, #00c896);
  color: var(--color-accent-contrast, #000);
  font-weight: 700;
}

/* ═══════════════════════════════════════════════════════════
   DIALOG STYLES (profile & password)
   ═══════════════════════════════════════════════════════════ */
.btn-primary-dialog {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  padding: 8px 16px; font-size: 0.85rem; font-weight: 600;
  color: var(--color-accent-contrast); background: var(--color-accent); border: none; border-radius: 3px;
  cursor: pointer; transition: background 0.12s;
}
.btn-primary-dialog:hover { background: var(--color-accent-hover); }

.btn-secondary-dialog {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  padding: 8px 16px; font-size: 0.85rem; font-weight: 600;
  color: var(--color-text); background: var(--color-border); border: none; border-radius: 3px;
  cursor: pointer; transition: background 0.12s;
}
.btn-secondary-dialog:hover { background: var(--color-scrollbar-thumb-hover); color: var(--color-text); }

.btn-ghost-dialog {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  padding: 8px 16px; font-size: 0.85rem; font-weight: 500;
  color: var(--color-text-muted); background: transparent; border: none; border-radius: 3px;
  cursor: pointer; transition: color 0.12s, background 0.12s;
}
.btn-ghost-dialog:hover { color: var(--color-text); background: var(--color-hover-bg-soft); }

/* Profile Info Rows */
.profile-info-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 14px; border-radius: 3px;
  background: var(--color-surface); border: 1px solid var(--color-border);
}
.profile-label { font-size: 0.8rem; font-weight: 600; color: var(--color-text-muted); }
.profile-value { font-size: 0.9rem; font-weight: 600; color: var(--color-text); }

.profile-input {
  background: var(--color-elevated); color: var(--color-text); padding: 6px 12px;
  border-radius: 3px; border: 1px solid var(--color-border); font-size: 0.9rem;
  outline: none; transition: border-color 0.12s;
}
.profile-input:focus { border-color: var(--color-accent); }

/* Password Input */
.password-input {
  width: 100%; background: var(--color-elevated); color: var(--color-text);
  padding: 10px 12px; border-radius: 3px;
  border: 1px solid var(--color-border); font-size: 0.9rem;
  outline: none; transition: border-color 0.12s;
}
.password-input:focus { border-color: var(--color-accent); }
.password-input::placeholder { color: var(--color-text-subtle); }

/* Dialog Header */
.dialog-header-custom {
  display: flex; align-items: center; justify-content: center;
  width: 100%; position: relative;
}
.dialog-title-centered {
  font-size: 1rem; font-weight: 600; color: var(--color-text); text-align: center;
}
.dialog-close-btn {
  position: absolute; right: -8px; top: 50%; transform: translateY(-50%);
  width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
  color: var(--color-text-muted); background: transparent; border: none; border-radius: 3px;
  cursor: pointer; transition: all 0.12s;
}
.dialog-close-btn:hover { color: var(--color-text); background: var(--color-hover-bg); }

/* Responsive */
@media (max-width: 1100px) {
  .nav-inner {
    padding: 0 18px;
  }

  .nav-brand {
    display: none;
  }
}

@media (max-width: 900px) {
  .nav-inner {
    height: auto;
    min-height: 52px;
    padding: 7px 10px;
    gap: 8px;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: space-between;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
  }

  .nav-inner::-webkit-scrollbar {
    display: none;
  }

  .nav-left {
    order: 1;
    flex: 0 0 auto;
    justify-content: flex-start;
    flex-wrap: nowrap;
    overflow: visible;
    padding-bottom: 0;
    min-height: 0;
    gap: 6px;
  }

  .nav-right {
    order: 2;
    flex: 0 0 auto;
    justify-content: flex-end;
    gap: 6px;
    flex-wrap: nowrap;
    overflow: visible;
    min-height: 0;
  }

  .nav-link {
    padding: 6px 9px;
    font-size: 0.74rem;
    letter-spacing: 0.025em;
  }

  .nav-btn-ghost,
  .nav-btn-accent {
    padding: 6px 9px;
    font-size: 0.74rem;
    min-height: 30px;
  }

  .nav-theme-toggle {
    display: none;
  }

  .nav-theme-label {
    display: none;
  }

  .nav-user-btn {
    min-height: 30px;
    padding: 2px 7px 2px 2px;
  }

  .nav-lang-btn {
    min-height: 30px;
    padding: 5px 7px;
  }

  .nav-avatar,
  .nav-avatar-placeholder {
    width: 23px;
    height: 23px;
  }

  .nav-avatar-placeholder {
    display: grid;
    place-items: center;
    line-height: 1;
  }
}

@media (max-width: 640px) {
  .nav-inner {
    min-height: 44px;
    padding: 6px 8px;
    gap: 6px;
    flex-wrap: nowrap;
    justify-content: space-between;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
  }

  .nav-inner::-webkit-scrollbar {
    display: none;
  }

  .nav-lang-code {
    display: none;
  }

  .nav-theme-toggle {
    display: none;
  }

  .nav-btn-ghost,
  .nav-btn-accent {
    padding: 5px 7px;
    font-size: 0.7rem;
    min-height: 28px;
  }

  .nav-left {
    order: 1;
    flex: 0 0 auto;
    width: auto;
    max-width: none;
    margin: 0;
    justify-content: flex-start;
    flex-wrap: nowrap;
    gap: 4px;
    overflow: visible;
    padding-bottom: 0;
    min-height: 0;
  }

  .nav-right {
    order: 2;
    flex: 0 0 auto;
    width: auto;
    max-width: none;
    margin: 0;
    justify-content: flex-end;
    flex-wrap: nowrap;
    gap: 4px;
    overflow: visible;
    padding-bottom: 0;
    min-height: 0;
  }

  .nav-link {
    padding: 5px 7px;
    font-size: 0.7rem;
    letter-spacing: 0.02em;
  }

  .nav-user-btn {
    padding: 2px 6px 2px 2px;
    min-height: 28px;
  }

  .nav-lang-btn {
    min-height: 28px;
    padding: 4px 6px;
  }

  .nav-avatar,
  .nav-avatar-placeholder {
    width: 22px;
    height: 22px;
  }

  .nav-avatar-placeholder {
    display: grid;
    place-items: center;
    font-size: 0.72rem;
    line-height: 1;
  }
}
</style>
