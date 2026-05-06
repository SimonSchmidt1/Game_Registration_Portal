<template>
  <div class="steam-page steam-theme pv-root">
    <Toast />

    <!-- Loading -->
    <div v-if="loading" class="state-message">
      <div class="pv-loader"></div>
      <span>{{ t('project.detail_loading') }}</span>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="steam-panel steam-panel-danger">
      <div>
        <strong>{{ error }}</strong>
        <p>{{ t('common.try_again') }}</p>
        <Button :label="t('project.back_btn')" class="steam-btn steam-btn-dark steam-btn-sm" @click="goBack" />
      </div>
    </div>

    <!-- Project content -->
    <div v-else-if="project" class="pv-sections">

      <!-- Top bar -->
      <div class="pv-topbar">
        <button class="back-btn" @click="goBack"><i class="pi pi-arrow-left"></i><span>{{ t('project.back_btn') }}</span></button>
        <button v-if="isCurrentUserScrumMaster" class="pv-edit-btn" @click="editProject">{{ t('project.edit_btn') }}</button>
      </div>

      <!-- ── HERO CARD ────────────────────────────────── -->
      <section class="pv-card pv-hero pv-animate" style="--delay:0">
        <div class="pv-hero-accent"></div>
        <div class="pv-hero-body">
          <div class="pv-hero-top">
            <h1 class="pv-title">{{ project.title }}</h1>
            <div class="pv-badges">
              <span v-if="project.school_type" class="pv-badge pv-badge-info">{{ getSchoolTypeLabel(project.school_type) }}</span>
              <span v-if="project.year_of_study" class="pv-badge pv-badge-info">{{ project.year_of_study }}. ročník</span>
              <span v-if="project.subject" class="pv-badge pv-badge-info">{{ project.subject }}</span>
              <span v-if="project.predmet" class="pv-badge pv-badge-info">{{ project.predmet }}</span>
            </div>
          </div>

          <!-- Rating row -->
          <div class="pv-rating-row">
            <div class="pv-stars">
              <span
                v-for="star in 5"
                :key="star"
                @click="submitRating(star)"
                :class="ratingStarClass(star)"
                :title="t('project.rate_title', { n: star })"
              >★</span>
            </div>
            <span class="pv-rating-value">{{ formatRating(project.rating) }} / 5</span>
          </div>
          <span class="pv-rating-count">Hlasov: {{ project.rating_count || 0 }}</span>

          <!-- Tags row -->
          <div class="pv-tags">
            <span class="pv-tag pv-tag-team" @click="goToTeam(project.team?.id)">{{ project.team?.name || t('common.unknown_team') }}</span>
            <span v-if="project.academic_year" class="pv-tag">{{ project.academic_year.name }}</span>
            <span v-if="project.release_date" class="pv-tag">{{ formatDate(project.release_date) }}</span>
            <span class="pv-tag pv-tag-accent">{{ t('project_types.' + project.type) || project.type }}</span>
            <span class="pv-tag pv-tag-views"><i class="pi pi-eye" aria-hidden="true"></i> {{ formatViews(project.views) }}</span>
          </div>
        </div>
      </section>

      <!-- ── VIDEO / SPLASH ────────────────────────────── -->
      <section v-if="project.video_path || project.splash_screen_path || (project.video_url && !project.type === 'webgl')" class="pv-card pv-animate" style="--delay:1">
        <h2 class="pv-section-title">{{ t('project.video_section') }}</h2>

        <div v-if="isYouTubeUrl(project.video_url)" class="pv-media-frame">
          <!-- Splash screen with play button — opens YouTube in new tab -->
          <div v-if="project.splash_screen_path" class="w-full h-full relative cursor-pointer" @click="openYouTube(project.video_url)">
            <img :src="getImageUrl(project.splash_screen_path)" :alt="project.title" class="w-full h-full object-cover" />
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="pv-play-circle">
                <i class="pi pi-play" style="font-size:1.3rem; margin-left:2px; margin-top:1px;"></i>
              </div>
            </div>
          </div>
          <!-- No splash screen — embed directly -->
          <iframe v-else :src="getYouTubeEmbedUrl(project.video_url)" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
        <div v-else-if="project.video_path" class="pv-media-frame pv-video-wrap" ref="videoContainer" tabindex="0">
          <video ref="videoPlayer" class="w-full h-full cursor-pointer" :src="getVideoUrl(project.video_path)" :poster="project.splash_screen_path ? getImageUrl(project.splash_screen_path) : ''" playsinline preload="metadata" @loadedmetadata="onLoadedMetadata" @timeupdate="onTimeUpdate" @play="videoPlaying = true" @pause="videoPlaying = false" @click="togglePlay"></video>
          <!-- Big play button overlay -->
          <div v-if="!videoPlaying" class="pv-play-overlay" @click="togglePlay">
            <div class="pv-play-circle">
              <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" style="margin-left:3px"><path d="M8 5v14l11-7z"/></svg>
            </div>
          </div>
          <!-- Controls bar -->
          <div class="pv-video-controls">
            <div class="pv-progress-row">
              <span class="pv-time">{{ formatTime(currentTime) }}</span>
              <div class="pv-progress-track">
                <div class="pv-progress-fill" :style="{ width: (duration > 0 ? (currentTime / duration * 100) : 0) + '%' }"></div>
                <input type="range" min="0" :max="duration" :value="currentTime" @input="onSeekInput" @change="onSeekChange" class="pv-progress-input" />
              </div>
              <span class="pv-time">{{ formatTime(duration) }}</span>
            </div>
            <div class="pv-controls-row">
              <div class="pv-controls-left">
                <button @click.stop="togglePlay" class="pv-ctrl-btn">
                  {{ videoPlaying ? 'Pause' : 'Play' }}
                </button>
                <button @click.stop="skip(-10)" class="pv-ctrl-btn pv-ctrl-sm">-10s</button>
                <button @click.stop="skip(10)" class="pv-ctrl-btn pv-ctrl-sm">+10s</button>
              </div>
              <div class="pv-controls-right">
                <button @click.stop="toggleMute" class="pv-ctrl-btn">
                  {{ (isMuted || volume===0) ? 'Muted' : 'Sound' }}
                </button>
                <input type="range" min="0" max="1" step="0.05" :value="isMuted ? 0 : volume" @input="changeVolume" class="pv-volume-slider" />
                <button @click.stop="toggleFullscreen" class="pv-ctrl-btn">
                  {{ isFullscreen ? 'Exit' : 'Full' }}
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="project.splash_screen_path && project.type !== 'webgl'" class="pv-media-frame pv-no-video">
          <img :src="getImageUrl(project.splash_screen_path)" :alt="project.title" class="w-full h-full object-cover pv-no-video-img" />
          <div class="pv-no-video-overlay" :title="t('project.no_video_hint')">
            <div class="pv-no-video-icon-wrap" aria-hidden="true">
              <i class="pi pi-video pv-no-video-icon"></i>
              <span class="pv-no-video-slash"></span>
            </div>
            <span class="pv-no-video-label">{{ t('project.no_video_label') }}</span>
          </div>
        </div>
      </section>

      <!-- ── WEBGL GAME ──────────────────────────────────── -->
      <section v-if="project.type === 'webgl' && webglSrc" class="pv-card pv-animate" style="--delay:2">
        <h2 class="pv-section-title">{{ t('project.webgl_section') }}</h2>
        <div class="pv-webgl-container">
          <!-- Splash / placeholder before activating -->
          <div v-if="!webglActive" class="pv-media-frame pv-webgl-preview cursor-pointer" @click="webglActive = true">
            <img v-if="project.splash_screen_path" :src="getImageUrl(project.splash_screen_path)" :alt="project.title" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center pv-webgl-placeholder">
              <i class="pi pi-desktop" style="font-size:3rem; opacity:0.3;"></i>
            </div>
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3">
              <div class="pv-play-circle">
                <i class="pi pi-play" style="font-size:1.3rem; margin-left:2px; margin-top:1px;"></i>
              </div>
              <span class="pv-webgl-play-label">{{ t('project.webgl_play') }}</span>
            </div>
          </div>
          <!-- Embedded game iframe -->
          <iframe
            v-else
            :src="webglSrc"
            class="pv-webgl-frame"
            allow="fullscreen; autoplay; gamepad"
            allowfullscreen
          ></iframe>
          <div class="pv-webgl-actions">
            <button class="pv-webgl-btn" @click="openWebGL(webglSrc)">
              <i class="pi pi-external-link"></i> {{ t('project.webgl_open') }}
            </button>
          </div>
        </div>
      </section>

      <!-- ── DESCRIPTION ──────────────────────────────── -->
      <section class="pv-card pv-animate" style="--delay:2">
        <h2 class="pv-section-title">{{ t('project.description_section') }}</h2>
        <p class="pv-description">{{ project.description || t('project.no_description') }}</p>
      </section>

      <!-- ── METADATA ─────────────────────────────────── -->
      <section v-if="project.metadata && hasAnyMetadata" class="pv-card pv-animate" style="--delay:3">
        <h2 class="pv-section-title">{{ t('project.project_section') }}</h2>
        <div class="pv-meta-grid">
          <a v-if="project.metadata.live_url" :href="project.metadata.live_url" target="_blank" rel="noopener" class="pv-meta-item pv-meta-link">
            <div>
              <span class="pv-meta-label">{{ t('project.live_url_label') }}</span>
              <span class="pv-meta-value pv-link-text">{{ project.metadata.live_url }}</span>
            </div>
          </a>
          <a v-if="project.metadata.github_url" :href="project.metadata.github_url" target="_blank" rel="noopener" class="pv-meta-item pv-meta-link">
            <div>
              <span class="pv-meta-label">GitHub</span>
              <span class="pv-meta-value pv-link-text">{{ project.metadata.github_url }}</span>
            </div>
          </a>
          <a v-if="project.metadata.npm_url" :href="project.metadata.npm_url" target="_blank" rel="noopener" class="pv-meta-item pv-meta-link">
            <div>
              <span class="pv-meta-label">NPM</span>
              <span class="pv-meta-value pv-link-text">{{ project.metadata.npm_url }}</span>
            </div>
          </a>
          <div v-if="project.metadata.package_name" class="pv-meta-item">
            <div>
              <span class="pv-meta-label">{{ t('project.package_label') }}</span>
              <span class="pv-meta-value">{{ project.metadata.package_name }}</span>
            </div>
          </div>
          <div v-if="project.metadata.platform" class="pv-meta-item">
            <div>
              <span class="pv-meta-label">{{ t('project.platform_label') }}</span>
              <span class="pv-meta-value">{{ project.metadata.platform }}</span>
            </div>
          </div>
        </div>
        <div v-if="techStack.length" class="pv-techstack">
          <span class="pv-meta-label">Tech Stack</span>
          <div class="pv-techstack-list">
            <span v-for="tech in techStack" :key="tech" class="pv-tech-pill">{{ tech }}</span>
          </div>
        </div>
      </section>

      <!-- ── TEAM MEMBERS ─────────────────────────────── -->
      <section v-if="project.team?.members && project.team.members.length > 0" class="pv-card pv-animate" style="--delay:4">
        <h2 class="pv-section-title">{{ t('project.team_members_section') }}</h2>
        <div class="pv-members-grid">
          <div v-for="member in project.team.members" :key="member.id" class="pv-member pv-member-clickable" :class="{ 'pv-member-sm': member.pivot?.role_in_team === 'scrum_master', 'pv-member-deactivated': isUserDeactivated(member) }" @click="selectedMember = member">
            <div class="pv-member-avatar" :class="{ 'pv-avatar-sm': member.pivot?.role_in_team === 'scrum_master' }">
              <img v-if="member.avatar_path" :src="getImageUrl(member.avatar_path)" :alt="member.name" class="pv-member-avatar-img" />
              <template v-else>{{ memberInitials(member.name) }}</template>
            </div>
            <div class="pv-member-info">
              <div class="pv-member-name-row">
                <span :class="member.is_absolvent ? 'pv-member-name pv-grad' : 'pv-member-name'">{{ member.name }}</span>
                <span v-if="member.is_absolvent" class="pv-grad-badge">{{ t('common.graduate') }}</span>
              </div>
              <span v-if="member.pivot?.role_in_team === 'scrum_master'" class="pv-member-role pv-role-sm">
                SM &bull; {{ formatOccupation(member.pivot?.occupation) || t('common.unspecified') }}
              </span>
              <span v-else class="pv-member-role">
                {{ formatOccupation(member.pivot?.occupation) || t('common.unspecified') }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- ── MEMBER POPUP ──────────────────────────────── -->
      <Teleport to="body">
        <div v-if="selectedMember" class="pv-member-overlay" @click.self="selectedMember = null">
          <div class="pv-member-popup">
            <button class="pv-popup-close" @click="selectedMember = null">×</button>
            <div class="pv-popup-avatar" :class="{ 'pv-popup-avatar-deactivated': isUserDeactivated(selectedMember) }">
              <img v-if="selectedMember.avatar_path" :src="getImageUrl(selectedMember.avatar_path)" :alt="selectedMember.name" class="pv-popup-avatar-img" />
              <template v-else>{{ memberInitials(selectedMember.name) }}</template>
            </div>
            <div class="pv-popup-name">{{ selectedMember.name }}</div>
            <div v-if="selectedMember.is_absolvent" class="pv-grad-badge" style="margin: 0 auto 0.5rem;">{{ t('common.graduate') }}</div>
            <div v-if="isUserDeactivated(selectedMember)" class="pv-deactivated-notice">
              Používateľ bol deaktivovaný adminom. Pravdepodobne už neštuduje na UCM.
            </div>
            <div class="pv-popup-row">
              <span class="pv-popup-label">E-mail</span>
              <span class="pv-popup-value">{{ selectedMember.email }}</span>
            </div>
            <div class="pv-popup-row" v-if="selectedMember.pivot?.occupation">
              <span class="pv-popup-label">Rola v tíme</span>
              <span class="pv-popup-value">{{ formatOccupation(selectedMember.pivot.occupation) || selectedMember.pivot.occupation }}</span>
            </div>
            <div class="pv-popup-row" v-if="selectedMember.pivot?.role_in_team === 'scrum_master'">
              <span class="pv-popup-label">Pozícia</span>
              <span class="pv-popup-value">Scrum Master</span>
            </div>
            <div class="pv-popup-row" v-if="selectedMember.student_type">
              <span class="pv-popup-label">Typ štúdia</span>
              <span class="pv-popup-value">{{ selectedMember.student_type === 'denny' ? 'Denné' : 'Externé' }}</span>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ── DOWNLOADS ────────────────────────────────── -->
      <section class="pv-card pv-animate" style="--delay:5">
        <h2 class="pv-section-title">{{ t('project.downloads_section') }}</h2>
        <div class="pv-downloads">
          <button v-if="project.files?.documentation" class="pv-dl-item" @click="downloadFile(project.files.documentation)">
            <span>{{ t('project.download_doc') }}</span>
          </button>
          <button v-if="project.files?.presentation" class="pv-dl-item" @click="downloadFile(project.files.presentation)">
            <span>{{ t('project.download_pres') }}</span>
          </button>
          <button v-if="project.files?.source_code" class="pv-dl-item" @click="downloadFile(project.files.source_code)">
            <span>{{ t('project.download_src') }}</span>
          </button>
          <button v-if="project.files?.export" class="pv-dl-item" @click="downloadFile(project.files.export)">
            <span>{{ getExportLabel() }}</span>
          </button>
          <button v-if="project.files?.project_folder" class="pv-dl-item" @click="downloadFile(project.files.project_folder)">
            <span>{{ t('project.download_folder') }}</span>
          </button>
          <button v-if="!project.files?.export && project.files?.apk_file" class="pv-dl-item" @click="downloadFile(project.files.apk_file)">
            <span>{{ t('project.download_apk') }}</span>
          </button>
          <button v-if="!project.files?.export && project.files?.ios_file" class="pv-dl-item" @click="downloadFile(project.files.ios_file)">
            <span>{{ t('project.download_ios') }}</span>
          </button>
          <p v-if="!hasAnyFiles" class="pv-no-files">{{ t('project.no_files') }}</p>
        </div>
      </section>

    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import { apiFetch } from '@/utils/apiFetch'
import { isUserDeactivated } from '../utils/userStatus.js'
import { viewCounts } from '@/utils/viewCountStore'
import { formatViews } from '@/utils/formatViews'

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { t } = useI18n();
const API_URL = import.meta.env.VITE_API_URL;

const token = ref(localStorage.getItem('access_token') || '')
const isGuest = computed(() => !token.value)
const project = ref(null)
const loading = ref(true)
const error = ref('')
const userHasRated = ref(false)
const currentUserId = ref(null)
const isCurrentUserScrumMaster = ref(false)
const videoContainer = ref(null)
const videoPlayer = ref(null)
const videoPlaying = ref(false)
const webglActive = ref(false)

const webglSrc = computed(() => {
  const meta = project.value?.metadata
  if (!meta) return null
  // Prefer URL if explicitly set (user chose URL mode), otherwise use local build
  if (meta.webgl_url) return meta.webgl_url
  if (meta.webgl_local_path)
    return `${API_URL}/storage/${meta.webgl_local_path.replace(/\/+$/, '')}/index.html`
  return null
})
const selectedMember = ref(null)
const currentTime = ref(0)
const duration = ref(0)
const isMuted = ref(false)
const isFullscreen = ref(false)
const volume = ref(1)
const seeking = ref(false)
const techStack = ref([])
const hasAnyMetadata = ref(false)

const hasAnyFiles = computed(() => {
  const files = project.value?.files || {}
  return !!(files.documentation || files.presentation || files.source_code || files.export || files.project_folder || files.apk_file || files.ios_file)
})

function getExportLabel() {
  const exportType = project.value?.metadata?.export_type
  if (!exportType) return t('project.download_export')
  
  const labels = {
    'standalone': t('project.download_standalone'),
    'webgl': t('project.download_webgl'),
    'mobile': t('project.download_mobile'),
    'executable': t('project.download_executable')
  }
  return labels[exportType] || t('project.download_export')
}

async function loadProject() {
  loading.value = true
  error.value = ''
  const id = route.params.id
  if (!id) { error.value = t('project.id_missing'); loading.value = false; return }
  try {
    const url = isGuest.value ? `${API_URL}/api/public/projects/${id}` : `${API_URL}/api/projects/${id}`
    const headers = isGuest.value ? { 'Accept': 'application/json' } : { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    const res = await apiFetch(url, { headers })
    const data = await res.json()
    if (res.ok && data.project) {
      project.value = data.project
      prepareMetadata()
      await incrementViews(id)
      if (isGuest.value) {
        userHasRated.value = false
        isCurrentUserScrumMaster.value = false
      } else {
        await loadUserRating(id)
        await checkScrumMasterStatus()
      }
    } else {
      error.value = data.message || t('project.not_found')
    }
  } catch (e) { error.value = t('project.load_error') } finally { loading.value = false }
}

async function loadCurrentUser() {
  if (!token.value) return
  try {
    const res = await apiFetch(`${API_URL}/api/user`, {
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    })
    if (res.ok) {
      const data = await res.json()
      currentUserId.value = data.id
    }
  } catch (_) {}
}

async function checkScrumMasterStatus() {
  if (!project.value || !project.value.team || !currentUserId.value) {
    isCurrentUserScrumMaster.value = false
    return
  }
  
  // Check if current user is Scrum Master of the project's team
  const currentUserMember = project.value.team.members?.find(m => m.id === currentUserId.value)
  isCurrentUserScrumMaster.value = currentUserMember?.pivot?.role_in_team === 'scrum_master' || 
                                    project.value.team.scrum_master_id === currentUserId.value
}

function editProject() {
  if (project.value) {
    router.push(`/edit-project/${project.value.id}`)
  }
}

async function loadUserRating(id) {
  if (isGuest.value) return
  try {
    const res = await apiFetch(`${API_URL}/api/projects/${id}/user-rating`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    if (res.ok) {
      const data = await res.json();
      if (data.rating !== null) userHasRated.value = true
    }
  } catch (_) {}
}

async function incrementViews(id) {
  // Optimistic update — user sees the new count immediately
  const optimistic = (project.value?.views || 0) + 1
  if (project.value) project.value.views = optimistic
  viewCounts[id] = optimistic

  const url = isGuest.value ? `${API_URL}/api/public/projects/${id}/views` : `${API_URL}/api/projects/${id}/views`
  const headers = isGuest.value ? {} : { 'Authorization': 'Bearer ' + token.value }
  try {
    const res = await apiFetch(url, { method: 'POST', headers })
    if (res.ok) {
      const data = await res.json()
      // Sync with real DB value
      if (data.views !== undefined) {
        if (project.value) project.value.views = data.views
        viewCounts[id] = data.views
      }
    }
    // If throttled we keep the optimistic value in the store
  } catch (_) {}
}

function formatRating(val) { return Number(val || 0).toFixed(1) }
function formatOccupation(occupation) {
  if (!occupation) return null
  const normalized = occupation.toLowerCase().trim()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
  const occupationMap = {
    'programator': t('occupations.programmer'),
    'grafik 2d': t('occupations.graphic_2d'),
    'grafik 3d': t('occupations.graphic_3d'),
    'tester': t('occupations.tester'),
    'animator': t('occupations.animator')
  }
  return occupationMap[normalized] || null
}
function ratingStarClass(star) {
  const avg = Math.round(Number(project.value?.rating) || 0)
  return star <= avg
    ? 'pv-star-filled'
    : 'pv-star-empty'
}
function setGuestRatingFlag(id) {
  if (!id) return
  localStorage.setItem(`guest_rated_${id}`, '1')
}
async function submitRating(star) {
  if (!isGuest.value && userHasRated.value) { toast.add({ severity: 'warn', summary: t('project.already_rated'), detail: t('project.already_rated_desc'), life: 4000 }); return }
  try {
    const url = isGuest.value ? `${API_URL}/api/public/projects/${route.params.id}/rate` : `${API_URL}/api/projects/${route.params.id}/rate`
    const headers = isGuest.value
      ? { 'Accept': 'application/json', 'Content-Type': 'application/json' }
      : { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json', 'Content-Type': 'application/json' }
    const res = await apiFetch(url, { method: 'POST', headers, body: JSON.stringify({ rating: star }) })
    const data = await res.json();
    if (res.ok) {
      if (data.already_rated) {
        userHasRated.value = true
        if (isGuest.value) setGuestRatingFlag(route.params.id)
        if (data.rating !== undefined && project.value) project.value.rating = data.rating
        if (data.rating_count !== undefined && project.value) project.value.rating_count = data.rating_count
        toast.add({ severity: 'warn', summary: t('project.already_rated'), detail: t('project.already_rated_desc'), life: 4000 })
        return
      }

      userHasRated.value = true
      if (isGuest.value) setGuestRatingFlag(route.params.id)
      if (data.rating !== undefined && project.value) project.value.rating = data.rating
      if (data.rating_count !== undefined && project.value) project.value.rating_count = data.rating_count
      const starWord = star === 1 ? 'hviezdu' : star <= 4 ? 'hviezdy' : 'hviezd'
      toast.add({ severity: 'success', summary: t('project.rating_saved'), detail: `Dal si ${star} ${starWord}.`, life: 4000 })
    } else {
      toast.add({ severity: 'error', summary: t('toast.error'), detail: data.message || t('project.rating_error'), life: 4000 })
    }
  } catch (_) { toast.add({ severity: 'error', summary: t('common.network_error'), detail: t('project.rating_error'), life: 4000 }) }
}
function formatDate(d) { if (!d) return 'Neznámy dátum'; return new Date(d).toLocaleDateString('sk-SK',{year:'numeric',month:'long',day:'numeric'}) }
function isYouTubeUrl(url){ return url && (url.includes('youtube.com')||url.includes('youtu.be')) }
function getYouTubeEmbedUrl(url){ if(!url) return ''; let id=''; if(url.includes('watch?v=')) id=url.split('v=')[1]?.split('&')[0]; else if(url.includes('youtu.be/')) id=url.split('youtu.be/')[1]?.split('?')[0]; else if(url.includes('embed/')) return url; return id?`https://www.youtube.com/embed/${id}`:url }
function getImageUrl(path){ if(!path) return ''; return path.startsWith('http')?path:`${API_URL}/storage/${path}` }
function openYouTube(url){ window.open(url, '_blank') }
function openWebGL(url){ window.open(url, '_blank') }
function getVideoUrl(path){ if(!path) return ''; return path.startsWith('http')?path:`${API_URL}/video/${path}` }
function downloadFile(path){ if(!path) return; const url=path.startsWith('http')?path:`${API_URL}/storage/${path}`; const a=document.createElement('a'); a.href=url; a.download=''; a.target='_blank'; document.body.appendChild(a); a.click(); document.body.removeChild(a); toast.add({severity:'success',summary:'Sťahovanie',detail:'Súbor sa sťahuje...',life:3000}) }
function togglePlay(){ if(!videoPlayer.value) return; if(videoPlayer.value.paused){ videoPlayer.value.play().catch((e)=>{ console.error("Video Play Error:", e); }); } else { videoPlayer.value.pause(); } }
function skip(sec){ const v=videoPlayer.value; if(!v) return; const target=Math.min(Math.max(0,v.currentTime+sec), v.duration||Infinity); v.currentTime=target; currentTime.value=target }
function onSeekInput(e){ if(!videoPlayer.value) return; seeking.value=true; currentTime.value=parseFloat(e.target.value) }
function onSeekChange(e){ const v=videoPlayer.value; if(!v) return; const val=parseFloat(e.target.value); const target=Math.min(Math.max(0,val), v.duration||Infinity); v.currentTime=target; currentTime.value=target; seeking.value=false }
function toggleMute(){ if(!videoPlayer.value) return; videoPlayer.value.muted=!videoPlayer.value.muted; isMuted.value=videoPlayer.value.muted }
function changeVolume(e){ if(!videoPlayer.value) return; volume.value=Number(e.target.value); videoPlayer.value.volume=volume.value; if(volume.value===0){ videoPlayer.value.muted=true; isMuted.value=true } else if(isMuted.value){ videoPlayer.value.muted=false; isMuted.value=false } }
function onLoadedMetadata(){ if(videoPlayer.value){ duration.value=videoPlayer.value.duration||0; videoPlayer.value.volume=volume.value } }
function onTimeUpdate(){ if(!videoPlayer.value) return; if(!seeking.value) currentTime.value=videoPlayer.value.currentTime }
function toggleFullscreen(){ const el=videoContainer.value; if(!el) return; if(!document.fullscreenElement){ el.requestFullscreen?.().then(()=> isFullscreen.value=true) } else { document.exitFullscreen?.().then(()=> isFullscreen.value=false) } }
function formatTime(t){ const s=Math.floor(t); const m=Math.floor(s/60); const r=s%60; return `${m}:${String(r).padStart(2,'0')}` }
function goToTeam(teamId){
  if (!teamId) return
  if (isGuest.value) {
    toast.add({ severity: 'info', summary: 'Len pre prihlásených', detail: 'Detail tímu je dostupný po prihlásení.', life: 4000 })
    return
  }
  router.push(`/team/${teamId}`)
}
function goBack(){ router.push(isGuest.value ? '/guest' : '/') }
function getSchoolTypeLabel(type) {
  const map = {
    'zs': 'ZŠ',
    'ss': 'SŠ',
    'vs': 'VŠ'
  }
  return map[type] || type
}
function memberInitials(name) {
  if (!name) return '?'
  return name.split(/\s+/).map(w => w[0]).join('').toUpperCase().slice(0, 2)
}
let fsHandler
function prepareMetadata(){
  if(!project.value || !project.value.metadata) { techStack.value=[]; hasAnyMetadata.value=false; return }
  const m = project.value.metadata
  hasAnyMetadata.value = !!(m.live_url || m.github_url || m.npm_url || m.package_name || m.platform || m.tech_stack)
  if(Array.isArray(m.tech_stack)) techStack.value = m.tech_stack.filter(v=>!!v).map(v=>String(v).trim())
  else if(typeof m.tech_stack === 'string') techStack.value = m.tech_stack.split(/[,;]/).map(v=>v.trim()).filter(v=>v)
  else techStack.value = []
}
function handleKey(e){ const tag=(e.target&&e.target.tagName?e.target.tagName.toLowerCase():''); if(['input','textarea','select','button'].includes(tag)) return; if(!videoPlayer.value) return; switch(e.code){ case 'Space': e.preventDefault(); togglePlay(); break; case 'ArrowLeft': e.preventDefault(); skip(-5); break; case 'ArrowRight': e.preventDefault(); skip(5); break; } }
onMounted(()=>{
  if (!isGuest.value) loadCurrentUser()
  loadProject()
  fsHandler=()=>{ isFullscreen.value=!!document.fullscreenElement }
  document.addEventListener('fullscreenchange',fsHandler)
  document.addEventListener('keydown',handleKey)
})
onUnmounted(()=>{ if(fsHandler) document.removeEventListener('fullscreenchange',fsHandler); document.removeEventListener('keydown',handleKey) })
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════
   ProjectView – Elegant Redesign
   ═══════════════════════════════════════════════════════════ */

/* ── Entrance animation ──────────────────────────────────── */
@keyframes pvSlideIn {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}

.pv-animate {
  animation: pvSlideIn 0.5s ease-out both;
  animation-delay: calc(var(--delay, 0) * 0.08s);
}

/* ── Page layout ─────────────────────────────────────────── */
.steam-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px 28px 64px;
}

.pv-sections {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* ── Loading spinner ─────────────────────────────────────── */
.state-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 80px 24px;
  color: var(--color-text-muted);
}

.pv-loader {
  width: 36px;
  height: 36px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-accent);
  border-radius: 50%;
  animation: pvSpin 0.7s linear infinite;
}

@keyframes pvSpin {
  to { transform: rotate(360deg); }
}

/* ── Error panel ─────────────────────────────────────────── */
.steam-panel {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 20px;
  border-radius: 12px;
  font-size: 0.9rem;
}

.steam-panel strong { display: block; margin-bottom: 4px; }

.steam-panel-danger {
  background: rgba(var(--color-danger-rgb), 0.08);
  border: 1px solid rgba(var(--color-danger-rgb), 0.25);
  color: var(--color-danger);
}

/* ── Top bar ─────────────────────────────────────────────── */
.pv-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pv-edit-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px 6px 10px;
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: 20px;
  border: 1px solid var(--color-border);
  background: transparent;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: all 0.15s;
  letter-spacing: 0.02em;
}

.pv-edit-btn:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
  background: rgba(var(--color-accent-rgb), 0.06);
}

