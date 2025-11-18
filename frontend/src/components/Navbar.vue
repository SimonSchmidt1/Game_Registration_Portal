<template>
  <nav class="border-b p-4 flex justify-center items-center shadow-sm">
    <div class="w-full max-w-6xl flex justify-between items-center px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-6">
        <RouterLink to="/" class="text-lg font-semibold hover:underline">Domov</RouterLink>
        <RouterLink to="/add" class="text-lg font-semibold hover:underline">Pridať hru</RouterLink>
      </div>

      <div class="flex items-center gap-3">
        <RouterLink v-if="!isLoggedIn" to="/register">
          <Button label="Registrovať sa" icon="pi pi-user-plus" class="p-button-outlined p-button-sm text-black" />
        </RouterLink>

        <RouterLink v-if="!isLoggedIn" to="/login">
          <Button label="Prihlásiť sa" icon="pi pi-user" class="p-button-outlined p-button-sm text-black" />
        </RouterLink>

        <div v-if="isLoggedIn" class="flex items-center gap-3 pr-4">
          <span class="text-sm font-medium text-white flex items-center gap-2">
            <i class="pi pi-user text-white"></i>
            {{ userName }}
          </span>
          <Button
            label="Odhlásiť sa"
            icon="pi pi-sign-out"
            class="p-button-sm"
            @click="logout"
          />
        </div>
      </div>
    </div>

    <!-- Toast notifikácie -->
    <Toast ref="toast" />
  </nav>
</template>

<script setup>
import { RouterLink, useRouter } from 'vue-router'
import Button from 'primevue/button'
import axios from 'axios'
import { ref, onMounted } from 'vue'
import Toast from 'primevue/toast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const isLoggedIn = ref(!!localStorage.getItem('access_token'))
const userName = ref('')
const toast = ref(null)

async function logout() {
  try {
    await axios.post(`${API_URL}/api/logout`, {}, {
      headers: { Authorization: `Bearer ${localStorage.getItem('access_token')}` }
    })
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')
    isLoggedIn.value = false

    toast.value.add({
      severity: 'success',
      summary: 'Odhlásenie',
      detail: 'Úspešne odhlásený',
      life: 3000
    })

    router.push('/login')
  } catch (error) {
    toast.value.add({
      severity: 'error',
      summary: 'Chyba',
      detail: 'Nepodarilo sa odhlásiť',
      life: 3000
    })
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

  window.addEventListener('login', () => {
    isLoggedIn.value = true
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    userName.value = user.name || ''
  })
})
</script>


