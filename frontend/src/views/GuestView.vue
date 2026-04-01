<template>
  <div class="guest-root">
    <Toast />

    <div class="content-wrap">
      <section class="guest-hero">
        <div>
          <h1 class="guest-title">{{ t('guest.hero_title') }}</h1>
          <p class="guest-sub">{{ t('guest.hero_subtitle') }}</p>
        </div>
        <div class="guest-actions">
          <button class="steam-btn steam-btn-dark steam-btn-sm" @click="$router.push('/login')">{{ t('guest.login_btn') }}</button>
        </div>
      </section>

      <section class="filter-toolbar">
        <div class="filter-toolbar-top">
          <h2 class="section-heading">{{ t('filter.title') }}</h2>
          <div class="filter-toolbar-actions">
            <button v-if="hasActiveFilters" class="steam-btn steam-btn-ghost steam-btn-sm" @click="resetFilters">
              Reset
            </button>
          </div>
        </div>

        <div class="search-row">
          <div class="search-box">
            <input
              type="text"
              v-model="search"
              :placeholder="t('filter.search_placeholder')"
              class="search-input"
            />
          </div>
        </div>

        <div class="filter-grid">
          <div class="filter-item">
            <label class="filter-label">{{ t('filter.school_type_label') }}</label>
            <Dropdown
              v-model="filterSchoolType"
              :options="filterSchoolTypes"
              optionLabel="label"
              optionValue="value"
              :placeholder="t('filter.all_types')"
              class="filter-dropdown"
              @change="applyFilters"
            />
          </div>
          <div class="filter-item">
            <label class="filter-label">{{ t('filter.year_label') }}</label>
            <Dropdown
              v-model="filterYearOfStudy"
              :options="filterYears"
              optionLabel="label"
              optionValue="value"
              :placeholder="t('filter.all_years')"
              class="filter-dropdown"
              @change="applyFilters"
            />
          </div>
          <div class="filter-item">
            <label class="filter-label">{{ t('filter.subject_label') }}</label>
            <Dropdown
              v-model="filterSubject"
              :options="filterSubjects"
              optionLabel="label"
              optionValue="value"
              :placeholder="t('filter.all_subjects')"
              class="filter-dropdown"
              @change="applyFilters"
            />
          </div>
          <div class="filter-item">
            <label class="filter-label">{{ t('filter.project_type_label') }}</label>
            <Dropdown
              v-model="selectedType"
              :options="types"
              optionLabel="label"
              optionValue="value"
              :placeholder="t('filter.all_types')"
              class="filter-dropdown"
              @change="applyFilters"
            />
          </div>
        </div>
      </section>

      <section v-if="topRatedProjects.length || loadingTopRated" class="top-rated-section">
        <TopRatedCarousel
          v-if="topRatedProjects.length"
          :projects="topRatedProjects"
          @select="viewProjectDetail"
        />
        <div v-else-if="loadingTopRated" class="top-rated-loading">
          <span>{{ t('common.loading_top') }}</span>
        </div>
      </section>

      <div v-if="loadingGames" class="state-message">
        <span>{{ t('common.loading_projects') }}</span>
      </div>

      <div v-else-if="filteredGames.length === 0" class="state-message">
        <span>{{ t('common.no_projects') }}</span>
      </div>

      <section v-else class="project-grid">
        <div
          v-for="game in filteredGames"
          :key="game.id"
          class="project-card"
          @click="viewProjectDetail(game)"
        >
          <div class="card-thumb">
            <span v-if="!game.splash_screen_path" class="card-thumb-empty">{{ t('common.no_preview') }}</span>
            <img
              v-else
              :src="getSplashUrl(game.splash_screen_path)"
              :alt="game.title"
              class="card-thumb-img"
            />
          </div>

          <div class="card-body">
            <h3 class="card-title">{{ game.title }}</h3>

            <div class="card-tags">
              <span class="card-tag card-tag-accent" @click.stop="filterByType(game.type)">
                {{ formatProjectType(game.type) }}
              </span>
              <span v-if="game.school_type" class="card-tag" @click.stop="filterBySchoolType(game.school_type)">
                {{ getSchoolTypeLabel(game.school_type) }}
              </span>
              <span v-if="game.year_of_study" class="card-tag">{{ game.year_of_study }}{{ t('common.year_suffix') }}</span>
              <span v-if="game.subject" class="card-tag" @click.stop="filterBySubject(game.subject)">
                {{ game.subject }}
              </span>
            </div>

            <p class="card-desc">{{ game.description || t('common.no_description') }}</p>

            <div class="card-footer">
              <div class="card-meta-left">
                <div class="card-rating">
                  <span
                    v-for="star in 5"
                    :key="star"
                    :class="star <= Math.round(Number(game.rating || 0)) ? 'star-filled' : 'star-empty'"
                  >★</span>
                  <span class="rating-num">{{ Number(game.rating || 0).toFixed(1) }}</span>
                </div>
                <div class="card-views">
                  <i class="pi pi-eye view-icon" aria-hidden="true"></i>
                  <span class="view-num">{{ game.views || 0 }}</span>
                </div>
              </div>
              <div class="card-meta-right">
                <span class="card-team-name">{{ game.team?.name || t('common.unknown_team') }}</span>
                <span v-if="game.academic_year" class="card-year">{{ game.academic_year.name }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── PAGINATION ── -->
      <div v-if="lastPage > 1" class="pagination-bar">
        <button class="page-btn" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
          Prev
        </button>
        <template v-for="p in pageNumbers" :key="p">
          <span v-if="p === '...'" class="page-ellipsis">…</span>
          <button v-else class="page-btn" :class="{ 'page-btn-active': p === currentPage }" @click="goToPage(p)">{{ p }}</button>
        </template>
        <button class="page-btn" :disabled="currentPage === lastPage" @click="goToPage(currentPage + 1)">
          Next
        </button>
        <span class="page-info">{{ (currentPage - 1) * 20 + 1 }}–{{ Math.min(currentPage * 20, totalProjects) }} / {{ totalProjects }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Toast from 'primevue/toast'
import Dropdown from 'primevue/dropdown'
import TopRatedCarousel from '@/components/TopRatedCarousel.vue'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const { t } = useI18n()

const search = ref('')
const types = computed(() => [
  { label: t('project_types.all'), value: 'all' },
  { label: t('project_types.game'), value: 'game' },
  { label: 'Web App', value: 'web_app' },
  { label: 'Mobile App', value: 'mobile_app' },
  { label: t('project_types.library'), value: 'library' },
  { label: t('project_types.other'), value: 'other' }
])
const selectedType = ref('all')
const games = ref([])
const loadingGames = ref(true)
const topRatedProjects = ref([])
const loadingTopRated = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const totalProjects = ref(0)
const searchDebounce = ref(null)

const filterSchoolType = ref(null)
const filterYearOfStudy = ref(null)
const filterSubject = ref(null)
const filterAcademicYear = ref(null)

const filterSchoolTypes = computed(() => [
  { label: t('filter.all_types'), value: null },
  { label: t('school_types.zs'), value: 'zs' },
  { label: t('school_types.ss'), value: 'ss' },
  { label: t('school_types.vs'), value: 'vs' }
])
const filterSubjects = computed(() => [
  { label: t('subjects.all'), value: null },
  { label: t('subjects.sk_language'), value: 'Slovenský jazyk' },
  { label: t('subjects.math'), value: 'Matematika' },
  { label: t('subjects.history'), value: 'Dejepis' },
  { label: t('subjects.geography'), value: 'Geografia' },
  { label: t('subjects.informatics'), value: 'Informatika' },
  { label: t('subjects.graphics'), value: 'Grafika' },
  { label: t('subjects.chemistry'), value: 'Chémia' },
  { label: t('subjects.physics'), value: 'Fyzika' }
])
const filterYears = computed(() => [
  { label: t('filter.all_years'), value: null },
  ...Array.from({ length: 9 }, (_, i) => ({ label: `${i + 1}${t('common.year_suffix')}`, value: i + 1 }))
])

const hasActiveFilters = computed(() =>
  filterSchoolType.value !== null ||
  filterYearOfStudy.value !== null ||
  filterSubject.value !== null ||
  filterAcademicYear.value !== null ||
  selectedType.value !== 'all' ||
  search.value !== ''
)

const filteredGames = computed(() => games.value)

const pageNumbers = computed(() => {
  const total = lastPage.value
  const current = currentPage.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const pages = []
  pages.push(1)
  if (current > 3) pages.push('...')
  for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) pages.push(i)
  if (current < total - 2) pages.push('...')
  pages.push(total)
  return pages
})

function goToPage(page) {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  currentPage.value = page
  loadAllGames()
  document.querySelector('.project-grid, .state-message')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function resetFilters() {
  filterSchoolType.value = null
  filterYearOfStudy.value = null
  filterSubject.value = null
  filterAcademicYear.value = null
  selectedType.value = 'all'
  search.value = ''
  currentPage.value = 1
  loadAllGames()
}
function filterByType(type) { selectedType.value = type; currentPage.value = 1; loadAllGames() }
function filterBySchoolType(st) { filterSchoolType.value = st; currentPage.value = 1; loadAllGames() }
function filterBySubject(sub) { filterSubject.value = sub; currentPage.value = 1; loadAllGames() }
function applyFilters() { currentPage.value = 1; loadAllGames() }
function viewProjectDetail(project) { router.push({ name: 'ProjectDetail', params: { id: project.id } }) }

async function loadAllGames() {
  loadingGames.value = true
  try {
    const params = new URLSearchParams()
    if (selectedType.value && selectedType.value !== 'all') params.append('type', selectedType.value)
    if (search.value) params.append('search', search.value)
    if (filterSchoolType.value) params.append('school_type', filterSchoolType.value)
    if (filterYearOfStudy.value) params.append('year_of_study', filterYearOfStudy.value)
    if (filterSubject.value) params.append('subject', filterSubject.value)
    if (filterAcademicYear.value) params.append('academic_year_id', filterAcademicYear.value)
    params.append('page', currentPage.value)
    const query = params.toString() ? `?${params.toString()}` : ''
    const res = await fetch(`${API_URL}/api/public/projects${query}`, { headers: { 'Accept': 'application/json' } })
    if (res.ok) {
      const data = await res.json()
      games.value = data.data ?? data
      lastPage.value = data.last_page ?? 1
      totalProjects.value = data.total ?? games.value.length
    }
  } catch (_) {}
  finally { loadingGames.value = false }
}

async function loadTopRatedProjects() {
  loadingTopRated.value = true
  try {
    const res = await fetch(`${API_URL}/api/public/projects/top-rated`, { headers: { 'Accept': 'application/json' } })
    if (res.ok) topRatedProjects.value = await res.json()
  } catch (_) {}
  finally { loadingTopRated.value = false }
}

function getSchoolTypeLabel(type) { return { 'zs': 'ZŠ', 'ss': 'SŠ', 'vs': 'VŠ' }[type] || type }
function getSplashUrl(path) { if (!path) return ''; if (path.startsWith('http')) return path; return `${API_URL}/storage/${path}` }
function formatProjectType(type) { return { game: t('project_types.game'), web_app: 'Web App', mobile_app: 'Mobile App', library: t('project_types.library'), other: t('project_types.other') }[type] || type }

onMounted(() => {
  loadAllGames()
  loadTopRatedProjects()
})
watch(selectedType, () => { currentPage.value = 1; loadAllGames() })
watch([filterSchoolType, filterYearOfStudy, filterSubject, filterAcademicYear], () => { currentPage.value = 1; loadAllGames() })
watch(search, () => { clearTimeout(searchDebounce.value); searchDebounce.value = setTimeout(() => { currentPage.value = 1; loadAllGames() }, 400) })
</script>

<style scoped>
.guest-root {
  min-height: 100%;
}

.content-wrap {
  max-width: 1280px;
  margin: 0 auto;
  padding: 24px 32px 48px;
}

.guest-hero {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 24px;
}
.guest-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.guest-sub {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  margin-top: 6px;
}
.guest-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.filter-toolbar {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 20px 24px;
  margin-bottom: 24px;
}
.filter-toolbar-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
  gap: 12px;
  flex-wrap: wrap;
}
.section-heading {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.filter-toolbar-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.search-row { margin-bottom: 16px; }
.search-box { position: relative; }
.search-input {
  width: 100%;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 3px;
  color: var(--color-text);
  padding: 10px 12px;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.15s;
}
 .search-input::placeholder { color: var(--color-text-subtle); }
 .search-input:focus { border-color: var(--color-accent); }

.filter-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
@media (max-width: 900px) { .filter-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 540px) { .filter-grid { grid-template-columns: 1fr; } }
.filter-item { display: flex; flex-direction: column; gap: 4px; }
.filter-label { font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.04em; }

.top-rated-loading {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 18px 20px;
  margin-bottom: 24px;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
}

.state-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 80px 24px;
  color: var(--color-text-muted);
  font-size: 1rem;
}

.project-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
@media (max-width: 1100px) { .project-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .project-grid { grid-template-columns: 1fr; } }

/* ── Pagination ── */
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 24px 0 8px;
  flex-wrap: wrap;
}
.page-btn {
  min-width: 36px;
  height: 36px;
  padding: 0 10px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  color: var(--color-text);
  font-size: 0.875rem;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.page-btn:hover:not(:disabled) {
  background: var(--color-elevated);
  border-color: var(--color-accent);
  color: var(--color-accent);
}
.page-btn:disabled {
  opacity: 0.35;
  cursor: default;
}
.page-btn-active {
  background: var(--color-accent) !important;
  border-color: var(--color-accent) !important;
  color: #fff !important;
  font-weight: 600;
}
.page-ellipsis {
  color: var(--color-text-muted);
  padding: 0 4px;
  user-select: none;
}
.page-info {
  margin-left: 12px;
  font-size: 0.8rem;
  color: var(--color-text-muted);
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
.card-thumb-empty { font-size: 0.75rem; color: var(--color-text-subtle); }
.card-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}
.project-card:hover .card-thumb-img { transform: scale(1.03); }

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
.project-card:hover .card-title { color: var(--color-text-strong); }

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
  cursor: pointer;
  transition: background 0.12s, color 0.12s;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  font-weight: 500;
}
.card-tag:hover { background: var(--color-border); color: var(--color-text); }
.card-tag-accent {
  background: rgba(var(--color-accent-rgb), 0.12);
  border-color: rgba(var(--color-accent-rgb), 0.3);
  color: var(--color-accent);
}
.card-tag-accent:hover { background: rgba(var(--color-accent-rgb), 0.22); }

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
.card-team-name {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}
.card-year {
  font-size: 0.7rem;
  color: var(--color-text-subtle);
}

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