/* ═══════════════════════════════════════════════════════════
   CARD BASE
   ═══════════════════════════════════════════════════════════ */
.pv-card {
  position: relative;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  padding: 28px;
  transition: border-color 0.25s, box-shadow 0.25s;
}

.pv-card:hover {
  border-color: rgba(var(--color-accent-rgb), 0.25);
  box-shadow: 0 0 30px -8px rgba(var(--color-accent-rgb), 0.06);
}

.pv-section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--color-text-strong);
  margin: 0 0 20px;
  letter-spacing: -0.01em;
}


/* ═══════════════════════════════════════════════════════════
   HERO CARD
   ═══════════════════════════════════════════════════════════ */
.pv-hero {
  overflow: hidden;
  padding: 0;
}

.pv-hero-accent {
  height: 4px;
  background: var(--color-accent);
}

.pv-hero-body {
  padding: 28px 28px 24px;
}

.pv-hero-top {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

.pv-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--color-text-strong);
  line-height: 1.2;
  letter-spacing: -0.02em;
  margin: 0;
}

/* ── Badges ──────────────────────────────────────────────── */
.pv-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.pv-badge {
  padding: 5px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 20px;
  white-space: nowrap;
}

.pv-badge-info {
  background: rgba(var(--color-accent-rgb), 0.1);
  color: var(--color-accent);
  border: 1px solid rgba(var(--color-accent-rgb), 0.2);
}

