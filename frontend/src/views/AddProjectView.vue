<template>
  <Toast />
  <div class="steam-page steam-theme">
    <div class="steam-card">
      <h2 class="section-heading section-heading-center">{{ isEditMode ? t('add_project.edit_title') : t('add_project.add_title') }}</h2>

      <div v-if="loadingTeam" class="state-message">
        <span>{{ t('add_project.loading_team') }}</span>
      </div>
      <div v-else-if="!teamId && !isAdmin" class="steam-panel steam-panel-danger">
        <div>
          <strong>{{ t('add_project.no_team_title') }}</strong>
          <p>{{ t('add_project.no_team_desc') }}</p>
        </div>
      </div>
      <div v-else-if="!isScrumMaster && !isAdmin" class="steam-panel steam-panel-danger">
        <div>
          <strong>{{ t('add_project.access_denied_title') }}</strong>
          <p>{{ t('add_project.scrum_master_only') }}</p>
        </div>
      </div>
      <div v-else-if="teamStatus === 'pending'" class="steam-panel steam-panel-warn">
        <div>
          <strong>{{ t('add_project.pending_title') }}</strong>
          <p>{{ t('add_project.pending_desc') }}</p>
          <p>{{ t('add_project.pending_after') }}</p>
          <Button 
            :label="t('add_project.go_home_btn')"
            class="steam-btn steam-btn-warn steam-btn-sm"
            @click="router.push('/')"
          />
        </div>
      </div>
      <div v-else-if="teamStatus === 'suspended'" class="steam-panel steam-panel-danger">
        <div>
          <strong>{{ t('add_project.suspended_title') }}</strong>
          <p>{{ t('add_project.suspended_desc') }}</p>
          <p>{{ t('add_project.suspended_contact') }}</p>
          <Button 
            :label="t('add_project.go_home_btn')" 
            class="steam-btn steam-btn-danger steam-btn-sm"
            @click="router.push('/')"
          />
        </div>
      </div>

      <form v-else @submit.prevent="submitForm" class="steam-form">
        <!-- Typ projektu -->
        <div>
          <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.project_type_label') }}</label>
          <Dropdown v-model="projectType" :options="projectTypes" optionLabel="label" optionValue="value" :placeholder="t('add_project.project_type_placeholder')" class="w-full bg-gray-800 text-white border-gray-700" required />
        </div>

        <!-- Názov -->
        <div>
          <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.name_label') }}</label>
          <InputText v-model="name" :placeholder="t('add_project.name_placeholder')" class="w-full bg-gray-800 text-white border-gray-700" required />
        </div>

        <!-- Nový systém kategorizácie -->
        <div class="border-t border-gray-700 pt-4">
          <h3 class="text-lg font-semibold mb-4 text-teal-400">{{ t('add_project.categorization_section') }}</h3>
          
          <!-- Typ školy -->
          <div class="mb-4">
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.school_type_label') }} <span class="text-red-400">*</span></label>
            <Dropdown 
              v-model="schoolType" 
              :options="schoolTypes" 
              optionLabel="label" 
              optionValue="value" 
              :placeholder="t('add_project.school_type_placeholder')" 
              class="w-full bg-gray-800 text-white border-gray-700" 
              required 
              @change="onSchoolTypeChange"
            />
          </div>

          <!-- Ročník -->
          <div class="mb-4">
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.year_label') }}</label>
            <Dropdown 
              v-model="yearOfStudy" 
              :options="availableYears" 
              optionLabel="label" 
              optionValue="value" 
              :placeholder="t('add_project.year_placeholder')" 
              class="w-full bg-gray-800 text-white border-gray-700"
            />
          </div>

          <!-- Predmet -->
          <div class="mb-4">
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.subject_label') }} <span class="text-red-400">*</span></label>
            <Dropdown 
              v-model="subject" 
              :options="subjects" 
              optionLabel="label" 
              optionValue="value" 
              :placeholder="t('add_project.subject_placeholder')" 
              class="w-full bg-gray-800 text-white border-gray-700" 
              required
            />
          </div>

          <!-- Univerzitný predmet -->
          <div class="mb-4">
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.uni_subject_label') }} <span class="text-red-400">*</span></label>
            <Dropdown 
              v-model="predmet" 
              :options="predmety" 
              optionLabel="label" 
              optionValue="value" 
              :placeholder="t('add_project.uni_subject_placeholder')"
              class="w-full bg-gray-800 text-white border-gray-700"
              required
            />
          </div>
        </div>

        <!-- Dátum vydania -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.release_date_label') }}</label>
        <Calendar v-model="releaseDate" dateFormat="yy-mm-dd" class="w-full bg-gray-800 text-white border-gray-700" />
      </div>

      <!-- Popis -->
      <div>
        <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.description_label') }}</label>
        <Textarea v-model="description" rows="4" :placeholder="t('add_project.description_placeholder')" class="w-full bg-gray-800 text-white border-gray-700" autoResize required />
      </div>

      <!-- Video -->
      <div class="border-t border-gray-700 pt-4">
        <h3 class="text-lg font-semibold mb-2 text-teal-400">{{ t('add_project.video_section') }}</h3>
        <div class="flex items-center gap-2 mb-4">
          <button type="button" :class="['px-4 py-2 rounded-lg border border-gray-700 transition-colors', videoType === 'upload' ? 'bg-teal-600 text-white hover:bg-teal-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600']" @click="videoType = 'upload'">{{ t('add_project.upload_file_btn') }}</button>
          <button type="button" :class="['px-4 py-2 rounded-lg border border-gray-700 transition-colors', videoType === 'url' ? 'bg-teal-600 text-white hover:bg-teal-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600']" @click="videoType = 'url'">{{ t('add_project.youtube_link_btn') }}</button>
        </div>
        <div v-if="videoType === 'url'">
          <InputText v-model="videoUrl" placeholder="https://www.youtube.com/watch?v=..." class="w-full bg-gray-800 text-white border-gray-700" />
        </div>
        <div v-else>
          <FileUpload name="video" mode="basic" accept="video/*" :maxFileSize="104857600" :chooseLabel="t('add_project.choose_video_btn')" @select="onFileSelect($event, 'video')" @clear="onFileClear('video')" />
          <p class="text-sm text-gray-400 mt-1">{{ t('add_project.video_hint') }}</p>
          <p v-if="files.video.name" class="text-sm mt-2 text-gray-400">{{ t('add_project.uploaded_file') }} <strong>{{ files.video.name }}</strong></p>
        </div>
      </div>

      <!-- Splash Screen -->
      <div class="border-t border-gray-700 pt-4">
        <h3 class="text-lg font-semibold mb-2 text-teal-400">{{ t('add_project.splash_section') }}</h3>
        <p class="text-sm text-gray-400 mb-2">{{ t('add_project.splash_recommended') }}</p>
        <FileUpload name="splash_screen" mode="basic" accept="image/*" :maxFileSize="8388608" :chooseLabel="t('add_project.choose_image_btn')" @select="onFileSelect($event, 'splash_screen')" @clear="onFileClear('splash_screen')" />
        <p v-if="files.splash_screen.name" class="text-sm mt-2 text-gray-400">{{ t('add_project.uploaded_file') }} <strong>{{ files.splash_screen.name }}</strong></p>
      </div>

      <!-- Universal File Uploads -->
      <div class="border-t border-gray-700 pt-4">
        <h3 class="text-lg font-semibold mb-4 text-teal-400">{{ t('add_project.files_section') }}</h3>
        
        <!-- Documentation -->
        <div class="mb-4">
          <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.doc_label') }}</label>
          <FileUpload name="documentation" mode="basic" accept=".pdf,.docx,.zip,.rar" :maxFileSize="10485760" :chooseLabel="t('add_project.choose_doc_btn')" @select="onFileSelect($event, 'documentation')" @clear="onFileClear('documentation')" />
          <p class="text-sm text-gray-400 mt-1">{{ t('add_project.doc_hint') }}</p>
          <p v-if="files.documentation.name" class="text-sm mt-2 text-gray-400">{{ t('add_project.uploaded_file') }} <strong>{{ files.documentation.name }}</strong></p>
        </div>

        <!-- Presentation -->
        <div class="mb-4">
          <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.pres_label') }}</label>
          <FileUpload name="presentation" mode="basic" accept=".pdf,.ppt,.pptx" :maxFileSize="15728640" :chooseLabel="t('add_project.choose_pres_btn')" @select="onFileSelect($event, 'presentation')" @clear="onFileClear('presentation')" />
          <p class="text-sm text-gray-400 mt-1">{{ t('add_project.pres_hint') }}</p>
          <p v-if="files.presentation.name" class="text-sm mt-2 text-gray-400">{{ t('add_project.uploaded_file') }} <strong>{{ files.presentation.name }}</strong></p>
        </div>

        <!-- Source Code -->
        <div class="mb-4">
          <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.src_label') }}</label>
          <FileUpload name="source_code" mode="basic" accept=".zip,.rar" :maxFileSize="209715200" :chooseLabel="t('add_project.choose_src_btn')" @select="onFileSelect($event, 'source_code')" @clear="onFileClear('source_code')" />
          <p class="text-sm text-gray-400 mt-1">{{ t('add_project.src_hint') }}</p>
          <p v-if="files.source_code.name" class="text-sm mt-2 text-gray-400">{{ t('add_project.uploaded_file') }} <strong>{{ files.source_code.name }}</strong></p>
        </div>

        <!-- Export with Type Selector -->
        <div class="mb-4">
          <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.export_label') }}</label>
          <div class="mb-2">
            <Dropdown 
              v-model="exportType" 
              :options="exportTypeOptions" 
              optionLabel="label" 
              optionValue="value" 
              :placeholder="t('add_project.export_type_placeholder')" 
              class="w-full bg-gray-800 text-white border-gray-700"
            />
          </div>
          <FileUpload name="export" mode="basic" accept=".zip,.rar,.exe,.apk,.ipa" :maxFileSize="524288000" :chooseLabel="t('add_project.choose_export_btn')" @select="onFileSelect($event, 'export')" @clear="onFileClear('export')" />
          <p class="text-sm text-gray-400 mt-1">{{ t('add_project.export_hint') }}</p>
          <template v-if="exportType">
            <p class="text-sm text-teal-400 mt-1">{{ exportTypeOptions.find(o => o.value === exportType)?.label }}</p>
          </template>
          <p v-if="files.export.name" class="text-sm mt-2 text-gray-400">{{ t('add_project.uploaded_file') }} <strong>{{ files.export.name }}</strong></p>
        </div>

        <!-- Project Folder -->
        <div class="mb-4">
          <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.project_folder_label') }}</label>
          <FileUpload name="project_folder" mode="basic" accept=".zip,.rar" :maxFileSize="20971520" :chooseLabel="t('add_project.choose_folder_btn')" @select="onFileSelect($event, 'project_folder')" @clear="onFileClear('project_folder')" />
          <p class="text-sm text-gray-400 mt-1">{{ t('add_project.project_folder_hint') }}</p>
          <p v-if="files.project_folder.name" class="text-sm mt-2 text-gray-400">{{ t('add_project.uploaded_file') }} <strong>{{ files.project_folder.name }}</strong></p>
        </div>
      </div>

      <!-- Typovo špecifické polia -->
      <div class="border-t border-gray-700 pt-4 flex flex-col gap-4">
        <h3 class="text-lg font-semibold mb-2 text-teal-400">{{ t('add_project.additional_section') }}</h3>

        <template v-if="projectType === 'game'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.github_url_label') }} ({{ t('common.optional') }})</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.tech_stack_label') }} {{ t('add_project.tech_stack_hint_semi') }}</label>
            <InputText v-model="techStack" placeholder="Unity, C#, Photon" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
        </template>

        <template v-if="projectType === 'web_app'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.live_url_label') }}</label>
            <InputText v-model="liveUrl" placeholder="https://app.example.com" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.github_url_label') }}</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.tech_stack_label') }} {{ t('add_project.tech_stack_hint') }}</label>
            <InputText v-model="techStack" placeholder="Vue, Laravel, MySQL" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
        </template>

        <template v-if="projectType === 'mobile_app'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.platform_label') }}</label>
            <Dropdown v-model="platform" :options="platformOptions" :placeholder="t('add_project.platform_label')" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.github_url_label') }} ({{ t('common.optional') }})</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.tech_stack_label') }}</label>
            <InputText v-model="techStack" placeholder="Flutter, Firebase" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.apk_label') }} ({{ t('common.optional') }})</label>
            <FileUpload name="apk_file" mode="basic" accept=".apk" :maxFileSize="524288000" :chooseLabel="t('add_project.choose_apk_btn')" @select="onFileSelect($event, 'apk_file')" @clear="onFileClear('apk_file')" />
            <p v-if="files.apk_file.name" class="text-sm mt-2 text-gray-400">{{ t('add_project.uploaded_file') }} <strong>{{ files.apk_file.name }}</strong></p>
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.ios_label') }} ({{ t('common.optional') }})</label>
            <FileUpload name="ios_file" mode="basic" accept=".ipa,.zip" :maxFileSize="524288000" :chooseLabel="t('add_project.choose_ios_btn')" @select="onFileSelect($event, 'ios_file')" @clear="onFileClear('ios_file')" />
            <p v-if="files.ios_file.name" class="text-sm mt-2 text-gray-400">{{ t('add_project.uploaded_file') }} <strong>{{ files.ios_file.name }}</strong></p>
          </div>
        </template>

        <template v-if="projectType === 'library'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.package_name_label') }}</label>
            <InputText v-model="packageName" placeholder="my-awesome-lib" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.npm_url_label') }}</label>
            <InputText v-model="npmUrl" placeholder="https://www.npmjs.com/package/..." class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.github_url_label') }}</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.tech_stack_label') }}</label>
            <InputText v-model="techStack" placeholder="TypeScript, Vite" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
        </template>
        <template v-if="projectType === 'other'">
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.live_url_label') }} ({{ t('common.optional') }})</label>
            <InputText v-model="liveUrl" placeholder="https://example.com" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.github_url_label') }} ({{ t('common.optional') }})</label>
            <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-300">{{ t('add_project.tech_stack_label') }} ({{ t('common.optional') }})</label>
            <InputText v-model="techStack" placeholder="Rust, WASM" class="w-full bg-gray-800 text-white border-gray-700" />
          </div>
        </template>
      </div>

      <div class="mt-4">
        <Button type="submit" :label="isEditMode ? t('add_project.update_btn') : t('add_project.submit_btn')" class="steam-btn steam-btn-accent auth-btn-block" :loading="loadingSubmit" :disabled="loadingSubmit" />
      </div>
    </form>
  </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
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
const { t } = useI18n()

