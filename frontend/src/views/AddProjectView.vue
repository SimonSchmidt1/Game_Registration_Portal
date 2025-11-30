<template>
  <Toast />
  <div class="max-w-2xl mx-auto mt-10 border rounded-xl p-6 shadow-sm bg-gray-900 text-gray-100 border-gray-700">
    <h2 class="text-2xl font-semibold mb-6 text-center text-teal-400">{{ isEditMode ? 'Upraviť projekt' : 'Pridať nový projekt' }}</h2>

    <div v-if="loadingTeam" class="text-center p-8">
      <i class="pi pi-spin pi-spinner text-4xl text-teal-400"></i>
      <p class="mt-4 text-gray-400">Načítavam stav tímu...</p>
    </div>
    <div v-else-if="!teamId" class="text-center p-8 bg-red-900/30 rounded-lg border border-red-800">
      <i class="pi pi-users text-4xl text-red-400"></i>
      <p class="mt-4 font-semibold text-red-200">Musíte byť členom tímu, aby ste mohli pridávať projekt.</p>
    </div>
    <div v-else-if="!isScrumMaster" class="text-center p-8 bg-red-900/30 rounded-lg border border-red-800">
      <i class="pi pi-lock text-4xl text-red-400"></i>
      <p class="mt-4 font-semibold text-red-200">Iba Scrum Master môže pridať projekt.</p>
    </div>

    <form v-else @submit.prevent="submitForm" class="flex flex-col gap-5">
      <!-- Typ projektu -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">Typ projektu</label>
        <Dropdown v-model="projectType" :options="projectTypes" optionLabel="label" optionValue="value" placeholder="Vyberte typ" class="w-full bg-gray-800 text-white border-gray-700" required />
      </div>

      <!-- Názov -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">Názov</label>
        <InputText v-model="name" placeholder="Zadajte názov" class="w-full bg-gray-800 text-white border-gray-700" required />
      </div>

      <!-- Nový systém kategorizácie -->
      <div class="border-t border-gray-700 pt-4">
        <h3 class="text-lg font-semibold mb-4 text-teal-400">Kategorizácia projektu</h3>
        
        <!-- Typ školy -->
        <div class="mb-4">
          <label class="block mb-1 font-medium text-gray-300">Typ školy <span class="text-red-400">*</span></label>
          <Dropdown 
            v-model="schoolType" 
            :options="schoolTypes" 
            optionLabel="label" 
            optionValue="value" 
            placeholder="Vyberte typ školy" 
            class="w-full bg-gray-800 text-white border-gray-700" 
            required 
            @change="onSchoolTypeChange"
          />
        </div>

        <!-- Ročník -->
        <div class="mb-4">
          <label class="block mb-1 font-medium text-gray-300">Ročník</label>
          <Dropdown 
            v-model="yearOfStudy" 
            :options="availableYears" 
            optionLabel="label" 
            optionValue="value" 
            placeholder="Vyberte ročník (voliteľné)" 
            class="w-full bg-gray-800 text-white border-gray-700"
          />
        </div>

        <!-- Predmet -->
        <div class="mb-4">
          <label class="block mb-1 font-medium text-gray-300">Predmet <span class="text-red-400">*</span></label>
          <Dropdown 
            v-model="subject" 
            :options="subjects" 
            optionLabel="label" 
            optionValue="value" 
            placeholder="Vyberte predmet" 
            class="w-full bg-gray-800 text-white border-gray-700" 
            required
          />
        </div>
      </div>

      <!-- Dátum vydania -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">Dátum vydania</label>
        <Calendar v-model="releaseDate" dateFormat="yy-mm-dd" showIcon class="w-full bg-gray-800 text-white border-gray-700" />
      </div>

      <!-- Popis -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">Popis</label>
        <Textarea v-model="description" rows="4" placeholder="Stručne opíšte projekt" class="w-full bg-gray-800 text-white border-gray-700" autoResize required />
      </div>

      <!-- Video -->
      <div class="border-t border-gray-700 pt-4">
        <h3 class="text-lg font-semibold mb-2 text-teal-400">Video (súbor alebo YouTube link)</h3>
        <div class="flex items-center gap-2 mb-4">
          <button type="button" :class="['px-4 py-2 rounded-lg border border-gray-700 transition-colors', videoType === 'upload' ? 'bg-teal-600 text-white hover:bg-teal-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600']" @click="videoType = 'upload'">Nahrať súbor</button>
          <button type="button" :class="['px-4 py-2 rounded-lg border border-gray-700 transition-colors', videoType === 'url' ? 'bg-teal-600 text-white hover:bg-teal-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600']" @click="videoType = 'url'">YouTube link</button>
        </div>
        <div v-if="videoType === 'url'">
          <InputText v-model="videoUrl" placeholder="https://www.youtube.com/watch?v=..." class="w-full bg-gray-800 text-white border-gray-700" />
        </div>
        <div v-else>
          <FileUpload name="video" mode="basic" accept="video/*" :maxFileSize="52428800" chooseLabel="Vybrať video (max. 50MB)" @select="onFileSelect($event, 'video')" @clear="onFileClear('video')" />
          <p v-if="files.video.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.video.name }}</strong></p>
        </div>
      </div>

      <!-- Splash Screen -->
      <div class="border-t border-gray-700 pt-4">
        <h3 class="text-lg font-semibold mb-2 text-teal-400">Poster / Náhľad</h3>
        <FileUpload name="splash_screen" mode="basic" accept="image/*" :maxFileSize="5242880" chooseLabel="Vybrať obrázok" @select="onFileSelect($event, 'splash_screen')" @clear="onFileClear('splash_screen')" />
        <p v-if="files.splash_screen.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.splash_screen.name }}</strong></p>
      </div>

      <!-- Typovo špecifické polia -->
      <div class="border-t border-gray-700 pt-4 flex flex-col gap-4">
        <h3 class="text-lg font-semibold mb-2 text-teal-400">Typovo Špecifické</h3>

        <template v-if="projectType === 'game'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">Export hry (.zip, .exe, .apk)</label>
            <FileUpload name="export" mode="basic" accept=".zip,.exe,.apk" :maxFileSize="524288000" chooseLabel="Vybrať export" @select="onFileSelect($event, 'export')" @clear="onFileClear('export')" />
            <p v-if="files.export.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.export.name }}</strong></p>
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">GitHub URL (voliteľné)</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">Tech Stack (čiarkou alebo ; oddelené)</label>
            <InputText v-model="techStack" placeholder="Unity, C#, Photon" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
        </template>

        <template v-if="projectType === 'web_app'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">Live URL</label>
            <InputText v-model="liveUrl" placeholder="https://app.example.com" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">GitHub URL</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">Tech Stack (čiarkou oddelené)</label>
            <InputText v-model="techStack" placeholder="Vue, Laravel, MySQL" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">Zdrojový kód (.zip)</label>
            <FileUpload name="source_code" mode="basic" accept=".zip" :maxFileSize="524288000" chooseLabel="Vybrať archív" @select="onFileSelect($event, 'source_code')" @clear="onFileClear('source_code')" />
            <p v-if="files.source_code.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.source_code.name }}</strong></p>
          </div>
        </template>

        <template v-if="projectType === 'mobile_app'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">Platforma</label>
            <Dropdown v-model="platform" :options="platformOptions" placeholder="Vyber platformu" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">GitHub URL (voliteľné)</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">Tech Stack</label>
            <InputText v-model="techStack" placeholder="Flutter, Firebase" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">APK súbor</label>
            <FileUpload name="apk_file" mode="basic" accept=".apk" :maxFileSize="524288000" chooseLabel="Vybrať APK" @select="onFileSelect($event, 'apk_file')" @clear="onFileClear('apk_file')" />
            <p v-if="files.apk_file.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.apk_file.name }}</strong></p>
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">iOS build (IPA / zip)</label>
            <FileUpload name="ios_file" mode="basic" accept=".ipa,.zip" :maxFileSize="524288000" chooseLabel="Vybrať iOS build" @select="onFileSelect($event, 'ios_file')" @clear="onFileClear('ios_file')" />
            <p v-if="files.ios_file.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.ios_file.name }}</strong></p>
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">Zdrojový kód (.zip)</label>
            <FileUpload name="source_code" mode="basic" accept=".zip" :maxFileSize="524288000" chooseLabel="Vybrať archív" @select="onFileSelect($event, 'source_code')" @clear="onFileClear('source_code')" />
            <p v-if="files.source_code.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.source_code.name }}</strong></p>
          </div>
        </template>

        <template v-if="projectType === 'library'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">Package Name</label>
            <InputText v-model="packageName" placeholder="my-awesome-lib" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">NPM URL</label>
            <InputText v-model="npmUrl" placeholder="https://www.npmjs.com/package/..." class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">GitHub URL</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">Tech Stack</label>
            <InputText v-model="techStack" placeholder="TypeScript, Vite" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">Dokumentácia (PDF/ZIP)</label>
            <FileUpload name="documentation" mode="basic" accept=".pdf,.zip" :maxFileSize="524288000" chooseLabel="Vybrať dokumentáciu" @select="onFileSelect($event, 'documentation')" @clear="onFileClear('documentation')" />
            <p v-if="files.documentation.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.documentation.name }}</strong></p>
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">Zdrojový kód (.zip)</label>
            <FileUpload name="source_code" mode="basic" accept=".zip" :maxFileSize="524288000" chooseLabel="Vybrať archív" @select="onFileSelect($event, 'source_code')" @clear="onFileClear('source_code')" />
            <p v-if="files.source_code.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.source_code.name }}</strong></p>
          </div>
        </template>
        <template v-if="projectType === 'other'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">Live URL (voliteľné)</label>
            <InputText v-model="liveUrl" placeholder="https://example.com" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">GitHub URL (voliteľné)</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">Tech Stack (voliteľné)</label>
            <InputText v-model="techStack" placeholder="Rust, WASM" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
        </template>
      </div>

      <!-- Zdrojový kód (pre game aj other) -->
      <div class="border-t border-gray-700 pt-4" v-if="projectType === 'game' || projectType === 'other'">
        <h3 class="text-lg font-semibold mb-2 text-teal-400">Zdrojový kód (.zip)</h3>
        <FileUpload name="source_code" mode="basic" accept=".zip" :maxFileSize="524288000" chooseLabel="Vybrať archív" @select="onFileSelect($event, 'source_code')" @clear="onFileClear('source_code')" />
        <p v-if="files.source_code.name" class="text-sm mt-2 text-gray-400">Nahratý súbor: <strong>{{ files.source_code.name }}</strong></p>
      </div>

      <div class="mt-4">
        <Button type="submit" :label="isEditMode ? 'Uložiť zmeny' : 'Zverejniť projekt'" icon="pi pi-check" class="w-full p-button-success p-button-lg" :loading="loadingSubmit" :disabled="loadingSubmit" />
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
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
const toast = useToast()

