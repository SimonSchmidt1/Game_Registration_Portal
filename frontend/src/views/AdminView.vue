<template>
  <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 bg-gray-900 min-h-screen">
    <Toast />

    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
          <Button 
            icon="pi pi-arrow-left" 
            class="p-button-text p-button-lg text-white"
            @click="$router.push('/')" 
          />
          <h1 class="text-3xl font-bold text-white">Admin Panel</h1>
        </div>
        <Button 
          icon="pi pi-refresh" 
          label="Obnoviť"
          class="p-button-outlined"
          @click="loadData"
          :loading="loading"
        />
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-800 to-blue-900 rounded-xl p-4 border border-blue-700">
          <div class="text-blue-300 text-sm mb-1">Celkom tímov</div>
          <div class="text-3xl font-bold text-white">{{ stats.total_teams || 0 }}</div>
        </div>
        <div class="bg-gradient-to-br from-green-800 to-green-900 rounded-xl p-4 border border-green-700">
          <div class="text-green-300 text-sm mb-1">Celkom projektov</div>
          <div class="text-3xl font-bold text-white">{{ stats.total_projects || 0 }}</div>
        </div>
        <div class="bg-gradient-to-br from-purple-800 to-purple-900 rounded-xl p-4 border border-purple-700">
          <div class="text-purple-300 text-sm mb-1">Používatelia</div>
          <div class="text-3xl font-bold text-white">{{ stats.total_users || 0 }}</div>
        </div>
        <div 
          :class="[
            'bg-gradient-to-br rounded-xl p-4 border transition-all',
            (stats.pending_teams || 0) > 0 
              ? 'from-yellow-700 to-orange-800 border-yellow-500 shadow-lg shadow-yellow-500/50 animate-pulse' 
              : 'from-yellow-800 to-yellow-900 border-yellow-700'
          ]"
        >
          <div class="flex items-center justify-between">
            <div>
              <div class="text-yellow-300 text-sm mb-1">Čakajúce tímy</div>
              <div class="text-3xl font-bold text-white">{{ stats.pending_teams || 0 }}</div>
            </div>
            <i 
              v-if="(stats.pending_teams || 0) > 0" 
              class="pi pi-bell text-yellow-300 text-2xl animate-bounce"
            ></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Teams Section (if any) -->
    <div v-if="pendingTeams.length > 0" class="mb-8 animate-fade-in">
      <div class="bg-gradient-to-br from-yellow-900/50 to-orange-900/50 rounded-2xl p-6 border-2 border-yellow-500 shadow-xl shadow-yellow-500/20">
        <h2 class="text-xl font-bold text-yellow-300 mb-4 flex items-center gap-2">
          <i class="pi pi-bell text-yellow-400 animate-pulse"></i>
          <span>Čakajúce na schválenie</span>
          <span class="px-3 py-1 bg-yellow-600 text-white rounded-full text-sm font-bold animate-pulse">
            {{ pendingTeams.length }}
          </span>
        </h2>
        <div class="grid gap-3">
          <div 
            v-for="team in pendingTeams" 
            :key="team.id"
            class="flex items-center justify-between bg-gray-800 rounded-lg p-4 border border-gray-700"
          >
            <div>
              <span class="font-semibold text-white">{{ team.name }}</span>
              <span class="text-gray-400 text-sm ml-2">
                ({{ team.scrum_master?.name || 'Neznámy' }})
              </span>
              <span v-if="team.academic_year" class="text-gray-500 text-xs ml-2">
                {{ team.academic_year.name }}
              </span>
            </div>
            <div class="flex gap-2">
              <Button 
                icon="pi pi-check" 
                label="Schváliť"
                class="p-button-sm p-button-success"
                @click="approveTeam(team)"
              />
              <Button 
                icon="pi pi-times" 
                label="Zamietnuť"
                class="p-button-sm p-button-danger p-button-outlined"
                @click="showRejectDialog(team)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-5 border border-gray-700 mb-6">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
          <span class="p-input-icon-left w-full">
            <i class="pi pi-search" />
            <InputText 
              v-model="searchQuery" 
              placeholder="Hľadať tím podľa názvu..." 
              class="w-full"
              @input="filterTeams"
            />
          </span>
        </div>
        <Dropdown
          v-model="statusFilter"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Filter podľa stavu"
          class="w-full sm:w-48"
          @change="filterTeams"
        />
      </div>
    </div>

    <!-- Teams Overview Table -->
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border border-gray-700 overflow-hidden">
      <div class="p-5 border-b border-gray-700">
        <h2 class="text-xl font-bold text-white flex items-center gap-2">
          <i class="pi pi-users"></i>
          Prehľad tímov ({{ filteredTeams.length }})
        </h2>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <i class="pi pi-spin pi-spinner text-4xl text-blue-400"></i>
        <p class="mt-4 text-gray-400">Načítavam...</p>
      </div>

      <div v-else-if="filteredTeams.length === 0" class="p-8 text-center text-gray-400">
        <i class="pi pi-inbox text-4xl mb-4"></i>
        <p>Žiadne tímy nenájdené</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-900">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Tím</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademický rok</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Členovia</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Projekty</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">
                <span title="Má projekt">Projekt</span>
              </th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">
                <span title="Má video">Video</span>
              </th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">
                <span title="Má náhľad">Náhľad</span>
              </th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">
                <span title="Má export">Export</span>
              </th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Stav</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Akcie</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-700">
            <tr 
              v-for="team in filteredTeams" 
              :key="team.id"
              class="hover:bg-gray-800/50 transition"
            >
              <td class="px-4 py-4">
                <div>
                  <div class="font-semibold text-white">{{ team.name }}</div>
                  <div class="text-xs text-gray-500">{{ team.invite_code }}</div>
                </div>
              </td>
              <td class="px-4 py-4 text-gray-300">
                {{ team.academic_year?.name || '-' }}
              </td>
              <td class="px-4 py-4 text-center text-gray-300">
                {{ team.members_count }}
              </td>
              <td class="px-4 py-4 text-center text-gray-300">
                {{ team.projects_count }}
              </td>
              <td class="px-4 py-4 text-center">
                <i v-if="team.has_project" class="pi pi-check-circle text-green-500 text-lg"></i>
                <i v-else class="pi pi-times-circle text-red-500 text-lg"></i>
              </td>
              <td class="px-4 py-4 text-center">
                <i v-if="team.has_video" class="pi pi-check-circle text-green-500 text-lg"></i>
                <i v-else class="pi pi-times-circle text-red-500 text-lg"></i>
              </td>
              <td class="px-4 py-4 text-center">
                <i v-if="team.has_splash" class="pi pi-check-circle text-green-500 text-lg"></i>
                <i v-else class="pi pi-times-circle text-red-500 text-lg"></i>
              </td>
              <td class="px-4 py-4 text-center">
                <i v-if="team.has_export" class="pi pi-check-circle text-green-500 text-lg"></i>
                <i v-else class="pi pi-times-circle text-red-500 text-lg"></i>
              </td>
              <td class="px-4 py-4 text-center">
                <span 
                  :class="getStatusClass(team.status)"
                  class="px-2 py-1 rounded-md text-xs font-semibold"
                >
                  {{ getStatusLabel(team.status) }}
                </span>
              </td>
              <td class="px-4 py-4 text-right">
                <div class="flex justify-end gap-1">
                  <Button 
                    icon="pi pi-eye" 
                    class="p-button-sm p-button-text p-button-rounded"
                    v-tooltip.top="'Zobraziť detail'"
                    @click="showTeamDetails(team)"
                  />
                  <Button 
                    icon="pi pi-pencil" 
                    class="p-button-sm p-button-text p-button-rounded"
                    v-tooltip.top="'Upraviť'"
                    @click="showEditDialog(team)"
                  />
                  <Button 
                    icon="pi pi-trash" 
                    class="p-button-sm p-button-text p-button-rounded p-button-danger"
                    v-tooltip.top="'Zmazať'"
                    @click="confirmDeleteTeam(team)"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Team Details Dialog -->
    <Dialog 
      v-model:visible="showDetailsDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-2/3 lg:w-1/2"
      :contentStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', padding: '1.5rem' }"
      :headerStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', borderBottom: '1px solid #374151' }"
    >
      <template #header>
        <span class="text-lg font-semibold">Detail tímu: {{ selectedTeam?.name }}</span>
      </template>

      <div v-if="selectedTeamDetails" class="space-y-6">
        <!-- Team Info -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-800 rounded-lg p-3">
            <div class="text-gray-400 text-sm">Kód pre pripojenie</div>
            <div class="text-white font-mono text-lg">{{ selectedTeamDetails.team.invite_code }}</div>
          </div>
          <div class="bg-gray-800 rounded-lg p-3">
            <div class="text-gray-400 text-sm">Akademický rok</div>
            <div class="text-white">{{ selectedTeamDetails.team.academic_year?.name || '-' }}</div>
          </div>
        </div>

        <!-- Members -->
        <div>
          <h3 class="text-lg font-semibold text-white mb-3">Členovia ({{ selectedTeamDetails.members.length }})</h3>
          <div class="space-y-2">
            <div 
              v-for="member in selectedTeamDetails.members" 
              :key="member.id"
              class="flex items-center justify-between bg-gray-800 rounded-lg p-3"
            >
              <div>
                <span class="text-white font-medium">{{ member.name }}</span>
                <span class="text-gray-400 text-sm ml-2">{{ member.email }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span v-if="member.occupation" class="px-2 py-1 bg-indigo-900 text-indigo-300 rounded text-xs">
                  {{ member.occupation }}
                </span>
                <span 
                  v-if="member.role_in_team === 'scrum_master'"
                  class="px-2 py-1 bg-blue-900 text-blue-300 rounded text-xs font-semibold"
                >
                  SM
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Projects -->
        <div>
          <h3 class="text-lg font-semibold text-white mb-3">Projekty ({{ selectedTeamDetails.projects.length }})</h3>
          <div v-if="selectedTeamDetails.projects.length === 0" class="text-gray-400 text-center py-4">
            Žiadne projekty
          </div>
          <div v-else class="space-y-2">
            <div 
              v-for="project in selectedTeamDetails.projects" 
              :key="project.id"
              class="bg-gray-800 rounded-lg p-3"
            >
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-white font-medium">{{ project.title }}</span>
                  <span class="text-gray-400 text-xs ml-2">({{ project.type }})</span>
                </div>
                <div class="flex items-center gap-3 text-xs">
                  <span class="flex items-center gap-1">
                    <i class="pi pi-star-fill text-yellow-500"></i>
                    {{ project.rating || '0.0' }}
                  </span>
                  <span class="flex items-center gap-1">
                    <i class="pi pi-eye text-gray-400"></i>
                    {{ project.views || 0 }}
                  </span>
                </div>
              </div>
              <div class="flex gap-2 mt-2">
                <span v-if="project.has_video" class="px-2 py-1 bg-green-900 text-green-300 rounded text-xs">Video ✓</span>
                <span v-if="project.has_splash" class="px-2 py-1 bg-green-900 text-green-300 rounded text-xs">Náhľad ✓</span>
                <span v-if="project.has_export" class="px-2 py-1 bg-green-900 text-green-300 rounded text-xs">Export ✓</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Zavrieť" class="p-button-text" @click="showDetailsDialog = false" />
      </template>
    </Dialog>

    <!-- Edit Team Dialog -->
    <Dialog 
      v-model:visible="showEditTeamDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-1/3"
      :contentStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', padding: '1.5rem' }"
      :headerStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', borderBottom: '1px solid #374151' }"
    >
      <template #header>
        <span class="text-lg font-semibold">Upraviť tím</span>
      </template>

      <div class="space-y-4">
        <div>
          <label class="block text-gray-300 mb-1">Názov tímu</label>
          <InputText v-model="editForm.name" class="w-full" />
        </div>
        <div>
          <label class="block text-gray-300 mb-1">Stav</label>
          <Dropdown
            v-model="editForm.status"
            :options="statusOptions.filter(s => s.value)"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Zrušiť" class="p-button-text" @click="showEditTeamDialog = false" />
        <Button label="Uložiť" icon="pi pi-check" @click="saveTeamEdit" :loading="saving" />
      </template>
    </Dialog>

    <!-- Reject Team Dialog -->
    <Dialog 
      v-model:visible="showRejectTeamDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-1/3"
      :contentStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', padding: '1.5rem' }"
      :headerStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', borderBottom: '1px solid #374151' }"
    >
      <template #header>
        <span class="text-lg font-semibold">Zamietnuť tím</span>
      </template>

      <div class="space-y-4">
        <p class="text-gray-300">Naozaj chcete zamietnuť tím <strong>{{ selectedTeam?.name }}</strong>?</p>
        <div>
          <label class="block text-gray-300 mb-1">Dôvod zamietnutia (voliteľné)</label>
          <Textarea v-model="rejectReason" rows="3" class="w-full" placeholder="Uveďte dôvod..." />
        </div>
      </div>

      <template #footer>
        <Button label="Zrušiť" class="p-button-text" @click="showRejectTeamDialog = false" />
        <Button label="Zamietnuť" icon="pi pi-times" class="p-button-danger" @click="rejectTeam" :loading="saving" />
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog 
      v-model:visible="showDeleteDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-1/3"
      :contentStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', padding: '1.5rem' }"
      :headerStyle="{ backgroundColor: '#1f2937', color: '#f3f4f6', borderBottom: '1px solid #374151' }"
    >
      <template #header>
        <span class="text-lg font-semibold text-red-400">Zmazať tím</span>
      </template>

      <div class="text-center">
        <i class="pi pi-exclamation-triangle text-5xl text-yellow-500 mb-4"></i>
        <p class="text-gray-300">
          Naozaj chcete zmazať tím <strong class="text-white">{{ selectedTeam?.name }}</strong>?
        </p>
        <p class="text-gray-400 text-sm mt-2">Táto akcia je nevratná.</p>
      </div>

      <template #footer>
        <Button label="Zrušiť" class="p-button-text" @click="showDeleteDialog = false" />
        <Button label="Zmazať" icon="pi pi-trash" class="p-button-danger" @click="deleteTeam" :loading="saving" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const toast = useToast()

// State
const loading = ref(true)
const saving = ref(false)
const stats = ref({})
const teams = ref([])
const searchQuery = ref('')
const statusFilter = ref(null)

// Dialogs
const showDetailsDialog = ref(false)
const showEditTeamDialog = ref(false)
const showRejectTeamDialog = ref(false)
const showDeleteDialog = ref(false)
const selectedTeam = ref(null)
const selectedTeamDetails = ref(null)
const editForm = ref({ name: '', status: '' })
const rejectReason = ref('')

// Options
const statusOptions = ref([
  { label: 'Všetky', value: null },
  { label: 'Aktívne', value: 'active' },
  { label: 'Čakajúce', value: 'pending' },
  { label: 'Pozastavené', value: 'suspended' },
])

// Computed
const pendingTeams = computed(() => teams.value.filter(t => t.status === 'pending'))

const filteredTeams = computed(() => {
  let result = teams.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(t => t.name.toLowerCase().includes(query))
  }

  if (statusFilter.value) {
    result = result.filter(t => t.status === statusFilter.value)
  }

  return result
})