const projectTypes = computed(() => [
  { label: t('project_types.game'), value: 'game' },
  { label: t('project_types.web_app'), value: 'web_app' },
  { label: t('project_types.mobile_app'), value: 'mobile_app' },
  { label: t('project_types.library'), value: 'library' },
  { label: t('project_types.other'), value: 'other' }
])

const projectType = ref(null)
const name = ref('')
const schoolType = ref(null)
const yearOfStudy = ref(null)
const subject = ref(null)
const predmet = ref(null)
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
  documentation: { file: null, name: '' },
  presentation: { file: null, name: '' },
  project_folder: { file: null, name: '' },
  apk_file: { file: null, name: '' },
  ios_file: { file: null, name: '' }
})

const exportType = ref(null)
const exportTypeOptions = [
  { label: 'Standalone aplikácia', value: 'standalone' },
  { label: 'WebGL build', value: 'webgl' },
  { label: 'Mobilná hra (APK/IPA)', value: 'mobile' },
  { label: 'Spustiteľný súbor (.exe)', value: 'executable' }
]

// Nový systém kategorizácie
const schoolTypes = computed(() => [
  { label: t('school_types.zs'), value: 'zs' },
  { label: t('school_types.ss'), value: 'ss' },
  { label: t('school_types.vs'), value: 'vs' }
])

