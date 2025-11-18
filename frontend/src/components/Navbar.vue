<template>
  <nav class="border-b p-4 flex justify-between items-center shadow-sm">
    <div class="flex items-center gap-6">
      <RouterLink to="/" class="text-lg font-semibold hover:underline">Domov</RouterLink>
      <RouterLink to="/add" class="text-lg font-semibold hover:underline">Pridať hru</RouterLink>
    </div>

    <div class="flex items-center gap-2">
      <RouterLink v-if="!isLoggedIn" to="/login">
        <Button label="Prihlásiť sa" icon="pi pi-user" class="p-button-outlined p-button-sm text-black" />
      </RouterLink>

      <Button
        v-if="isLoggedIn"
        label="Odhlásiť sa"
        icon="pi pi-sign-out"
        class="p-button-sm"
        @click="logout"
      />
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

const router = useRouter()
const isLoggedIn = ref(!!localStorage.getItem('access_token'))
const toast = ref(null)

async function logout() {
  try {
    await axios.post('http://127.0.0.1:8000/api/logout', {}, {
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
    console.error('Chyba pri odhlásení:', error)
  }
}

onMounted(async () => {
  const token = localStorage.getItem('access_token')
  if (token) {
    try {
      await axios.get('http://127.0.0.1:8000/api/user', {
        headers: { Authorization: `Bearer ${token}` }
      })
      isLoggedIn.value = true
    } catch {
      localStorage.removeItem('access_token')
      localStorage.removeItem('user')
      isLoggedIn.value = false
    }
  }

  window.addEventListener('login', () => {
    isLoggedIn.value = true
  })
})
</script>


