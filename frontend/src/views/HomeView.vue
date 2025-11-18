<template>
  <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">

    <Toast />

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
      <h2 class="text-3xl font-bold">
        Zoznam Registrovaných Hier
      </h2>
      
      <div class="flex gap-4">
        <!-- TLAČIDLO: Info o tíme (viditeľné len ak je používateľ v tíme) -->
        <Button 
          v-if="hasTeam"
          label="Info o Tíme" 
          icon="pi pi-info-circle" 
          class="p-button-info p-button-lg"
          @click="showTeamStatusDialog = true" 
        />
        <!-- Pôvodné tlačidlá -->
        <Button 
          label="Pripojiť sa k tímu" 
          icon="pi pi-sign-in" 
          class="p-button-secondary p-button-lg"
          @click="showJoinTeam = true" 
          :disabled="hasTeam"
        />

        <Button 
          label="Vytvoriť Tím" 
          icon="pi pi-users" 
          class="p-button-primary p-button-lg"
          @click="showCreateTeam = true" 
          :disabled="hasTeam"
        />
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
        <h3 class="text-xl font-bold text-gray-800 mb-1 line-clamp-2">{{ game.title }}</h3>
        
        <div class="flex flex-wrap gap-2 text-sm mb-3">
          <!-- Kategória -->
          <span class="px-2.5 py-0.5 rounded-full border border-blue-400 bg-blue-50 text-blue-600 font-medium">
            {{ game.category }}
          </span>
          <!-- Tím a Rok -->
          <span class="px-2.5 py-0.5 rounded-full border border-teal-400 bg-teal-50 text-teal-600 font-medium">
            Tím: {{ game.team?.name || 'Neznámy' }}
          </span>
          <span v-if="game.academic_year" class="px-2.5 py-0.5 rounded-full border border-purple-400 bg-purple-50 text-purple-600 font-medium">
            {{ game.academic_year.name }}
          </span>
        </div>
        
        <!-- Používame game.description -->
        <p class="text-gray-700 text-base line-clamp-3 mb-4">{{ game.description || 'Popis nebol poskytnutý.' }}</p>

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
        <span class="text-gray-100 font-medium text-lg w-full">Informácie o tíme</span>
      </div>
    </template>
    <div v-if="teamInfo" class="flex flex-col gap-6">
        <!-- Názov tímu -->
        <div class="flex justify-between items-center pb-2 border-b border-gray-700">
            <span class="text-gray-400 font-medium">Názov:</span>
            <span class="text-2xl font-bold text-white">{{ teamInfo.name }}</span>
        </div>

        <!-- Kód pre pripojenie -->
        <div class="flex flex-col items-center p-4 bg-gray-800 rounded-lg shadow-lg">
            <p class="text-sm text-gray-400 mb-2">Kód pre pripojenie:</p>
            <div class="flex items-center gap-3">
                <span class="text-4xl font-extrabold tracking-widest text-teal-400 select-all">
                    {{ teamInfo.invite_code }}
                </span>
                <Button 
                    icon="pi pi-copy" 
                    class="p-button-sm p-button-text p-button-secondary"
                    @click="copyTeamCode(teamInfo.invite_code)" 
                    v-tooltip.top="'Kopírovať kód'"
                />
            </div>
        </div>

        <!-- Zoznam členov -->
        <div>
            <p class="text-sm text-gray-400 mb-2 flex justify-between items-center">
                Členovia tímu ({{ teamInfo.members?.length || 0 }}/4):
            </p>
            <ul class="flex flex-col gap-1.5 p-3 bg-gray-900 rounded-lg max-h-40 overflow-y-auto">
                <li v-for="member in teamInfo.members" :key="member.id" class="flex items-center text-gray-200 text-base">
                    <i class="pi pi-user text-sm mr-3 text-teal-400"></i>
                    {{ member.name }}
                </li>
            </ul>
        </div>

        <Button 
            label="Zavrieť" 
            class="p-button-text p-button-secondary mt-3"
            @click="showTeamStatusDialog = false" 
        />
    </div>
    <div v-else class="text-center text-gray-400">
        Načítavam informácie o tíme...
    </div>
</Dialog>

</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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

// -------------------------
// Global/User Status
// -------------------------
const token = ref(localStorage.getItem('access_token') || '')
const hasTeam = ref(false) 
const teamInfo = ref(null) 
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
        
        // 🛑 OPRAVA: Overujeme, či data.team existuje, aby sme predišli TypeError: Cannot read properties of undefined (reading 'name')
        if (res.ok && data.team) {
            teamInfo.value = data.team 

            toast.add({ severity: 'success', summary: 'Pripojenie Úspešné', detail: `Úspešne ste sa pripojili k tímu "${data.team.name}".`, life: 5000 })
            hasTeam.value = true
            showJoinTeam.value = false 
            joinTeamCode.value = ''
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
      teamInfo.value = data.team;
      teamCreatedSuccess.value = true;
      hasTeam.value = true;
      toast.add({ severity: 'success', summary: 'Tím Vytvorený', detail: `Tím "${team.value.name}" bol úspešne vytvorený.`, life: 5000 });
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
    // TODO: Implement game detail view
    // Tu by nasledovala logika pre presmerovanie/otvorenie detailu
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
        // TÁTO ROTA BOLA CHÝBAJÚCA
        const res = await fetch(`${API_URL}/api/user/team`, { 
            headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
        })
        
        // Pokúsime sa načítať JSON aj pri chybovom stave (pre správy)
        let data = {};
        if (res.headers.get('content-type')?.includes('application/json')) {
            data = await res.json();
        }

        if (res.ok) {
            if (data.team) {
                hasTeam.value = true
                teamInfo.value = data.team 
                console.log('✅ Používateľ je v tíme:', data.team.name);
            } else {
                hasTeam.value = false;
                teamInfo.value = null;
            }
        } else if (res.status === 404) {
            console.warn(`⚠️ Chyba 404: Endpoint /api/user/team nebol nájdený. Skontrolujte routes/api.php.`)
            hasTeam.value = false;
            teamInfo.value = null;
        } else if (res.status === 401) {
             console.warn(`⚠️ Chyba 401: Neautorizovaný prístup k stavu tímu. Token neplatný/vypršal.`)
             hasTeam.value = false;
             teamInfo.value = null;
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