<template>
  <div class="steam-page">
    <Toast />

    <!-- Loading State -->
    <div v-if="loading" class="state-message">
      <span>{{ t('common.loading') }}</span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="steam-panel steam-panel-danger">
      <div>
        <strong>{{ error }}</strong>
        <p>{{ t('common.try_again') }}</p>
        <Button :label="t('common.back')" class="steam-btn steam-btn-dark steam-btn-sm" @click="$router.push('/')" />
      </div>
    </div>

    <!-- Team Detail -->
    <div v-else-if="team" class="tv-layout">

      <!-- ── HERO ─────────────────────────────────────────── -->
      <div class="tv-hero">
        <button class="back-btn" @click="$router.push('/')">
          <i class="pi pi-arrow-left"></i>
          <span>{{ t('common.back') }}</span>
        </button>
        <h1 class="tv-hero-name">{{ team.name }}</h1>
        <div class="tv-hero-footer">
          <div class="tv-stats">
            <div class="tv-stat">
              <i class="pi pi-users"></i>
              <span>{{ team.members?.length || 0 }}<span class="tv-stat-max">/10</span></span>
              <span class="tv-stat-label">{{ t('team.members_count_label') }}</span>
            </div>
            <div class="tv-stat-divider"></div>
            <div class="tv-stat">
              <i class="pi pi-folder"></i>
              <span>{{ projectCount }}</span>
              <span class="tv-stat-label">{{ t('team.projects_count_label') }}</span>
            </div>
            <div class="tv-stat-divider"></div>
            <div class="tv-stat">
              <i class="pi pi-calendar"></i>
              <span>{{ team.academic_year?.name || '—' }}</span>
              <span class="tv-stat-label">{{ t('create_team.academic_year_label') }}</span>
            </div>
          </div>
          <div class="tv-invite-inline">
            <span class="tv-invite-label">{{ t('team.invite_code_section') }}</span>
            <span class="steam-invite-code">{{ team.invite_code }}</span>
            <button class="tv-copy-icon" @click="copyInviteCode">
              <i class="pi pi-copy"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- ── MEMBERS ───────────────────────────────────────── -->
      <div class="tv-section">
        <h2 class="tv-section-heading">{{ t('project.team_members_section') }}</h2>
        <div class="members-grid">
          <div
            v-for="(member, idx) in team.members"
            :key="member.id"
            :class="['member-row', isScumMaster(member) ? 'member-row-sm' : '', isUserDeactivated(member) ? 'member-row-deactivated' : '']"
            @click="showMemberDetails(member)"
          >
            <div class="member-rank">{{ idx + 1 }}</div>
            <div class="member-main">
              <div v-if="member.avatar_path" class="member-avatar">
                <img :src="getAvatarUrl(member.avatar_path)" :alt="member.name" class="member-avatar-img" />
              </div>
              <div v-else class="member-avatar member-avatar-fallback">
                <span>{{ member.name?.charAt(0).toUpperCase() }}</span>
              </div>
              <div class="member-meta">
                <div class="member-name-row">
                  <p :class="member.is_absolvent ? 'member-name member-name-muted' : 'member-name'">{{ member.name }}</p>
                  <span v-if="member.is_absolvent" class="steam-badge badge-muted">{{ t('team.absolvent') }}</span>
                </div>
                <span class="steam-badge badge-sm">{{ formatOccupation(member.pivot?.occupation) || t('common.unspecified') }}</span>
              </div>
            </div>
            <div class="member-role">
              <span v-if="isScumMaster(member)" class="steam-badge badge-active">SM</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── PROJECTS ─────────────────────────────────────── -->
      <div class="tv-section">
        <h2 class="tv-section-heading">{{ t('team.projects_section') }}</h2>
        
        <div v-if="loadingProjects" class="state-message">
          <span>{{ t('common.loading_projects') }}</span>
        </div>

        <div v-else-if="projects.length === 0" class="state-message">
          <span>{{ t('team.no_projects') }}</span>
        </div>

        <div v-else class="project-grid">
          <div
            v-for="project in projects"
            :key="project.id"
            class="project-card"
            @click="$router.push(`/project/${project.id}`)"
          >
            <div class="card-thumb">
              <span v-if="!project.splash_screen_path" class="card-thumb-empty">{{ t('common.no_preview') }}</span>
              <img 
                v-else 
                :src="getSplashUrl(project.splash_screen_path)" 
                :alt="project.title"
                class="card-thumb-img"
              />
            </div>
            
            <div class="card-body">
              <h3 class="card-title">{{ project.title }}</h3>
            
              <div class="card-tags">
                <span class="card-tag card-tag-accent">
                {{ formatProjectType(project.type) }}
              </span>
                <span v-if="project.school_type" class="card-tag">
                  {{ getSchoolTypeLabel(project.school_type) }}
                </span>
                <span
                  v-if="project.year_of_study"
                  class="card-tag card-tag-static"
                  @click.stop
                  @keydown.enter.stop
                  @keydown.space.stop
                >
                  {{ project.year_of_study }}{{ t('common.year_suffix') }}
                </span>
                <span v-if="project.subject" class="card-tag">
                  {{ project.subject }}
                </span>
              </div>
            
              <p class="card-desc">
                {{ project.description || t('team.no_description_short') }}
              </p>

              <div class="card-footer">
                <div class="card-meta-left">
                  <div class="card-rating">
                    <span v-for="star in 5" :key="star" :class="star <= Math.round(Number(project.rating || 0)) ? 'star-filled' : 'star-empty'">★</span>
                    <span class="rating-num">{{ Number(project.rating || 0).toFixed(1) }}</span>
                  </div>
                  <div class="card-views">
                    <i class="pi pi-eye view-icon" aria-hidden="true"></i>
                    <span class="view-num">{{ formatViews(viewCounts[project.id] ?? project.views) }}</span>
                  </div>
                </div>
                <div class="card-meta-right">
                  <span v-if="project.academic_year" class="card-year">{{ project.academic_year.name }}</span>
                </div>
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
    :closable="false"
    :draggable="false"
    :dismissableMask="true"
    :showHeader="false"
    class="dialog-shell dialog-md"
    :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '0', border: 'none' }"
    :style="{ borderRadius: '8px', overflow: 'hidden', border: '1px solid var(--color-border)' }"
  >
    <div v-if="selectedMember" class="dlg-card">

      <!-- Close button -->
      <button class="dlg-close" @click="showMemberDialog = false">
        <i class="pi pi-times"></i>
      </button>

      <!-- Avatar + identity -->
      <div class="dlg-identity">
        <div class="dlg-avatar-wrap" :class="{ 'dlg-avatar-deactivated': isUserDeactivated(selectedMember) }">
          <img v-if="selectedMember.avatar_path" :src="getAvatarUrl(selectedMember.avatar_path)" alt="Avatar" class="dlg-avatar-img" />
          <div v-else class="dlg-avatar-fallback"><span>{{ selectedMember.name?.charAt(0).toUpperCase() }}</span></div>
        </div>
        <h3 class="dlg-name">{{ selectedMember.name }}</h3>
        <div class="dlg-badges">
          <span v-if="isScumMaster(selectedMember)" class="steam-badge badge-active">Scrum Master</span>
          <span v-if="selectedMember.is_absolvent" class="steam-badge badge-muted">{{ t('team.absolvent') }}</span>
        </div>
      </div>

      <!-- Deactivated notice -->
      <div v-if="isUserDeactivated(selectedMember)" class="dlg-deactivated-notice">
        Používateľ bol deaktivovaný adminom. Pravdepodobne už neštuduje na UCM.
      </div>

      <!-- Info rows -->
      <div class="dlg-info">
        <div class="dlg-field">
          <span class="dlg-field-label">{{ t('profile.email_label') }}</span>
          <span class="dlg-field-value">{{ selectedMember.email }}</span>
        </div>
        <div class="dlg-field">
          <span class="dlg-field-label">{{ t('team.occupation_label') }}</span>
          <span class="dlg-field-value">{{ formatOccupation(selectedMember.pivot?.occupation) || t('common.unspecified') }}</span>
        </div>
        <div class="dlg-field">
          <span class="dlg-field-label">{{ t('auth.student_type_label') }}</span>
          <span class="dlg-field-value">{{ selectedMember.student_type ? getStudentTypeLabel(selectedMember.student_type) : t('common.unspecified') }}</span>
        </div>
      </div>

      <!-- Close button -->
      <div class="dlg-footer">
        <button class="steam-btn steam-btn-dark auth-btn-block" @click="showMemberDialog = false">
          {{ t('common.close') }}
        </button>
      </div>

    </div>
  </Dialog>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import { apiFetch } from '@/utils/apiFetch'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'