const subjects = computed(() => [
  { label: t('subjects.sk_language'), value: 'Slovenský jazyk' },
  { label: t('subjects.math'), value: 'Matematika' },
  { label: t('subjects.history'), value: 'Dejepis' },
  { label: t('subjects.geography'), value: 'Geografia' },
  { label: t('subjects.informatics'), value: 'Informatika' },
  { label: t('subjects.graphics'), value: 'Grafika' },
  { label: t('subjects.chemistry'), value: 'Chémia' },
  { label: t('subjects.physics'), value: 'Fyzika' }
])

const predmety = ref([
  { label: 'Grafika', value: 'Grafika' },
  { label: 'Multimediálne systémy', value: 'Multimediálne systémy' },
  { label: 'Grafika 2', value: 'Grafika 2' },
  { label: 'Systémy Virtuálnej Reality', value: 'Systémy Virtuálnej Reality' },
  { label: 'Tímový projekt', value: 'Tímový projekt' },
  { label: 'Internetové Technológie', value: 'Internetové Technológie' }
])

const availableYears = computed(() => {
  if (schoolType.value === 'zs') {
    return Array.from({ length: 9 }, (_, i) => ({
      label: `${i + 1}${t('common.year_suffix')}`,
      value: i + 1
    }))
  } else if (schoolType.value === 'ss' || schoolType.value === 'vs') {
    return Array.from({ length: 5 }, (_, i) => ({
      label: `${i + 1}${t('common.year_suffix')}`,
      value: i + 1
    }))
  }
  return []
})

