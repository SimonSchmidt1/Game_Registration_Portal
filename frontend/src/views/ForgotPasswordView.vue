<template>
  <div class="max-w-md mx-auto p-6 mt-12">
    <Card>
      <template #header>
        <div class="text-center pt-6">
          <i class="pi pi-lock text-4xl text-blue-500"></i>
        </div>
      </template>

      <template #title>
        <h2 class="text-center">Zabudnuté heslo</h2>
      </template>

      <template #content>
        <p class="text-center text-gray-600 mb-6">
          Zadaj svoj UCM e-mail a pošleme ti odkaz na reset hesla.
        </p>

        <form @submit.prevent="sendResetLink">
          <div class="mb-4">
            <label class="block mb-2 font-medium">Email</label>
            <InputText 
              v-model="email" 
              type="email" 
              class="w-full" 
              placeholder="1234567@ucm.sk"
              pattern="[0-9]{7}@ucm\.sk"
              title="Email musí byť v tvare: 7 číslic@ucm.sk"
              required
              :disabled="loading"
            />
          </div>

          <Button 
            type="submit" 
            label="Poslať odkaz na reset" 
            icon="pi pi-send" 
            class="w-full mb-4"
            :loading="loading"
          />

          <div class="text-center">
            <router-link to="/login" class="text-blue-500 hover:underline text-sm">
              <i class="pi pi-arrow-left mr-1"></i> Späť na prihlásenie
            </router-link>
          </div>
        </form>

        <Message v-if="successMessage" severity="success" :closable="false" class="mt-4">
          {{ successMessage }}
        </Message>
      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Toast from 'primevue/toast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const toast = useToast()

const email = ref('')
const loading = ref(false)
const successMessage = ref('')

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
        summary: 'Email odoslaný',
        detail: 'Skontroluj svoju schránku',
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
    loading.value = false
  }
}
</script>