const projectTypes = [
  { label: 'Hra', value: 'game' },
  { label: 'Web App', value: 'web_app' },
  { label: 'Mobile App', value: 'mobile_app' },
  { label: 'Knižnica', value: 'library' },
  { label: 'Iné', value: 'other' }
]

const projectType = ref(null)
const name = ref('')
const schoolType = ref(null)
const yearOfStudy = ref(null)
const subject = ref(null)
const releaseDate = ref(null)
const description = ref('')
const videoType = ref('upload')
const videoUrl = ref('')
const loadingSubmit = ref(false)

const liveUrl = ref('')
const githubUrl = ref('')
const techStack = ref('')
const platform = ref(null)
const platformOptions = ['android','ios','both']
const packageName = ref('')
const npmUrl = ref('')

const files = ref({
  video: { file: null, name: '' },
  splash_screen: { file: null, name: '' },
  source_code: { file: null, name: '' },
  export: { file: null, name: '' },
  apk_file: { file: null, name: '' },
  ios_file: { file: null, name: '' },
  documentation: { file: null, name: '' }
})

// Nový systém kategorizácie
const schoolTypes = ref([
  { label: 'Základná Škola (ZŠ)', value: 'zs' },
  { label: 'Stredná škola (SŠ)', value: 'ss' },
  { label: 'Vysoká Škola (VŠ)', value: 'vs' }
])