// Dynamicky generovať ročníky podľa typu školy
function onSchoolTypeChange() {
  yearOfStudy.value = null // Reset ročníka pri zmene typu školy
}

const token = ref(localStorage.getItem('access_token') || '')
const teamId = ref(null)
const isScrumMaster = ref(false)
const isAdmin = ref(false)
const teamStatus = ref('active') // Team approval status: 'active', 'pending', 'suspended'
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
  
  // Check if user is admin
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  isAdmin.value = user.role === 'admin'
  
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
        teamStatus.value = active.status || 'active'
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
      predmet.value = existingProject.value.predmet
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
      
      // Set available years based on school type (computed, no manual call needed)
      
      // Check if user is Scrum Master
      await loadUserTeamStatus()
    } else {
      toast.add({ severity: 'error', summary: t('common.error'), detail: t('add_project.submit_error'), life: 5000 })
      router.push('/')
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: t('add_project.submit_error'), life: 5000 })
    router.push('/')
  } finally {
    loadingTeam.value = false
  }
}

async function submitForm() {
  if ((!teamId.value || !isScrumMaster.value) && !isAdmin.value) {
    toast.add({ severity: 'error', summary: t('add_project.permission_error'), detail: isEditMode.value ? t('add_project.no_permission_edit') : t('add_project.no_permission_add'), life: 5000 })
    return
  }
  if (!projectType.value) {
    toast.add({ severity: 'warn', summary: t('add_project.missing_type_title'), detail: t('add_project.missing_type_desc'), life: 4000 })
    return
  }
  if (!schoolType.value) {
    toast.add({ severity: 'warn', summary: t('add_project.missing_school_type_title'), detail: t('add_project.missing_school_type_desc'), life: 4000 })
    return
  }
  if (!subject.value) {
    toast.add({ severity: 'warn', summary: t('add_project.missing_subject_title'), detail: t('add_project.missing_subject_desc'), life: 4000 })
    return
  }
  if (!predmet.value) {
    toast.add({ severity: 'warn', summary: t('add_project.missing_uni_subject_title'), detail: t('add_project.missing_uni_subject_desc'), life: 4000 })
    return
  }
  if (!name.value || !name.value.trim()) {
    toast.add({ severity: 'warn', summary: t('add_project.missing_name_title'), detail: t('add_project.missing_name_desc'), life: 4000 })
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
    formData.append('predmet', predmet.value)
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

    // Universal file uploads (for all project types)
    if (files.value.documentation.file) formData.append('documentation', files.value.documentation.file)
    if (files.value.presentation.file) formData.append('presentation', files.value.presentation.file)
    if (files.value.source_code.file) formData.append('source_code', files.value.source_code.file)
    if (files.value.export.file) {
      formData.append('export', files.value.export.file)
      if (exportType.value) formData.append('export_type', exportType.value)
    }
    if (files.value.project_folder.file) formData.append('project_folder', files.value.project_folder.file)

    // Type-specific meta/files (keep for backward compatibility and additional metadata)
    if (projectType.value === 'game') {
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }
    if (projectType.value === 'web_app') {
      if (liveUrl.value) formData.append('live_url', liveUrl.value)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }
    if (projectType.value === 'mobile_app') {
      if (platform.value) formData.append('platform', platform.value)
      if (files.value.apk_file.file) formData.append('apk_file', files.value.apk_file.file)
      if (files.value.ios_file.file) formData.append('ios_file', files.value.ios_file.file)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }
    if (projectType.value === 'library') {
      if (packageName.value) formData.append('package_name', packageName.value)
      if (npmUrl.value) formData.append('npm_url', npmUrl.value)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }
    if (projectType.value === 'other') {
      if (liveUrl.value) formData.append('live_url', liveUrl.value)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }

    const url = isEditMode.value ? `${API_URL}/api/projects/${projectId.value}` : `${API_URL}/api/projects`
    
    // For PUT requests with FormData, use POST with method spoofing to avoid CORS issues
    if (isEditMode.value) {
      formData.append('_method', 'PUT')
    }
    
    const res = await fetch(url, { 
      method: 'POST', // Always use POST, Laravel will handle _method spoofing
      headers: { 
        'Authorization': 'Bearer ' + token.value,
        'Accept': 'application/json'
      }, 
      body: formData 
    })
    
    let data
    try {
      data = await res.json()
    } catch (parseError) {
      console.error('Failed to parse response:', parseError)
      toast.add({ 
        severity: 'error', 
        summary: t('add_project.response_error'), 
        detail: t('add_project.response_error_desc'), 
        life: 7000 
      })
      loadingSubmit.value = false
      return
    }
    
    if (res.ok && data.project) {
      toast.add({ 
        severity: 'success', 
        summary: isEditMode.value ? t('add_project.updated_title') : t('add_project.created_title'), 
        detail: isEditMode.value ? t('add_project.update_success') : t('add_project.submit_success'), 
        life: 6000 
      })
      if (isEditMode.value) {
        router.push(`/project/${projectId.value}`)
      } else {
        resetForm()
      }
    } else {
      console.error('Backend error response:', { status: res.status, data })
      // Handle team status errors specifically
      if (res.status === 403 && data.team_status) {
        toast.add({ 
          severity: 'warn', 
          summary: t('add_project.team_not_active_title'), 
          detail: data.message || t('add_project.submit_error'), 
          life: 8000 
        })
      } else if (res.status === 422) {
        // Validation errors
        const errors = data.errors || {}
        const errorMessages = Object.entries(errors).map(([key, msgs]) => `${key}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`).join('\n')
        toast.add({ 
          severity: 'error', 
          summary: t('add_project.validation_errors_title'), 
          detail: errorMessages || t('add_project.submit_error'), 
          life: 10000 
        })
      } else {
        toast.add({ 
          severity: 'error', 
          summary: t('common.error'), 
          detail: data.message || t('add_project.submit_error'), 
          life: 7000 
        })
      }
    }
  } catch (e) {
    console.error('Network/fetch error:', e)
    toast.add({ severity: 'fatal', summary: t('common.network_error'), detail: t('common.server_unavailable') + ': ' + (e.message || t('common.error')), life: 8000 })
  } finally { loadingSubmit.value = false }
}

