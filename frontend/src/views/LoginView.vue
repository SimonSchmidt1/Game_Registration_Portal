<template>
  <div class="max-w-sm mx-auto p-6 border rounded-lg mt-12">
    <h2 class="text-2xl font-semibold mb-6 text-center">Prihlásenie</h2>

    <form @submit.prevent="login">
      <div class="mb-4">
        <label class="block mb-1 font-medium">Používateľské meno</label>
        <InputText v-model="username" class="w-full" required />
      </div>

      <div class="mb-6">
        <label class="block mb-1 font-medium">Heslo</label>
        <InputText v-model="password" type="password" class="w-full" required />
      </div>

      <Button label="Prihlásiť sa" icon="pi pi-sign-in" class="w-full" />
    </form>

    <p v-if="message" class="text-green-600 mt-4 text-center">{{ message }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const username = ref('')
const password = ref('')
const message = ref('')

function login() {
  if (username.value && password.value) {
    localStorage.setItem('user', username.value)
    message.value = 'Prihlásenie úspešné ✅'
    setTimeout(() => router.push('/'), 1000)
  }
}
</script>
