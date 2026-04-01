<template>
  <div class="auth-shell">
    <div class="auth-card">
      <div v-if="isResentMode" class="auth-state">
        <h2 class="auth-title">Skontrolujte svoj email</h2>
        <p class="auth-text">
          Príliš veľa neúspešných pokusov o prihlásenie. Overovací e-mail bol odoslaný znova na vašu emailovú adresu.
        </p>
        <p class="auth-text">
          Kliknite na odkaz v e-maili na overenie účtu a potom sa môžete prihlásiť.
        </p>
        <Button 
          label="Späť na prihlásenie" 
          class="steam-btn steam-btn-dark auth-btn-block" 
          @click="goToLogin"
        />
      </div>

      <div v-else-if="loading" class="auth-state">
        <h2 class="auth-title">Overujem váš email...</h2>
        <p class="auth-text">Prosím chvíľu počkajte.</p>
      </div>

      <div v-else-if="success" class="auth-state">
        <h2 class="auth-title">Email overený!</h2>
        <p class="auth-text">Váš účet bol úspešne overený. Presmerovávame vás na prihlásenie...</p>
        <Button 
          label="Prihlásiť sa teraz" 
          class="steam-btn steam-btn-accent auth-btn-block" 
          @click="goToLogin"
        />
      </div>

      <div v-else-if="error" class="auth-state">
        <h2 class="auth-title">Overenie zlyhalo</h2>
        <p class="auth-text">{{ errorMessage }}</p>
        <Button 
          label="Späť na hlavnú stránku" 
          class="steam-btn steam-btn-dark auth-btn-block" 
          @click="goToHome"
        />
      </div>
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

<style scoped>
/* ═══════════════════════════════════════════════════════════ */
/* AUTH SHELL (Steam-style)                                   */
/* ═══════════════════════════════════════════════════════════ */
.auth-shell {
  max-width: 520px;
  margin: 0 auto;
  padding: 84px 24px 120px;
}

.auth-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 32px 28px;
  box-shadow: 0 14px 40px rgba(10, 18, 26, 0.35);
}

.auth-state {
  text-align: center;
}

.auth-title {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 10px 0 10px;
}

.auth-text {
  color: var(--color-text-muted);
  font-size: 0.92rem;
  line-height: 1.6;
  margin-bottom: 14px;
}

.auth-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(var(--color-accent-rgb), 0.12);
  color: var(--color-accent);
  font-size: 1.8rem;
}

.auth-icon-warn {
  background: rgba(var(--color-warning-rgb), 0.12);
  color: var(--color-warning);
}

.auth-icon-success {
  background: rgba(var(--color-accent-rgb), 0.12);
  color: var(--color-accent);
}

.auth-icon-danger {
  background: rgba(var(--color-danger-rgb), 0.12);
  color: var(--color-danger);
}

.auth-btn-block {
  width: 100%;
}

/* ═══════════════════════════════════════════════════════════ */
/* BUTTONS (Steam-flat style)                                 */
/* ═══════════════════════════════════════════════════════════ */
.steam-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 3px;
  border: none;
  cursor: pointer;
  transition: background 0.12s, color 0.12s, opacity 0.12s;
  white-space: nowrap;
  line-height: 1.4;
}

.steam-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.steam-btn-accent {
  background: var(--color-accent);
  color: var(--color-accent-contrast);
}

.steam-btn-accent:hover:not(:disabled) {
  background: var(--color-accent-hover);
}

.steam-btn-dark {
  background: var(--color-border);
  color: var(--color-text);
}

.steam-btn-dark:hover:not(:disabled) {
  background: var(--color-border-strong);
  color: var(--color-text-strong);
}

@media (max-width: 640px) {
  .auth-shell {
    padding: 64px 16px 96px;
  }

  .auth-card {
    padding: 24px 20px;
  }
}
</style>
