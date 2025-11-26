<template>
  <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8 bg-gray-900 min-h-screen">

    <Toast />

    <div class="flex flex-col gap-4 mb-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-3xl font-bold text-gray-100">
          Zoznam Registrovaných Projektov 
        </h2>
        
        <div v-if="token" class="flex gap-3 flex-wrap items-center">
          <!-- TLAČIDLO: Info o tíme (viditeľné len ak je používateľ v tíme) -->
          <Button 
            v-if="hasTeam"
            label="Moje Tímy" 
            class="p-button-outlined p-button-lg"
            @click="showTeamStatusDialog = true" 
          />
          <!-- Tlačidlá -->
          <Button 
            label="Pripojiť sa k tímu" 
            class="p-button-outlined p-button-lg"
            @click="showJoinTeam = true" 
          />

          <Button 
            label="Vytvoriť Tím" 
            class="p-button-outlined p-button-lg"
            @click="showCreateTeam = true" 
          />
        </div>
      </div>

      <!-- Team Selector (minimalistic) -->
      <div v-if="hasTeam && teams.length > 0" class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-2xl p-5 shadow-xl">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
          <div class="flex items-center gap-3 flex-1">
            <div class="flex-1">
              <label class="text-sm font-semibold text-white mb-1 block">Aktívny Tím:</label>
              <Dropdown
                v-model="selectedTeam"
                :options="teams"
                optionLabel="name"
                placeholder="Vyberte tím"
                class="w-full sm:w-80 text-white"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value" class="flex items-center">
                    <span class="font-semibold text-white">{{ slotProps.value.name }}</span>
                  </div>
                </template>
                <template #option="slotProps">
                  <div class="flex flex-col">
                    <div class="font-semibold text-white">{{ slotProps.option.name }}</div>
                    <div class="text-xs text-gray-300" v-if="slotProps.option.academic_year">
                      {{ slotProps.option.academic_year.name }}
                    </div>
                  </div>
                </template>
              </Dropdown>
            </div>
          </div>
          <div v-if="selectedTeam" class="flex flex-wrap items-center gap-2 text-xs sm:text-sm">
            <div class="px-3.5 py-1.5 bg-gray-700 rounded-md border border-gray-600 shadow-lg">
              <span class="font-medium text-gray-200">{{ selectedTeam.academic_year?.name || 'N/A' }}</span>
            </div>
            <div class="px-3.5 py-1.5 bg-gray-700 rounded-md border border-gray-600 shadow-lg">
              <span class="font-medium text-gray-200">{{ selectedTeam.members?.length || 0 }} členov</span>
            </div>
            <div v-if="selectedTeam.is_scrum_master" class="px-3.5 py-1.5 bg-gray-700 rounded-md border border-blue-500 shadow-lg">
              <span class="font-semibold text-blue-300">Scrum Master</span>
            </div>
          </div>
        </div>
      </div>
    </div>


    <div v-if="token" class="flex flex-col sm:flex-row gap-4 mb-8 p-5 border border-gray-700 rounded-2xl bg-gradient-to-br from-gray-800 to-gray-900 shadow-xl">
      <div class="flex-grow">
        <span class="p-float-label w-full">
          <InputText id="search" v-model="search" class="w-full" />
          <label for="search">Vyhľadať podľa názvu projektu</label>
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
      <div class="w-full sm:w-56">
        <Dropdown
          v-model="selectedType"
          :options="types"
          optionLabel="label"
          optionValue="value"
          placeholder="Typ projektu"
          class="w-full"
        />
      </div>
      <div class="w-full sm:w-48 flex items-center gap-2">
        <Button v-if="hasTeam && selectedTeam && !showingMyProjects" label="Moje Projekty" class="p-button-outlined w-full" icon="pi pi-filter" @click="loadMyProjects" />
        <Button v-if="showingMyProjects" label="Všetky Projekty" class="p-button-outlined w-full" icon="pi pi-arrow-left" @click="loadAllGames" />
      </div>
    </div>

    <!-- 🛑 SEKCIA: Dynamické Zobrazenie Hier z DB (s loadingom a prázdnym stavom) -->
    <!-- Not logged in message -->
    <div v-if="!token" class="text-center p-20 bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border border-gray-700 shadow-xl">
      <i class="pi pi-lock text-6xl text-gray-500 mb-6"></i>
      <h3 class="text-2xl font-bold text-gray-100 mb-4">Prihláste sa aby ste videli projekty v systéme</h3>
      <p class="text-gray-400 mb-6">Pre zobrazenie projektov a funkcionalitu systému sa musíte prihlásiť.</p>
      <div class="flex gap-3 justify-center">
        <Button 
          label="Prihlásiť sa" 
          icon="pi pi-sign-in"
          class="p-button-lg"
          @click="$router.push('/login')"
        />
        <Button 
          label="Registrovať sa" 
          icon="pi pi-user-plus"
          class="p-button-outlined p-button-lg"
          @click="$router.push('/register')"
        />
      </div>
    </div>

    <!-- Logged in - show projects -->
    <div v-else>
      <div v-if="loadingGames" class="flex items-center justify-center p-20 text-xl text-blue-400">
        <i class="pi pi-spin pi-spinner text-4xl mr-2 text-blue-400"></i> Načítavam projekty...
      </div>
      <div v-else-if="filteredGames.length === 0" class="text-center p-12 text-lg text-gray-300 bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border border-gray-700 shadow-xl">
        Zatiaľ nebol pridaný žiadny projekt.
      </div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="game in filteredGames"
        :key="game.id"
        class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-2xl p-5 shadow-xl hover:shadow-2xl hover:border-gray-600 transition-all duration-200 flex flex-col"
      >
        <div class="aspect-video bg-gray-950 rounded-xl mb-4 overflow-hidden flex items-center justify-center text-xs text-gray-500">
          <span v-if="!game.splash_screen_path">Bez náhľadu</span>
          <img 
            v-else 
            :src="getSplashUrl(game.splash_screen_path)" 
            :alt="game.title" 
            class="object-cover w-full h-full" 
          />
        </div>

        <h3 class="text-lg font-semibold text-gray-100 mb-3 line-clamp-2">{{ game.title }}</h3>
        
        <div class="flex flex-wrap gap-2 text-xs mb-3">
          <span class="px-3 py-1 rounded-md border border-teal-600 bg-teal-700 text-teal-100 font-medium shadow-lg uppercase">{{ game.type.replace('_', ' ') }}</span>
          <span class="px-3 py-1 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg">{{ game.category }}</span>
          <span 
            class="px-3 py-1 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg cursor-pointer hover:bg-gray-600 transition"
            @click.stop="goToTeam(game.team?.id)"
          >
            {{ game.team?.name || 'Neznámy' }}
          </span>
          <span v-if="game.academic_year" class="px-3 py-1 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg">{{ game.academic_year.name }}</span>
        </div>
        
        <p class="text-gray-400 text-sm leading-relaxed line-clamp-3 mb-4">{{ game.description || 'Popis nebol poskytnutý.' }}</p>

        <div class="mt-auto">
          <div class="flex items-center justify-between mb-3 text-xs text-gray-400 pb-3 border-b border-gray-700">
            <div class="flex items-center gap-1">
              <i 
                v-for="star in 5" 
                :key="star" 
                :class="star <= Math.round(Number(game.rating || 0)) ? 'pi pi-star-fill text-yellow-400' : 'pi pi-star text-gray-600'"
                class="text-sm"
              ></i>
              <span class="font-semibold text-gray-300 ml-1">{{ Number(game.rating || 0).toFixed(1) }}</span>
            </div>
            <div class="flex items-center gap-1">Zobrazenia: <span class="font-semibold text-gray-300">{{ game.views || 0 }}</span></div>
          </div>

          <Button 
            label="Zobraziť detail" 
            icon="pi pi-arrow-right"
            class="p-button-sm p-button-outlined w-full"
            @click="viewProjectDetail(game)" 
          />
        </div>
      </div>
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
        <div v-for="team in teams" :key="team.id" class="bg-gray-800 rounded-lg p-4 border border-gray-700">
          <!-- Hlavička tímu -->
          <div class="flex justify-between items-start mb-3 pb-3 border-b border-gray-700">
            <div>
              <h3 class="text-lg font-semibold text-white">{{ team.name }}</h3>
              <div class="mt-1">
                <span v-if="team.academic_year" class="text-xs text-gray-400">
                  {{ team.academic_year.name }}
                </span>
              </div>
            </div>
            <div v-if="team.is_scrum_master" class="px-2 py-1 bg-gray-700 text-gray-200 rounded text-xs font-medium">
              Scrum Master
            </div>
          </div>

          <!-- Kód pre pripojenie -->
          <div class="flex flex-col items-center p-3 bg-gray-900 rounded-lg mb-3">
            <p class="text-xs text-gray-400 mb-1">Kód pre pripojenie:</p>
            <div class="flex items-center gap-2">
              <span class="text-xl font-mono tracking-widest text-gray-200 select-all">
                {{ team.invite_code }}
              </span>
              <Button 
                class="p-button-sm p-button-text"
                @click="copyTeamCode(team.invite_code)" 
                v-tooltip.top="'Kopírovať kód'"
                label="Kopírovať"
              />
            </div>
          </div>

          <!-- Zoznam členov -->
          <div>
            <p class="text-xs text-gray-400 mb-2">Členovia ({{ team.members?.length || 0 }}/4):</p>
            <div class="grid grid-cols-2 gap-2">
              <div v-for="member in team.members" :key="member.id" class="flex items-center justify-between gap-2 text-gray-200 text-sm bg-gray-900 rounded px-2 py-1">
                <div class="flex flex-col truncate">
                  <span class="truncate">{{ member.name }}</span>
                  <span :class="getRoleClass(team, member)" class="text-xs font-semibold">{{ getRoleLabel(team, member) }}</span>
                </div>
                <Button
                  v-if="team.is_scrum_master && member.id !== currentUserId"
                  label="Odstrániť"
                  class="p-button-text p-button-sm text-red-300 hover:text-red-200"
                  @click="confirmRemoveMember(team, member)"
                />
                <Button
                  v-if="!team.is_scrum_master && member.id === currentUserId"
                  label="Opustiť"
                  class="p-button-text p-button-sm text-yellow-300 hover:text-yellow-200"
                  @click="confirmLeaveTeam(team)"
                />
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
        <p class="text-sm">Nie ste členom žiadneho tímu</p>
    </div>
