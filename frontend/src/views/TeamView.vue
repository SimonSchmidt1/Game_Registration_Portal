<template>
  <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8 bg-gray-900 min-h-screen">
    <Toast />

    <!-- Loading State -->
    <div v-if="loading" class="text-center p-12 text-lg text-gray-300">
      <i class="pi pi-spin pi-spinner text-4xl mr-2 text-blue-400"></i> Načítavam tím...
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center p-12">
      <div class="bg-red-900 border border-red-700 rounded-2xl p-8 text-red-100">
        <i class="pi pi-exclamation-triangle text-4xl mb-4"></i>
        <p class="text-lg">{{ error }}</p>
        <Button label="Späť na projekty" class="mt-4" @click="$router.push('/')" />
      </div>
    </div>

    <!-- Team Detail -->
    <div v-else-if="team" class="space-y-6">
      <!-- Header with Back Button -->
      <div class="flex items-center gap-4 mb-6">
        <Button 
          icon="pi pi-arrow-left" 
          class="p-button-text p-button-lg text-white"
          @click="$router.push('/')" 
        />
        <h1 class="text-4xl font-bold text-white">{{ team.name }}</h1>
      </div>

      <!-- Team Info Card -->
      <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-2xl p-8 shadow-xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Left Column -->
          <div class="space-y-6">
            <div>
              <h3 class="text-sm font-semibold text-gray-400 mb-2">Základné Informácie</h3>
              <div class="space-y-3">
                <div class="flex items-center gap-3">
                  <i class="pi pi-calendar text-blue-400"></i>
                  <div>
                    <span class="text-gray-400 text-sm">Akademický rok:</span>
                    <p class="text-white font-medium">{{ team.academic_year?.name || 'N/A' }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <i class="pi pi-users text-blue-400"></i>
                  <div>
                    <span class="text-gray-400 text-sm">Počet členov:</span>
                    <p class="text-white font-medium">{{ team.members?.length || 0 }}/10</p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <i class="pi pi-briefcase text-blue-400"></i>
                  <div>
                    <span class="text-gray-400 text-sm">Počet projektov:</span>
                    <p class="text-white font-medium">{{ projectCount }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Invite Code -->
            <div class="bg-gray-900 rounded-lg p-4">
              <h3 class="text-sm font-semibold text-gray-400 mb-2">Kód pre pripojenie</h3>
              <div class="flex items-center gap-3">
                <span class="text-2xl font-mono tracking-widest text-blue-300">
                  {{ team.invite_code }}
                </span>
                <Button 
                  icon="pi pi-copy"
                  class="p-button-sm p-button-text"
                  @click="copyInviteCode"
                  v-tooltip.top="'Kopírovať kód'"
                />
              </div>
            </div>
          </div>

          <!-- Right Column: Team Members -->
          <div>
            <h3 class="text-sm font-semibold text-gray-400 mb-4">Členovia Tímu</h3>
            <div class="space-y-2">
              <div 
                v-for="member in team.members" 
                :key="member.id"
                class="flex items-center justify-between gap-3 bg-gray-900 rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-800 transition"
                @click="showMemberDetails(member)"
              >
                <div class="flex items-center gap-3">
                  <div 
                    v-if="member.avatar_path"
                    class="w-10 h-10 rounded-full overflow-hidden border-2 border-gray-700"
                  >
                    <img 
                      :src="getAvatarUrl(member.avatar_path)" 
                      :alt="member.name"
                      class="w-full h-full object-cover"
                    />
                  </div>
                  <div 
                    v-else
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold"
                  >
                    {{ member.name?.charAt(0).toUpperCase() }}
                  </div>
                  <div class="flex-1">
                    <p class="text-white font-medium">{{ member.name }}</p>
                    <p class="text-xs text-gray-400">{{ member.email }}</p>
                    <div v-if="member.pivot?.occupation" class="mt-2">
                      <span class="px-2 py-1 bg-indigo-900 border border-indigo-700 text-indigo-300 rounded-md text-xs font-medium">
                        {{ member.pivot.occupation }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center">
                  <span 
                    v-if="isScumMaster(member)"
                    class="px-3 py-1 bg-blue-900 border border-blue-700 text-blue-300 rounded-md text-xs font-semibold"
                  >
                    S
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Team Projects -->
      <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-2xl p-8 shadow-xl">
        <h2 class="text-2xl font-bold text-white mb-6">Projekty Tímu</h2>
        
        <div v-if="loadingProjects" class="text-center p-8 text-gray-300">
          <i class="pi pi-spin pi-spinner text-3xl text-blue-400"></i>
          <p class="mt-2">Načítavam projekty...</p>
        </div>

        <div v-else-if="projects.length === 0" class="text-center p-8 text-gray-400">
          <i class="pi pi-inbox text-4xl mb-4"></i>
          <p>Tento tím zatiaľ nemá žiadne projekty.</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="project in projects"
            :key="project.id"
            class="bg-gray-900 border border-gray-700 rounded-xl p-4 hover:border-gray-600 transition cursor-pointer"
            @click="$router.push(`/project/${project.id}`)"
          >
            <div class="aspect-video bg-gray-950 rounded-lg mb-3 overflow-hidden flex items-center justify-center">
              <span v-if="!project.splash_screen_path" class="text-xs text-gray-500">Bez náhľadu</span>
              <img 
                v-else 
                :src="getSplashUrl(project.splash_screen_path)" 
                :alt="project.title"
                class="object-cover w-full h-full"
              />
            </div>
            
            <h3 class="text-white font-semibold mb-2 line-clamp-1">{{ project.title }}</h3>
            
            <div class="flex flex-wrap gap-2 text-xs mb-2">
              <span class="px-2 py-1 rounded bg-teal-700 text-teal-100 uppercase">
                {{ formatProjectType(project.type) }}
              </span>
              <span v-if="project.school_type" class="px-2 py-1 rounded bg-blue-700 text-blue-100">
                {{ getSchoolTypeLabel(project.school_type) }}
              </span>
              <span v-if="project.year_of_study" class="px-2 py-1 rounded bg-purple-700 text-purple-100">
                {{ project.year_of_study }}. ročník
              </span>
              <span v-if="project.subject" class="px-2 py-1 rounded bg-green-700 text-green-100">
                {{ project.subject }}
              </span>
            </div>
            
            <p class="text-gray-400 text-sm line-clamp-2">
              {{ project.description || 'Bez popisu' }}
            </p>

            <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
              <div class="flex items-center gap-1">
                <i class="pi pi-star-fill text-yellow-500"></i>
                <span>{{ project.rating || '0.0' }}</span>
              </div>
              <div class="flex items-center gap-1">
                <i class="pi pi-eye"></i>
                <span>{{ project.views || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Member Details Dialog -->
  <Dialog 
    v-model:visible="showMemberDialog" 
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
        <span class="text-gray-100 font-medium text-lg w-full">Informácie o členovi</span>
      </div>
    </template>
    
    <div v-if="selectedMember" class="flex flex-col gap-6">
      <!-- Avatar Section -->
      <div class="flex flex-col items-center gap-4 p-6 bg-gray-800 rounded-lg">
        <div class="relative">
          <img 
            v-if="selectedMember.avatar_path" 
            :src="getAvatarUrl(selectedMember.avatar_path)" 
            alt="Avatar"
            class="w-24 h-24 rounded-full object-cover border-4 border-blue-500 shadow-lg"
          />
          <div v-else class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-4xl shadow-lg border-4 border-blue-500">
            {{ selectedMember.name?.charAt(0).toUpperCase() }}
          </div>
        </div>
      </div>
   
      <!-- Member Info -->
      <div class="flex flex-col gap-3">
        <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
          <span class="text-gray-400 font-medium">
            Meno:
          </span>
          <span class="text-white font-semibold">{{ selectedMember.name }}</span>
        </div>
        
        <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
          <span class="text-gray-400 font-medium">
            Email:
          </span>
          <span class="text-white font-semibold text-sm">{{ selectedMember.email }}</span>
        </div>
        
        <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
          <span class="text-gray-400 font-medium">
            Typ študenta:
          </span>
          <span v-if="selectedMember.student_type" class="px-3 py-1 bg-purple-900 border border-purple-700 text-purple-300 rounded-md text-sm font-semibold">
            {{ getStudentTypeLabel(selectedMember.student_type) }}
          </span>
          <span v-else class="text-gray-500 text-sm">Neuvedené</span>
        </div>

        <div v-if="selectedMember.pivot?.occupation" class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
          <span class="text-gray-400 font-medium">
            Povolanie:
          </span>
          <span class="px-3 py-1 bg-indigo-900 border border-indigo-700 text-indigo-300 rounded-md text-sm font-semibold">
            {{ selectedMember.pivot.occupation }}
          </span>
        </div>

        <div v-if="isScumMaster(selectedMember)" class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
          <span class="text-gray-400 font-medium">
            Rola:
          </span>
          <span class="px-3 py-1 bg-blue-900 border border-blue-700 text-blue-300 rounded-md text-sm font-semibold">
            S
          </span>
        </div>
      </div>
   
      <div class="flex gap-2">
        <Button 
          label="Zavrieť" 
          class="p-button-outlined w-full"
          @click="showMemberDialog = false" 
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const API_URL = import.meta.env.VITE_API_URL
const route = useRoute()
const router = useRouter()
const toast = useToast()

const team = ref(null)
const projects = ref([])
const loading = ref(true)
const loadingProjects = ref(true)
const error = ref(null)
const showMemberDialog = ref(false)
const selectedMember = ref(null)

const projectCount = computed(() => projects.value.length)

async function loadTeam() {
  const teamId = route.params.id
  const token = localStorage.getItem('access_token')
  
  try {
    const res = await fetch(`${API_URL}/api/teams/${teamId}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    if (!res.ok) {
      if (res.status === 404) {
        error.value = 'Tím nebol nájdený.'
      } else {
        error.value = 'Nepodarilo sa načítať tím.'
      }
      return
    }

    const data = await res.json()
    team.value = data.team || data
  } catch (err) {
    console.error('Error loading team:', err)
    error.value = 'Chyba pri komunikácii so serverom.'
  } finally {
    loading.value = false
  }
}

async function loadProjects() {
  const teamId = route.params.id
  const token = localStorage.getItem('access_token')
  
  try {
    const res = await fetch(`${API_URL}/api/projects/my?team_id=${teamId}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    if (res.ok) {
      const data = await res.json()
      projects.value = data.projects || []
    }
  } catch (err) {
    console.error('Error loading projects:', err)
  } finally {
    loadingProjects.value = false
  }
}

function isScumMaster(member) {
  return member.pivot?.role_in_team === 'scrum_master' || team.value.scrum_master_id === member.id
}

function getAvatarUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${API_URL}/storage/${path}?t=${Date.now()}`
}

function getSplashUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${API_URL}/storage/${path}`
}

function formatProjectType(type) {
  return type.replace(/_/g, ' ')
}

function getSchoolTypeLabel(type) {
  const map = {
    'zs': 'ZŠ',
    'ss': 'SŠ',
    'vs': 'VŠ'
  }
  return map[type] || type
}

function copyInviteCode() {
  if (team.value?.invite_code) {
    navigator.clipboard.writeText(team.value.invite_code)
    toast.add({ 
      severity: 'success', 
      summary: 'Kód skopírovaný', 
      detail: 'Kód bol skopírovaný do schránky.', 
      life: 3000 
    })
  }
}

function showMemberDetails(member) {
  selectedMember.value = member
  showMemberDialog.value = true
}

function getStudentTypeLabel(type) {
  if (!type) return 'Neuvedené'
  const map = {
    'denny': 'Denný študent',
    'externy': 'Externý študent'
  }
  return map[type] || type
}

onMounted(() => {
  loadTeam()
  loadProjects()
})
</script>