/* ── Star rating ─────────────────────────────────────────── */
.pv-rating-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  padding-bottom: 6px;
  margin-bottom: 4px;
}

.pv-rating-count {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  padding-bottom: 16px;
  margin-bottom: 16px;
  border-bottom: 1px solid var(--color-border);
  display: block;
}

.pv-stars {
  display: flex;
  gap: 4px;
}

.pv-stars span {
  font-size: 1.4rem;
  cursor: pointer;
  transition: transform 0.15s, filter 0.15s;
}

.pv-stars span:hover {
  transform: scale(1.25);
  filter: brightness(1.2);
}

.pv-star-filled {
  color: #facc15;
  text-shadow: 0 0 8px rgba(250, 204, 21, 0.4);
}

.pv-star-empty {
  color: var(--color-text-subtle);
}

.pv-rating-value {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-strong);
  letter-spacing: -0.01em;
}

/* ── Tags row ────────────────────────────────────────────── */
.pv-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.pv-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  font-size: 0.8rem;
  font-weight: 500;
  background: var(--color-elevated);
  color: var(--color-text);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  transition: all 0.15s;
}

.pv-tag-team {
  cursor: pointer;
  border-color: rgba(var(--color-accent-rgb), 0.25);
}

.pv-tag-team:hover {
  background: rgba(var(--color-accent-rgb), 0.1);
  border-color: rgba(var(--color-accent-rgb), 0.4);
  color: var(--color-accent);
}

