<template>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="auth-header">
        <h2 class="auth-title">{{ success ? t('auth.password_changed_title') : t('auth.reset_title') }}</h2>
        <p class="auth-subtitle" v-if="!success">{{ t('auth.reset_subtitle') }}</p>
      </div>

      <div v-if="loading" class="auth-state">
        {{ t('auth.verifying_token') }}
      </div>

      <div v-else-if="success" class="auth-state">
        <p class="auth-text">
          {{ t('auth.password_changed_long') }}
        </p>
        <Button
          :label="t('auth.sign_in_btn')"
          @click="router.replace('/login')"
          class="steam-btn steam-btn-accent auth-btn-block"
        />
      </div>

      <div v-else-if="tokenError" class="auth-state">
        <div class="auth-alert auth-alert-danger">
          <div>
            <strong>{{ t('auth.token_failed_title') }}</strong>
            <p>{{ tokenError }}</p>
          </div>
        </div>
        <div class="auth-field">
          <label class="auth-label">{{ t('auth.recovery_email_label') }}</label>
          <InputText
            v-model="resendEmail"
            type="email"
            class="dlg-input"
            placeholder="1234567@ucm.sk"
            :disabled="resendLoading || resendCooldown > 0"
          />
        </div>
        <div class="auth-actions">
          <Button
          :label="t('auth.resend_btn')"
            @click="resendResetEmail"
            class="steam-btn steam-btn-accent auth-btn-block"
            :disabled="resendLoading || resendCooldown > 0 || !resendEmail"
            :loading="resendLoading"
          />
          <Button
          :label="t('auth.go_to_request_btn')"
            @click="router.push('/forgot-password')"
            class="steam-btn steam-btn-dark auth-btn-block"
          />
        </div>
        <div v-if="resendCooldown > 0" class="auth-hint auth-hint-center">
          {{ t('auth.cooldown_hint', { n: resendCooldown }) }}
        </div>
      </div>

      <form v-else @submit.prevent="resetPassword" class="auth-form">
        <div class="auth-field">
          <label class="auth-label">{{ t('auth.new_password_label') }}</label>
          <InputText
            v-model="password"
            type="password"
            class="dlg-input"
            placeholder="Aspoň 8 znakov"
            required
            minlength="8"
          />
        </div>
        <div class="auth-field">
          <label class="auth-label">{{ t('auth.confirm_password_label') }}</label>
          <InputText
            v-model="passwordConfirm"
            type="password"
            class="dlg-input"
            placeholder="Zadaj heslo znova"
            required
          />
        </div>
        <Button
          type="submit"
          :label="t('auth.reset_btn')"
          class="steam-btn steam-btn-accent auth-btn-block"
          :loading="submitting"
          :disabled="submitting"
        />
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const API_URL = import.meta.env.VITE_API_URL
const route = useRoute()
const router = useRouter()
const toast = useToast()
const { t } = useI18n()

const loading = ref(true)
const success = ref(false)
const tokenError = ref('')
const password = ref('')
const passwordConfirm = ref('')
const submitting = ref(false)
const token = ref('')
const resendEmail = ref('')
const resendLoading = ref(false)
const resendCooldown = ref(0)
let cooldownTimer = null

onMounted(() => {
  token.value = route.query.token
  // Prefill email from query if present (link contains email)
  const emailQuery = route.query.email
  if (typeof emailQuery === 'string') {
    resendEmail.value = emailQuery
  }

  if (!token.value || token.value.length !== 64) {
    tokenError.value = 'Neplatný odkaz na reset hesla.'
    loading.value = false
  } else {
    // Token looks valid, show form
    loading.value = false
  }
})

async function resetPassword() {
  if (password.value !== passwordConfirm.value) {
    toast.add({
      severity: 'error',
      summary: t('toast.error'),
      detail: t('auth.passwords_mismatch'),
      life: 3000,
    })
    return
  }

  if (password.value.length < 8) {
    toast.add({
      severity: 'error',
      summary: t('toast.error'),
      detail: t('auth.password_too_short'),
      life: 3000,
    })
    return
  }

  submitting.value = true

  try {
    const response = await fetch(`${API_URL}/api/reset-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        token: token.value,
        password: password.value,
        password_confirmation: passwordConfirm.value,
      }),
    })

    const data = await response.json()

    if (response.ok) {
      success.value = true
      toast.add({
        severity: 'success',
        summary: t('toast.success'),
        detail: data.message || 'Heslo bolo zmenené',
        life: 4000,
      })
    } else {
      tokenError.value = data.message || 'Nepodarilo sa zmeniť heslo'
      toast.add({
        severity: 'error',
        summary: t('toast.error'),
        detail: data.message || 'Nepodarilo sa zmeniť heslo',
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
    submitting.value = false
  }
}

async function resendResetEmail() {
  if (!resendEmail.value || !/^[0-9]{7}@ucm\.sk$/.test(resendEmail.value)) {
    toast.add({
      severity: 'warn',
      summary: 'Neplatný email',
      detail: 'Email musí byť v tvare 7 číslic@ucm.sk (napr. 1234567@ucm.sk)',
      life: 4000,
    })
    return
  }

  resendLoading.value = true
  try {
    const response = await fetch(`${API_URL}/api/forgot-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: resendEmail.value }),
    })

    const data = await response.json()

    if (response.ok) {
      toast.add({
        severity: 'success',
        summary: 'Email odoslaný',
        detail: data.message || 'Odoslali sme ti nový email na obnovu hesla. Skontroluj schránku (aj SPAM).',
        life: 5000,
      })
      // Start cooldown to match backend throttle (60s minimal)
      resendCooldown.value = Math.max(60, data.retry_after || 60)
      if (cooldownTimer) clearInterval(cooldownTimer)
      cooldownTimer = setInterval(() => {
        resendCooldown.value = Math.max(0, resendCooldown.value - 1)
        if (resendCooldown.value === 0) {
          clearInterval(cooldownTimer)
          cooldownTimer = null
        }
      }, 1000)
    } else if (response.status === 429) {
      // Rate limited - extract retry-after from header
      const retryAfter = response.headers.get('retry-after')
      const cooldownSeconds = retryAfter ? parseInt(retryAfter, 10) : 60
      
      resendCooldown.value = cooldownSeconds
      if (cooldownTimer) clearInterval(cooldownTimer)
      cooldownTimer = setInterval(() => {
        resendCooldown.value = Math.max(0, resendCooldown.value - 1)
        if (resendCooldown.value === 0) {
          clearInterval(cooldownTimer)
          cooldownTimer = null
        }
      }, 1000)
      
      toast.add({
        severity: 'warn',
        summary: 'Príliš veľa pokusov',
        detail: `Príliš veľa pokusov. Skús znova o ${cooldownSeconds} sekúnd.`,
        life: 5000,
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Chyba',
        detail: data.message || 'Nepodarilo sa odoslať email',
        life: 4000,
      })
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Chyba siete',
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

.auth-state {
  text-align: center;
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

.auth-text {
  color: var(--color-text-muted);
  margin-bottom: 16px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.auth-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  text-align: left;
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

.auth-btn-block {
  width: 100%;
}

.auth-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.auth-alert {
  display: flex;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 4px;
  margin-bottom: 14px;
  font-size: 0.85rem;
  text-align: left;
}

.auth-alert strong {
  display: block;
  margin-bottom: 4px;
}

.auth-alert p {
  margin: 0;
  opacity: 0.85;
}

.auth-alert-danger {
  background: rgba(var(--color-danger-rgb), 0.12);
  border: 1px solid rgba(var(--color-danger-rgb), 0.3);
  color: var(--color-danger);
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
