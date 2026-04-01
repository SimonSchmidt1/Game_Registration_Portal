<template>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="auth-header">
        <h2 class="auth-title">{{ t('auth.forgot_title') }}</h2>
        <p class="auth-subtitle">{{ t('auth.forgot_subtitle') }}</p>
      </div>

      <form class="auth-form" @submit.prevent="sendResetLink">
        <div class="auth-field">
          <label class="auth-label">{{ t('auth.email_label') }}</label>
          <InputText 
            v-model="email" 
            type="email" 
            class="dlg-input" 
            placeholder="1234567@ucm.sk"
            pattern="[0-9]{7}@ucm\.sk"
            title="Email musí byť v tvare: 7 číslic@ucm.sk"
            required
            :disabled="loading"
          />
        </div>

        <Button 
          type="submit" 
          :label="t('auth.forgot_send_btn')" 
          class="steam-btn steam-btn-accent auth-btn-block"
          :loading="loading"
        />

        <div class="auth-links">
          <router-link to="/login" class="auth-link">
            {{ t('common.back') }}
          </router-link>
        </div>
      </form>

      <div v-if="successMessage" class="auth-alert auth-alert-success">
        <div>
          <strong>{{ t('auth.email_sent') }}</strong>
          <p>{{ successMessage }}</p>
        </div>
      </div>

      <div v-if="successMessage" class="auth-actions">
        <Button 
          :label="t('auth.resend_btn')" 
          class="steam-btn steam-btn-accent auth-btn-block"
          @click="resendResetLink"
          :disabled="resendLoading || resendCooldown > 0"
          :loading="resendLoading"
        />
        <Button 
          :label="t('auth.back_to_login_btn')" 
          class="steam-btn steam-btn-dark auth-btn-block"
          @click="router.push('/login')"
        />
      </div>
      <div v-if="resendCooldown > 0" class="auth-hint auth-hint-center">
        {{ t('auth.cooldown_hint', { n: resendCooldown }) }}
      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Toast from 'primevue/toast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const toast = useToast()
const { t } = useI18n()

const email = ref('')
const loading = ref(false)
const successMessage = ref('')
const resendLoading = ref(false)
const resendCooldown = ref(0)
let cooldownTimer = null

async function sendResetLink() {
  loading.value = true
  successMessage.value = ''

  try {
    const response = await fetch(`${API_URL}/api/forgot-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email.value }),
    })

    const data = await response.json()

    if (response.ok) {
      successMessage.value = data.message
      toast.add({
        severity: 'success',
        summary: t('toast.success'),
        detail: 'Skontroluj svoju schránku',
        life: 5000,
      })
    } else {
      toast.add({
        severity: 'error',
        summary: t('toast.error'),
        detail: data.message || 'Nepodarilo sa odoslať email',
        life: 4000,
      })
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: t('common.network_error'),
      detail: 'Skontroluj pripojenie',
      life: 4000,
    })
  } finally {
    loading.value = false
  }
}

async function resendResetLink() {
  if (resendCooldown.value > 0 || resendLoading.value) return
  resendLoading.value = true

  try {
    const response = await fetch(`${API_URL}/api/forgot-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email.value }),
    })

    const data = await response.json()

    if (response.ok) {
      toast.add({
        severity: 'success',
        summary: t('toast.success'),
        detail: 'Odoslali sme nový email na reset hesla. Skontroluj schránku (aj SPAM).',
        life: 5000,
      })
      // Start a cooldown to match backend throttle (at least 60s)
      resendCooldown.value = Math.max(60, data.retry_after || 60)
      if (cooldownTimer) clearInterval(cooldownTimer)
      cooldownTimer = setInterval(() => {
        resendCooldown.value = Math.max(0, resendCooldown.value - 1)
        if (resendCooldown.value === 0) {
          clearInterval(cooldownTimer)
          cooldownTimer = null
        }
      }, 1000)
    } else {
      toast.add({
        severity: 'error',
        summary: t('toast.error'),
        detail: data.message || 'Nepodarilo sa odoslať email',
        life: 4000,
      })
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: t('common.network_error'),
      detail: 'Skontroluj pripojenie',
      life: 4000,
    })
  } finally {
    resendLoading.value = false
  }
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
  padding: 28px 28px 32px;
  box-shadow: 0 14px 40px rgba(10, 18, 26, 0.35);
}

.auth-header {
  text-align: center;
  margin-bottom: 22px;
}

.auth-icon {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  margin: 0 auto 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(var(--color-accent-rgb), 0.12);
  color: var(--color-accent);
  font-size: 1.4rem;
}

.auth-title {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 6px;
}

.auth-subtitle {
  color: var(--color-text-muted);
  font-size: 0.9rem;
  margin: 0;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 18px;
}

.auth-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.auth-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.auth-hint {
  font-size: 0.78rem;
  color: var(--color-text-subtle);
}

.auth-hint-center {
  text-align: center;
  margin-top: 8px;
}

.auth-links {
  text-align: center;
}

.auth-link {
  color: var(--color-accent);
  font-size: 0.85rem;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: color 0.15s;
}

.auth-link:hover {
  color: var(--color-accent-hover);
  text-decoration: underline;
}

.auth-btn-block {
  width: 100%;
}

.auth-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 14px;
}

.auth-alert {
  display: flex;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 4px;
  margin-top: 14px;
  font-size: 0.85rem;
}

.auth-alert strong {
  display: block;
  margin-bottom: 4px;
}

.auth-alert p {
  margin: 0;
  opacity: 0.85;
}

.auth-alert-success {
  background: rgba(var(--color-accent-rgb), 0.12);
  border: 1px solid rgba(var(--color-accent-rgb), 0.3);
  color: var(--color-accent);
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

/* ═══════════════════════════════════════════════════════════ */
/* PRIMEVUE OVERRIDES                                         */
/* ═══════════════════════════════════════════════════════════ */
:deep(.dlg-input) {
  background: var(--color-elevated) !important;
  border: 1px solid var(--color-border) !important;
  border-radius: 3px !important;
  color: var(--color-text) !important;
  width: 100%;
}

:deep(.dlg-input::placeholder) {
  color: var(--color-text-subtle) !important;
}

:deep(.dlg-input:enabled:hover) {
  border-color: var(--color-border-strong) !important;
}

:deep(.dlg-input:enabled:focus) {
  border-color: var(--color-accent) !important;
  box-shadow: none !important;
}

@media (max-width: 640px) {
  .auth-shell {
    padding: 64px 16px 96px;
  }

  .auth-card {
    padding: 22px 20px 26px;
  }

  .auth-actions {
    grid-template-columns: 1fr;
  }
}
</style>
