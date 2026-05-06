<template>
  <Toast />
  <div class="steam-page steam-theme">
    <div class="apv-container">

      <!-- Page header -->
      <div class="apv-header">
        <button class="back-btn" @click="router.push('/')">
          <i class="pi pi-arrow-left"></i>
          <span>{{ t('common.back') }}</span>
        </button>
        <h1 class="apv-title">{{ isEditMode ? t('add_project.edit_title') : t('add_project.add_title') }}</h1>
      </div>

      <!-- State panels -->
      <div v-if="loadingTeam" class="apv-state">
        <i class="pi pi-spin pi-spinner apv-state-icon"></i>
        <span>{{ t('add_project.loading_team') }}</span>
      </div>
      <div v-else-if="!teamId && !isAdmin" class="apv-alert apv-alert-danger">
        <i class="pi pi-times-circle"></i>
        <div><strong>{{ t('add_project.no_team_title') }}</strong><p>{{ t('add_project.no_team_desc') }}</p></div>
      </div>
      <div v-else-if="!isScrumMaster && !isAdmin" class="apv-alert apv-alert-danger">
        <i class="pi pi-lock"></i>
        <div><strong>{{ t('add_project.access_denied_title') }}</strong><p>{{ t('add_project.scrum_master_only') }}</p></div>
      </div>
      <div v-else-if="teamStatus === 'pending'" class="apv-alert apv-alert-warn">
        <i class="pi pi-clock"></i>
        <div>
          <strong>{{ t('add_project.pending_title') }}</strong>
          <p>{{ t('add_project.pending_desc') }}</p>
          <p>{{ t('add_project.pending_after') }}</p>
          <button class="back-btn" style="margin-top:10px" @click="router.push('/')"><i class="pi pi-arrow-left"></i><span>{{ t('add_project.go_home_btn') }}</span></button>
        </div>
      </div>
      <div v-else-if="teamStatus === 'suspended'" class="apv-alert apv-alert-danger">
        <i class="pi pi-ban"></i>
        <div>
          <strong>{{ t('add_project.suspended_title') }}</strong>
          <p>{{ t('add_project.suspended_desc') }}</p>
          <p>{{ t('add_project.suspended_contact') }}</p>
          <button class="back-btn" style="margin-top:10px" @click="router.push('/')"><i class="pi pi-arrow-left"></i><span>{{ t('add_project.go_home_btn') }}</span></button>
        </div>
      </div>

      <form v-else @submit.prevent="submitForm" class="apv-form">

        <!-- ① Základné informácie -->
        <div class="apv-section">
          <div class="apv-section-header">
            <span class="apv-section-num">1</span>
            <h2 class="apv-section-title">{{ t('add_project.basic_section') }}</h2>
          </div>
          <div class="apv-fields">
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.project_type_label') }} <span class="apv-req">*</span></label>
              <Dropdown v-model="projectType" :options="projectTypes" optionLabel="label" optionValue="value" :placeholder="t('add_project.project_type_placeholder')" class="apv-input" />
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.name_label') }} <span class="apv-req">*</span></label>
              <InputText v-model="name" :placeholder="t('add_project.name_placeholder')" class="apv-input" />
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.description_label') }} <span class="apv-req">*</span></label>
              <Textarea v-model="description" rows="4" :placeholder="t('add_project.description_placeholder')" class="apv-input" autoResize />
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.release_date_label') }}</label>
              <Calendar v-model="releaseDate" dateFormat="yy-mm-dd" class="apv-input" />
            </div>
          </div>
        </div>

        <!-- ② Kategorizácia -->
        <div class="apv-section">
          <div class="apv-section-header">
            <span class="apv-section-num">2</span>
            <h2 class="apv-section-title">{{ t('add_project.categorization_section') }}</h2>
          </div>
          <div class="apv-fields apv-fields-2col">
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.school_type_label') }} <span class="apv-req">*</span></label>
              <Dropdown v-model="schoolType" :options="schoolTypes" optionLabel="label" optionValue="value" :placeholder="t('add_project.school_type_placeholder')" class="apv-input" @change="onSchoolTypeChange" />
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.year_label') }}</label>
              <Dropdown v-model="yearOfStudy" :options="availableYears" optionLabel="label" optionValue="value" :placeholder="t('add_project.year_placeholder')" class="apv-input" />
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.subject_label') }} <span class="apv-req">*</span></label>
              <Dropdown v-model="subject" :options="subjects" optionLabel="label" optionValue="value" :placeholder="t('add_project.subject_placeholder')" class="apv-input" />
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.uni_subject_label') }} <span class="apv-req">*</span></label>
              <Dropdown v-model="predmet" :options="predmety" optionLabel="label" optionValue="value" :placeholder="t('add_project.uni_subject_placeholder')" class="apv-input" />
            </div>
          </div>
        </div>

        <!-- ③ Médiá -->
        <div class="apv-section">
          <div class="apv-section-header">
            <span class="apv-section-num">3</span>
            <h2 class="apv-section-title">{{ t('add_project.media_section') }}</h2>
          </div>
          <div class="apv-fields">
            <!-- Splash -->
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.splash_section') }} <span v-if="!isEditMode || !existingProject?.splash_screen_path" class="apv-req">*</span></label>
              <p class="apv-hint">{{ t('add_project.splash_recommended') }}</p>
              <FileUpload name="splash_screen" mode="basic" accept="image/*" :maxFileSize="8388608" :chooseLabel="t('add_project.choose_image_btn')" @select="onFileSelect($event, 'splash_screen')" @clear="onFileClear('splash_screen')" />
              <p v-if="files.splash_screen.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.splash_screen.name }}</p>
            </div>
            <!-- Video -->
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.video_section') }}</label>
              <div class="apv-toggle">
                <button type="button" :class="['apv-toggle-btn', videoType === 'upload' ? 'apv-toggle-active' : '']" @click="videoType = 'upload'">{{ t('add_project.upload_file_btn') }}</button>
                <button type="button" :class="['apv-toggle-btn', videoType === 'url' ? 'apv-toggle-active' : '']" @click="videoType = 'url'">{{ t('add_project.youtube_link_btn') }}</button>
              </div>
              <div v-if="videoType === 'url'" class="apv-toggle-content">
                <InputText v-model="videoUrl" placeholder="https://www.youtube.com/watch?v=..." class="apv-input" />
              </div>
              <div v-else class="apv-toggle-content">
                <FileUpload name="video" mode="basic" accept="video/*" :maxFileSize="268435456" :chooseLabel="t('add_project.choose_video_btn')" @select="onFileSelect($event, 'video')" @clear="onFileClear('video')" />
                <p class="apv-hint">{{ t('add_project.video_hint') }}</p>
                <p v-if="files.video.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.video.name }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ④ Súbory -->
        <div class="apv-section">
          <div class="apv-section-header">
            <span class="apv-section-num">4</span>
            <h2 class="apv-section-title">{{ t('add_project.files_section') }}</h2>
          </div>
          <div class="apv-fields">
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.doc_label') }}</label>
              <FileUpload name="documentation" mode="basic" accept=".pdf,.docx,.zip,.rar" :maxFileSize="10485760" :chooseLabel="t('add_project.choose_doc_btn')" @select="onFileSelect($event, 'documentation')" @clear="onFileClear('documentation')" />
              <p class="apv-hint">{{ t('add_project.doc_hint') }}</p>
              <p v-if="files.documentation.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.documentation.name }}</p>
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.pres_label') }}</label>
              <FileUpload name="presentation" mode="basic" accept=".pdf,.ppt,.pptx" :maxFileSize="15728640" :chooseLabel="t('add_project.choose_pres_btn')" @select="onFileSelect($event, 'presentation')" @clear="onFileClear('presentation')" />
              <p class="apv-hint">{{ t('add_project.pres_hint') }}</p>
              <p v-if="files.presentation.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.presentation.name }}</p>
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.src_label') }}</label>
              <FileUpload name="source_code" mode="basic" accept=".zip,.rar" :maxFileSize="209715200" :chooseLabel="t('add_project.choose_src_btn')" @select="onFileSelect($event, 'source_code')" @clear="onFileClear('source_code')" />
              <p class="apv-hint">{{ t('add_project.src_hint') }}</p>
              <p v-if="files.source_code.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.source_code.name }}</p>
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.export_label') }}</label>
              <Dropdown v-model="exportType" :options="exportTypeOptions" optionLabel="label" optionValue="value" :placeholder="t('add_project.export_type_placeholder')" class="apv-input" style="margin-bottom:8px" />
              <FileUpload name="export" mode="basic" accept=".zip,.rar,.exe,.apk,.ipa" :maxFileSize="268435456" :chooseLabel="t('add_project.choose_export_btn')" @select="onFileSelect($event, 'export')" @clear="onFileClear('export')" />
              <p class="apv-hint">{{ t('add_project.export_hint') }}</p>
              <p v-if="files.export.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.export.name }}</p>
            </div>
            <div class="apv-field">
              <label class="apv-label">{{ t('add_project.project_folder_label') }}</label>
              <FileUpload name="project_folder" mode="basic" accept=".zip,.rar" :maxFileSize="20971520" :chooseLabel="t('add_project.choose_folder_btn')" @select="onFileSelect($event, 'project_folder')" @clear="onFileClear('project_folder')" />
              <p class="apv-hint">{{ t('add_project.project_folder_hint') }}</p>
              <p v-if="files.project_folder.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.project_folder.name }}</p>
            </div>
          </div>
        </div>

        <!-- ⑤ Špecifické polia (conditional) -->
        <div v-if="projectType" class="apv-section">
          <div class="apv-section-header">
            <span class="apv-section-num">5</span>
            <h2 class="apv-section-title">{{ t('add_project.additional_section') }}</h2>
          </div>
          <div class="apv-fields">

            <template v-if="projectType === 'game'">
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.github_url_label') }}</label>
                <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.tech_stack_label') }} {{ t('add_project.tech_stack_hint_semi') }}</label>
                <InputText v-model="techStack" placeholder="Unity, C#, Photon" class="apv-input" />
              </div>
            </template>

            <template v-if="projectType === 'web_app'">
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.live_url_label') }}</label>
                <InputText v-model="liveUrl" placeholder="https://app.example.com" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.github_url_label') }}</label>
                <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.tech_stack_label') }} {{ t('add_project.tech_stack_hint') }}</label>
                <InputText v-model="techStack" placeholder="Vue, Laravel, MySQL" class="apv-input" />
              </div>
            </template>

            <template v-if="projectType === 'mobile_app'">
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.platform_label') }}</label>
                <Dropdown v-model="platform" :options="platformOptions" :placeholder="t('add_project.platform_label')" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.github_url_label') }}</label>
                <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.tech_stack_label') }}</label>
                <InputText v-model="techStack" placeholder="Flutter, Firebase" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.apk_label') }}</label>
                <FileUpload name="apk_file" mode="basic" accept=".apk" :maxFileSize="268435456" :chooseLabel="t('add_project.choose_apk_btn')" @select="onFileSelect($event, 'apk_file')" @clear="onFileClear('apk_file')" />
                <p v-if="files.apk_file.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.apk_file.name }}</p>
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.ios_label') }}</label>
                <FileUpload name="ios_file" mode="basic" accept=".ipa,.zip" :maxFileSize="268435456" :chooseLabel="t('add_project.choose_ios_btn')" @select="onFileSelect($event, 'ios_file')" @clear="onFileClear('ios_file')" />
                <p v-if="files.ios_file.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.ios_file.name }}</p>
              </div>
            </template>

            <template v-if="projectType === 'library'">
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.package_name_label') }}</label>
                <InputText v-model="packageName" placeholder="my-awesome-lib" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.npm_url_label') }}</label>
                <InputText v-model="npmUrl" placeholder="https://www.npmjs.com/package/..." class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.github_url_label') }}</label>
                <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.tech_stack_label') }}</label>
                <InputText v-model="techStack" placeholder="TypeScript, Vite" class="apv-input" />
              </div>
            </template>

            <template v-if="projectType === 'other'">
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.live_url_label') }}</label>
                <InputText v-model="liveUrl" placeholder="https://example.com" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.github_url_label') }}</label>
                <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.tech_stack_label') }}</label>
                <InputText v-model="techStack" placeholder="Rust, WASM" class="apv-input" />
              </div>
            </template>

            <template v-if="projectType === 'webgl'">
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.webgl_source_label') }}</label>
                <div class="apv-toggle">
                  <button type="button" :class="['apv-toggle-btn', webglMode === 'build' ? 'apv-toggle-active' : '']" @click="webglMode = 'build'">
                    <i class="pi pi-upload"></i> {{ t('add_project.webgl_mode_build') }}
                  </button>
                  <button type="button" :class="['apv-toggle-btn', webglMode === 'url' ? 'apv-toggle-active' : '']" @click="webglMode = 'url'">
                    <i class="pi pi-link"></i> {{ t('add_project.webgl_mode_url') }}
                  </button>
                </div>
                <div class="apv-toggle-content">
                  <div v-if="webglMode === 'build'">
                    <FileUpload name="webgl_build" mode="basic" accept=".zip" :maxFileSize="104857600" :chooseLabel="t('add_project.webgl_build_btn')" @select="onFileSelect($event, 'webgl_build')" @clear="onFileClear('webgl_build')" />
                    <p v-if="files.webgl_build.name" class="apv-file-name"><i class="pi pi-check-circle"></i> {{ files.webgl_build.name }}</p>
                    <p class="apv-hint">{{ t('add_project.webgl_build_hint') }}</p>
                    <div class="apv-webgl-warn">
                      <div class="apv-webgl-warn-row">
                        <i class="pi pi-exclamation-triangle apv-webgl-warn-icon"></i>
                        <span class="apv-webgl-warn-title">Pred nahraním skontrolujte tieto nastavenia v Unity:</span>
                      </div>
                      <ul class="apv-webgl-warn-list">
                        <li><strong>Compression Format → Disabled</strong> — Brotli/Gzip komprimované buildy sa nedajú prehrať na tomto serveri</li>
                        <li><strong>Formát archívu: ZIP</strong> — RAR a iné formáty nie sú podporované</li>
                        <li><strong>Max. veľkosť ZIP: 100 MB</strong>, nekomprimované súbory: 200 MB</li>
                        <li><strong>index.html musí byť v archíve</strong> — Unity WebGL build ho vždy obsahuje</li>
                      </ul>
                      <p class="apv-webgl-warn-path">Unity: <em>File → Build Settings → Player Settings → Publishing Settings → Compression Format → Disabled</em></p>
                    </div>
                  </div>
                  <div v-else>
                    <InputText v-model="webglUrl" placeholder="https://example.com/game/" class="apv-input" />
                    <p class="apv-hint">{{ t('add_project.webgl_url_hint') }}</p>
                  </div>
                </div>
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.github_url_label') }}</label>
                <InputText v-model="githubUrl" placeholder="https://github.com/org/repo" class="apv-input" />
              </div>
              <div class="apv-field">
                <label class="apv-label">{{ t('add_project.tech_stack_label') }}</label>
                <InputText v-model="techStack" placeholder="Unity, WebGL, C#" class="apv-input" />
              </div>
            </template>

          </div>
        </div>

        <!-- Submit -->
        <div class="apv-submit">
          <Button type="submit" :label="isEditMode ? t('add_project.update_btn') : t('add_project.submit_btn')" class="steam-btn steam-btn-accent apv-submit-btn" :loading="loadingSubmit" :disabled="loadingSubmit" />
          <p class="apv-submit-note">{{ t('add_project.required_note') }}</p>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '@/utils/apiFetch'
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
  { label: t('project_types.webgl'), value: 'webgl' },
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
const webglUrl = ref('')
const webglMode = ref('build') // 'build' | 'url'
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
  ios_file: { file: null, name: '' },
  webgl_build: { file: null, name: '' }
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

  // Admin override: if a team_id is supplied in the URL, create the project for that team
  // (admin can create projects for any team — bypasses Scrum Master / team status guards)
  if (isAdmin.value) {
    const queryTeamId = route.query.team_id
    if (queryTeamId) {
      const parsed = parseInt(queryTeamId, 10)
      if (!Number.isNaN(parsed) && parsed > 0) {
        teamId.value = parsed
        isScrumMaster.value = true
        teamStatus.value = 'active'
        loadingTeam.value = false
        return
      }
    }
  }

  if (!token.value) { loadingTeam.value = false; return }
  try {
    const res = await apiFetch(`${API_URL}/api/user/team`, { headers: { 'Authorization': 'Bearer ' + token.value } })
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
    const res = await apiFetch(`${API_URL}/api/projects/${projectId.value}`, {
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
      webglUrl.value = meta.webgl_url || ''
      webglMode.value = meta.webgl_local_path ? 'build' : 'url'
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
  const descWords = (description.value || '').trim().split(/\s+/).filter(w => w.length > 0)
  if (descWords.length < 10) {
    toast.add({ severity: 'warn', summary: t('add_project.missing_desc_title'), detail: t('add_project.missing_desc_desc'), life: 4000 })
    return
  }
  const hasSplash = files.value.splash_screen.file || (isEditMode.value && existingProject.value?.splash_screen_path)
  if (!hasSplash) {
    toast.add({ severity: 'warn', summary: t('add_project.missing_splash_title'), detail: t('add_project.missing_splash_desc'), life: 4000 })
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
    if (projectType.value === 'webgl') {
      if (webglMode.value === 'build' && files.value.webgl_build.file) formData.append('webgl_build', files.value.webgl_build.file)
      if (webglMode.value === 'url' && webglUrl.value) formData.append('webgl_url', webglUrl.value)
      if (githubUrl.value) formData.append('github_url', githubUrl.value)
      if (techStack.value) formData.append('tech_stack', techStack.value)
    }

    const url = isEditMode.value ? `${API_URL}/api/projects/${projectId.value}` : `${API_URL}/api/projects`
    
    // For PUT requests with FormData, use POST with method spoofing to avoid CORS issues
    if (isEditMode.value) {
      formData.append('_method', 'PUT')
    }
    
    const res = await apiFetch(url, { 
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
  webglUrl.value = ''
  webglMode.value = 'build'
  techStack.value = ''
  platform.value = null
  packageName.value = ''
  npmUrl.value = ''
  Object.keys(files.value).forEach(k => files.value[k] = { file: null, name: '' })
}

onMounted(() => {
  // Always start at the top of the form, even when navigating in from
  // a scrolled page (e.g. admin clicks "Pridať projekt" mid-list)
  window.scrollTo({ top: 0, left: 0, behavior: 'auto' })

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
/* ═══════════════════════════════════════════════════════════════ */
/* PAGE SHELL                                                      */
/* ═══════════════════════════════════════════════════════════════ */
.steam-page {
  padding: 28px 24px 64px;
}

.apv-container {
  max-width: 720px;
  margin: 0 auto;
}

/* ═══════════════════════════════════════════════════════════════ */
/* HEADER                                                         */
/* ═══════════════════════════════════════════════════════════════ */
.apv-header {
  margin-bottom: 28px;
}

.apv-title {
  font-size: 1.65rem;
  font-weight: 800;
  color: var(--color-text);
  margin: 14px 0 4px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  text-align: center;
}

.apv-subtitle {
  font-size: 0.88rem;
  color: var(--color-text-muted);
  margin: 0;
}

/* ═══════════════════════════════════════════════════════════════ */
/* STATE + ALERT PANELS                                           */
/* ═══════════════════════════════════════════════════════════════ */
.apv-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 56px 24px;
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

.apv-state-icon {
  font-size: 1.6rem;
  opacity: 0.55;
}

.apv-alert {
  display: flex;
  gap: 14px;
  align-items: flex-start;
  padding: 16px 20px;
  border-radius: 8px;
  font-size: 0.9rem;
  margin-bottom: 20px;
}

.apv-alert i { font-size: 1.1rem; margin-top: 2px; flex-shrink: 0; }
.apv-alert strong { display: block; margin-bottom: 3px; }
.apv-alert p { margin: 2px 0 0; opacity: 0.82; font-size: 0.85rem; }

.apv-alert-danger {
  background: rgba(var(--color-danger-rgb), 0.09);
  border: 1px solid rgba(var(--color-danger-rgb), 0.25);
  color: var(--color-danger);
}

.apv-alert-warn {
  background: rgba(var(--color-warning-rgb), 0.09);
  border: 1px solid rgba(var(--color-warning-rgb), 0.25);
  color: var(--color-warning);
}

/* ═══════════════════════════════════════════════════════════════ */
/* FORM                                                           */
/* ═══════════════════════════════════════════════════════════════ */
.apv-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* ═══════════════════════════════════════════════════════════════ */
/* SECTION CARDS                                                  */
/* ═══════════════════════════════════════════════════════════════ */
.apv-section {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  overflow: hidden;
}

.apv-section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 13px 20px;
  border-bottom: 1px solid var(--color-border);
  background: rgba(var(--color-accent-rgb), 0.03);
}

.apv-section-num {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--color-accent);
  color: var(--color-accent-contrast);
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  line-height: 1;
}

.apv-section-title {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin: 0;
}

/* ═══════════════════════════════════════════════════════════════ */
/* FIELDS                                                         */
/* ═══════════════════════════════════════════════════════════════ */
.apv-fields {
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 20px;
}

.apv-fields-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 20px;
  padding: 20px;
}

.apv-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.apv-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.apv-req {
  color: var(--color-accent);
  margin-left: 1px;
}

.apv-input {
  width: 100%;
}

.apv-hint {
  font-size: 0.77rem;
  color: var(--color-text-muted);
  opacity: 0.65;
  margin: 0;
  line-height: 1.4;
}

.apv-file-name {
  font-size: 0.81rem;
  color: #4ade80;
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 0;
}

.apv-webgl-warn {
  margin-top: 12px;
  background: rgba(234, 179, 8, 0.06);
  border: 1px solid rgba(234, 179, 8, 0.25);
  border-radius: 8px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.apv-webgl-warn-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.apv-webgl-warn-icon {
  color: #eab308;
  font-size: 0.95rem;
  flex-shrink: 0;
}

.apv-webgl-warn-title {
  font-size: 0.82rem;
  font-weight: 600;
  color: #eab308;
}

.apv-webgl-warn-list {
  margin: 0;
  padding-left: 18px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.apv-webgl-warn-list li {
  font-size: 0.79rem;
  color: var(--color-text-muted);
  line-height: 1.45;
}

.apv-webgl-warn-list li strong {
  color: var(--color-text);
}

.apv-webgl-warn-path {
  font-size: 0.74rem;
  color: var(--color-text-muted);
  margin: 0;
  padding-top: 4px;
  border-top: 1px solid rgba(234, 179, 8, 0.15);
}

.apv-webgl-warn-path em {
  color: #eab308;
  font-style: normal;
}

/* ═══════════════════════════════════════════════════════════════ */
/* TOGGLE CONTROL                                                 */
/* ═══════════════════════════════════════════════════════════════ */
.apv-toggle {
  display: inline-flex;
  align-self: flex-start;
  border: 1px solid var(--color-border);
  border-radius: 7px;
  overflow: hidden;
  background: var(--color-bg);
}

.apv-toggle-btn {
  padding: 7px 16px;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-text-muted);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
  display: flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}

.apv-toggle-btn + .apv-toggle-btn {
  border-left: 1px solid var(--color-border);
}

.apv-toggle-active {
  background: var(--color-accent);
  color: var(--color-accent-contrast);
}

.apv-toggle-content {
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* ═══════════════════════════════════════════════════════════════ */
/* SUBMIT AREA                                                    */
/* ═══════════════════════════════════════════════════════════════ */
.apv-submit {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 10px 0 8px;
}

.apv-submit-btn {
  min-width: 220px;
  padding: 12px 32px !important;
  font-size: 0.95rem !important;
  border-radius: 8px !important;
  font-weight: 700 !important;
}

.apv-submit-note {
  font-size: 0.76rem;
  color: var(--color-text-muted);
  opacity: 0.55;
  margin: 0;
}

/* ═══════════════════════════════════════════════════════════════ */
/* BUTTON STYLES (submit + back-in-alerts)                        */
/* ═══════════════════════════════════════════════════════════════ */
.steam-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 18px;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: background 0.12s, opacity 0.12s;
  white-space: nowrap;
  line-height: 1.4;
}

.steam-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.steam-btn-accent { background: var(--color-accent); color: var(--color-accent-contrast); }
.steam-btn-accent:hover:not(:disabled) { background: var(--color-accent-hover); }

/* ═══════════════════════════════════════════════════════════════ */
/* RESPONSIVE                                                     */
/* ═══════════════════════════════════════════════════════════════ */
@media (max-width: 640px) {
  .steam-page { padding: 16px 14px 48px; }
  .apv-fields-2col { grid-template-columns: 1fr; }
  .apv-title { font-size: 1.35rem; }
}
</style>
