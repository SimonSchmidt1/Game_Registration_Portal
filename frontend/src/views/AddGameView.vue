<!-- DEPRECATED VIEW: AddGameView (use AddProjectView). Retained for rollback; do not modify. -->
<template>
  <Toast />
  <div class="steam-page steam-theme max-w-2xl mx-auto mt-10 border rounded-xl p-6 shadow-sm bg-gray-900 text-gray-100 border-gray-700">
    <h2 class="text-2xl font-semibold mb-6 text-center text-teal-400">Pridať novú hru</h2>

    <!-- Ochranný blok pred ne-Scrum Mastermi -->
    <div v-if="loadingTeam" class="text-center p-8">
        <p class="mt-4 text-gray-400">Načítavam stav tímu...</p>
    </div>
    <div v-else-if="!teamId" class="text-center p-8 bg-red-900/30 rounded-lg border border-red-800">
        <p class="mt-4 font-semibold text-red-200">Musíte byť členom tímu, aby ste mohli pridávať hry.</p>
    </div>
    <div v-else-if="!isScrumMaster" class="text-center p-8 bg-red-900/30 rounded-lg border border-red-800">
      <p class="mt-4 font-semibold text-red-200">Iba Scrum Master (vedúci tímu) môže pridať hru.</p>
    </div>

    <!-- Hlavný formulár -->
    <form v-else @submit.prevent="submitForm" class="flex flex-col gap-5">
      <!-- Názov hry -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">Názov hry</label>
        <InputText v-model="name" placeholder="Zadajte názov hry" class="w-full bg-gray-800 text-white border-gray-700" required />
      </div>

      <!-- Kategória -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">Kategória</label>
        <Dropdown
          v-model="selectedCategory"
          :options="categories"
          optionLabel="name"
          optionValue="name"
          placeholder="Vyberte kategóriu"
          class="w-full bg-gray-800 text-white border-gray-700"
          required
        />
      </div>

      <!-- Dátum vydania -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">Dátum vydania (plánovaný)</label>
        <Calendar v-model="releaseDate" dateFormat="yy-mm-dd" class="w-full bg-gray-800 text-white border-gray-700" required />
      </div>

      <!-- Popis hry -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">Popis</label>
        <Textarea
          v-model="description"
          rows="4"
          placeholder="Stručne opíšte svoju hru"
          class="w-full bg-gray-800 text-white border-gray-700"
          autoResize
          required
        />
      </div>

      <!-- Trailer (upload alebo YouTube link) -->
      <div class="border-t border-gray-700 pt-4">
        <h3 class="text-lg font-semibold mb-2 text-teal-400">Trailer k hre (Video súbor alebo YouTube link)</h3>

        <!-- Tab-like prepínač -->
        <div class="flex items-center gap-2 mb-4">
          <button
            type="button"
            :class="['px-4 py-2 rounded-lg border border-gray-700 transition-colors', videoType === 'upload' ? 'bg-teal-600 text-white hover:bg-teal-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600']"
            @click="videoType = 'upload'"
          >
            Nahrať súbor
          </button>

          <button
            type="button"
            :class="['px-4 py-2 rounded-lg border border-gray-700 transition-colors', videoType === 'url' ? 'bg-teal-600 text-white hover:bg-teal-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600']"
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
            class="w-full bg-gray-800 text-white border-gray-700"
          />
        </div>

        <!-- Upload súbor (zobrazí sa iba ak videoType === 'upload') -->
        <div v-if="videoType === 'upload'">
          <FileUpload
            name="video"
            mode="basic"
            accept="video/*"
            :maxFileSize="104857600"
            chooseLabel="Vybrať video súbor (max. 100MB)"
            @select="onFileSelect($event, 'trailer')"
            @clear="onFileClear('trailer')"
            class="w-full"
          />
          <p v-if="files.trailer.name" class="text-sm mt-2 text-gray-400">
            Nahratý súbor: <strong>{{ files.trailer.name }}</strong>
          </p>
        </div>
      </div>
      
      <!-- Upload ďalších súborov -->
      <div class="border-t border-gray-700 pt-4 flex flex-col gap-4">
          <h3 class="text-lg font-semibold mb-2 text-teal-400">Prílohy (Voliteľné)</h3>

          <!-- Splash Screen -->
          <div>
            <label class="block mb-1 font-medium text-gray-300">Náhľadový obrázok / Video poster (max. 5MB)</label>
            <p class="text-xs text-gray-400 mb-2">Tento obrázok sa zobrazí ako úvodná obrazovka videa pred prehratím.</p>
            <FileUpload
                name="splash_screen"
                mode="basic"
                accept="image/*"
                :maxFileSize="5242880"
                chooseLabel="Vybrať obrázok"
                @select="onFileSelect($event, 'splash_screen')"
                @clear="onFileClear('splash_screen')"
            />
            <p v-if="files.splash_screen.name" class="text-sm mt-2 text-gray-400">
                Nahratý súbor: <strong>{{ files.splash_screen.name }}</strong>
            </p>
          </div>

          <!-- Zdrojový kód -->
          <div>
            <label class="block mb-1 font-medium text-gray-300">Zdrojový kód (.zip, max. 100MB)</label>
            <FileUpload
                name="source_code"
                mode="basic"
                accept=".zip,.rar,.7z"
                :maxFileSize="104857600"
                chooseLabel="Vybrať archív"
                @select="onFileSelect($event, 'source_code')"
                @clear="onFileClear('source_code')"
            />
            <p v-if="files.source_code.name" class="text-sm mt-2 text-gray-400">
                Nahratý súbor: <strong>{{ files.source_code.name }}</strong>
            </p>
          </div>

          <!-- Export Hry -->
          <div>
            <label class="block mb-1 font-medium text-gray-300">Export Hry (napr. .exe, .apk, .zip, max. 100MB)</label>
            <FileUpload
                name="export"
                mode="basic"
                accept=".exe,.apk,.zip"
                :maxFileSize="104857600"
                chooseLabel="Vybrať export"
                @select="onFileSelect($event, 'export')"
                @clear="onFileClear('export')"
            />
            <p v-if="files.export.name" class="text-sm mt-2 text-gray-400">
                Nahratý súbor: <strong>{{ files.export.name }}</strong>
            </p>
          </div>
      </div>

      <!-- Tlačidlo Odoslať -->
      <div class="mt-4">
        <Button 
            type="submit" 
            label="Zverejniť hru" 
            class="w-full p-button-success p-button-lg" 
            :loading="loadingSubmit"
            :disabled="loadingSubmit"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const route = useRoute()
