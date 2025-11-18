<template>
  <div class="max-w-sm mx-auto p-6 border rounded-lg mt-12">
    <h2 class="text-2xl font-semibold mb-6 text-center">Prihlásenie</h2>

    <form @submit.prevent="login">
      <div class="mb-4">
        <label class="block mb-1 font-medium">Email</label>
        <InputText v-model="email" class="w-full" required />
      </div>

      <div class="mb-6">
        <label class="block mb-1 font-medium">Heslo</label>
        <InputText v-model="password" type="password" class="w-full" required />
      </div>

      <Button type="submit" label="Prihlásiť sa" icon="pi pi-sign-in" class="w-full" />
    </form>

    <!-- Toast komponent -->
    <Toast />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()

const email = ref('')
const password = ref('')

async function login() {
  try {
    const response = await axios.post('http://127.0.0.1:8000/api/login', {
      email: email.value,
      password: password.value
    })

    localStorage.setItem('access_token', response.data.access_token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

    // Poslanie eventu pre Navbar
    window.dispatchEvent(new Event('login'))

    // Toast upozornenie
    toast.add({ 
      severity: 'success', 
      summary: 'Prihlásenie', 
      detail: 'Prihlásenie úspešné', 
      life: 3000 
    })

    setTimeout(() => router.push('/'), 1000)

  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Chyba',
      detail: error.response?.status === 401
        ? 'Nesprávny email alebo heslo ❌'
        : 'Chyba pri prihlásení ❌',
      life: 3000
    })
  }
}
</script>