.steam-btn-dark {
  background: var(--color-border);
  color: var(--color-text);
}
.steam-btn-dark:hover:not(:disabled) { background: var(--color-border-strong); color: var(--color-text-strong); }

.steam-btn-ghost {
  background: transparent;
  color: var(--color-text-muted);
}
.steam-btn-ghost:hover:not(:disabled) { color: var(--color-text); background: var(--color-hover-bg-soft); }

.steam-btn-sm { padding: 6px 12px; font-size: 0.8rem; }

:deep(.filter-dropdown) {
  background: var(--color-elevated) !important;
  border: 1px solid var(--color-border) !important;
  border-radius: 3px !important;
  min-width: 200px;
}
:deep(.filter-dropdown .p-select-label),
:deep(.filter-dropdown .p-dropdown-label) {
  color: var(--color-text) !important;
  font-size: 0.85rem;
}
:deep(.filter-dropdown:not(.p-disabled):hover) { border-color: var(--color-border-strong) !important; }
:deep(.filter-dropdown:not(.p-disabled).p-focus) { border-color: var(--color-accent) !important; box-shadow: none !important; }

@media (max-width: 768px) {
  .content-wrap { padding: 16px 16px 40px; }
  .guest-hero { flex-direction: column; align-items: flex-start; }
  .filter-toolbar { padding: 16px; }
}

@media (max-width: 640px) {
  .guest-actions,
  .filter-toolbar-actions {
    width: 100%;
  }

  .guest-actions .steam-btn {
    width: 100%;
  }

  .card-footer {
    flex-direction: column;
    align-items: flex-start;
  }

  .card-meta-right {
    width: 100%;
    justify-content: space-between;
  }

  .page-info {
    width: 100%;
    margin-left: 0;
    text-align: center;
  }

  :deep(.filter-dropdown) {
    min-width: 0;
    width: 100%;
  }
}
</style>
