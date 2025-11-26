<template>
  <div class="max-w-md mx-auto p-6 mt-12">
    <Card>
      <template #header>
        <div class="text-center pt-6">
          <i class="pi pi-info-circle text-4xl text-blue-500"></i>
        </div>
      </template>

      <template #title>
        <h2 class="text-center">Presmerovanie...</h2>
      </template>

      <template #content>
        <Message severity="info" :closable="false">
          Prihlásenie s dočasným heslom je teraz súčasťou hlavnej prihlasovacej stránky. Budeš presmerovaný o chvíľu...
        </Message>
      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()

onMounted(() => {
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Prihlásenie s dočasným heslom je teraz na hlavnej prihlasovacej stránke.',
    life: 3000,
  })
  setTimeout(() => {
    router.push('/login')
  }, 2000)
})
</script>