function resetForm() {
  projectType.value = null
  name.value = ''
  schoolType.value = null
  yearOfStudy.value = null
  subject.value = null
  predmet.value = null
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

<style scoped>
/* ═══════════════════════════════════════════════════════════ */
/* PAGE + FORM                                                */
/* ═══════════════════════════════════════════════════════════ */
.steam-page {
  max-width: 960px;
  margin: 0 auto;
  padding: 24px 32px 48px;
}

.steam-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 24px 24px 28px;
}

.steam-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.section-heading {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 16px;
}

.section-heading-center {
  text-align: center;
}

.state-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 60px 24px;
  color: var(--color-text-muted);
  font-size: 1rem;
}

.state-icon {
  font-size: 2.2rem;
  opacity: 0.6;
}

.steam-panel {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 16px 18px;
  border-radius: 4px;
  font-size: 0.9rem;
}

.steam-panel strong { display: block; margin-bottom: 4px; }
.steam-panel p { margin: 0; opacity: 0.85; }

.steam-panel-danger {
  background: rgba(var(--color-danger-rgb), 0.1);
  border: 1px solid rgba(var(--color-danger-rgb), 0.3);
  color: var(--color-danger);
}

.steam-panel-warn {
  background: rgba(var(--color-warning-rgb), 0.1);
  border: 1px solid rgba(var(--color-warning-rgb), 0.3);
  color: var(--color-warning);
}

