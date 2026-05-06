<template>
  <div class="auth-shell">
    <div class="auth-card">
      <div v-if="isResentMode" class="auth-state">
        <h2 class="auth-title">Overte svoju e-mailovú adresu</h2>
        <p class="auth-text">
          Na vašu e-mailovú adresu <span v-if="pendingEmail"><strong>{{ pendingEmail }}</strong></span>
          sme odoslali overovací e-mail.
          Kliknutím na odkaz v e-maili si aktivujete svoj účet.
        </p>
        <p class="auth-text auth-text-warning">
          Doručenie e-mailu je pomalé, trvá minimálne 3 minúty. Skontrolujte aj priečinok <strong>spam</strong>.
        </p>

        <!-- Resend block -->
        <div class="resend-block">
          <p class="auth-text" style="margin-bottom: 8px; font-size: 0.85rem;">E-mail neprišiel ani po pár minútach?</p>
          <Button
            :label="resendCooldown > 0 ? `Odoslať znova za ${resendCooldown}s` : 'Odoslať overovací e-mail znova'"
            class="steam-btn steam-btn-dark auth-btn-block"
            :disabled="resendCooldown > 0 || resending"
            :loading="resending"
            @click="resendEmail"
            style="margin-bottom: 8px;"
          />
          <p class="resend-note">Opätovné odoslanie je možné raz za minútu, maximálne 5-krát za hodinu.</p>
          <p v-if="resendMessage" class="resend-msg" :class="resendSuccess ? 'resend-msg-ok' : 'resend-msg-err'">
            {{ resendMessage }}
          </p>
        </div>

        <Button
          label="Späť na prihlásenie"
          class="steam-btn steam-btn-dark auth-btn-block"
          @click="goToLogin"
        />
      </div>

      <!-- NEW: Two-step verification — show button instead of auto-verifying -->
      <div v-else-if="awaitingClick" class="auth-state">
        <h2 class="auth-title">Overenie e-mailovej adresy</h2>
        <p class="auth-text">Kliknite na tlačidlo nižšie pre dokončenie overenia vášho účtu.</p>
        <Button
          label="✓ Overiť môj účet"
          class="steam-btn steam-btn-accent auth-btn-block"
          @click="doVerify"
          :loading="loading"
          :disabled="loading"
          style="margin-bottom: 12px; font-size: 1rem; padding: 14px 24px;"
        />
        <p class="auth-text" style="font-size: 0.8rem; color: var(--color-text-muted);">
          Ak ste si nevytvorili účet, tento odkaz môžete ignorovať.
        </p>
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

const loading = ref(false)
const success = ref(false)
const error = ref(false)
const errorMessage = ref('')
const isResentMode = ref(false)
const awaitingClick = ref(false)

// Resend state
const resending = ref(false)
const resendCooldown = ref(0)
const resendMessage = ref('')
const resendSuccess = ref(false)
const pendingEmail = ref('')

// Store token from URL
const verificationToken = ref('')

let redirectTimer = null
let cooldownTimer = null

onUnmounted(() => {
  if (redirectTimer) clearTimeout(redirectTimer)
  if (cooldownTimer) clearInterval(cooldownTimer)
})

onMounted(() => {
  const token = route.query.token
  const resent = route.query.resent

  // Capture email from query for resend functionality
  if (route.query.email) {
    pendingEmail.value = decodeURIComponent(route.query.email)
  }

  // If resent=true, just show the "check your email" message
  if (resent === 'true') {
    isResentMode.value = true
    return
  }

  if (!token) {
    // Show "check your email" message instead of error
    isResentMode.value = true
    return
  }

  // Store the token and show the verify button (two-step)
  // This prevents email client link scanners from auto-consuming the token
  verificationToken.value = token
  awaitingClick.value = true
})

async function doVerify() {
  if (!verificationToken.value || loading.value) return

  loading.value = true
  awaitingClick.value = true  // keep showing same view with loading state

  try {
    console.log('🔄 Verifying email with token:', verificationToken.value)
    
    const response = await fetch(`${API_URL}/api/verify-email`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ token: verificationToken.value })
    })
    
    const data = await response.json()
    console.log('Response status:', response.status, 'Data:', data)
    
    if (!response.ok) {
      throw new Error(data.message || 'Verification failed')
    }
    
    console.log('✅ Verification successful:', data)
    
    loading.value = false
    awaitingClick.value = false
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
    awaitingClick.value = false
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
}

async function resendEmail() {
  if (!pendingEmail.value) {
    resendMessage.value = 'Email adresa nie je známa. Skúste sa znova zaregistrovať.'
    resendSuccess.value = false
    return
  }

  resending.value = true
  resendMessage.value = ''

  try {
    const res = await fetch(`${API_URL}/api/resend-verification`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email: pendingEmail.value })
    })

    if (res.status === 429) {
      resendMessage.value = 'Dosiahli ste limit odosielania. Skúste to o chvíľu alebo o hodinu.'
      resendSuccess.value = false
    } else {
      resendMessage.value = 'E-mail bol odoslaný. Skontrolujte schránku aj spam.'
      resendSuccess.value = true
      // Start 60s cooldown
      resendCooldown.value = 60
      cooldownTimer = setInterval(() => {
        resendCooldown.value--
        if (resendCooldown.value <= 0) {
          clearInterval(cooldownTimer)
          resendCooldown.value = 0
        }
      }, 1000)
    }
  } catch {
    resendMessage.value = 'Chyba pri odosielaní. Skúste to neskôr.'
    resendSuccess.value = false
  } finally {
    resending.value = false
  }
}

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

.auth-text-warning {
  color: var(--color-text);
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.55;
  background: rgba(var(--color-warning-rgb, 200, 140, 40), 0.08);
  border: 1px solid rgba(var(--color-warning-rgb, 200, 140, 40), 0.25);
  border-radius: 6px;
  padding: 10px 14px;
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

.resend-block {
  margin-bottom: 16px;
  padding: 14px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 6px;
}

.resend-note {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin: 0 0 8px;
  text-align: center;
}

.resend-msg {
  font-size: 0.82rem;
  margin: 4px 0 0;
  text-align: center;
}

.resend-msg-ok { color: #4ade80; }
.resend-msg-err { color: #f87171; }

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