const toast = useToast()

// -------------------------
// Form State
// -------------------------
const name = ref('')
const selectedCategory = ref(null)
const releaseDate = ref(null)
const description = ref('')
const videoType = ref('upload') // 'upload' | 'url'
const videoUrl = ref('')
const loadingSubmit = ref(false)

const files = ref({
    trailer: { file: null, name: '' },
    splash_screen: { file: null, name: '' },
    source_code: { file: null, name: '' },
    export: { file: null, name: '' },
})

const categories = ref([
  { name: 'Akčná' },
  { name: 'Strategická' },
  { name: 'RPG' },
  { name: 'Simulátor' },
])

// -------------------------
// Team & Auth Status
// -------------------------
const token = ref(localStorage.getItem('access_token') || '')
const teamId = ref(null)
const allTeams = ref([])
const scrumMasterTeams = ref([])
const isScrumMaster = ref(false)
const loadingTeam = ref(true)
// Odstránené obmedzenie na jednu hru na tím – tím môže mať ľubovoľný počet hier.

// -------------------------
// File Upload Logic
// -------------------------
function onFileSelect(event, type) {
  const file = event.files?.[0]
  if (file) {
    files.value[type].file = file
    files.value[type].name = file.name
  }
}

function onFileClear(type) {
  files.value[type].file = null
  files.value[type].name = ''
}