</Dialog>

</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
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
// Persist active team selection for cross-view authorization (AddGameView, Navbar)
function setActiveTeam(team) {
  if (!team) {
    localStorage.removeItem('active_team_id')
    localStorage.removeItem('active_team_is_scrum_master')
  } else {
    localStorage.setItem('active_team_id', String(team.id))
    localStorage.setItem('active_team_is_scrum_master', team.is_scrum_master ? '1' : '0')
    // Broadcast change so Navbar / other views can react without reload
    window.dispatchEvent(new CustomEvent('team-changed', { detail: { id: team.id, isScrumMaster: team.is_scrum_master } }))
  }
}
const showTeamStatusDialog = ref(false) 
const currentUserId = ref(null)
const removingMember = ref(false)

// Helper: derive role label and class even if pivot missing
function getRoleLabel(team, member) {
  const pivotRole = member.pivot?.role_in_team
  if (pivotRole === 'scrum_master' || team.scrum_master_id === member.id) return 'Scrum Master'
  return 'Člen'
}
function getRoleClass(team, member) {
  const isScrum = (member.pivot?.role_in_team === 'scrum_master') || (team.scrum_master_id === member.id)
  return isScrum ? 'text-teal-400' : 'text-gray-500'
}

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
  { name: 'Všetky', value: null },
  { name: 'Akčná' }, { name: 'Strategická' }, { name: 'RPG' }, { name: 'Simulátor' },
  { name: 'Horor' }, { name: 'Dobrodužná' }, { name: 'Logická' }, { name: 'Adventura' },
  { name: 'Puzzle' }, { name: 'Šport' }, { name: 'Preteky' }, { name: 'Vždelávacie' }
])
const types = ref([
  { label: 'Všetky', value: 'all' },
  { label: 'Hra', value: 'game' },
  { label: 'Web App', value: 'web_app' },
  { label: 'Mobile App', value: 'mobile_app' },
  { label: 'Knižnica', value: 'library' },
  { label: 'Iné', value: 'other' }
])
const selectedType = ref('all')
const games = ref([]) 
const loadingGames = ref(true)
const showingMyProjects = ref(false) 