import { isUserDeactivated } from '../utils/userStatus.js'
import { formatViews } from '@/utils/formatViews'
import { viewCounts } from '@/utils/viewCountStore'

const vTooltip = Tooltip

const API_URL = import.meta.env.VITE_API_URL
const route = useRoute()
const router = useRouter()
const toast = useToast()
const { t } = useI18n()

const team = ref(null)
const projects = ref([])
const loading = ref(true)
const loadingProjects = ref(true)
const error = ref(null)
const showMemberDialog = ref(false)
const selectedMember = ref(null)

// Helper function to format occupation with proper diacritics and canonical labels
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

const projectCount = computed(() => projects.value.length)

async function loadTeam() {
  const teamId = route.params.id
  const token = localStorage.getItem('access_token')
  
  try {
    const res = await apiFetch(`${API_URL}/api/teams/${teamId}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    if (!res.ok) {
      if (res.status === 404) {
        error.value = t('team.not_found')
      } else {
        error.value = t('team.load_error')
      }
      return
    }

    const data = await res.json()
    team.value = data.team || data
  } catch (err) {
    console.error('Error loading team:', err)
    error.value = t('team.server_error')
  } finally {
    loading.value = false
  }
}

async function loadProjects() {
  const teamId = route.params.id
  const token = localStorage.getItem('access_token')
  
  try {
    const res = await apiFetch(`${API_URL}/api/projects/my?team_id=${teamId}`, {
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
      summary: t('team.code_copied'), 
      detail: t('team.code_copied_desc'), 
      life: 3000 
    })
  }
}

function showMemberDetails(member) {
  selectedMember.value = member
  showMemberDialog.value = true
}

function getStudentTypeLabel(type) {
  if (!type) return t('common.unspecified')
  const map = {
    'denny': t('auth.full_time'),
    'externy': t('auth.part_time')
  }
  return map[type] || type
}

onMounted(() => {
  loadTeam()
  loadProjects()
})
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════ */
/* PAGE LAYOUT                                                */
/* ═══════════════════════════════════════════════════════════ */
.steam-page {
  max-width: 1280px;
  margin: 0 auto;
  padding: 24px 32px 48px;
}

/* ═══════════════════════════════════════════════════════════ */
/* PAGE LAYOUT                                                */
/* ═══════════════════════════════════════════════════════════ */
.tv-layout {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* ═══════════════════════════════════════════════════════════ */
/* HERO                                                       */
/* ═══════════════════════════════════════════════════════════ */
.tv-hero {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-top: 3px solid var(--color-accent);
  border-radius: 4px;
  padding: 24px 28px 20px;
}


.tv-hero-name {
  font-size: 2rem;
  font-weight: 800;
  color: var(--color-text-strong);
  letter-spacing: -0.02em;
  margin-bottom: 18px;
  line-height: 1.15;
  text-align: center;
}

.tv-hero-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.tv-stats {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0;
}

.tv-stat {
  display: flex;
  align-items: center;
  gap: 7px;
  padding-right: 18px;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--color-text);
}

.tv-stat i {
  color: var(--color-accent);
  font-size: 0.85rem;
}

.tv-stat-max {
  font-weight: 400;
  color: var(--color-text-muted);
  font-size: 0.82rem;
}

.tv-stat-label {
  font-size: 0.72rem;
  font-weight: 400;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-left: -3px;
}

.tv-stat-divider {
  width: 1px;
  height: 22px;
  background: var(--color-border);
  margin: 0 16px 0 2px;
}

.tv-invite-inline {
  display: flex;
  align-items: center;
  gap: 10px;
}

.tv-invite-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-muted);
  font-weight: 600;
}

.tv-copy-icon {
  background: transparent;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 2px 4px;
  border-radius: 3px;
  font-size: 0.85rem;
  line-height: 1;
  transition: color 0.15s;
}

.tv-copy-icon:hover {
  color: var(--color-accent);
}

/* ═══════════════════════════════════════════════════════════ */
/* SECTIONS                                                   */
/* ═══════════════════════════════════════════════════════════ */
.tv-section {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 22px 24px;
}

.tv-section-heading {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--color-text-muted);
  margin-bottom: 14px;
}

/* ═══════════════════════════════════════════════════════════ */
/* (legacy)                                                   */
/* ═══════════════════════════════════════════════════════════ */
.steam-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.steam-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text);
}

.steam-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 22px 24px;
}

.steam-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 24px;
}

.steam-stack {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.steam-stack-sm {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.rating-num { font-size: 0.8rem; color: var(--color-text-muted); font-weight: 600; }
.star-filled { color: var(--color-warning); font-size: 0.75rem; }

.steam-stack-xs {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.section-heading {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 12px;
}

.steam-info-row {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--color-text-muted);
}

.steam-info-row i {
  color: var(--color-accent);
}

.steam-label {
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.steam-value {
  font-size: 0.95rem;
  color: var(--color-text);
  font-weight: 600;
}

.steam-invite {
  background: var(--color-surface-deep);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 14px 16px;
}

.steam-invite-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.steam-invite-code {
  font-family: monospace;
  font-size: 1.4rem;
  letter-spacing: 0.18em;
  color: var(--color-accent);
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
  margin-bottom: 20px;
}

.steam-panel strong {
  display: block;
  margin-bottom: 4px;
}

.steam-panel-danger {
  background: rgba(var(--color-danger-rgb), 0.1);
  border: 1px solid rgba(var(--color-danger-rgb), 0.3);
  color: var(--color-danger);
}

/* ═══════════════════════════════════════════════════════════ */
/* MEMBER LIST                                                */
/* ═══════════════════════════════════════════════════════════ */
.members-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 6px;
}

/* Every 3rd member (1st, 4th, 7th, 10th…) spans full width — alternating horizontal/vertical rhythm */
.members-grid .member-row:nth-child(3n+1) {
  grid-column: span 2;
}

@media (max-width: 700px) {
  .members-grid { grid-template-columns: 1fr; }
  .members-grid .member-row:nth-child(3n+1) { grid-column: span 1; }
}

.member-rank {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--color-text-subtle);
  width: 18px;
  text-align: center;
  flex-shrink: 0;
}

.member-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 8px 12px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-left: 3px solid transparent;
  border-radius: 4px;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}

.member-row-sm {
  border-left-color: var(--color-accent);
}

.member-row-deactivated {
  opacity: 0.45;
  filter: grayscale(0.6);
}

.member-row:hover {
  border-right-color: var(--color-accent);
  border-top-color: var(--color-accent);
  border-bottom-color: var(--color-accent);
  background: rgba(var(--color-accent-rgb), 0.04);
}

.member-main {
  display: flex;
  align-items: center;
  gap: 12px;
}

.member-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid var(--color-border);
  flex-shrink: 0;
}

.member-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-avatar-fallback {
  position: relative;
  overflow: hidden;
  background: rgba(var(--color-accent-rgb), 0.15);
  color: var(--color-accent);
  font-weight: 700;
}
.member-avatar-fallback span {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.member-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.member-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.member-name {
  color: var(--color-text);
  font-weight: 600;
}

.member-name-muted {
  color: var(--color-text-muted);
  opacity: 0.7;
}

.member-email {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.member-occupation {
  margin-top: 2px;
}

.member-role {
  display: flex;
  align-items: center;
}

/* ═══════════════════════════════════════════════════════════ */
/* PROJECT GRID (Steam-style)                                 */
/* ═══════════════════════════════════════════════════════════ */
.project-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

@media (max-width: 1100px) {
  .project-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
  .project-grid { grid-template-columns: 1fr; }
}

.project-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  overflow: hidden;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  transition: background 0.15s, border-color 0.15s, transform 0.15s;
}

.project-card:hover {
  background: var(--color-elevated);
  border-color: var(--color-accent);
  transform: translateY(-2px);
}

.card-thumb {
  width: 100%;
  aspect-ratio: 16/9;
  background: var(--color-surface-deep);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.card-thumb-empty {
  font-size: 0.75rem;
  color: var(--color-text-subtle);
}

.card-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.project-card:hover .card-thumb-img {
  transform: scale(1.03);
}
.project-card:hover .card-title { color: var(--color-text-strong); }

.card-body {
  padding: 14px 16px 16px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.card-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
  line-height: 1.3;
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 10px;
}

.card-tag {
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: 2px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
  font-weight: 500;
}

.card-tag.card-tag-static {
  cursor: default;
}

.card-tag-accent {
  background: rgba(var(--color-accent-rgb), 0.12);
  border-color: rgba(var(--color-accent-rgb), 0.3);
  color: var(--color-accent);
}

.card-desc {
  font-size: 0.82rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 12px;
  flex: 1;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid var(--color-border);
  padding-top: 10px;
  gap: 8px;
}

.card-meta-left { display: flex; align-items: center; gap: 12px; }
.card-rating { display: flex; align-items: center; gap: 2px; }
.card-views { display: flex; align-items: center; gap: 4px; }
.view-icon { font-size: 0.8rem; color: var(--color-text-muted); position: relative; top: 1px; }
.view-num { font-size: 0.8rem; color: var(--color-text-muted); font-weight: 600; }
.star-filled { color: var(--color-warning); font-size: 0.75rem; }
.star-empty  { color: var(--color-border); font-size: 0.75rem; }
.rating-num  { font-size: 0.8rem; color: var(--color-text-muted); margin-left: 4px; font-weight: 600; }
.card-meta-right { display: flex; align-items: center; gap: 8px; }
.card-year { font-size: 0.7rem; color: var(--color-text-subtle); }

/* ═══════════════════════════════════════════════════════════ */
/* BUTTONS & BADGES                                           */
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
.steam-btn-xs { padding: 4px 10px; font-size: 0.72rem; }

.steam-btn-dark {
  background: var(--color-border);
  color: var(--color-text);
}

.steam-btn-dark:hover:not(:disabled) { background: var(--color-border-strong); color: var(--color-text-strong); }

.steam-btn-ghost {
  background: transparent;
  color: var(--color-text-muted);
}

.steam-btn-ghost:hover:not(:disabled) {
  color: var(--color-text);
  background: var(--color-hover-bg-soft);
}

.steam-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 2px 7px;
  font-size: 0.65rem;
  font-weight: 600;
  border-radius: 2px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  line-height: 1;
}

.badge-active { background: rgba(var(--color-accent-rgb), 0.15); color: var(--color-accent); }
.badge-sm { background: rgba(var(--color-accent-rgb), 0.12); color: var(--color-accent); }
.badge-muted { background: rgba(var(--color-text-muted-rgb), 0.12); color: var(--color-text-muted); }

/* ═══════════════════════════════════════════════════════════ */
/* DIALOG — profile card                                     */
/* ═══════════════════════════════════════════════════════════ */
.dialog-shell { width: 92%; max-width: 360px; }
.dialog-md { max-width: 360px; }

.dlg-card {
  display: flex;
  flex-direction: column;
  position: relative;
}

.dlg-close {
  position: absolute;
  top: 14px;
  right: 14px;
  background: transparent;
  border: none;
  color: var(--color-text-muted);
  font-size: 0.9rem;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 3px;
  line-height: 1;
  transition: color 0.15s, background 0.15s;
  z-index: 1;
}
.dlg-close:hover { color: var(--color-text); background: var(--color-hover-bg-soft); }

/* Identity block — centered avatar + name + badges */
.dlg-identity {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 32px 24px 24px;
  background: var(--color-elevated);
  border-bottom: 1px solid var(--color-border);
}

.dlg-avatar-wrap {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid rgba(var(--color-accent-rgb), 0.35);
  flex-shrink: 0;
}

.dlg-avatar-deactivated {
  filter: grayscale(1);
  opacity: 0.6;
  border-color: var(--color-border);
}

.dlg-deactivated-notice {
  font-size: 0.78rem;
  color: #ef4444;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 6px;
  padding: 8px 12px;
  text-align: center;
  margin: 0 24px 4px;
  line-height: 1.4;
}

.dlg-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.dlg-avatar-fallback {
  width: 100%;
  height: 100%;
  position: relative;
  overflow: hidden;
  background: rgba(var(--color-accent-rgb), 0.15);
  color: var(--color-accent);
  font-weight: 700;
  font-size: 2rem;
}
.dlg-avatar-fallback span {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dlg-name {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-strong);
  text-align: center;
  line-height: 1.3;
}

.dlg-badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: center;
  min-height: 18px;
}

/* Stacked info fields */
.dlg-info {
  display: flex;
  flex-direction: column;
  padding: 20px 24px;
  gap: 16px;
}

.dlg-field {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.dlg-field-label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--color-text-muted);
}

.dlg-field-value {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-text);
}

.dlg-footer {
  padding: 0 24px 24px;
}

.auth-btn-block { width: 100%; }

@media (max-width: 900px) {
  .steam-grid { grid-template-columns: 1fr; }
  .tv-hero-footer { flex-direction: column; align-items: flex-start; }
}

@media (max-width: 768px) {
  .steam-page { padding: 16px 16px 40px; }

  .steam-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .steam-title {
    font-size: 1.55rem;
  }

  .steam-card {
    padding: 16px;
  }

  .steam-panel {
    padding: 14px;
  }

  .member-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .member-role {
    width: 100%;
    justify-content: flex-end;
  }

  .steam-invite-row {
    flex-wrap: wrap;
  }

  .steam-invite-code {
    font-size: 1.05rem;
    letter-spacing: 0.1em;
    word-break: break-all;
  }

}

@media (max-width: 640px) {
  .card-footer {
    flex-direction: column;
    align-items: flex-start;
  }

  .card-meta-right {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
