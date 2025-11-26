<template>
  <div class="max-w-md mx-auto p-6 mt-12">
    <Card>
      <template #header>
        <div class="text-center pt-6">
          <i v-if="loading" class="pi pi-spin pi-spinner text-4xl text-blue-500"></i>
          <i v-else-if="success" class="pi pi-check-circle text-4xl text-green-500"></i>
          <i v-else class="pi pi-key text-4xl text-blue-500"></i>
        </div>
      </template>

      <template #title>
        <h2 class="text-center">
          {{ success ? 'Heslo zmenené' : 'Nové heslo' }}
        </h2>
      </template>

      <template #content>
        <div v-if="loading" class="text-center text-gray-600">
          Overujem token...
        </div>

        <div v-else-if="success" class="text-center">
          <p class="mb-4 text-gray-700">
            Tvoje heslo bolo úspešne zmenené. Môžeš sa teraz prihlásiť s novým heslom.
          </p>
          <Button 
            label="Prihlásiť sa" 
            icon="pi pi-sign-in" 
            @click="router.push('/login')"
            class="w-full"
          />
        </div>

        <div v-else-if="tokenError" class="text-center">
          <Message severity="error" :closable="false" class="mb-4">
            {{ tokenError }}
          </Message>
          <Button 
            label="Požiadať o nový odkaz" 
            icon="pi pi-refresh" 
            @click="router.push('/forgot-password')"
            class="w-full"
          />
        </div>

        <form v-else @submit.prevent="resetPassword">
          <div class="mb-4">
            <label class="block mb-2 font-medium">Nové heslo</label>
            <InputText 
              v-model="password" 
              type="password" 
              class="w-full" 
              placeholder="Aspoň 8 znakov"
              required
              minlength="8"
            />
          </div>

          <div class="mb-6">
            <label class="block mb-2 font-medium">Potvrdiť heslo</label>
            <InputText 
              v-model="passwordConfirm" 
              type="password" 
              class="w-full" 
              placeholder="Zadaj heslo znova"
              required
            />
          </div>

          <Button 
            type="submit" 
            label="Zmeniť heslo" 
            icon="pi pi-check" 
            class="w-full"
            :loading="submitting"
          />
        </form>
      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Toast from 'primevue/toast'

const API_URL = import.meta.env.VITE_API_URL
const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(true)
const success = ref(false)
const tokenError = ref('')
const password = ref('')
const passwordConfirm = ref('')
const submitting = ref(false)
const token = ref('')

onMounted(() => {
  token.value = route.query.token

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
      summary: 'Chyba',
      detail: 'Heslá sa nezhodujú',
      life: 3000,
    })
    return
  }

  if (password.value.length < 8) {
    toast.add({
      severity: 'error',
      summary: 'Chyba',
      detail: 'Heslo musí mať aspoň 8 znakov',
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
        summary: 'Úspech',
        detail: data.message || 'Heslo bolo zmenené',
        life: 4000,
      })
    } else {
      tokenError.value = data.message || 'Nepodarilo sa zmeniť heslo'
      toast.add({
        severity: 'error',
        summary: 'Chyba',
        detail: data.message || 'Nepodarilo sa zmeniť heslo',
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
    submitting.value = false
  }
}
</script>
