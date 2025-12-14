<template>
  <nav class="backdrop-blur-md bg-slate-900/30 border-b border-slate-700/30">
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
      <div class="flex justify-between items-center">
        <!-- Left side navigation -->
        <div class="flex items-center gap-2 sm:gap-4">
          <RouterLink 
            to="/" 
            class="nav-link group"
          >
            <span>Domov</span>
          </RouterLink>
          
          <RouterLink 
            v-if="canAddGame && !isAdmin" 
            to="/add-project" 
            class="nav-link group"
          >
            <span>Pridať projekt</span>
          </RouterLink>
          
          <RouterLink 
            v-if="isAdmin" 
            to="/admin" 
            class="admin-badge group"
          >
            <span>Admin Panel</span>
          </RouterLink>
        </div>

        <!-- Right side - Auth buttons -->
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Not logged in -->
          <template v-if="!isLoggedIn">
            <RouterLink to="/register">
              <button class="btn-ghost-nav">
                Registrovať sa
              </button>
            </RouterLink>

            <RouterLink to="/login">
              <button class="btn-primary-nav">
                Prihlásiť sa
              </button>
            </RouterLink>
          </template>

          <!-- Logged in -->
          <template v-else>
            <!-- User Profile Button -->
            <button 
              @click="showUserProfileDialog = true"
              class="user-profile-btn group"
            >
              <div class="relative">
                <img 
                  v-if="currentUser?.avatar_path" 
                  :key="currentUser.avatar_path"
                  :src="getAvatarUrl(currentUser.avatar_path)" 
                  alt="Avatar"
                  class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-500/50 group-hover:ring-indigo-400 transition-all"
                />
                <div 
                  v-else 
                  class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-sm ring-2 ring-indigo-500/50 group-hover:ring-indigo-400 transition-all"
                >
                  {{ userName?.charAt(0).toUpperCase() }}
                </div>
              </div>
              <span class="font-medium text-slate-200 hidden sm:inline group-hover:text-white transition-colors">{{ userName }}</span>
            </button>

            <!-- Logout Button -->
            <button
              @click="logout"
              class="btn-logout group"
            >
              <i class="pi pi-sign-out text-base"></i>
              <span class="hidden sm:inline">Odhlásiť sa</span>
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Toast notifikácie -->
    <Toast ref="toast" />
  </nav>

  <!-- USER PROFILE DIALOG -->
  <Dialog 
    v-model:visible="showUserProfileDialog" 
    :modal="true" 
    :closable="false" 
    :draggable="false"
    class="w-11/12 md:w-[420px]"
    :contentStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', padding: '1.5rem', border: 'none' }" 
    :headerStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', borderBottom: '1px solid rgba(71, 85, 105, 0.5)', padding: '1rem 1.5rem', position: 'relative' }"
    :style="{ borderRadius: '16px', overflow: 'hidden' }"
  >
    <template #header>
      <div class="dialog-header-custom">
        <span class="dialog-title-centered">Môj Profil</span>
        <button class="dialog-close-btn" @click="showUserProfileDialog = false">
          <i class="pi pi-times"></i>
        </button>
      </div>
    </template>
    
    <div v-if="currentUser" class="flex flex-col gap-5">
      <!-- Avatar Section -->
      <div class="flex flex-col items-center gap-4 p-6 bg-gradient-to-br from-slate-800/50 to-slate-900/50 rounded-xl border border-slate-700/30">
        <div class="relative group cursor-pointer">
          <img 
            v-if="currentUser.avatar_path" 
            :key="currentUser.avatar_path"
            :src="getAvatarUrl(currentUser.avatar_path)" 
            alt="Avatar"
            class="w-28 h-28 rounded-full object-cover ring-4 ring-indigo-500/30 shadow-2xl transition-all group-hover:ring-indigo-400/50"
          />
          <div 
            v-else 
            class="w-28 h-28 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-4xl shadow-2xl ring-4 ring-indigo-500/30 transition-all group-hover:ring-indigo-400/50"
          >
            {{ currentUser.name?.charAt(0).toUpperCase() }}
          </div>
          
          <!-- Upload overlay -->
          <label class="absolute inset-0 flex items-center justify-center bg-slate-900/70 rounded-full opacity-0 group-hover:opacity-100 transition-all cursor-pointer backdrop-blur-sm">
            <div class="flex flex-col items-center gap-1">
              <i class="pi pi-camera text-white text-2xl"></i>
              <span class="text-xs text-white/80">Zmeniť</span>
            </div>
            <input 
              type="file" 
              accept="image/*" 
              class="hidden" 
              @change="handleAvatarUpload"
            />
          </label>
        </div>
        
        <p class="text-xs text-slate-500 text-center">Kliknutím na avatar môžete zmeniť obrázok</p>
      </div>

      <!-- User Info -->
      <div class="flex flex-col gap-2.5">
        <div class="profile-info-row">
          <span class="profile-label">
            <i class="pi pi-user text-xs"></i>
            Meno
          </span>
          <span v-if="!editMode" class="profile-value">{{ currentUser.name }}</span>
          <input 
            v-else 
            v-model="editName" 
            type="text" 
            class="profile-input"
          />
        </div>
        
        <div class="profile-info-row">
          <span class="profile-label">
            <i class="pi pi-envelope text-xs"></i>
            Email
          </span>
          <span class="profile-value text-sm">{{ currentUser.email }}</span>
        </div>
        
        <div class="profile-info-row">
          <span class="profile-label">
            <i class="pi pi-id-card text-xs"></i>
            Rola
          </span>
          <span class="profile-value capitalize">{{ currentUser.role }}</span>
        </div>
        
        <div class="profile-info-row">
          <span class="profile-label">
            <i class="pi pi-graduation-cap text-xs"></i>
            Typ študenta
          </span>
          <span class="profile-value">{{ getStudentTypeLabel(currentUser.student_type) }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div v-if="!editMode" class="flex gap-2 pt-2">
        <button 
          class="btn-secondary-dialog flex-1"
          @click="startEdit" 
        >
          Upraviť profil
        </button>
        <button 
          class="btn-ghost-dialog flex-1"
          @click="openPasswordDialog" 
        >
          Zmeniť heslo
        </button>
      </div>
      <div v-else class="flex gap-2 pt-2">
        <button 
          class="btn-primary-dialog flex-1"
          @click="saveProfile" 
        >
          Uložiť
        </button>
        <button 
          class="btn-ghost-dialog flex-1"
          @click="cancelEdit" 
        >
          Zrušiť
        </button>
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
    :contentStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', padding: '1.5rem', border: 'none' }" 
    :headerStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', borderBottom: '1px solid rgba(71, 85, 105, 0.5)', padding: '1rem 1.5rem', position: 'relative' }"
    :style="{ borderRadius: '16px', overflow: 'hidden' }"
  >
    <template #header>
      <div class="dialog-header-custom">
        <span class="dialog-title-centered">Zmena hesla</span>
        <button class="dialog-close-btn" @click="showPasswordDialog = false">
          <i class="pi pi-times"></i>
        </button>
      </div>
    </template>
    
    <div class="flex flex-col gap-4">
      <div>
        <label class="block mb-2 font-medium text-slate-300 text-sm">Aktuálne heslo</label>
        <div class="relative">
          <i class="pi pi-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
          <input 
            v-model="currentPassword" 
            type="password" 
            class="password-input" 
            placeholder="Zadaj aktuálne heslo"
          />
        </div>
      </div>
      <div>
        <label class="block mb-2 font-medium text-slate-300 text-sm">Nové heslo</label>
        <div class="relative">
          <i class="pi pi-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
          <input 
            v-model="newPassword" 
            type="password" 
            class="password-input" 
            placeholder="Aspoň 8 znakov"
          />
        </div>
      </div>
      <div>
        <label class="block mb-2 font-medium text-slate-300 text-sm">Potvrdiť nové heslo</label>
        <div class="relative">
          <i class="pi pi-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
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
          Zrušiť
        </button>
        <button 
          class="btn-primary-dialog flex-1"
          @click="savePassword" 
        >
          Uložiť
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
import { ref, onMounted, onUnmounted } from 'vue'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
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
      toastService.add({ severity: 'success', summary: 'Avatar aktualizovaný', detail: 'Váš avatar bol úspešne zmenený.', life: 3000 })
      
      // Reset input to allow re-selecting the same file
      event.target.value = ''
    } else {
      console.error('Avatar upload failed:', data)
      toastService.add({ severity: 'error', summary: 'Chyba', detail: data.message || 'Nepodarilo sa aktualizovať avatar.', life: 3000 })
    }
  } catch (err) {
    console.error('Error uploading avatar:', err)
    toastService.add({ severity: 'error', summary: 'Chyba', detail: 'Chyba pri nahrávaní avatara.', life: 3000 })
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
      summary: 'Chyba', 
      detail: 'Heslá sa nezhodujú', 
      life: 3000 
    })
    return
  }

  if (newPassword.value.length < 8) {
    toastService.add({ 
      severity: 'error', 
      summary: 'Chyba', 
      detail: 'Heslo musí mať aspoň 8 znakov', 
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
        summary: 'Úspech', 
        detail: data.message || 'Heslo bolo zmenené', 
        life: 4000 
      })
      showPasswordDialog.value = false
    } else {
      toastService.add({ 
        severity: 'error', 
        summary: 'Chyba', 
        detail: data.message || 'Nepodarilo sa zmeniť heslo', 
        life: 4000 
      })
    }
  } catch (err) {
    toastService.add({ 
      severity: 'error', 
      summary: 'Chyba siete', 
      detail: 'Nepodarilo sa spojiť so serverom', 
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
      summary: 'Odhlásenie',
      detail: 'Úspešne odhlásený',
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

  // Initialize scrum master flag from persisted active team
  refreshActiveTeamStatus()
})

// Cleanup event listeners on unmount to prevent memory leaks
onUnmounted(() => {
  window.removeEventListener('login', handleLoginEvent)
  window.removeEventListener('team-changed', handleTeamChangedEvent)
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
      toastService.add({ severity: 'success', summary: 'Profil aktualizovaný', detail: 'Vaše údaje boli úspešne zmenené.', life: 3000 })
    } else {
      toastService.add({ severity: 'error', summary: 'Chyba', detail: data.message || 'Nepodarilo sa aktualizovať profil.', life: 3000 })
    }
  } catch (err) {
    console.error('Error updating profile:', err)
    toastService.add({ severity: 'error', summary: 'Chyba', detail: 'Chyba pri aktualizácii profilu.', life: 3000 })
  }
}
</script>

