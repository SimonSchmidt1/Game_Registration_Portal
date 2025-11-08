<template>
  <div class="max-w-2xl mx-auto mt-10 border rounded-xl p-6 shadow-sm">
    <h2 class="text-2xl font-semibold mb-6 text-center">Pridať novú hru</h2>

    <form @submit.prevent="submitForm" class="flex flex-col gap-5">
      <!-- Názov hry -->
      <div>
        <label class="block mb-1 font-medium">Názov hry</label>
        <InputText v-model="name" placeholder="Zadajte názov hry" class="w-full" required />
      </div>

      <!-- Kategória -->
      <div>
        <label class="block mb-1 font-medium">Kategória</label>
        <Dropdown
          v-model="selectedCategory"
          :options="categories"
          optionLabel="name"
          placeholder="Vyberte kategóriu"
          class="w-full"
          required
        />
      </div>

      <!-- Dátum vydania -->
      <div>
        <label class="block mb-1 font-medium">Dátum vydania</label>
        <Calendar v-model="releaseDate" dateFormat="yy-mm-dd" showIcon class="w-full" required />
      </div>

      <!-- Popis hry -->
      <div>
        <label class="block mb-1 font-medium">Popis</label>
        <Textarea
          v-model="description"
          rows="4"
          placeholder="Stručne opíšte svoju hru"
          class="w-full"
          autoResize
          required
        />
      </div>

      <!-- Trailer (upload alebo YouTube link) -->
<div class="border-t pt-4">
  <h3 class="text-lg font-semibold mb-2">Trailer k hre</h3>

  <!-- Tab-like prepínač -->
  <div class="flex items-center gap-2 mb-4">
    <button
      type="button"
      :class="['px-4 py-2 rounded-tr rounded-br border', videoType === 'upload' ? 'font-semibold' : 'opacity-70']"
      @click="videoType = 'upload'"
    >
      Nahrať súbor
    </button>

    <button
      type="button"
      :class="['px-4 py-2 rounded-tl rounded-bl border', videoType === 'url' ? 'font-semibold' : 'opacity-70']"
      @click="videoType = 'url'"
    >
      YouTube link
    </button>
  </div>

  <!-- YouTube link (zobrazí sa iba ak videoType === 'url') -->
  <div v-if="videoType === 'url'">
    <InputText
      v-model="videoUrl"
      placeholder="https://www.youtube.com/watch?v=..."
      class="w-full"
    />
  </div>

  <!-- Upload súbor (zobrazí sa iba ak videoType === 'upload') -->
  <div v-if="videoType === 'upload'">
    <FileUpload
      name="video"
      mode="basic"
      accept="video/*"
      chooseLabel="Vybrať súbor"
      @select="onVideoSelect"
      @clear="onVideoClear"
    />
    <p v-if="uploadedVideoName" class="text-sm mt-2 text-gray-600">
      Nahratý súbor: <strong>{{ uploadedVideoName }}</strong>
    </p>
  </div>
</div>


      <!-- Tlačidlo Odoslať -->
      <div class="mt-4">
        <Button type="submit" label="Zverejniť hru" icon="pi pi-check" class="w-full" />
      </div>

      <p v-if="message" class="text-green-600 text-center mt-4">{{ message }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'
import Tabs from 'primevue/tabs'
import TabPanel from 'primevue/tabpanel'

const name = ref('')
const videoType = ref('upload')
const selectedCategory = ref(null)
const releaseDate = ref(null)
const description = ref('')
const videoUrl = ref('')
const uploadedVideoName = ref('')
const message = ref('')

const categories = ref([
  { name: 'Akčná' },
  { name: 'Strategická' },
  { name: 'RPG' },
  { name: 'Simulátor' },
])

function onVideoSelect(event) {
  const file = event.files?.[0]
  if (file) {
    uploadedVideoName.value = file.name
  }
}

function onVideoClear() {
  uploadedVideoName.value = ''
}

function submitForm() {
  const game = {
    name: name.value,
    category: selectedCategory.value?.name,
    releaseDate: releaseDate.value,
    description: description.value,
    trailer: videoUrl.value || uploadedVideoName.value,
  }

  console.log('Nová hra:', game)
  message.value = 'Hra bola úspešne pridaná ✅'

  // Reset formulára
  name.value = ''
  selectedCategory.value = null
  releaseDate.value = null
  description.value = ''
  videoUrl.value = ''
  uploadedVideoName.value = ''
}
</script>

<style scoped>
.p-tabview .p-tabview-nav li .p-tabview-nav-link {
  font-weight: 500;
}
</style>
