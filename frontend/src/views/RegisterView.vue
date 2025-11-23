<template>
  <div class="max-w-sm mx-auto p-6 border rounded-lg mt-12">
    <h2 class="text-2xl font-semibold mb-6 text-center">Registrácia</h2>

    <form @submit.prevent="register">
      <div class="mb-4">
        <label class="block mb-1 font-medium">Meno</label>
        <InputText v-model="name" class="w-full" required />
      </div>

      <div class="mb-4">
        <label class="block mb-1 font-medium">Email (UCM študentský)</label>
        <InputText 
          v-model="email" 
          type="email" 
          class="w-full" 
          placeholder="1234567@ucm.sk"
          pattern="[0-9]{7}@ucm\.sk"
          title="Email musí byť v tvare: 7 číslic@ucm.sk (napr. 1234567@ucm.sk)"
          required 
        />
        <small class="text-gray-500">Formát: 7 číslic@ucm.sk</small>
      </div>

      <div class="mb-4">
        <label class="block mb-1 font-medium">Heslo</label>
        <InputText v-model="password" type="password" class="w-full" required />
      </div>

      <div class="mb-6">
        <label class="block mb-1 font-medium">Potvrdiť heslo</label>
        <InputText v-model="password_confirmation" type="password" class="w-full" required />
      </div>

      <Button type="submit" label="Registrovať sa" icon="pi pi-user-plus" class="w-full" />
    </form>

    <!-- Toast komponent -->
    <Toast />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Toast from 'primevue/toast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const toast = useToast()

const name = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')

async function register() {
  try {
    const response = await axios.post(`${API_URL}/api/register`, {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value
    })

    // Toast upozornenie
    toast.add({ 
      severity: 'success', 
      summary: 'Registrácia úspešná', 
      detail: response.data.message || 'Skontrolujte e-mail a dokončite overenie.', 
      life: 5000 
    })

    // Redirect to verify-email page (not home, since user has no token yet)
    setTimeout(() => router.push('/verify-email'), 2000)

  } catch (error) {
    const errorMessage = error.response?.data?.errors 
      ? Object.values(error.response.data.errors).flat().join(', ')
      : error.response?.data?.message || 'Chyba pri registrácii ❌'

    toast.add({
      severity: 'error',
      summary: 'Chyba',
      detail: errorMessage,
      life: 5000
    })
  }
}
</script>
