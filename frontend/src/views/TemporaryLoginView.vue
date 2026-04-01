<template>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="auth-state">
        <h2 class="auth-title">Presmerovanie...</h2>
        <p class="auth-text">
          Prihlásenie s dočasným heslom je teraz súčasťou hlavnej prihlasovacej stránky. Budeš presmerovaný o chvíľu...
        </p>
      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()

let redirectTimer = null

onMounted(() => {
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Prihlásenie s dočasným heslom je teraz na hlavnej prihlasovacej stránke.',
    life: 3000,
  })
  redirectTimer = setTimeout(() => {
    router.push('/login')
  }, 2000)
})

onUnmounted(() => {
  if (redirectTimer) {
    clearTimeout(redirectTimer)
  }
})
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
  margin-bottom: 0;
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

@media (max-width: 640px) {
  .auth-shell {
    padding: 64px 16px 96px;
  }

  .auth-card {
    padding: 24px 20px;
  }
}
</style>
