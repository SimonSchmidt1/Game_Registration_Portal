<template>
  <div class="min-h-screen flex flex-col">
    <Navbar />

    <main class="flex-1">
      <router-view />
    </main>

    <footer ref="footerEl" class="app-footer">
      <div class="footer-inner">

        <!-- University info -->
        <div class="footer-uni">
          <p class="footer-uni-name">Univerzita sv. Cyrila a Metoda v Trnave</p>
          <p class="footer-uni-sub">Fakulta prírodných vied &nbsp;·&nbsp; Nám. J. Herdu 2, 917 01 Trnava</p>
        </div>

      </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Navbar from './components/Navbar.vue'

const router = useRouter()
const toast = useToast()
const footerEl = ref(null)

let footerGlowTimer = null
function onScroll() {
  if (!footerEl.value) return
  footerEl.value.classList.add('footer-glow')
  clearTimeout(footerGlowTimer)
  footerGlowTimer = setTimeout(() => {
    if (footerEl.value) footerEl.value.classList.remove('footer-glow')
  }, 600)
}

const INACTIVITY_TIMEOUT = 5 * 60 * 1000 // 5 minutes in milliseconds
let inactivityTimer = null

const logout = () => {
  localStorage.removeItem('access_token')
  localStorage.removeItem('user')
  localStorage.removeItem('last_activity')
  window.dispatchEvent(new Event('logout'))
  toast.add({
    severity: 'warn',
    summary: 'Automatické odhlásenie',
    detail: 'Boli ste odhlásení kvôli neaktivite.',
    life: 5000
  })
  router.push('/login')
}

const checkInactivity = () => {
  if (!localStorage.getItem('access_token')) return
  const last = parseInt(localStorage.getItem('last_activity') || '0', 10)
  if (last && Date.now() - last >= INACTIVITY_TIMEOUT) {
    logout()
  }
}

const resetInactivityTimer = () => {
  if (!localStorage.getItem('access_token')) return
  localStorage.setItem('last_activity', Date.now().toString())
  clearTimeout(inactivityTimer)
  inactivityTimer = setTimeout(logout, INACTIVITY_TIMEOUT)
}

const onVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    checkInactivity()
    if (localStorage.getItem('access_token')) {
      resetInactivityTimer()
    }
  }
}

const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']

onMounted(() => {
  // Check if already expired from a previous closed tab
  checkInactivity()

  resetInactivityTimer()

  activityEvents.forEach(event => window.addEventListener(event, resetInactivityTimer))
  document.addEventListener('visibilitychange', onVisibilityChange)
  window.addEventListener('scroll', onScroll)
})

onUnmounted(() => {
  clearTimeout(inactivityTimer)
  clearTimeout(footerGlowTimer)
  activityEvents.forEach(event => window.removeEventListener(event, resetInactivityTimer))
  document.removeEventListener('visibilitychange', onVisibilityChange)
  window.removeEventListener('scroll', onScroll)
})
</script>
