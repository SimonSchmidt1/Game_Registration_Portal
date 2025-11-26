<template>
  <div class="max-w-sm mx-auto p-6 border rounded-lg mt-12">
    <h2 class="text-2xl font-semibold mb-6 text-center">
      {{ showTemporaryLogin ? 'Prihlásenie s dočasným heslom' : 'Prihlásenie' }}
    </h2>

    <!-- Temporary Password Info Message -->
    <div v-if="showTemporaryLogin" class="mb-4 p-4 bg-orange-100 border border-orange-300 rounded-lg">
      <div class="flex items-start gap-2">
        <i class="pi pi-info-circle text-orange-600 text-xl mt-0.5"></i>
        <div>
          <p class="text-sm text-orange-800 font-medium">Príliš veľa neúspešných pokusov</p>
          <p class="text-sm text-orange-700 mt-1">Skontroluj svoj e-mail a použi dočasné heslo, ktoré sme ti poslali.</p>
        </div>
      </div>
    </div>

    <form @submit.prevent="showTemporaryLogin ? loginWithTemporaryPassword() : login()">
      <div class="mb-4">
        <label class="block mb-1 font-medium">Email</label>
        <InputText 
          v-model="email" 
          type="email" 
          class="w-full" 
          placeholder="1234567@ucm.sk"
          pattern="[0-9]{7}@ucm\.sk"
          title="Email musí byť v tvare: 7 číslic@ucm.sk (napr. 1234567@ucm.sk)"
          required 
        />
      </div>

      <div class="mb-6">
        <label class="block mb-1 font-medium">Heslo</label>
        <InputText 
          v-if="!showTemporaryLogin"
          v-model="password" 
          type="password" 
          class="w-full" 
          required 
        />
        <InputText 
          v-else
          v-model="temporaryPassword" 
          type="text" 
          class="w-full" 
          placeholder="XXXX-XXXX-XXXX"
          required 
        />
        <small v-if="showTemporaryLogin" class="text-gray-500">Formát: XXXX-XXXX-XXXX (z e-mailu)</small>
      </div>

      <Button type="submit" label="Prihlásiť sa" icon="pi pi-sign-in" class="w-full mb-4" />

      <div v-if="!showTemporaryLogin" class="text-center space-y-2">
        <router-link to="/forgot-password" class="block text-blue-500 hover:underline text-sm">
          Zabudnuté heslo?
        </router-link>
        <div class="text-gray-500 text-sm mt-4">
          Nemáš účet? 
          <router-link to="/register" class="text-blue-500 hover:underline">
            Zaregistruj sa
          </router-link>
        </div>
      </div>
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

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const toast = useToast()

const email = ref('')
const password = ref('')
const temporaryPassword = ref('')
const showTemporaryLogin = ref(false)

async function login() {
  try {
    const response = await axios.post(`${API_URL}/api/login`, {
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

    // Redirect immediately after successful login
    router.push('/')

  } catch (error) {
    const data = error.response?.data || {}
    const status = error.response?.status

    // Email not verified - block login
    if (status === 403 && data.requires_verification) {
      toast.add({
        severity: 'warn',
        summary: 'Overenie požadované',
        detail: data.message || 'Účet nie je overený. Skontrolujte e-mail a dokončite overenie.',
        life: 8000
      })
      setTimeout(() => router.push('/verify-email'), 1600)
      return
    }

    // 5+ attempts - account blocked (verified OR unverified after resend period)
    if (data.too_many_attempts) {
      toast.add({
        severity: 'info',
        summary: 'Obnovte účet',
        detail: data.message || 'Príliš veľa pokusov. Skontrolujte e‑mail a obnovte účet.',
        life: 8000
      })
      setTimeout(() => router.push('/verify-email?resent=true'), 1600)
      return
    }

    // 5th attempt - temporary password sent
    if (data.verification_resent) {
      toast.add({
        severity: 'info',
        summary: 'Dočasné heslo odoslané',
        detail: 'Poslali sme ti e-mail s dočasným heslom. Skontroluj si schránku.',
        life: 7000
      })
      showTemporaryLogin.value = true
      toast.add({
        severity: 'info',
        summary: 'Použi dočasné heslo',
        detail: 'Zadaj dočasné heslo z e-mailu v poli nižšie.',
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

    localStorage.setItem('access_token', response.data.access_token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

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
</script>