// Methods
async function loadData() {
  loading.value = true
  const token = localStorage.getItem('access_token')

  try {
    // Load stats and teams in parallel
    const [statsRes, teamsRes] = await Promise.all([
      fetch(`${API_URL}/api/admin/stats`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
      }),
      fetch(`${API_URL}/api/admin/teams`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
      })
    ])

    if (statsRes.ok) {
      const statsData = await statsRes.json()
      stats.value = statsData
      console.log('✅ Stats loaded:', statsData)
    } else {
      const errorData = await statsRes.json().catch(() => ({}))
      console.error('❌ Stats error:', statsRes.status, errorData)
      toast.add({ 
        severity: 'error', 
        summary: 'Chyba štatistík', 
        detail: errorData.error || errorData.message || `HTTP ${statsRes.status}`, 
        life: 5000 
      })
    }

    if (teamsRes.ok) {
      const data = await teamsRes.json()
      teams.value = data.teams || []
      console.log('✅ Teams loaded:', data.teams?.length || 0)
    } else if (teamsRes.status === 403) {
      toast.add({ severity: 'error', summary: 'Prístup zamietnutý', detail: 'Nemáte oprávnenie na prístup k admin panelu', life: 5000 })
      router.push('/')
    } else {
      const errorData = await teamsRes.json().catch(() => ({}))
      console.error('❌ Teams error:', teamsRes.status, errorData)
      toast.add({ 
        severity: 'error', 
        summary: 'Chyba načítania tímov', 
        detail: errorData.error || errorData.message || `HTTP ${teamsRes.status}`, 
        life: 5000 
      })
    }
  } catch (err) {
    console.error('Error loading admin data:', err)
    toast.add({ severity: 'error', summary: 'Chyba', detail: 'Nepodarilo sa načítať dáta', life: 4000 })
  } finally {
    loading.value = false
  }
}