/* ═══════════════════════════════════════════════════════════ */
/* BUTTONS                                                    */
/* ═══════════════════════════════════════════════════════════ */
.steam-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 3px;
  border: none;
  cursor: pointer;
  transition: background 0.12s, color 0.12s, opacity 0.12s;
  white-space: nowrap;
  line-height: 1.4;
}

.steam-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.steam-btn-sm { padding: 6px 12px; font-size: 0.8rem; }

.steam-btn-accent { background: var(--color-accent); color: var(--color-accent-contrast); }
.steam-btn-accent:hover:not(:disabled) { background: var(--color-accent-hover); }

.steam-btn-warn { background: rgba(var(--color-warning-rgb), 0.15); color: var(--color-warning); }
.steam-btn-warn:hover:not(:disabled) { background: rgba(var(--color-warning-rgb), 0.25); }

.steam-btn-danger { background: rgba(var(--color-danger-rgb), 0.15); color: var(--color-danger); }
.steam-btn-danger:hover:not(:disabled) { background: rgba(var(--color-danger-rgb), 0.25); }

.auth-btn-block { width: 100%; }

/* Tailwind & PrimeVue overrides now live in main.css (global) */

@media (max-width: 768px) {
  .steam-page { padding: 16px 16px 40px; }
}
</style>
