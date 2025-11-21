<template>
  <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">

    <Toast />

    <div class="flex flex-col gap-4 mb-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-3xl font-bold">
          Zoznam Registrovaných Hier
        </h2>
        
        <div class="flex gap-3 flex-wrap items-center">
          <!-- TLAČIDLO: Info o tíme (viditeľné len ak je používateľ v tíme) -->
          <Button 
            v-if="hasTeam"
            label="Moje Tímy" 
            icon="pi pi-users" 
            class="p-button-info p-button-lg"
            @click="showTeamStatusDialog = true" 
          />
          <!-- Tlačidlá -->
          <Button 
            label="Pripojiť sa k tímu" 
            icon="pi pi-user-plus" 
            class="p-button-secondary p-button-lg"
            @click="showJoinTeam = true" 
          />

          <Button 
            label="Vytvoriť Tím" 
            icon="pi pi-plus-circle" 
            class="p-button-primary p-button-lg"
            @click="showCreateTeam = true" 
          />
        </div>
      </div>

      <!-- Team Selector -->
      <div v-if="hasTeam && teams.length > 0" class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-4 shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
          <div class="flex items-center gap-3 flex-1">
            <div class="bg-blue-500 text-white rounded-full p-3 shadow-md">
              <i class="pi pi-briefcase text-xl"></i>
            </div>
            <div class="flex-1">
              <label class="text-sm font-semibold text-gray-700 mb-1 block">Aktívny Tím:</label>
              <Dropdown
                v-model="selectedTeam"
                :options="teams"
                optionLabel="name"
                placeholder="Vyberte tím"
                class="w-full sm:w-80"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value" class="flex items-center gap-2">
                    <i class="pi pi-shield text-blue-600"></i>
                    <span class="font-semibold">{{ slotProps.value.name }}</span>
                  </div>
                </template>
                <template #option="slotProps">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-shield text-blue-600"></i>
                    <div>
                      <div class="font-semibold">{{ slotProps.option.name }}</div>
                      <div class="text-xs text-gray-500" v-if="slotProps.option.academic_year">
                        <i class="pi pi-calendar text-purple-600"></i> {{ slotProps.option.academic_year.name }}
                      </div>
                    </div>
                  </div>
                </template>
              </Dropdown>
            </div>
          </div>
          <div v-if="selectedTeam" class="flex items-center gap-3 text-sm">
            <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-purple-200 shadow-sm">
              <i class="pi pi-graduation-cap text-purple-600"></i>
              <span class="font-medium text-gray-700">{{ selectedTeam.academic_year?.name || 'N/A' }}</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-green-200 shadow-sm">
              <i class="pi pi-users text-green-600"></i>
              <span class="font-medium text-gray-700">{{ selectedTeam.members?.length || 0 }} členov</span>
            </div>
            <div v-if="selectedTeam.is_scrum_master" class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-yellow-50 to-amber-50 rounded-lg border border-yellow-300 shadow-sm">
              <i class="pi pi-star-fill text-yellow-600"></i>
              <span class="font-semibold text-yellow-700">Scrum Master</span>
            </div>
          </div>
        </div>
      </div>
    </div>


    <div class="flex flex-col sm:flex-row gap-4 mb-8 p-4 border rounded-lg">
      <div class="flex-grow">
        <span class="p-float-label w-full">
          <InputText id="search" v-model="search" class="w-full" />
          <label for="search">Vyhľadať podľa názvu hry</label>
        </span>
      </div>

      <div class="w-full sm:w-60">
        <Dropdown
          v-model="selectedCategory"
          :options="categories"
          optionLabel="name"
          placeholder="Vyber kategóriu"
          class="w-full"
        />
      </div>
    </div>

    <!-- 🛑 SEKCIA: Dynamické Zobrazenie Hier z DB (s loadingom a prázdnym stavom) -->
    <div v-if="loadingGames" class="text-center p-8 text-xl text-gray-500">
        <i class="pi pi-spin pi-spinner text-3xl mr-2"></i> Načítavam hry...
    </div>
    <div v-else-if="filteredGames.length === 0" class="text-center p-8 text-xl text-gray-500">
        Zatiaľ nebola pridaná žiadna hra.
    </div>
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
      <div
        v-for="game in filteredGames"
        :key="game.id"
        class="border rounded-xl p-5 shadow-md hover:shadow-xl transition duration-300"
      >
        <div class="aspect-video bg-gray-100 rounded-lg flex items-center justify-center text-gray-500 mb-4 overflow-hidden">
          <!-- TODO: Ak existuje splash screen, zobraziť ho namiesto ikony -->
          <i class="pi pi-video text-5xl"></i>
        </div>

        <!-- Používame game.title -->
        <h3 class="text-xl font-bold text-white mb-1 line-clamp-2">{{ game.title }}</h3>
        
        <div class="flex flex-wrap gap-2 text-sm mb-3">
          <!-- Kategória -->
          <span class="px-3 py-1.5 rounded-lg border-2 border-blue-400 bg-blue-50 text-blue-700 font-semibold shadow-sm">
            <i class="pi pi-tag mr-1"></i>{{ game.category }}
          </span>
          <!-- Tím -->
          <span class="px-3 py-1.5 rounded-lg border-2 border-teal-400 bg-teal-50 text-teal-700 font-semibold shadow-sm">
            <i class="pi pi-shield mr-1"></i>{{ game.team?.name || 'Neznámy' }}
          </span>
          <!-- Rok -->
          <span v-if="game.academic_year" class="px-3 py-1.5 rounded-lg border-2 border-purple-400 bg-purple-50 text-purple-700 font-semibold shadow-sm">
            <i class="pi pi-graduation-cap mr-1"></i>{{ game.academic_year.name }}
          </span>
        </div>
        
        <!-- Používame game.description -->
        <p class="text-white text-base line-clamp-3 mb-3">{{ game.description || 'Popis nebol poskytnutý.' }}</p>

        <!-- Rating and Views -->
        <div class="flex items-center gap-4 mb-4 text-sm">
          <!-- Star Rating -->
          <div class="flex items-center gap-1">
            <i 
              v-for="star in 5" 
              :key="star" 
              :class="star <= Math.round(game.rating || 0) ? 'pi pi-star-fill text-yellow-400' : 'pi pi-star text-gray-400'"
            ></i>
            <span class="ml-1 text-gray-300 font-semibold">{{ Number(game.rating || 0).toFixed(1) }}</span>
          </div>
          <!-- Views Counter -->
          <div class="flex items-center gap-1 text-gray-300">
            <i class="pi pi-eye"></i>
            <span class="font-semibold">{{ game.views || 0 }}</span>
          </div>
        </div>

        <Button 
            label="Zobraziť Detail" 
            icon="pi pi-arrow-right" 
            iconPos="right"
            class="p-button-secondary p-button-outlined p-button-sm w-full"
            @click="viewGameDetail(game)" 
        />
      </div>
    </div>
  </div>

  <!-- DIALÓG PRE VYTVORENIE TÍMU (Zostáva nezmenený) -->
  <Dialog v-model:visible="showCreateTeam" :modal="true" :closable="true" :draggable="false" class="w-11/12 md:w-1/3" :headerStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', borderBottom: '1px solid #374151', padding: '1.25rem 1.5rem', position: 'relative' }">
    <template #header>
      <div class="flex items-center justify-center w-full">
        <span class="text-gray-100 font-medium text-lg w-full">Vytvoriť Nový Tím</span>
      </div>
    </template>
    <div v-if="!teamCreatedSuccess">
        <form @submit.prevent="createTeam" class="flex flex-col gap-5 p-4">
          <InputText v-model="teamName" placeholder="Názov tímu" required class="p-inputtext-lg" />
          
          <Dropdown
              v-model="academicYear"
              :options="academicYears"
              optionLabel="name"
              optionValue="id"
              placeholder="Vyber akademický rok"
              class="p-dropdown-lg"
          />
          
          <Button 
              label="Vytvoriť Tím" 
              icon="pi pi-check" 
              type="submit" 
              class="p-button-primary p-button-lg mt-2" 
              :loading="loadingCreate"
          />
        </form>
    </div>
    <div v-else class="p-4 flex flex-col items-center gap-4 text-center">
        <i class="pi pi-check-circle text-6xl text-green-500"></i>
        <h3 class="text-xl font-semibold">Tím bol úspešne vytvorený!</h3>
        
        <div class="border-2 border-dashed border-gray-300 p-4 rounded-lg w-full">
        <p class="text-sm text-gray-600 mb-2">Váš unikátny kód pre pripojenie členov:</p>
        <span class="text-4xl font-extrabold tracking-widest text-primary-500 select-all">
          {{ team.invite_code }} </span>
        </div>

        <Button 
          label="Kopírovať Kód" 
          icon="pi pi-copy" 
          class="p-button-secondary w-full"
          @click="copyTeamCode(team.invite_code)" />
        
        <Button 
          label="Zavrieť a pokračovať" 
          class="p-button-text w-full"
          @click="closeCreateTeamDialog" 
          />
    </div>
    
    <p v-if="teamMessage && !teamCreatedSuccess" :class="teamMessage.startsWith('✅') ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'" class="mt-4 text-center">
      {{ teamMessage }}
    </p>
  </Dialog>

  <!-- DIALÓG PRE PRIPOJENIE K TÍMU (Zostáva nezmenený) -->
  <Dialog 
    v-model:visible="showJoinTeam" 
    :modal="true" 
    :closable="true" 
    :draggable="false"
    class="w-11/12 md:w-1/4"
    :contentStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', padding: '1rem', border: 'none' }" 
    :headerStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', borderBottom: '1px solid #374151', padding: '1.25rem 1.5rem', position: 'relative' }"
    :style="{ borderRadius: '8px', overflow: 'hidden' }"
  >
    <template #header>
      <div class="flex items-center justify-center w-full">
        <span class="text-gray-100 font-medium text-lg w-full">Pripojiť sa k tímu</span>
      </div>
    </template>
    
    <div class="p-4 flex flex-col items-center gap-5 text-center">
        
        <form @submit.prevent="joinTeam" class="flex flex-col gap-4 w-full">
          <InputText
              v-model="joinTeamCode"
              placeholder="Kód (napr. A1B2C3)"
              required
              :class="{ 'p-invalid': joinTeamError }"
              class="p-inputtext-lg text-center font-mono tracking-widest"
          />
          <Button
              type="submit"
              label="Pripojiť sa"
              icon="pi pi-sign-in"
              class="p-button-primary p-button-lg"
              :loading="loadingJoin"
          />
        </form>

        <small v-if="joinTeamError" class="text-red-400 font-semibold mt-2">{{ joinTeamError }}</small>
    </div>