function filterTeams() {
  // Filtering is handled by computed property
}

function getStatusClass(status) {
  switch (status) {
    case 'active': return 'bg-green-900 text-green-300 border border-green-700'
    case 'pending': return 'bg-yellow-900 text-yellow-300 border border-yellow-700'
    case 'suspended': return 'bg-red-900 text-red-300 border border-red-700'
    default: return 'bg-gray-700 text-gray-300 border border-gray-600'
  }
}

function getStatusLabel(status) {
  switch (status) {
    case 'active': return 'Aktívny'
    case 'pending': return 'Čakajúci'
    case 'suspended': return 'Pozastavený'
    default: return status || 'Aktívny'
  }
}

async function showTeamDetails(team) {
  selectedTeam.value = team
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${team.id}`, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    if (res.ok) {
      selectedTeamDetails.value = await res.json()
      showDetailsDialog.value = true
    } else {
      toast.add({ severity: 'error', summary: 'Chyba', detail: 'Nepodarilo sa načítať detail tímu', life: 4000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Chyba', detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  }
}

function showEditDialog(team) {
  selectedTeam.value = team
  editForm.value = { name: team.name, status: team.status || 'active' }
  showEditTeamDialog.value = true
}

async function saveTeamEdit() {
  if (!editForm.value.name.trim()) {
    toast.add({ severity: 'warn', summary: 'Upozornenie', detail: 'Názov tímu je povinný', life: 3000 })
    return
  }

  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${selectedTeam.value.id}`, {
      method: 'PUT',
      headers: { 
        'Authorization': `Bearer ${token}`, 
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(editForm.value)
    })

    if (res.ok) {
      toast.add({ severity: 'success', summary: 'Úspech', detail: 'Tím bol aktualizovaný', life: 3000 })
      showEditTeamDialog.value = false
      loadData()
    } else {
      const data = await res.json()
      toast.add({ severity: 'error', summary: 'Chyba', detail: data.error || 'Nepodarilo sa aktualizovať tím', life: 4000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Chyba', detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

function confirmDeleteTeam(team) {
  selectedTeam.value = team
  showDeleteDialog.value = true
}

async function deleteTeam() {
  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${selectedTeam.value.id}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    const data = await res.json().catch(() => ({}))

    if (res.ok) {
      console.log('✅ Team deleted successfully:', data)
      toast.add({ severity: 'success', summary: 'Úspech', detail: data.message || 'Tím bol zmazaný', life: 3000 })
      showDeleteDialog.value = false
      selectedTeam.value = null
      // Reload data to refresh the list
      await loadData()
    } else {
      console.error('❌ Delete failed:', res.status, data)
      toast.add({ 
        severity: 'error', 
        summary: 'Chyba', 
        detail: data.error || data.message || `Nepodarilo sa zmazať tím (HTTP ${res.status})`, 
        life: 5000 
      })
    }
  } catch (err) {
    console.error('❌ Delete error:', err)
    toast.add({ severity: 'error', summary: 'Chyba', detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

async function approveTeam(team) {
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${team.id}/approve`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    if (res.ok) {
      toast.add({ severity: 'success', summary: 'Úspech', detail: `Tím '${team.name}' bol schválený`, life: 4000 })
      loadData()
    } else {
      const data = await res.json()
      toast.add({ severity: 'error', summary: 'Chyba', detail: data.error || data.message || 'Nepodarilo sa schváliť tím', life: 4000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Chyba', detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  }
}

function showRejectDialog(team) {
  selectedTeam.value = team
  rejectReason.value = ''
  showRejectTeamDialog.value = true
}

async function rejectTeam() {
  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${selectedTeam.value.id}/reject`, {
      method: 'POST',
      headers: { 
        'Authorization': `Bearer ${token}`, 
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ reason: rejectReason.value })
    })

    if (res.ok) {
      toast.add({ severity: 'success', summary: 'Úspech', detail: `Tím '${selectedTeam.value.name}' bol zamietnutý`, life: 4000 })
      showRejectTeamDialog.value = false
      loadData()
    } else {
      const data = await res.json()
      toast.add({ severity: 'error', summary: 'Chyba', detail: data.error || data.message || 'Nepodarilo sa zamietnuť tím', life: 4000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Chyba', detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

// Check if user is admin on mount
onMounted(async () => {
  const token = localStorage.getItem('access_token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  if (!token || user.role !== 'admin') {
    toast.add({ severity: 'error', summary: 'Prístup zamietnutý', detail: 'Táto stránka je dostupná len pre administrátorov', life: 5000 })
    router.push('/')
    return
  }

  loadData()
})
</script>