<style scoped>
/* Navigation Link */
.nav-link {
  @apply flex items-center gap-2 px-3 py-2 text-slate-300 font-medium rounded-lg transition-all duration-200;
}

.nav-link:hover {
  @apply text-white bg-slate-800/50;
}

/* Admin Badge */
.admin-badge {
  @apply flex items-center gap-2 px-3 py-2 font-semibold text-sm rounded-xl transition-all duration-200;
  background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(185, 28, 28, 0.3) 100%);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
}

.admin-badge:hover {
  background: linear-gradient(135deg, rgba(220, 38, 38, 0.3) 0%, rgba(185, 28, 28, 0.4) 100%);
  border-color: rgba(239, 68, 68, 0.6);
  color: #fecaca;
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

/* Ghost Nav Button */
.btn-ghost-nav {
  @apply flex items-center gap-2 px-3 py-2 text-slate-300 font-medium rounded-lg transition-all duration-200;
}

.btn-ghost-nav:hover {
  @apply text-white bg-slate-800/50;
}

/* Primary Nav Button */
.btn-primary-nav {
  @apply flex items-center gap-2 px-4 py-2 font-semibold text-white rounded-xl transition-all duration-200;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}

.btn-primary-nav:hover {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
  transform: translateY(-1px);
}

/* User Profile Button */
.user-profile-btn {
  @apply flex items-center gap-2.5 px-3 py-1.5 rounded-xl transition-all duration-200;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(71, 85, 105, 0.5);
  backdrop-filter: blur(8px);
}

.user-profile-btn:hover {
  background: rgba(51, 65, 85, 0.6);
  border-color: rgba(99, 102, 241, 0.5);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Logout Button */
.btn-logout {
  @apply flex items-center gap-2 px-3 py-2 text-slate-300 font-medium rounded-xl transition-all duration-200;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(71, 85, 105, 0.5);
  backdrop-filter: blur(8px);
}

.btn-logout:hover {
  @apply text-rose-300;
  background: rgba(159, 18, 57, 0.2);
  border-color: rgba(244, 63, 94, 0.4);
}

/* Dialog Buttons */
.btn-primary-dialog {
  @apply flex items-center justify-center gap-2 px-4 py-2.5 font-semibold text-white rounded-xl transition-all duration-200;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}

.btn-primary-dialog:hover {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.btn-secondary-dialog {
  @apply flex items-center justify-center gap-2 px-4 py-2.5 font-semibold text-slate-200 rounded-xl transition-all duration-200;
  background: rgba(51, 65, 85, 0.5);
  border: 1px solid rgba(100, 116, 139, 0.5);
}

.btn-secondary-dialog:hover {
  @apply text-white;
  background: rgba(71, 85, 105, 0.6);
  border-color: rgba(148, 163, 184, 0.5);
}

.btn-ghost-dialog {
  @apply flex items-center justify-center gap-2 px-4 py-2.5 font-medium text-slate-400 rounded-xl transition-all duration-200;
  background: transparent;
  border: 1px solid transparent;
}

.btn-ghost-dialog:hover {
  @apply text-slate-200;
  background: rgba(51, 65, 85, 0.3);
  border-color: rgba(71, 85, 105, 0.3);
}

/* Profile Info Rows */
.profile-info-row {
  @apply flex justify-between items-center p-3 rounded-lg;
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(51, 65, 85, 0.5);
}

.profile-label {
  @apply flex items-center gap-2 text-slate-400 font-medium text-sm;
}

.profile-value {
  @apply text-white font-semibold;
}

.profile-input {
  @apply bg-slate-800/80 text-white px-3 py-1.5 rounded-lg border border-slate-600/50 transition-all duration-200;
}

.profile-input:focus {
  @apply outline-none border-indigo-500/50;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

/* Password Input */
.password-input {
  @apply w-full bg-slate-800/50 text-white px-3 py-2.5 pl-10 rounded-xl border border-slate-700/50 transition-all duration-200;
}

.password-input:focus {
  @apply outline-none border-indigo-500/50;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.password-input::placeholder {
  @apply text-slate-500;
}

/* Dialog Header - Custom centered layout with close button */
.dialog-header-custom {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  position: relative;
}

.dialog-title-centered {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f1f5f9;
  text-align: center;
}

.dialog-close-btn {
  position: absolute;
  right: -0.5rem;
  top: 50%;
  transform: translateY(-50%);
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  background: transparent;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dialog-close-btn:hover {
  color: #f1f5f9;
  background: rgba(71, 85, 105, 0.5);
}

.dialog-close-btn i {
  font-size: 0.875rem;
}
</style>