.pv-tag-accent {
  background: rgba(var(--color-accent-rgb), 0.12);
  color: var(--color-accent);
  border-color: rgba(var(--color-accent-rgb), 0.25);
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.pv-tag-views {
  background: transparent;
  border-color: transparent;
  color: var(--color-text-muted);
  padding-left: 4px;
}

/* ═══════════════════════════════════════════════════════════
   VIDEO / MEDIA
   ═══════════════════════════════════════════════════════════ */
.pv-media-frame {
  aspect-ratio: 16 / 9;
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  background: #000;
  position: relative;
}

.pv-play-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 2;
}

.pv-play-circle {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  border: 2px solid rgba(255, 255, 255, 0.6);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(8px);
  transition: background 0.2s, transform 0.2s;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
}

.pv-play-overlay:hover .pv-play-circle,
.w-full:hover .pv-play-circle {
  background: rgba(255, 255, 255, 0.28);
  transform: scale(1.08);
}

/* No-video placeholder: splash shown with a clear "no video" indicator */
.pv-no-video {
  cursor: default;
}
.pv-no-video-img {
  filter: brightness(0.55) saturate(0.85);
}
.pv-no-video-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #fff;
  text-align: center;
  pointer-events: none;
  background: radial-gradient(ellipse at center,
              rgba(0, 0, 0, 0.05) 0%,
              rgba(0, 0, 0, 0.55) 80%);
  z-index: 2;
}
.pv-no-video-icon-wrap {
  position: relative;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.55);
  border: 2px solid rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}