const subjects = ref([
  { label: 'Slovenský jazyk', value: 'Slovenský jazyk' },
  { label: 'Matematika', value: 'Matematika' },
  { label: 'Dejepis', value: 'Dejepis' },
  { label: 'Geografia', value: 'Geografia' },
  { label: 'Informatika', value: 'Informatika' },
  { label: 'Grafika', value: 'Grafika' },
  { label: 'Chémia', value: 'Chémia' },
  { label: 'Fyzika', value: 'Fyzika' }
])

const availableYears = ref([])

// Dynamicky generovať ročníky podľa typu školy
function onSchoolTypeChange() {
  yearOfStudy.value = null // Reset ročníka pri zmene typu školy
  if (schoolType.value === 'zs') {
    availableYears.value = Array.from({ length: 9 }, (_, i) => ({
      label: `${i + 1}. ročník`,
      value: i + 1
    }))
  } else if (schoolType.value === 'ss' || schoolType.value === 'vs') {
    availableYears.value = Array.from({ length: 5 }, (_, i) => ({
      label: `${i + 1}. ročník`,
      value: i + 1
    }))
  } else {
    availableYears.value = []
  }
}

const token = ref(localStorage.getItem('access_token') || '')
const teamId = ref(null)
const isScrumMaster = ref(false)
const loadingTeam = ref(true)
const route = useRoute()
const isEditMode = ref(false)
const projectId = ref(null)
const existingProject = ref(null)

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

