<template>
  <div class="min-h-screen flex flex-col">
    <Navbar />

    <main class="flex-1 p-6">
      <router-view />
    </main>

    <footer class="border-t p-4 text-center text-gray-600 text-sm">
      © 2025 Evidenčný portál hier
    </footer>
  </div>
</template>

<script setup>
import Navbar from './components/Navbar.vue'
</script>