.pv-no-video-icon {
  font-size: 1.9rem;
  color: #fff;
  opacity: 0.95;
}
.pv-no-video-slash {
  position: absolute;
  inset: 0;
  display: block;
  pointer-events: none;
}
.pv-no-video-slash::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 12%;
  right: 12%;
  height: 3px;
  background: #ef4444; /* red-500 */
  transform: rotate(-30deg);
  transform-origin: center;
  border-radius: 2px;
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.45);
}
.pv-no-video-label {
  font-size: 0.95rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  padding: 6px 14px;
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
}

/* Controls bar */
.pv-video-controls {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px 16px 12px;
  background: linear-gradient(to top, rgba(0,0,0,0.88), transparent);
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 3;
}

.pv-progress-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.pv-time {
  font-family: 'Inter', monospace;
  font-size: 0.72rem;
  color: rgba(255,255,255,0.7);
  min-width: 36px;
  text-align: center;
}

.pv-progress-track {
  flex: 1;
  position: relative;
  height: 4px;
  background: rgba(255,255,255,0.15);
  border-radius: 4px;
  cursor: pointer;
}

.pv-progress-fill {
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  background: var(--color-accent);
  border-radius: 4px;
  transition: width 0.1s linear;
}

.pv-progress-input {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
  z-index: 2;
}

