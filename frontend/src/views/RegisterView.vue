<template>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="auth-header">
        <h2 class="auth-title">{{ t('auth.register_title') }}</h2>
        <p class="auth-subtitle">Vytvor si nový účet v portáli.</p>
      </div>

      <form class="auth-form" @submit.prevent="register">
        <div class="auth-field">
          <label class="auth-label">{{ t('auth.name_label') }}</label>
          <InputText v-model="name" class="dlg-input" required />
        </div>

        <div class="auth-field">
          <label class="auth-label">Email (UCM študentský)</label>
          <InputText
            v-model="email"
            type="email"
            class="dlg-input"
            placeholder="1234567@ucm.sk"
            @input="email = email.trim()"
            pattern="[0-9]{7}@ucm\.sk"
            title="Email musí byť v tvare: 7 číslic@ucm.sk (napr. 1234567@ucm.sk)"
            required 
          />
          <span class="auth-hint">Formát: 7 číslic@ucm.sk</span>
        </div>

        <div class="auth-field">
          <label class="auth-label">{{ t('auth.student_type_label') }} <span class="auth-required">*</span></label>
          <Dropdown 
            v-model="studentType" 
            :options="studentTypes" 
            optionLabel="label" 
            optionValue="value" 
            placeholder="Vyberte typ študenta" 
            class="dlg-dropdown" 
            required
          />
        </div>

        <div class="auth-field">
          <label class="auth-label">{{ t('auth.password_label') }}</label>
          <InputText v-model="password" type="password" class="dlg-input" required />
        </div>

        <div class="auth-field">
          <label class="auth-label">{{ t('auth.confirm_password_label') }}</label>
          <InputText v-model="password_confirmation" type="password" class="dlg-input" required />
        </div>

        <Button type="submit" :label="t('auth.register_btn')" class="steam-btn steam-btn-accent auth-btn-block" />
      </form>
    </div>

    <Toast />
  </div>
</template>

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

.auth-required {
  color: var(--color-danger);
}

.auth-hint {
  font-size: 0.78rem;
  color: var(--color-text-subtle);
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

/* ═══════════════════════════════════════════════════════════ */
/* PRIMEVUE OVERRIDES                                         */
/* ═══════════════════════════════════════════════════════════ */
:deep(.dlg-dropdown) {
  background: var(--color-elevated) !important;
  border: 1px solid var(--color-border) !important;
  border-radius: 3px !important;
  min-width: 200px;
}

:deep(.dlg-dropdown .p-select-label),
:deep(.dlg-dropdown .p-dropdown-label) {
  color: var(--color-text) !important;
  font-size: 0.85rem;
}

:deep(.dlg-dropdown:not(.p-disabled):hover) {
  border-color: var(--color-border-strong) !important;
}

:deep(.dlg-dropdown:not(.p-disabled).p-focus) {
  border-color: var(--color-accent) !important;
  box-shadow: none !important;
}

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

  :deep(.dlg-dropdown) {
    min-width: 0;
    width: 100%;
  }
}
</style>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Toast from 'primevue/toast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const toast = useToast()
const { t } = useI18n()

const name = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const studentType = ref(null)

let redirectTimer = null

onUnmounted(() => {
  if (redirectTimer) {
    clearTimeout(redirectTimer)
  }
})

const studentTypes = computed(() => [
  { label: t('auth.full_time'), value: 'denny' },
  { label: t('auth.part_time'), value: 'externy' }
])

async function register() {
  if (!studentType.value) {
    toast.add({
      severity: 'warn',
      summary: 'Chýba typ študenta',
      detail: 'Vyberte typ študenta.',
      life: 4000
    })
    return
  }

  try {
    const response = await axios.post(`${API_URL}/api/register`, {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
      student_type: studentType.value
    })

    // Redirect immediately to verify-email page.
    // If the account existed but was unverified, server re-sends the token and flags resent=true.
    const resent = response.data?.resent === true
    router.push(`/verify-email?email=${encodeURIComponent(email.value)}${resent ? '&resent=true' : ''}`)

  } catch (error) {
    const errorMessage = error.response?.data?.errors 
      ? Object.values(error.response.data.errors).flat().join(', ')
      : error.response?.data?.message || 'Chyba pri registrácii ❌'

    toast.add({
      severity: 'error',
      summary: t('toast.error'),
      detail: errorMessage,
      life: 5000
    })
  }
}
</script>
