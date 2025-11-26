<template>
  <div class="min-h-screen flex flex-col">
    <Navbar />

    <main class="flex-1 p-6">
      <router-view />
    </main>

    <footer class="border-t p-4 text-center text-gray-600 text-sm">
      © 2025 Evidenčný portál hier
    </footer>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Navbar from './components/Navbar.vue'

const router = useRouter()
const toast = useToast()

const INACTIVITY_TIMEOUT = 5 * 60 * 1000 // 5 minutes in milliseconds
let inactivityTimer = null

const resetInactivityTimer = () => {
  // Clear existing timer
  if (inactivityTimer) {
    clearTimeout(inactivityTimer)
  }

  // Only set timer if user is logged in
  const token = localStorage.getItem('access_token')
  if (!token) {
    return
  }

  // Set new timer
  inactivityTimer = setTimeout(() => {
    logout()
  }, INACTIVITY_TIMEOUT)
}

const logout = () => {
  // Clear stored data
  localStorage.removeItem('access_token')
  localStorage.removeItem('user')
  
  // Show notification
  toast.add({
    severity: 'warn',
    summary: 'Automatické odhlásenie',
    detail: 'Boli ste odhlásení kvôli neaktivite.',
    life: 5000
  })

  // Redirect to login
  router.push('/login')
}

const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']

onMounted(() => {
  // Set initial timer
  resetInactivityTimer()

  // Add event listeners for user activity
  activityEvents.forEach(event => {
    window.addEventListener(event, resetInactivityTimer)
  })
})

onUnmounted(() => {
  // Clean up timer
  if (inactivityTimer) {
    clearTimeout(inactivityTimer)
  }

  // Remove event listeners
  activityEvents.forEach(event => {
    window.removeEventListener(event, resetInactivityTimer)
  })
})
</script>
