<template>
  <div class="max-w-md mx-auto p-6 border rounded-lg mt-12">
    <!-- Show "Check your email" message when resent=true -->
    <div v-if="isResentMode" class="text-center">
      <i class="pi pi-envelope text-6xl text-blue-500 mb-4"></i>
      <h2 class="text-2xl font-semibold mb-4 text-blue-600">Skontrolujte svoj email</h2>
      <p class="mb-4 text-gray-600">
        Príliš veľa neúspešných pokusov o prihlásenie. Overovací e-mail bol odoslaný znova na vašu emailovú adresu.
      </p>
      <p class="mb-6 text-gray-600">
        Kliknite na odkaz v e-maili na overenie účtu a potom sa môžete prihlásiť.
      </p>
      <Button 
        label="Späť na prihlásenie" 
        icon="pi pi-arrow-left" 
        class="w-full" 
        @click="goToLogin"
      />
    </div>

    <div v-else-if="loading" class="text-center">
      <i class="pi pi-spin pi-spinner text-4xl text-blue-500 mb-4"></i>
      <h2 class="text-xl font-semibold">Overujem váš email...</h2>
    </div>

    <div v-else-if="success" class="text-center">
      <i class="pi pi-check-circle text-6xl text-green-500 mb-4"></i>
      <h2 class="text-2xl font-semibold mb-4 text-green-600">Email overený!</h2>
      <p class="mb-6 text-gray-600">Váš účet bol úspešne overený. Presmerovávame vás na prihlásenie...</p>
      <Button 
        label="Prihlásiť sa teraz" 
        icon="pi pi-sign-in" 
        class="w-full" 
        @click="goToLogin"
      />
    </div>

    <div v-else-if="error" class="text-center">
      <i class="pi pi-times-circle text-6xl text-red-500 mb-4"></i>
      <h2 class="text-2xl font-semibold mb-4 text-red-600">Overenie zlyhalo</h2>
      <p class="mb-6 text-gray-600">{{ errorMessage }}</p>
      <Button 
        label="Späť na hlavnú stránku" 
        icon="pi pi-home" 
        class="w-full" 
        @click="goToHome"
      />
    </div>

    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Toast from 'primevue/toast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const route = useRoute()
const toast = useToast()

const loading = ref(true)
const success = ref(false)
const error = ref(false)
const errorMessage = ref('')
const isResentMode = ref(false)

let redirectTimer = null

onUnmounted(() => {
  if (redirectTimer) {
    clearTimeout(redirectTimer)
  }
})

onMounted(async () => {
  const token = route.query.token
  const resent = route.query.resent

  // If resent=true, just show the "check your email" message
  if (resent === 'true') {
    isResentMode.value = true
    loading.value = false
    return
  }

  if (!token) {
    // Show "check your email" message instead of error
    isResentMode.value = true
    loading.value = false
    return
  }

  try {
    console.log('🔄 Verifying email with token:', token)
    
    const response = await fetch('http://127.0.0.1:8000/api/verify-email', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ token })
    })
    
    const data = await response.json()
    console.log('Response status:', response.status, 'Data:', data)
    
    if (!response.ok) {
      throw new Error(data.message || 'Verification failed')
    }
    
    console.log('✅ Verification successful:', data)
    
    loading.value = false
    success.value = true

    toast.add({
      severity: 'success',
      summary: 'Email overený',
      detail: 'Váš účet bol úspešne overený',
      life: 3000
    })

    // Redirect to login after 3 seconds
    redirectTimer = setTimeout(() => {
      router.push('/login')
    }, 3000)

  } catch (err) {
    console.error('❌ Verification error:', err)
    loading.value = false
    error.value = true
    
    if (err.message) {
      errorMessage.value = err.message
    } else {
      errorMessage.value = 'Nastala chyba pri overovaní emailu. Skúste to neskôr.'
    }

    toast.add({
      severity: 'error',
      summary: 'Chyba overenia',
      detail: errorMessage.value,
      life: 5000
    })
  }
})

function goToLogin() {
  router.push('/login')
}

function goToHome() {
  router.push('/')
}
</script>