const filteredGames = computed(() => {
  return games.value.filter(
    (g) => {
      const matchesSearch = g.title.toLowerCase().includes(search.value.toLowerCase())
      const matchesCategory = !selectedCategory.value || selectedCategory.value.value === null || g.category === selectedCategory.value.name
      return matchesSearch && matchesCategory
    }
  )
})
const viewProjectDetail = (project) => {
  router.push({ name: 'ProjectDetail', params: { id: project.id } })
}

// -------------------------
// Načítanie dát
// -------------------------
async function loadAcademicYears() {
    if (!token.value) return
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

// Načítanie aktuálneho používateľa (pre skrytie tlačidla odstránenia pri sebe)
async function loadCurrentUser() {
  if (!token.value) return
  try {
    const res = await fetch(`${API_URL}/api/user`, {
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    })
    if (res.ok) {
      const data = await res.json()
      currentUserId.value = data.id
    }
  } catch (_) {}
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
                // Try restore previously selected team
                const storedId = localStorage.getItem('active_team_id')
                const found = storedId ? teams.value.find(t => String(t.id) === storedId) : null
                selectedTeam.value = found || teams.value[0] // Select restored or first team
                setActiveTeam(selectedTeam.value)
                console.log('✅ Používateľ je v tímoch:', data.teams.map(t => t.name).join(', '));
            } else {
                hasTeam.value = false;
                teams.value = [];
                selectedTeam.value = null;
                setActiveTeam(null); // Clear localStorage and notify Navbar
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
    showingMyProjects.value = false
    if (!token.value) {
        loadingGames.value = false
        return
    }
    loadingGames.value = true
    try {
        const query = selectedType.value && selectedType.value !== 'all' ? `?type=${encodeURIComponent(selectedType.value)}` : ''
        const res = await fetch(`${API_URL}/api/projects${query}`, {
            headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
        })
        
        if (res.ok) {
            const data = await res.json()
            games.value = data
        } else if (res.status === 404) {
            toast.add({ severity: 'error', summary: 'Chyba Načítania Projektov (404)', detail: 'Chýba routa GET /api/projects. Pridajte ju, prosím, do routes/api.php.', life: 10000 })
        }
         else {
            toast.add({ severity: 'error', summary: 'Chyba Načítania Projektov', detail: `Nepodarilo sa načítať zoznam projektov zo servera. Status: ${res.status}`, life: 5000 })
        }
    } catch (err) {
        console.error('❌ FATÁLNA CHYBA SIETE pri načítaní všetkých projektov. Server pravdepodobne nie je spustený alebo je nedostupný.', err)
        toast.add({ severity: 'fatal', summary: 'Chyba Pripojenia', detail: 'Server je nedostupný (Connection refused). Problém s komunikáciou pri načítaní projektov.', life: 10000 })
    } finally {
        loadingGames.value = false
    }
}

function confirmRemoveMember(team, member) {
  if (removingMember.value) return
  const ok = window.confirm(`Odstrániť člena "${member.name}" z tímu "${team.name}"?`)
  if (ok) removeMember(team, member)
}

function confirmLeaveTeam(team) {
  if (removingMember.value) return
  const ok = window.confirm(`Naozaj chcete opustiť tím "${team.name}"?`)
  if (ok) leaveTeam(team)
}

// Load only projects for active team
async function loadMyProjects(){
  if(!token.value || !selectedTeam.value) return
  showingMyProjects.value = true
  loadingGames.value = true
  try {
    const res = await fetch(`${API_URL}/api/projects/my?team_id=${selectedTeam.value.id}`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    if(res.ok){
      const data = await res.json()
      games.value = data.projects || []
      const count = data.count || games.value.length
      if(count === 0){
        toast.add({ severity: 'info', summary: 'Žiadne projekty', detail: 'Váš tím zatiaľ nemá žiadne projekty.', life: 3000 })
      } else {
        toast.add({ severity: 'success', summary: 'Filtrované', detail: `Zobrazených ${count} projektov vášho tímu.`, life: 3000 })
      }
    } else {
      const errorData = await res.json().catch(() => ({}))
      toast.add({ severity: 'warn', summary: 'Chyba', detail: errorData.message || 'Nepodarilo sa načítať projekty tímu.', life: 4000 })
    }
  } catch(_) {
    toast.add({ severity: 'error', summary: 'Chyba siete', detail: 'Server je nedostupný.', life: 5000 })
  } finally {
    loadingGames.value = false
  }
}

async function removeMember(team, member) {
  removingMember.value = true
  try {
    const res = await fetch(`${API_URL}/api/teams/${team.id}/members/${member.id}`, {
      method: 'DELETE',
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    })
    let msg = 'Nepodarilo sa odstrániť člena.'
    try { const data = await res.clone().json(); if (data?.message) msg = data.message; if (data?.team?.members) team.members = data.team.members } catch (_) {}
    if (res.ok) {
      toast.add({ severity: 'success', summary: 'Člen odstránený', detail: `${member.name} bol odstránený.`, life: 4000 })
    } else {
      toast.add({ severity: 'warn', summary: 'Operácia zlyhala', detail: msg, life: 6000 })
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Chyba siete', detail: 'Server je nedostupný.', life: 6000 })
  } finally {
    removingMember.value = false
  }
}

async function leaveTeam(team) {
  removingMember.value = true
  try {
    const res = await fetch(`${API_URL}/api/teams/${team.id}/leave`, {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    })
    let msg = 'Nepodarilo sa opustiť tím.'
    try { const data = await res.clone().json(); if (data?.message) msg = data.message } catch (_) {}
    if (res.ok) {
      toast.add({ severity: 'success', summary: 'Tím opustený', detail: `Úspešne ste opustili tím ${team.name}.`, life: 4000 })
      await loadTeamStatus()
      setActiveTeam(teams.value[0] || null)
      showTeamStatusDialog.value = false
    } else {
      toast.add({ severity: 'warn', summary: 'Operácia zlyhala', detail: msg, life: 6000 })
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Chyba siete', detail: 'Server je nedostupný.', life: 6000 })
  } finally {
    removingMember.value = false
  }
}


onMounted(() => {
  loadAcademicYears()
  loadTeamStatus() 
  loadAllGames() 
  loadCurrentUser()
})

// React to user changing selected team via dropdown
watch(selectedTeam, (val) => {
  setActiveTeam(val)
})
watch(selectedType, () => { loadAllGames() })

// Helper to resolve splash image path (local storage or absolute URL)
function getSplashUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${API_URL}/storage/${path}`
}

function formatProjectType(type) {
  const typeMap = {
    game: 'Hra',
    web_app: 'Web App',
    mobile_app: 'Mobile App',
    library: 'Knižnica',
    other: 'Iné'
  }
  return typeMap[type] || type
}

function goToTeam(teamId) {
  if (teamId) {
    router.push(`/team/${teamId}`)
  }
}
</script>