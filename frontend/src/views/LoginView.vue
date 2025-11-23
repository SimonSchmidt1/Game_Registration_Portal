<template>
  <div class="max-w-sm mx-auto p-6 border rounded-lg mt-12">
    <h2 class="text-2xl font-semibold mb-6 text-center">Prihlásenie</h2>

    <form @submit.prevent="login">
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

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const toast = useToast()

const email = ref('')
const password = ref('')

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

    // 5th attempt for unverified user - resend + redirect
    if (data.verification_resent) {
      // Craft clearer message depending on whether account was previously verified
      const wasVerified = data.account_verified === true
      const detail = wasVerified
        ? 'Z bezpečnostných dôvodov sme vám poslali overovací e‑mail. Ak ste neúspešné pokusy nevykonali vy, dokončite overenie.'
        : 'Overovací e‑mail bol odoslaný. Skontrolujte schránku a dokončite overenie účtu.'
      toast.add({
        severity: 'info',
        summary: 'Skontrolujte e‑mail',
        detail: detail,
        life: 7000
      })
      setTimeout(() => router.push('/verify-email?resent=true'), 1600)
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
</script>