.pv-controls-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pv-controls-left,
.pv-controls-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pv-ctrl-btn {
  background: none;
  border: none;
  color: rgba(255,255,255,0.85);
  cursor: pointer;
  padding: 4px 6px;
  font-size: 0.85rem;
  border-radius: 4px;
  transition: color 0.12s;
}

.pv-ctrl-btn:hover { color: var(--color-accent); }

.pv-ctrl-sm {
  font-size: 0.68rem;
  font-weight: 600;
  color: rgba(255,255,255,0.5);
}

.pv-volume-slider {
  width: 64px;
  height: 3px;
  appearance: none;
  background: rgba(255,255,255,0.15);
  border-radius: 3px;
  cursor: pointer;
  outline: none;
}

.pv-volume-slider::-webkit-slider-thumb {
  appearance: none;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #fff;
  cursor: pointer;
}

/* ═══════════════════════════════════════════════════════════
   DESCRIPTION
   ═══════════════════════════════════════════════════════════ */
.pv-description {
  color: var(--color-text);
  font-size: 0.95rem;
  line-height: 1.75;
  white-space: pre-wrap;
  margin: 0;
}

/* ═══════════════════════════════════════════════════════════
   METADATA
   ═══════════════════════════════════════════════════════════ */
.pv-meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 12px;
}

