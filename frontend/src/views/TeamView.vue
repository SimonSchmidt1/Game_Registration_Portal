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
    <div v-else-if="team" class="space-y-6">
      <!-- Header with Back Button -->
      <div class="steam-header">
        <Button 
          :label="t('common.back')"
          class="steam-btn steam-btn-ghost steam-btn-sm"
          @click="$router.push('/')" 
        />
        <h1 class="steam-title">{{ team.name }}</h1>
      </div>

      <!-- Team Info Card -->
      <div class="steam-card">
        <div class="steam-grid">
          <!-- Left Column -->
          <div class="steam-stack">
            <div>
              <h3 class="section-heading">{{ t('team.basic_info_section') }}</h3>
              <div class="steam-stack-sm">
                <div class="steam-info-row">
                  <div>
                    <span class="steam-label">{{ t('create_team.academic_year_label') }}:</span>
                    <p class="steam-value">{{ team.academic_year?.name || 'N/A' }}</p>
                  </div>
                </div>
                <div class="steam-info-row">
                  <div>
                    <span class="steam-label">{{ t('team.members_count_label') }}</span>
                    <p class="steam-value">{{ team.members?.length || 0 }}/10</p>
                  </div>
                </div>
                <div class="steam-info-row">
                  <div>
                    <span class="steam-label">{{ t('team.projects_count_label') }}</span>
                    <p class="steam-value">{{ projectCount }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Invite Code -->
            <div class="steam-invite">
              <h3 class="section-heading">{{ t('team.invite_code_section') }}</h3>
              <div class="steam-invite-row">
                <span class="steam-invite-code">
                  {{ team.invite_code }}
                </span>
                <button class="steam-btn steam-btn-ghost steam-btn-xs" @click="copyInviteCode">
                  {{ t('team.copy_code_sm') }}
                </button>
              </div>
            </div>
          </div>

          <!-- Right Column: Team Members -->
          <div>
            <h3 class="section-heading">{{ t('project.team_members_section') }}</h3>
            <div class="steam-stack-xs">
              <div 
                v-for="member in team.members" 
                :key="member.id"
                class="member-row"
                @click="showMemberDetails(member)"
              >
                <div class="member-main">
                  <div 
                    v-if="member.avatar_path"
                    class="member-avatar"
                  >
                    <img 
                      :src="getAvatarUrl(member.avatar_path)" 
                      :alt="member.name"
                      class="member-avatar-img"
                    />
                  </div>
                  <div 
                    v-else
                    class="member-avatar member-avatar-fallback"
                  >
                    {{ member.name?.charAt(0).toUpperCase() }}
                  </div>
                  <div class="member-meta">
                    <div class="member-name-row">
                      <p :class="member.is_absolvent ? 'member-name member-name-muted' : 'member-name'">{{ member.name }}</p>
                      <span
                        v-if="member.is_absolvent"
                        class="steam-badge badge-muted"
                      >
                        {{ t('team.absolvent') }}
                      </span>
                    </div>
                    <p class="member-email">{{ member.email }}</p>
                    <div class="member-occupation">
                      <span class="steam-badge badge-sm">
                        {{ formatOccupation(member.pivot?.occupation) || t('common.unspecified') }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="member-role">
                  <span 
                    v-if="isScumMaster(member)"
                    class="steam-badge badge-active"
                  >
                    SM
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Team Projects -->
      <div class="steam-card">
        <h2 class="section-heading">{{ t('team.projects_section') }}</h2>
        
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
                <span v-if="project.year_of_study" class="card-tag">
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
                    <span class="rating-num">{{ t('team.rating_label') }} {{ project.rating || '0.0' }}</span>
                  </div>
                </div>
                <div class="card-meta-right">
                  <span class="card-year"><i class="pi pi-eye" aria-hidden="true"></i> {{ project.views || 0 }} {{ t('common.views_count') }}</span>
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
    :closable="true" 
    :draggable="false"
    class="dialog-shell dialog-md"
    :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem', border: 'none' }" 
    :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)', padding: '1.25rem 1.5rem', position: 'relative' }"
    :style="{ borderRadius: '4px', overflow: 'hidden' }"
  >
    <template #header>
      <div class="dlg-header">
        <span class="dlg-title">{{ t('team.member_info_title') }}</span>
      </div>
    </template>
    
    <div v-if="selectedMember" class="dlg-body">
      <!-- Avatar Section -->
      <div class="dlg-panel">
        <div class="dlg-avatar">
          <img 
            v-if="selectedMember.avatar_path" 
            :src="getAvatarUrl(selectedMember.avatar_path)" 
            alt="Avatar"
            class="dlg-avatar-img"
          />
          <div v-else class="dlg-avatar-fallback">
            {{ selectedMember.name?.charAt(0).toUpperCase() }}
          </div>
        </div>
      </div>
   
      <!-- Member Info -->
      <div class="dlg-info">
        <div class="dlg-row">
          <span class="dlg-label">{{ t('profile.name_label') }}:</span>
          <span class="dlg-value">{{ selectedMember.name }}</span>
        </div>
        
        <div class="dlg-row">
          <span class="dlg-label">{{ t('profile.email_label') }}:</span>
          <span class="dlg-value dlg-value-sm">{{ selectedMember.email }}</span>
        </div>
        
        <div class="dlg-row">
          <span class="dlg-label">{{ t('auth.student_type_label') }}:</span>
          <span v-if="selectedMember.student_type" class="steam-badge badge-sm">
            {{ getStudentTypeLabel(selectedMember.student_type) }}
          </span>
            <span v-else class="dlg-muted">{{ t('common.unspecified') }}</span>
        </div>

        <div class="dlg-row">
          <span class="dlg-label">{{ t('team.occupation_label') }}</span>
          <span class="steam-badge badge-sm">
            {{ formatOccupation(selectedMember.pivot?.occupation) || t('common.unspecified') }}
          </span>
        </div>

        <div v-if="isScumMaster(selectedMember)" class="dlg-row">
          <span class="dlg-label">{{ t('team.role_label') }}</span>
          <span class="steam-badge badge-active">SM</span>
        </div>

        <div v-if="selectedMember.is_absolvent" class="dlg-row">
          <span class="dlg-label">{{ t('team.member_status_label') }}</span>
          <span class="steam-badge badge-muted">{{ t('team.absolvent') }}</span>
        </div>
      </div>
   
      <div class="dlg-actions">
        <Button 
          :label="t('common.close')" 
          class="steam-btn steam-btn-dark auth-btn-block"
          @click="showMemberDialog = false" 
        />
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
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

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
    const res = await fetch(`${API_URL}/api/teams/${teamId}`, {
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

.steam-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
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
  margin-bottom: 20px;
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
.member-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}

.member-row:hover {
  border-color: var(--color-accent);
  background: var(--color-elevated);
}

.member-main {
  display: flex;
  align-items: center;
  gap: 12px;
}

.member-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid var(--color-border);
}

.member-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-avatar-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--color-accent), var(--color-border));
  color: var(--color-accent-contrast);
  font-weight: 700;
}

