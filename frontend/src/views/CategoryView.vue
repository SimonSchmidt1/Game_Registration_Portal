<template>
  <div class="steam-page steam-theme cv-root">

    <!-- Top bar -->
    <div class="cv-topbar">
      <button class="back-btn" @click="goBack">
        <i class="pi pi-arrow-left"></i>
        <span>Späť</span>
      </button>
    </div>

    <!-- Hero -->
    <div class="cv-hero">
      <div class="cv-hero-blob cv-hero-blob-1"></div>
      <div class="cv-hero-blob cv-hero-blob-2"></div>
      <div class="cv-hero-blob cv-hero-blob-3"></div>
      <div class="cv-hero-content">
        <p class="cv-hero-label">{{ categoryLabel }}</p>
        <h1 class="cv-hero-title">{{ categoryValue }}</h1>
        <p v-if="!loading" class="cv-hero-count">
          <span class="cv-hero-count-num">{{ totalProjects }}</span>
          <span class="cv-hero-count-word">{{ projectWord(totalProjects) }}</span>
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="cv-state">
      <div class="cv-spinner"></div>
      <span>Načítavam projekty...</span>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="cv-panel cv-panel-danger">
      <strong>{{ error }}</strong>
    </div>

    <!-- Empty -->
    <div v-else-if="projects.length === 0" class="cv-empty">
      <i class="pi pi-inbox cv-empty-icon"></i>
      <p>Žiadne projekty v tejto kategórii.</p>
    </div>

    <!-- Grid -->
    <div v-else class="cv-grid">
      <ProjectCard
        v-for="project in projects"
        :key="project.id"
        :project="project"
        @select="p => router.push(`/project/${p.id}`)"
      />
    </div>

    <!-- Pagination -->
    <div v-if="lastPage > 1" class="cv-pagination">
      <button
        class="cv-page-btn"
        :disabled="currentPage <= 1"
        @click="changePage(currentPage - 1)"
      >
        <i class="pi pi-chevron-left"></i> Predchádzajúca
      </button>
      <span class="cv-page-info">{{ currentPage }} / {{ lastPage }}</span>
      <button
        class="cv-page-btn"
        :disabled="currentPage >= lastPage"
        @click="changePage(currentPage + 1)"
      >
        Nasledujúca <i class="pi pi-chevron-right"></i>
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '@/utils/apiFetch'
import ProjectCard from '@/components/ProjectCard.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const API_URL = import.meta.env.VITE_API_URL

const projects = ref([])
const loading = ref(true)
const error = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const totalProjects = ref(0)
const resolvedAcademicYearName = ref('')


const filterKey = computed(() => route.params.type)
const filterValue = computed(() => route.params.value)

const SCHOOL_TYPE_MAP = { zs: 'ZŠ', ss: 'SŠ', vs: 'VŠ' }

const categoryLabel = computed(() => {
  const map = {
    school_type: 'Typ školy',
    year_of_study: 'Ročník',
    subject: 'Predmet',
    type: 'Typ projektu',
    academic_year_id: 'Akademický rok',
  }
  return map[filterKey.value] || filterKey.value
})

const categoryValue = computed(() => {
  const key = filterKey.value
  const val = filterValue.value
  if (key === 'school_type') return SCHOOL_TYPE_MAP[val] || val
  if (key === 'year_of_study') return `${val}. ročník`
  if (key === 'type') {
    const types = ['game', 'web_app', 'mobile_app', 'library', 'webgl', 'other']
    return types.includes(val) ? t(`project_types.${val}`) : val
  }
  if (key === 'academic_year_id') return resolvedAcademicYearName.value || val
  return val
})

function projectWord(n) {
  if (n === 1) return 'projekt'
  if (n >= 2 && n <= 4) return 'projekty'
  return 'projektov'
}

async function loadProjects(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = new URLSearchParams({
      [filterKey.value]: filterValue.value,
      per_page: '21',
      page: String(page),
    })
    const res = await apiFetch(`${API_URL}/api/public/projects?${params}`)
    const data = await res.json()
    if (res.ok) {
      projects.value = data.data ?? []
      currentPage.value = data.current_page ?? 1
      lastPage.value = data.last_page ?? 1
      totalProjects.value = data.total ?? projects.value.length
      if (filterKey.value === 'academic_year_id' && projects.value.length > 0) {
        resolvedAcademicYearName.value = projects.value[0]?.academic_year?.name || filterValue.value
      }
    } else {
      error.value = data.message || 'Chyba pri načítaní projektov.'
    }
  } catch {
    error.value = 'Nepodarilo sa načítať projekty.'
  } finally {
    loading.value = false
  }
}

