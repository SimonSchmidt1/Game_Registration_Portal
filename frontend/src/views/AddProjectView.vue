<template>
  <Toast />
  <div class="max-w-2xl mx-auto mt-10 border rounded-xl p-6 shadow-sm bg-gray-900 text-gray-100 border-gray-700">
    <h2 class="text-2xl font-semibold mb-6 text-center text-teal-400">Pridať nový projekt</h2>

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

      <!-- Kategória -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">Kategória</label>
        <Dropdown v-model="selectedCategory" :options="categories" optionLabel="name" optionValue="name" placeholder="Vyberte kategóriu" class="w-full bg-gray-800 text-white border-gray-700" required />
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
        <Button type="submit" label="Zverejniť projekt" icon="pi pi-check" class="w-full p-button-success p-button-lg" :loading="loadingSubmit" :disabled="loadingSubmit" />
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

const API_URL = import.meta.env.VITE_API_URL
const route = useRoute()
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
const selectedCategory = ref(null)
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

const categories = ref([
  { name: 'Akčná' }, { name: 'Strategická' }, { name: 'RPG' }, { name: 'Simulátor' },
  { name: 'Horor' }, { name: 'Dobrodružná' }, { name: 'Logická' }
])

const token = ref(localStorage.getItem('access_token') || '')
const teamId = ref(null)
const isScrumMaster = ref(false)
const loadingTeam = ref(true)

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

async function submitForm() {
  if (!teamId.value || !isScrumMaster.value) {
    toast.add({ severity: 'error', summary: 'Chyba oprávnenia', detail: 'Nemáte povolenie pridať projekt.', life: 5000 })
    return
  }
  if (!projectType.value) {
    toast.add({ severity: 'warn', summary: 'Chýba typ', detail: 'Vyberte typ projektu.', life: 4000 })
    return
  }
  loadingSubmit.value = true
  try {
    const formData = new FormData()
    formData.append('title', name.value)
    formData.append('type', projectType.value)
    formData.append('team_id', teamId.value)
    formData.append('category', selectedCategory.value?.name || selectedCategory.value || '')
    formData.append('description', description.value)
    if (releaseDate.value) {
      const d = new Date(releaseDate.value)
      formData.append('release_date', `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`)
    }
    if (videoType.value === 'upload' && files.value.video.file) formData.append('video', files.value.video.file)
    if (videoType.value === 'url' && videoUrl.value) formData.append('video_url', videoUrl.value)
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

    const res = await fetch(`${API_URL}/api/projects`, { method: 'POST', headers: { 'Authorization': 'Bearer ' + token.value }, body: formData })
    const data = await res.json()
    if (res.ok && data.project) {
      toast.add({ severity: 'success', summary: 'Projekt vytvorený', detail: `Projekt "${data.project.title}" bol úspešne zverejnený!`, life: 6000 })
      resetForm()
    } else {
      toast.add({ severity: 'error', summary: 'Chyba', detail: data.message || 'Nepodarilo sa vytvoriť projekt.', life: 7000 })
    }
  } catch (e) {
    toast.add({ severity: 'fatal', summary: 'Chyba siete', detail: 'Problém s komunikáciou so serverom.', life: 8000 })
  } finally { loadingSubmit.value = false }
}

function resetForm() {
  projectType.value = null
  name.value = ''
  selectedCategory.value = null
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
  Object.keys(files.value).forEach(k => files.value[k] = { file: null, name: '' })
}

onMounted(() => { loadUserTeamStatus() })
</script>