.member-meta {
  display: flex;
  flex-direction: column;
  gap: 6px;
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

.card-meta-left { display: flex; align-items: center; }
.card-rating { display: flex; align-items: center; gap: 4px; }
.star-filled { color: var(--color-warning); font-size: 0.75rem; }
.rating-num { font-size: 0.8rem; color: var(--color-text-muted); font-weight: 600; }
.card-meta-right { display: flex; align-items: center; }
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
  display: inline-block;
  padding: 2px 7px;
  font-size: 0.65rem;
  font-weight: 600;
  border-radius: 2px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.badge-active { background: rgba(var(--color-accent-rgb), 0.15); color: var(--color-accent); }
.badge-sm { background: rgba(var(--color-accent-rgb), 0.12); color: var(--color-accent); }
.badge-muted { background: rgba(var(--color-text-muted-rgb), 0.12); color: var(--color-text-muted); }

/* ═══════════════════════════════════════════════════════════ */
/* DIALOG                                                     */
/* ═══════════════════════════════════════════════════════════ */
.dialog-shell { width: 92%; max-width: 520px; }
.dialog-md { max-width: 520px; }

.dlg-header {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.dlg-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
}

.dlg-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.dlg-panel {
  display: flex;
  justify-content: center;
  padding: 18px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 4px;
}

.dlg-avatar {
  position: relative;
}

.dlg-avatar-img,
.dlg-avatar-fallback {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  border: 4px solid rgba(var(--color-accent-rgb), 0.4);
}

.dlg-avatar-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--color-accent), var(--color-border));
  color: var(--color-accent-contrast);
  font-weight: 700;
  font-size: 2rem;
}

.dlg-info { display: flex; flex-direction: column; gap: 10px; }

.dlg-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 4px;
}

.dlg-label { color: var(--color-text-muted); font-weight: 600; font-size: 0.85rem; }
.dlg-value { color: var(--color-text); font-weight: 600; }
.dlg-value-sm { font-size: 0.85rem; }
.dlg-muted { color: var(--color-text-subtle); font-size: 0.85rem; }

.dlg-actions { display: flex; }

.auth-btn-block { width: 100%; }

@media (max-width: 900px) {
  .steam-grid { grid-template-columns: 1fr; }
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

  .dlg-row {
    flex-direction: column;
    align-items: flex-start;
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