.pv-meta-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  transition: border-color 0.15s, background 0.15s;
  text-decoration: none;
}

.pv-meta-item > div {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.pv-meta-label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--color-text-subtle);
}

.pv-meta-value {
  font-size: 0.85rem;
  color: var(--color-text);
  word-break: break-all;
}

.pv-meta-link {
  cursor: pointer;
}

.pv-meta-link:hover {
  border-color: rgba(var(--color-accent-rgb), 0.3);
  background: rgba(var(--color-accent-rgb), 0.05);
}

.pv-link-text {
  color: var(--color-accent);
}

.pv-techstack {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pv-techstack-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.pv-tech-pill {
  padding: 4px 12px;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 20px;
  background: rgba(var(--color-accent-rgb), 0.08);
  color: var(--color-accent);
  border: 1px solid rgba(var(--color-accent-rgb), 0.15);
}

/* ═══════════════════════════════════════════════════════════
   TEAM MEMBERS
   ═══════════════════════════════════════════════════════════ */
.pv-members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 10px;
}

.pv-member {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  transition: border-color 0.15s, transform 0.15s;
}

/* ── WebGL ───────────────────────────────────────────── */
.pv-webgl-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pv-webgl-preview {
  position: relative;
}

.pv-webgl-placeholder {
  background: var(--color-elevated);
  color: var(--color-text-muted);
}