// -------------------------
// Data Submission
// -------------------------
async function loadUserTeamStatus() {
    loadingTeam.value = true;
    if (!token.value) {
        toast.add({ severity: 'error', summary: 'Chyba', detail: 'Nie ste prihlásený, chýba token!', life: 5000 });
        loadingTeam.value = false;
        return;
    }

    try {
        const res = await fetch(`${API_URL}/api/user/team`, { 
            headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
        });        if (res.ok) {
            const data = await res.json();
            console.log('📦 Raw data from API:', data);
            
            // Backend returns { teams: [...] } array
            if (data.teams && data.teams.length > 0) {
              allTeams.value = data.teams;
              scrumMasterTeams.value = data.teams.filter(t => t.is_scrum_master);
              console.log('👥 All teams:', allTeams.value.map(t => t.name));
              console.log('🔐 Scrum master teams:', scrumMasterTeams.value.map(t => t.name));
              // Determine active team (persisted from HomeView) or fallback to first team
              const activeTeamId = localStorage.getItem('active_team_id');
              let activeTeam = activeTeamId ? allTeams.value.find(t => String(t.id) === activeTeamId) : null;
              if (!activeTeam) activeTeam = allTeams.value[0];
              teamId.value = activeTeam.id;
              isScrumMaster.value = !!activeTeam.is_scrum_master;
              console.log('🎯 Active team:', activeTeam.name, 'SM:', isScrumMaster.value);
              // Scrum master môže pridať ľubovoľný počet hier.
            }
        } else {
            const errorData = await res.json();
            toast.add({ severity: 'warn', summary: 'Upozornenie', detail: errorData.message || 'Chyba pri načítavaní stavu tímu.', life: 5000 });
        }
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Chyba siete', detail: 'Problém s komunikáciou so serverom.', life: 8000 });
    } finally {
        loadingTeam.value = false;
    }
}


async function submitForm() {
  console.log('🚀 Submit form started');
  console.log('📊 teamId:', teamId.value);
  console.log('📊 isScrumMaster:', isScrumMaster.value);
  if (!teamId.value || !isScrumMaster.value) {
    console.error('❌ Authorization check failed');
    toast.add({ severity: 'error', summary: 'Chyba oprávnenia', detail: 'Nemáte povolenie pridať hru.', life: 5000 });
    return;
  }

  loadingSubmit.value = true;
  try {
    const formData = new FormData();
    formData.append('team_id', teamId.value);
    formData.append('title', name.value);
    formData.append('category', selectedCategory.value?.name || selectedCategory.value || '');
    formData.append('description', description.value);

    if (releaseDate.value) {
      const date = new Date(releaseDate.value);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const formattedReleaseDate = `${year}-${month}-${day}`;
      formData.append('release_date', formattedReleaseDate);
    }

    if (videoType.value === 'upload' && files.value.trailer.file) {
      formData.append('trailer', files.value.trailer.file);
    } else if (videoType.value === 'url' && videoUrl.value) {
      formData.append('trailer_url', videoUrl.value);
    }

    if (files.value.splash_screen.file) formData.append('splash_screen', files.value.splash_screen.file);
    if (files.value.source_code.file) formData.append('source_code', files.value.source_code.file);
    if (files.value.export.file) formData.append('export', files.value.export.file);

    const res = await fetch(`${API_URL}/api/games`, {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' },
      body: formData,
    });
    const data = await res.json();

    if (res.ok && data.game) {
      toast.add({ severity: 'success', summary: 'Úspech', detail: `Hra "${data.game.title}" bola úspešne zverejnená!`, life: 5000 });
      // Reset formulára
      name.value = '';
      selectedCategory.value = null;
      releaseDate.value = null;
      description.value = '';
      videoUrl.value = '';
      videoType.value = 'upload';
      files.value.trailer = { file: null, name: '' };
      files.value.splash_screen = { file: null, name: '' };
      files.value.source_code = { file: null, name: '' };
      files.value.export = { file: null, name: '' };
    } else {
      let errorMessage = data.message || 'Chyba pri nahrávaní hry.';
      if (data.errors) {
        const errorMessages = Object.values(data.errors).flat().join('; ');
        errorMessage += `: ${errorMessages}`;
      }
      console.error('❌ Game submission failed:', errorMessage);
      console.error('❌ Full error data:', data);
      toast.add({ severity: 'error', summary: 'Chyba nahrávania', detail: errorMessage, life: 8000 });
    }
  } catch (error) {
    console.error('❌ Network error during game submission:', error);
    toast.add({ severity: 'fatal', summary: 'Chyba siete', detail: 'Problém s komunikáciou so serverom.', life: 8000 });
  } finally {
    loadingSubmit.value = false;
  }
}
onMounted(() => {
    loadUserTeamStatus();
})

// Watch for route changes (when user switches teams in HomeView and navigates back)
watch(() => route.path, () => {
    console.log('🔄 Route changed, reloading team status');
    loadUserTeamStatus();
})
</script>