async function changePage(page) {
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
  await loadProjects(page)
}

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/')
}

onMounted(() => loadProjects(1))
</script>

<style scoped>
.cv-root {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px 28px 64px;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

/* ── Top bar ── */
.cv-topbar {
  display: flex;
  align-items: center;
}

/* ── Hero — Gradient Mesh Design ── */
.cv-hero {
  position: relative;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  padding: 48px 52px;
  overflow: hidden;
  isolation: isolate;
}

/* Three colored blobs forming a gradient mesh background */
.cv-hero-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  pointer-events: none;
  z-index: -1;
  opacity: 0.55;
}

.cv-hero-blob-1 {
  width: 380px;
  height: 380px;
  top: -120px;
  right: -80px;
  background: radial-gradient(circle, rgba(var(--color-accent-rgb), 0.45) 0%, transparent 70%);
  animation: cvBlobFloat1 14s ease-in-out infinite;
}

.cv-hero-blob-2 {
  width: 300px;
  height: 300px;
  bottom: -100px;
  right: 30%;
  background: radial-gradient(circle, rgba(var(--color-accent-rgb), 0.28) 0%, transparent 70%);
  animation: cvBlobFloat2 18s ease-in-out infinite;
}

.cv-hero-blob-3 {
  width: 260px;
  height: 260px;
  top: 30%;
  right: 15%;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.22) 0%, transparent 70%);
  animation: cvBlobFloat3 22s ease-in-out infinite;
}

@keyframes cvBlobFloat1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50%      { transform: translate(-30px, 20px) scale(1.08); }
}

@keyframes cvBlobFloat2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50%      { transform: translate(40px, -20px) scale(0.95); }
}

@keyframes cvBlobFloat3 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50%      { transform: translate(-20px, 30px) scale(1.1); }
}

/* Subtle vignette to keep text readable on the left */
.cv-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, var(--color-surface) 0%, rgba(0, 0, 0, 0) 70%);
  pointer-events: none;
}

.cv-hero-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cv-hero-label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  color: var(--color-accent);
  margin: 0;
}

.cv-hero-title {
  font-size: 2.6rem;
  font-weight: 800;
  color: var(--color-text-strong);
  letter-spacing: -0.035em;
  margin: 0;
  line-height: 1.05;
  background: linear-gradient(180deg, var(--color-text-strong) 0%, var(--color-text) 100%);
  -webkit-background-clip: text;
  background-clip: text;
}

.cv-hero-count {
  display: inline-flex;
  align-items: baseline;
  gap: 8px;
  margin: 4px 0 0;
  padding: 6px 14px;
  align-self: flex-start;
  background: rgba(var(--color-accent-rgb), 0.1);
  border: 1px solid rgba(var(--color-accent-rgb), 0.22);
  border-radius: 999px;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.cv-hero-count-num {
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--color-accent);
  letter-spacing: -0.01em;
}

.cv-hero-count-word {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: lowercase;
}

/* ── States ── */
.cv-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  padding: 64px 24px;
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

.cv-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-accent);
  border-radius: 50%;
  animation: cvSpin 0.7s linear infinite;
  flex-shrink: 0;
}

@keyframes cvSpin {
  to { transform: rotate(360deg); }
}

.cv-panel {
  padding: 16px 20px;
  border-radius: 10px;
  font-size: 0.9rem;
}

.cv-panel-danger {
  background: rgba(var(--color-danger-rgb), 0.08);
  border: 1px solid rgba(var(--color-danger-rgb), 0.25);
  color: var(--color-danger);
}

.cv-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 64px 24px;
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

.cv-empty-icon {
  font-size: 2.5rem;
  opacity: 0.25;
}

/* ── Grid ── */
.cv-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

/* ── Pagination ── */
.cv-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 8px 0;
}

.cv-page-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text-muted);
  cursor: pointer;
  transition: all 0.15s;
}

.cv-page-btn:hover:not(:disabled) {
  border-color: rgba(var(--color-accent-rgb), 0.4);
  color: var(--color-accent);
  background: rgba(var(--color-accent-rgb), 0.05);
}

.cv-page-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.cv-page-info {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-text-muted);
  min-width: 60px;
  text-align: center;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .cv-root { padding: 16px 14px 40px; }
  .cv-hero { padding: 32px 24px; }
  .cv-hero-title { font-size: 1.9rem; }
  .cv-hero-blob-1 { width: 240px; height: 240px; top: -80px; right: -60px; }
  .cv-hero-blob-2 { width: 200px; height: 200px; }
  .cv-hero-blob-3 { display: none; }
  .cv-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
}
</style>