.pv-webgl-play-label {
  color: #fff;
  font-size: 0.9rem;
  font-weight: 600;
  letter-spacing: 0.03em;
  text-shadow: 0 1px 6px rgba(0,0,0,0.6);
}

.pv-webgl-frame {
  width: 100%;
  aspect-ratio: 16 / 9;
  border: none;
  border-radius: 10px;
  background: #000;
  display: block;
}

.pv-webgl-actions {
  display: flex;
  justify-content: flex-end;
}

.pv-webgl-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: transparent;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: all 0.15s;
}

.pv-webgl-btn:hover {
  background: var(--color-hover-bg);
  color: var(--color-text);
  border-color: var(--color-border-strong);
}

.pv-member-clickable { cursor: pointer; }
.pv-member-clickable:hover {
  border-color: var(--color-border-strong);
  transform: translateY(-1px);
}

.pv-member-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: pvFadeIn 0.15s ease-out both;
}

@keyframes pvFadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

.pv-member-popup {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 20px;
  padding: 2rem 2rem 1.75rem;
  width: 90%;
  max-width: 340px;
  position: relative;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
  animation: pvSlideIn 0.2s ease-out both;
}

.pv-popup-close {
  position: absolute;
  top: 1rem;
  right: 1.1rem;
  background: none;
  border: none;
  font-size: 1.4rem;
  line-height: 1;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: color 0.15s;
  padding: 0;
}
.pv-popup-close:hover { color: var(--color-text); }

.pv-popup-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(var(--color-accent-rgb), 0.12);
  border: 2px solid rgba(var(--color-accent-rgb), 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.2rem;
  margin: 0 auto 1rem;
  color: var(--color-accent);
  letter-spacing: 0.5px;
  overflow: hidden;
}

.pv-popup-name {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 0.2rem;
  text-align: center;
}

.pv-popup-row {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.6rem 0;
  border-top: 1px solid var(--color-border);
  margin-top: 0.5rem;
}

.pv-popup-label {
  color: var(--color-text-muted);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.pv-popup-value {
  color: var(--color-text);
  font-size: 0.92rem;
  word-break: break-word;
  line-height: 1.4;
}

.pv-member-sm {
  border-color: rgba(var(--color-accent-rgb), 0.2);
}

.pv-member-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: inherit;
  display: block;
}

.pv-popup-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}

.pv-member-avatar {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: 0.02em;
}

.pv-avatar-sm {
  background: linear-gradient(135deg, var(--color-accent), var(--color-accent-strong));
  color: var(--color-accent-contrast);
}

.pv-member-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.pv-member-name-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.pv-member-name {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--color-text);
}

.pv-member-name.pv-grad {
  color: var(--color-text-subtle);
  opacity: 0.7;
}

.pv-grad-badge {
  padding: 1px 6px;
  font-size: 0.65rem;
  font-weight: 600;
  border-radius: 4px;
  background: var(--color-hover-bg);
  color: var(--color-text-muted);
  border: 1px solid var(--color-border);
}

.pv-member-role {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.pv-role-sm {
  color: var(--color-accent);
  display: flex;
  align-items: center;
  gap: 4px;
}

.pv-member-deactivated {
  opacity: 0.45;
  filter: grayscale(0.6);
}

.pv-popup-avatar-deactivated {
  filter: grayscale(1);
  opacity: 0.6;
}

.pv-deactivated-notice {
  font-size: 0.78rem;
  color: #ef4444;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 6px;
  padding: 8px 12px;
  text-align: center;
  margin-bottom: 4px;
  line-height: 1.4;
}

/* ═══════════════════════════════════════════════════════════
   DOWNLOADS
   ═══════════════════════════════════════════════════════════ */
.pv-downloads {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pv-dl-item {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14px 18px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.18s;
  font-size: 0.88rem;
  font-weight: 500;
  color: var(--color-text);
  text-align: center;
}

.pv-dl-item > span {
  flex: none;
}

.pv-dl-item:hover {
  background: rgba(var(--color-accent-rgb), 0.06);
  border-color: rgba(var(--color-accent-rgb), 0.3);
}

.pv-no-files {
  text-align: center;
  padding: 24px;
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

/* ═══════════════════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
  .steam-page { padding: 16px 14px 40px; }
  .pv-card { padding: 20px; border-radius: 12px; }
  .pv-hero-body { padding: 20px 20px 18px; }
  .pv-title { font-size: 1.5rem; }
  .pv-meta-grid { grid-template-columns: 1fr; }
  .pv-members-grid { grid-template-columns: 1fr; }
}
</style>
