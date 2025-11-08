<template>
  <nav class="border-b p-4 flex justify-between items-center">
    <div class="flex items-center gap-6">
      <RouterLink to="/" class="text-lg font-semibold hover:underline">Domov</RouterLink>
      <RouterLink to="/add" class="text-lg font-semibold hover:underline">Pridať hru</RouterLink>
    </div>

    <RouterLink to="/login">
      <Button label="Prihlásiť sa" icon="pi pi-user" class="p-button-outlined p-button-sm" />
    </RouterLink>
  </nav>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import Button from 'primevue/button'
</script>
