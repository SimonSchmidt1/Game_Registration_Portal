<template>
  <nav class="border-b p-4 flex justify-center items-center shadow-sm">
    <div class="w-full max-w-6xl flex justify-between items-center px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-6">
        <RouterLink to="/" class="text-lg font-semibold hover:underline">Domov</RouterLink>
        <RouterLink v-if="canAddGame" to="/add-project" class="text-lg font-semibold hover:underline">Pridať projekt</RouterLink>
      </div>

      <div class="flex items-center gap-3">
        <RouterLink v-if="!isLoggedIn" to="/register">
          <Button label="Registrovať sa" icon="pi pi-user-plus" class="p-button-outlined p-button-sm text-black" />
        </RouterLink>

        <RouterLink v-if="!isLoggedIn" to="/login">
          <Button label="Prihlásiť sa" icon="pi pi-user" class="p-button-outlined p-button-sm text-black" />
        </RouterLink>

        <div v-if="isLoggedIn" class="flex items-center gap-3">
          <!-- User Avatar and Profile -->
          <div 
            @click="showUserProfileDialog = true"
            class="flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-gray-700 to-gray-800 border-2 border-gray-600 rounded-xl hover:border-blue-400 transition cursor-pointer shadow-sm"
            style="height:40px; min-width:120px;"
          >
            <div class="relative">
              <img 
                v-if="currentUser?.avatar_path" 
                :key="currentUser.avatar_path"
                :src="getAvatarUrl(currentUser.avatar_path)" 
                alt="Avatar"
                class="w-8 h-8 rounded-full object-cover border-2 border-white shadow"
              />
              <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow">
                {{ userName?.charAt(0).toUpperCase() }}
              </div>
            </div>
            <span class="font-semibold text-white hidden sm:inline text-sm">{{ userName }}</span>
          </div>

          <button
            @click="logout"
            class="flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-gray-700 to-gray-800 border-2 border-gray-600 rounded-xl hover:border-blue-400 transition cursor-pointer shadow-sm font-semibold text-white text-sm"
            style="height:40px; min-width:120px;"
          >
            <i class="pi pi-sign-out text-base"></i>
            <span class="hidden sm:inline">Odhlásiť sa</span>
          </button>
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
    :closable="true" 
    :draggable="false"
    class="w-11/12 md:w-1/3"
    :contentStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', padding: '1.5rem', border: 'none' }" 
    :headerStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', borderBottom: '1px solid #374151', padding: '1.25rem 1.5rem', position: 'relative' }"
    :style="{ borderRadius: '8px', overflow: 'hidden' }"
  >
    <template #header>
      <div class="flex items-center justify-center w-full">
        <span class="text-gray-100 font-medium text-lg w-full">Môj Profil</span>
      </div>
    </template>
    
    <div v-if="currentUser" class="flex flex-col gap-6">
      <!-- Avatar Section -->
      <div class="flex flex-col items-center gap-4 p-6 bg-gray-800 rounded-lg">
        <div class="relative group">
          <img 
            v-if="currentUser.avatar_path" 
            :key="currentUser.avatar_path"
            :src="getAvatarUrl(currentUser.avatar_path)" 
            alt="Avatar"
            class="w-32 h-32 rounded-full object-cover border-4 border-blue-500 shadow-lg"
          />
          <div v-else class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-5xl shadow-lg border-4 border-blue-500">
            {{ currentUser.name?.charAt(0).toUpperCase() }}
          </div>
          
          <!-- Upload overlay -->
          <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-60 rounded-full opacity-0 group-hover:opacity-100 transition cursor-pointer">
            <i class="pi pi-camera text-white text-3xl"></i>
            <input 
              type="file" 
              accept="image/*" 
              class="hidden" 
              @change="handleAvatarUpload"
            />
          </label>
        </div>
        
        <p class="text-xs text-gray-400 text-center">Kliknutím na avatar môžete zmeniť obrázok</p>
      </div>

      <!-- User Info -->
      <div class="flex flex-col gap-3">
        <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
          <span class="text-gray-400 font-medium">
            Meno:
          </span>
          <span v-if="!editMode" class="text-white font-semibold">{{ currentUser.name }}</span>
          <input 
            v-else 
            v-model="editName" 
            type="text" 
            class="bg-gray-700 text-white px-2 py-1 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
          />
        </div>
        
        <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
          <span class="text-gray-400 font-medium">
            Email:
          </span>
          <span class="text-white font-semibold text-sm">{{ currentUser.email }}</span>
        </div>
        
        <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
          <span class="text-gray-400 font-medium">
            Rola:
          </span>
          <span class="text-white font-semibold">{{ currentUser.role }}</span>
        </div>
      </div>

      <div v-if="!editMode" class="flex gap-2">
        <Button 
          label="Upraviť profil" 
          class="p-button-outlined flex-1"
          @click="startEdit" 
        />
        <Button 
          label="Zmeniť heslo" 
          class="p-button-outlined flex-1"
          severity="secondary"
          @click="openPasswordDialog" 
        />
      </div>
      <div v-else class="flex gap-2">
        <Button 
          label="Uložiť" 
          class="p-button-outlined w-full"
          @click="saveProfile" 
        />
        <Button 
          label="Zrušiť" 
          class="p-button-text w-full"
          @click="cancelEdit" 
        />
      </div>
    </div>
  </Dialog>

  <!-- Password Change Dialog -->
  <Dialog 
    v-model:visible="showPasswordDialog" 
    header="Zmena hesla" 
    modal
    :style="{ width: '450px' }"
    class="p-fluid"
  >
    <div class="flex flex-col gap-4 p-4">
      <div>
        <label class="block mb-2 font-medium text-gray-300">Aktuálne heslo</label>
        <input 
          v-model="currentPassword" 
          type="password" 
          class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none" 
          placeholder="Zadaj aktuálne heslo"
        />
      </div>
      <div>
        <label class="block mb-2 font-medium text-gray-300">Nové heslo</label>
        <input 
          v-model="newPassword" 
          type="password" 
          class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none" 
          placeholder="Aspoň 8 znakov"
        />
      </div>
      <div>
        <label class="block mb-2 font-medium text-gray-300">Potvrdiť nové heslo</label>
        <input 
          v-model="newPasswordConfirm" 
          type="password" 
          class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none" 
          placeholder="Zadaj heslo znova"
        />
      </div>
      <div class="flex gap-2 mt-2">
        <Button 
          label="Zrušiť" 
          icon="pi pi-times" 
          @click="showPasswordDialog = false" 
          text 
          class="flex-1"
        />
        <Button 
          label="Uložiť" 
          icon="pi pi-check" 
          @click="savePassword" 
          class="flex-1"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { RouterLink, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import axios from 'axios'
import { ref, onMounted } from 'vue'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const isLoggedIn = ref(!!localStorage.getItem('access_token'))
const canAddGame = ref(false) // Derived from active team scrum master status (now for projects)
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
    } catch {
      localStorage.removeItem('access_token')
      localStorage.removeItem('user')
      isLoggedIn.value = false
      if (router.currentRoute.value.meta.requiresAuth) {
        router.push('/login')
      }
    }
  }

  window.addEventListener('login', async () => {
    isLoggedIn.value = true
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    userName.value = user.name || ''
    await loadCurrentUser()
  })

  // Initialize scrum master flag from persisted active team
  refreshActiveTeamStatus()
  // Listen for team changes broadcast from HomeView
  window.addEventListener('team-changed', (e) => {
    refreshActiveTeamStatus(e.detail)
  })
})

function refreshActiveTeamStatus(detail) {
  const token = localStorage.getItem('access_token')
  const stored = localStorage.getItem('active_team_is_scrum_master')
  
  // Must be logged in to add games
  if (!token) {
    canAddGame.value = false
    return
  }
  
  // Prefer event detail if available
  if (detail && typeof detail.isScrumMaster === 'boolean') {
    canAddGame.value = detail.isScrumMaster
  } else if (stored !== null) {
    canAddGame.value = stored === '1'
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