</Dialog>


  <!-- NOVÝ, MINIMALISTICKÝ DIALÓG PRE ZOBRAZENIE STAVU TÍMU -->
  <Dialog 
    v-model:visible="showTeamStatusDialog" 
    :modal="true" 
    :closable="true" 
    :draggable="false"
    class="w-11/12 md:w-1/3"
    :contentStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', padding: '1.5rem', border: 'none' }" 
    :headerStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', borderBottom: '1px solid #374151', padding: '1.25rem 1.5rem', position: 'relative' }"
    :style="{ borderRadius: '8px', overflow: 'hidden' }"
  >
    <template #header>
      <div class="flex items-center justify-center w-full">
        <span class="text-gray-100 font-medium text-lg w-full">Moje Tímy</span>
      </div>
    </template>
    <div v-if="teams.length > 0" class="flex flex-col gap-4 max-h-[70vh] overflow-y-auto">
        <!-- Zobrazenie všetkých tímov -->
        <div v-for="team in teams" :key="team.id" class="bg-gray-800 rounded-lg p-4 border-2 border-gray-700 hover:border-blue-500 transition">
            <!-- Hlavička tímu -->
            <div class="flex justify-between items-start mb-3 pb-3 border-b border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-600 text-white rounded-full p-2">
                        <i class="pi pi-shield text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">{{ team.name }}</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span v-if="team.academic_year" class="text-sm text-gray-400 flex items-center gap-1">
                                <i class="pi pi-graduation-cap text-purple-400"></i>
                                {{ team.academic_year.name }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-if="team.is_scrum_master" class="flex items-center gap-1 px-2 py-1 bg-yellow-900 text-yellow-300 rounded-lg text-xs font-semibold">
                    <i class="pi pi-star-fill"></i>
                    <span>Scrum Master</span>
                </div>
            </div>

            <!-- Kód pre pripojenie -->
            <div class="flex flex-col items-center p-3 bg-gray-900 rounded-lg mb-3">
                <p class="text-xs text-gray-400 mb-1">Kód pre pripojenie:</p>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-extrabold tracking-widest text-teal-400 select-all">
                        {{ team.invite_code }}
                    </span>
                    <Button 
                        icon="pi pi-copy" 
                        class="p-button-sm p-button-text p-button-secondary"
                        @click="copyTeamCode(team.invite_code)" 
                        v-tooltip.top="'Kopírovať kód'"
                    />
                </div>
            </div>

            <!-- Zoznam členov -->
            <div>
                <p class="text-xs text-gray-400 mb-2 flex items-center gap-2">
                    <i class="pi pi-users text-green-400"></i>
                    Členovia ({{ team.members?.length || 0 }}/4):
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <div v-for="member in team.members" :key="member.id" class="flex items-center gap-2 text-gray-200 text-sm bg-gray-900 rounded px-2 py-1">
                        <i class="pi pi-user text-xs text-teal-400"></i>
                        <span class="truncate">{{ member.name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <Button 
            label="Zavrieť" 
            class="p-button-text w-full mt-2"
            @click="showTeamStatusDialog = false" 
        />
    </div>
    <div v-else class="text-center text-gray-400 py-8">
        <i class="pi pi-inbox text-4xl mb-3 block"></i>
        <p>Nie ste členom žiadneho tímu</p>
    </div>
</Dialog>

</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'; 

const vTooltip = Tooltip; 

const API_URL = import.meta.env.VITE_API_URL
const toast = useToast()
const router = useRouter()

// -------------------------
// Global/User Status
// -------------------------
const token = ref(localStorage.getItem('access_token') || '')
const hasTeam = ref(false) 
const teams = ref([]) // All teams user is part of
const selectedTeam = ref(null) // Currently selected team
const showTeamStatusDialog = ref(false) 

// -------------------------
// Logika Pripojenia k Tímu
// -------------------------
const showJoinTeam = ref(false)
const joinTeamCode = ref('')
const joinTeamError = ref('')
const loadingJoin = ref(false)

async function joinTeam() {
    joinTeamError.value = ''
    
    if (!joinTeamCode.value) {
        joinTeamError.value = 'Kód tímu nemôže byť prázdny.'
        return
    }
    
    loadingJoin.value = true
    
    // Očistíme kód pre prípad, že ho používateľ skopíroval s bielymi znakmi
    const cleanCode = joinTeamCode.value.trim() 
    const payload = JSON.stringify({ invite_code: cleanCode });

    try {
        const res = await fetch(`${API_URL}/api/teams/join`, { 
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', 
                'Authorization': 'Bearer ' + token.value,
                'Accept': 'application/json'
            },
            body: payload
        })

        const data = await res.json()
        
        if (res.ok && data.team) {
            toast.add({ severity: 'success', summary: 'Pripojenie Úspešné', detail: `Úspešne ste sa pripojili k tímu "${data.team.name}".`, life: 5000 })
            hasTeam.value = true
            showJoinTeam.value = false 
            joinTeamCode.value = ''
            await loadTeamStatus() // Reload all teams
            loadAllGames() 
        } else {
            let errorMessage = data.message || 'Chyba pri pripájaní.'
            
            if (data.message && data.message.includes('Tím') && data.message.includes('dosiahol maximálny')) {
                // Konkrétna chyba z backendu pre max členov
                 toast.add({ severity: 'error', summary: 'Chyba Kapacity', detail: errorMessage, life: 6000 })
            }
            else if (errorMessage.includes('Už si v tíme') || errorMessage.includes('Už si členom tímu')) {
                 toast.add({ severity: 'warn', summary: 'Už ste v tíme', detail: errorMessage, life: 6000 })
            } else {
                 toast.add({ severity: 'error', summary: 'Chyba Pripojenia', detail: errorMessage, life: 6000 })
            }
            
            if (data.errors && data.errors.invite_code) {
                 joinTeamError.value = data.errors.invite_code.join(' ')
            } else {
                 joinTeamError.value = errorMessage
            }
        }
    } catch (err) {
        joinTeamError.value = 'Chyba siete/servera. (Server nedostupný)'
        toast.add({ severity: 'fatal', summary: 'Chyba Siete', detail: 'Server je nedostupný (Connection refused). Overte, či beží na porte 8000.', life: 10000 })
    } finally {
        loadingJoin.value = false
    }
}


// -------------------------
// Logika Vytvorenia Tímu
// -------------------------
const showCreateTeam = ref(false)
const teamName = ref('')
const academicYear = ref(null)
const academicYears = ref([]) 
const teamMessage = ref('') 
const team = ref(null) 
const teamCreatedSuccess = ref(false) 
const loadingCreate = ref(false)

async function createTeam() {
  teamMessage.value = ''
  // Extra validation and user feedback
  if (!teamName.value && !academicYear.value) {
    teamMessage.value = '❌ Vyplňte názov tímu a vyberte akademický rok.';
    toast.add({ severity: 'warn', summary: 'Upozornenie', detail: 'Vyplňte, prosím, názov tímu a akademický rok.', life: 4000 });
    return;
  }
  if (!teamName.value) {
    teamMessage.value = '❌ Názov tímu je povinný.';
    toast.add({ severity: 'warn', summary: 'Upozornenie', detail: 'Názov tímu je povinný.', life: 4000 });
    return;
  }
  if (!academicYear.value) {
    teamMessage.value = '❌ Akademický rok je povinný.';
    toast.add({ severity: 'warn', summary: 'Upozornenie', detail: 'Akademický rok je povinný.', life: 4000 });
    return;
  }
  loadingCreate.value = true;
  try {
    const formData = new FormData();
    formData.append('name', teamName.value);
    formData.append('academic_year_id', academicYear.value);

    const res = await fetch(`${API_URL}/api/teams`, {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' },
      body: formData,
    });

    const data = await res.json();

    if (res.ok && data.team) {
      team.value = data.team;
      teamCreatedSuccess.value = true;
      hasTeam.value = true;
      toast.add({ severity: 'success', summary: 'Tím Vytvorený', detail: `Tím "${team.value.name}" bol úspešne vytvorený.`, life: 5000 });
      await loadTeamStatus(); // Reload all teams
      loadAllGames();
    } else {
      let errorMessage = data.message || 'Chyba pri vytváraní tímu.';
      if (data.errors) {
        errorMessage += ' ' + Object.values(data.errors).map(e => e.join(', ')).join('. ');
      }
      teamMessage.value = '❌ ' + errorMessage;
      toast.add({ severity: 'error', summary: 'Chyba Registrácie', detail: errorMessage, life: 8000 });
    }
  } catch (err) {
    teamMessage.value = 'Chyba pri spojení s backendom. Server nedostupný.';
    toast.add({ severity: 'fatal', summary: 'Chyba Pripojenia', detail: 'Server je nedostupný (Connection refused). Overte, či beží na porte 8000.', life: 10000 });
  } finally {
    loadingCreate.value = false;
  }
}

const copyTeamCode = async (code) => {
  try {
    // Používame moderné asynchrónne Clipboard API
    await navigator.clipboard.writeText(code);
    toast.add({ severity: 'info', summary: 'Kód skopírovaný', detail: 'Kód bol skopírovaný do schránky.', life: 3000 });
  } catch (err) {
    toast.add({ severity: 'warn', summary: 'Kopírovanie zlyhalo', detail: 'Nepodarilo sa skopírovať kód. Prosím, skopírujte ho ručne.', life: 3000 });
  }
}

const closeCreateTeamDialog = () => {
    showCreateTeam.value = false
    teamCreatedSuccess.value = false
    team.value = null 
    teamName.value = ''
    academicYear.value = null
}

// -------------------------
// Statické Dáta a Filtrovanie
// -------------------------
const search = ref('')
const selectedCategory = ref(null)
const categories = ref([
  { name: 'Akčná' }, { name: 'Strategická' }, { name: 'RPG' }, { name: 'Simulátor' },
  { name: 'Horor' }, { name: 'Dobrodružná' }, { name: 'Logická' }, 
])
const games = ref([]) 
const loadingGames = ref(true) 

const filteredGames = computed(() => {
  return games.value.filter(
    (g) => g.title.toLowerCase().includes(search.value.toLowerCase()) && (!selectedCategory.value || g.category === selectedCategory.value.name)
  )
})
const viewGameDetail = (game) => {
    router.push({ name: 'GameDetail', params: { id: game.id } })
}

// -------------------------
// Načítanie dát
// -------------------------
async function loadAcademicYears() {
    try {
        const res = await fetch(`${API_URL}/api/academic-years`, {
        headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
        })
        if (!res.ok) return
        academicYears.value = await res.json()
    } catch (err) {
        // Silent fail - academic years are optional for display
    }
}

// 🛑 NOVÁ FUNKCIA pre /api/user/team
async function loadTeamStatus() {
    if (!token.value) return; 
    try {
        const res = await fetch(`${API_URL}/api/user/team`, { 
            headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
        })
        
        // Pokúsime sa načítať JSON aj pri chybovom stave (pre správy)
        let data = {};
        if (res.headers.get('content-type')?.includes('application/json')) {
            data = await res.json();
        }

        if (res.ok) {
            if (data.teams && data.teams.length > 0) {
                hasTeam.value = true
                teams.value = data.teams
                selectedTeam.value = teams.value[0] // Select first team by default
                console.log('✅ Používateľ je v tímoch:', data.teams.map(t => t.name).join(', '));
            } else {
                hasTeam.value = false;
                teams.value = [];
                selectedTeam.value = null;
            }
        } else if (res.status === 404) {
            console.warn(`⚠️ Chyba 404: Endpoint /api/user/team nebol nájdený. Skontrolujte routes/api.php.`)
            hasTeam.value = false;
        } else if (res.status === 401) {
             console.warn(`⚠️ Chyba 401: Neautorizovaný prístup k stavu tímu. Token neplatný/vypršal.`)
             hasTeam.value = false;
        } else {
             console.error(`❌ Chyba ${res.status} pri načítaní stavu tímu.`, res)
        }
    } catch (err) {
        console.error('❌ FATÁLNA CHYBA SIETE pri načítaní stavu tímu. Server pravdepodobne nie je spustený alebo je nedostupný.', err)
        toast.add({ severity: 'fatal', summary: 'Chyba Siete', detail: 'Server je nedostupný (Connection refused). Overte, či beží na porte 8000.', life: 10000 })
    }
}

// Načítanie všetkých hier z DB
async function loadAllGames() {
    loadingGames.value = true
    try {
        const res = await fetch(`${API_URL}/api/games`, {
            headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
        })

        if (res.ok) {
            const data = await res.json()
            games.value = data
        } else if (res.status === 404) {
            toast.add({ severity: 'error', summary: 'Chyba Načítania Hier (404)', detail: 'Chýba routa GET /api/games. Pridajte ju, prosím, do routes/api.php.', life: 10000 })
        }
         else {
            toast.add({ severity: 'error', summary: 'Chyba Načítania Hier', detail: `Nepodarilo sa načítať zoznam hier zo servera. Status: ${res.status}`, life: 5000 })
        }
    } catch (err) {
        console.error('❌ FATÁLNA CHYBA SIETE pri načítaní všetkých hier. Server pravdepodobne nie je spustený alebo je nedostupný.', err)
        toast.add({ severity: 'fatal', summary: 'Chyba Pripojenia', detail: 'Server je nedostupný (Connection refused). Problém s komunikáciou pri načítaní hier.', life: 10000 })
    } finally {
        loadingGames.value = false
    }
}


onMounted(() => {
  loadAcademicYears()
  loadTeamStatus() 
  loadAllGames() 
})
</script>