async function loadUserTeamStatus() {
  loadingTeam.value = true
  if (!token.value) { loadingTeam.value = false; return }
  try {
    const res = await fetch(`${API_URL}/api/user/team`, { headers: { 'Authorization': 'Bearer ' + token.value } })
    if (res.ok) {
      const data = await res.json()
      if (data.teams && data.teams.length) {
        const activeTeamId = localStorage.getItem('active_team_id')
        const active = activeTeamId ? data.teams.find(t => String(t.id) === activeTeamId) : data.teams[0]
        teamId.value = active.id
        isScrumMaster.value = !!active.is_scrum_master
      }
    }
  } catch (_) {} finally { loadingTeam.value = false }
}

async function loadProjectForEdit() {
  if (!projectId.value || !token.value) return
  loadingTeam.value = true
  try {
    const res = await fetch(`${API_URL}/api/projects/${projectId.value}`, {
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    })
    if (res.ok) {
      const data = await res.json()
      existingProject.value = data.project
      
      // Populate form with existing data
      projectType.value = existingProject.value.type
      name.value = existingProject.value.title
      schoolType.value = existingProject.value.school_type
      yearOfStudy.value = existingProject.value.year_of_study
      subject.value = existingProject.value.subject
      releaseDate.value = existingProject.value.release_date ? new Date(existingProject.value.release_date) : null
      description.value = existingProject.value.description || ''
      videoUrl.value = existingProject.value.video_url || ''
      videoType.value = existingProject.value.video_url ? 'url' : 'upload'
      
      // Set team ID
      teamId.value = existingProject.value.team_id
      
      // Load metadata
      const meta = existingProject.value.metadata || {}
      liveUrl.value = meta.live_url || ''
      githubUrl.value = meta.github_url || ''
      npmUrl.value = meta.npm_url || ''
      packageName.value = meta.package_name || ''
      platform.value = meta.platform || null
      techStack.value = Array.isArray(meta.tech_stack) ? meta.tech_stack.join(', ') : (meta.tech_stack || '')
      
      // Set available years based on school type
      onSchoolTypeChange()
      
      // Check if user is Scrum Master
      await loadUserTeamStatus()
    } else {
      toast.add({ severity: 'error', summary: 'Chyba', detail: 'Nepodarilo sa načítať projekt.', life: 5000 })
      router.push('/')
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Chyba', detail: 'Chyba pri načítaní projektu.', life: 5000 })
    router.push('/')
  } finally {
    loadingTeam.value = false
  }
}

async function submitForm() {
  if (!teamId.value || !isScrumMaster.value) {
    toast.add({ severity: 'error', summary: 'Chyba oprávnenia', detail: isEditMode.value ? 'Nemáte povolenie upravovať projekt.' : 'Nemáte povolenie pridať projekt.', life: 5000 })
    return
  }
  if (!projectType.value) {
    toast.add({ severity: 'warn', summary: 'Chýba typ', detail: 'Vyberte typ projektu.', life: 4000 })
    return
  }
  if (!schoolType.value) {
    toast.add({ severity: 'warn', summary: 'Chýba typ školy', detail: 'Vyberte typ školy.', life: 4000 })
    return
  }
  if (!subject.value) {
    toast.add({ severity: 'warn', summary: 'Chýba predmet', detail: 'Vyberte predmet.', life: 4000 })
    return
  }
  loadingSubmit.value = true
  try {
    const formData = new FormData()
    formData.append('title', name.value)
    formData.append('type', projectType.value)
    if (!isEditMode.value) {
      formData.append('team_id', teamId.value)
    }
    formData.append('school_type', schoolType.value)
    // Always send year_of_study when editing (even if null to clear it)
    if (isEditMode.value) {
      formData.append('year_of_study', yearOfStudy.value || '')
    } else if (yearOfStudy.value) {
      formData.append('year_of_study', yearOfStudy.value)
    }
    formData.append('subject', subject.value)
    formData.append('description', description.value || '')
    // Always send release_date when editing (even if null to clear it)
    if (releaseDate.value) {
      const d = new Date(releaseDate.value)
      formData.append('release_date', `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`)
    } else if (isEditMode.value) {
      // Send empty string to clear release_date when editing
      formData.append('release_date', '')
    }
    if (videoType.value === 'upload' && files.value.video.file) formData.append('video', files.value.video.file)
    if (videoType.value === 'url' && videoUrl.value) formData.append('video_url', videoUrl.value)
    else if (isEditMode.value && videoType.value === 'url' && !videoUrl.value) {
      // Send empty string to clear video_url when editing
      formData.append('video_url', '')
    }
    if (files.value.splash_screen.file) formData.append('splash_screen', files.value.splash_screen.file)

    // Type-specific meta/files
    if (projectType.value === 'game') {
      if (files.value.export.file) formData.append('export', files.value.export.file)
      if (files.value.source_code.file) formData.append('source_code', files.value.source_code.file)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }
    if (projectType.value === 'web_app') {
      if (liveUrl.value) formData.append('live_url', liveUrl.value)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
      if (files.value.source_code.file) formData.append('source_code', files.value.source_code.file)
    }
    if (projectType.value === 'mobile_app') {
      if (platform.value) formData.append('platform', platform.value)
      if (files.value.apk_file.file) formData.append('apk_file', files.value.apk_file.file)
      if (files.value.ios_file.file) formData.append('ios_file', files.value.ios_file.file)
      if (files.value.source_code.file) formData.append('source_code', files.value.source_code.file)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }
    if (projectType.value === 'library') {
      if (packageName.value) formData.append('package_name', packageName.value)
      if (npmUrl.value) formData.append('npm_url', npmUrl.value)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (files.value.documentation.file) formData.append('documentation', files.value.documentation.file)
      if (files.value.source_code.file) formData.append('source_code', files.value.source_code.file)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }
    if (projectType.value === 'other') {
      if (files.value.source_code.file) formData.append('source_code', files.value.source_code.file)
      if (liveUrl.value) formData.append('live_url', liveUrl.value)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }

    const url = isEditMode.value ? `${API_URL}/api/projects/${projectId.value}` : `${API_URL}/api/projects`
    
    // For PUT requests with FormData, use POST with method spoofing to avoid CORS issues
    if (isEditMode.value) {
      formData.append('_method', 'PUT')
    }
    
    // Debug logging
    console.log('Submitting form:', { isEditMode: isEditMode.value, method: isEditMode.value ? 'POST (_method=PUT)' : 'POST', url, projectId: projectId.value })
    console.log('Form data:', {
      title: name.value,
      type: projectType.value,
      school_type: schoolType.value,
      subject: subject.value,
      description: description.value
    })
    
    const res = await fetch(url, { 
      method: 'POST', // Always use POST, Laravel will handle _method spoofing
      headers: { 
        'Authorization': 'Bearer ' + token.value,
        'Accept': 'application/json'
      }, 
      body: formData 
    })
    const data = await res.json()
    if (res.ok && data.project) {
      toast.add({ 
        severity: 'success', 
        summary: isEditMode.value ? 'Projekt aktualizovaný' : 'Projekt vytvorený', 
        detail: `Projekt "${data.project.title}" bol úspešne ${isEditMode.value ? 'aktualizovaný' : 'zverejnený'}!`, 
        life: 6000 
      })
      if (isEditMode.value) {
        router.push(`/project/${projectId.value}`)
      } else {
        resetForm()
      }
    } else {
      toast.add({ severity: 'error', summary: 'Chyba', detail: data.message || (isEditMode.value ? 'Nepodarilo sa aktualizovať projekt.' : 'Nepodarilo sa vytvoriť projekt.'), life: 7000 })
    }
  } catch (e) {
    toast.add({ severity: 'fatal', summary: 'Chyba siete', detail: 'Problém s komunikáciou so serverom.', life: 8000 })
  } finally { loadingSubmit.value = false }
}

function resetForm() {
  projectType.value = null
  name.value = ''
  schoolType.value = null
  yearOfStudy.value = null
  subject.value = null
  releaseDate.value = null
  description.value = ''
  videoType.value = 'upload'
  videoUrl.value = ''
  liveUrl.value = ''
  githubUrl.value = ''
  techStack.value = ''
  platform.value = null
  packageName.value = ''
  npmUrl.value = ''
  availableYears.value = []
  Object.keys(files.value).forEach(k => files.value[k] = { file: null, name: '' })
}

onMounted(() => {
  // Check if we're in edit mode
  const id = route.params.id
  if (id) {
    isEditMode.value = true
    projectId.value = id
    loadProjectForEdit()
  } else {
    loadUserTeamStatus()
  }
})
</script>
