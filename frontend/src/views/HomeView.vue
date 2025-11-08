<template>
  <div class="max-w-6xl mx-auto">
    <h2 class="text-2xl font-semibold mb-4">Zoznam registrovaných hier</h2>

    <!-- Filter -->
    <div class="flex flex-wrap gap-4 mb-6">
      <span class="p-float-label">
        <InputText id="search" v-model="search" />
        <label for="search">Vyhľadať podľa názvu</label>
      </span>

      <Dropdown
        v-model="selectedCategory"
        :options="categories"
        optionLabel="name"
        placeholder="Kategória"
      />
    </div>

    <!-- Mriežka hier -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      <div
        v-for="game in filteredGames"
        :key="game.name"
        class="border rounded-lg p-4 shadow-sm hover:shadow-md transition"
      >
        <div class="aspect-video bg-gray-200 flex items-center justify-center text-gray-500">
          🎮 Náhľad videa
        </div>

        <h3 class="text-lg font-semibold mt-3">{{ game.name }}</h3>
        <p class="text-sm text-gray-600">{{ game.category }}</p>
        <p class="text-sm text-gray-500 mt-1">{{ game.releaseDate }}</p>
        <p class="text-gray-700 text-sm mt-2">{{ game.description }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const search = ref('')
const selectedCategory = ref(null)
const categories = ref([
  { name: 'Akčná' },
  { name: 'Strategická' },
  { name: 'RPG' },
  { name: 'Simulátor' },
])

const games = ref([
  { name: 'CyberQuest', category: 'RPG', releaseDate: '2023-10-01', description: 'Futuristické RPG v rozľahlom svete' },
  { name: 'FarmMaster', category: 'Simulátor', releaseDate: '2024-03-15', description: 'Oddychový simulátor farmy' },
  { name: 'BattleCore', category: 'Akčná', releaseDate: '2025-01-10', description: 'Multiplayer akčná strieľačka' },
  { name: 'CityBuilder', category: 'Strategická', releaseDate: '2023-07-20', description: 'Buduj a spravuj svoje mesto' },
  { name: 'DungeonRun', category: 'RPG', releaseDate: '2024-09-05', description: 'Dobrodružná RPG výprava' },
])

const filteredGames = computed(() => {
  return games.value.filter(
    (g) =>
      g.name.toLowerCase().includes(search.value.toLowerCase()) &&
      (!selectedCategory.value || g.category === selectedCategory.value.name)
  )
})
</script>
