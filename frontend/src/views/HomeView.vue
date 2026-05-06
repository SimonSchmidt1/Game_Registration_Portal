<template>
  <div class="home-root">
    <!-- Animated background layer -->
    <div class="bg-canvas" aria-hidden="true">
      <div class="bg-grid"></div>
      <div class="bg-orb bg-orb-1"></div>
      <div class="bg-orb bg-orb-2"></div>
      <div class="bg-orb bg-orb-3"></div>
      <div class="bg-particle bg-particle-1"></div>
      <div class="bg-particle bg-particle-2"></div>
      <div class="bg-particle bg-particle-3"></div>
      <div class="bg-particle bg-particle-4"></div>
      <div class="bg-particle bg-particle-5"></div>
      <div class="bg-particle bg-particle-6"></div>
    </div>
    <Toast />

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- TEAM SELECTOR BAR (with team actions) -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section v-if="hasTeam && teams.length > 0 && !isAdmin && token" class="team-bar-section">
      <div class="team-bar">
        <div class="team-bar-left">
          <label class="team-bar-label">{{ t('team.active_team') }}</label>
          <Dropdown
            v-model="selectedTeam"
            :options="teams"
            optionLabel="name"
            :placeholder="t('team.select_placeholder')"
            class="team-dropdown"
          >
            <template #value="slotProps">
              <div v-if="slotProps.value" class="flex items-center gap-2">
                <span class="font-medium text-slate-100">{{ slotProps.value.name }}</span>
                <span v-if="slotProps.value.status === 'pending'" class="steam-badge badge-pending">{{ t('team.waiting') }}</span>
                <span v-else-if="slotProps.value.status === 'suspended'" class="steam-badge badge-suspended">{{ t('team.suspended_short') }}</span>
              </div>
            </template>
            <template #option="slotProps">
              <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2">
                  <span class="font-medium text-slate-100">{{ slotProps.option.name }}</span>
                  <span v-if="slotProps.option.status === 'pending'" class="steam-badge badge-pending">{{ t('team.pending_full') }}</span>
                  <span v-else-if="slotProps.option.status === 'suspended'" class="steam-badge badge-suspended">{{ t('team.suspended_full') }}</span>
                </div>
                <span v-if="slotProps.option.academic_year" class="text-xs text-slate-500">{{ slotProps.option.academic_year.name }}</span>
              </div>
            </template>
          </Dropdown>
        </div>

        <!-- Team meta chips -->
        <div v-if="selectedTeam" class="team-bar-meta">
          <span v-if="selectedTeam.is_scrum_master" class="meta-chip meta-chip-accent">{{ t('team.scrum_master') }}</span>
          <span v-if="getCurrentUserOccupation(selectedTeam)" class="meta-chip">{{ getCurrentUserOccupation(selectedTeam) }}</span>
        </div>

        <!-- Team action buttons -->
        <div class="team-bar-actions">
          <button class="steam-btn steam-btn-dark steam-btn-sm" @click="showTeamStatusDialog = true">
            {{ t('team.my_teams') }}
          </button>
          <button class="steam-btn steam-btn-dark steam-btn-sm" @click="showJoinTeam = true">
            {{ t('team.join') }}
          </button>
          <button class="steam-btn steam-btn-accent steam-btn-sm" @click="showCreateTeam = true">
            {{ t('team.create') }}
          </button>
        </div>
      </div>

      <!-- Status warnings -->
      <div v-if="selectedTeam && selectedTeam.status === 'pending'" class="team-warning team-warning-pending">
        <div>
          <strong>{{ t('team.pending_warning_title') }}</strong>
          <p>{{ t('team.pending_warning_desc') }}</p>
        </div>
      </div>
      <div v-if="selectedTeam && selectedTeam.status === 'suspended'" class="team-warning team-warning-suspended">
        <div>
          <strong>{{ t('team.suspended_warning_title') }}</strong>
          <p>{{ t('team.suspended_warning_desc') }}</p>
        </div>
      </div>
    </section>

    <!-- No-team action strip (logged in, no team yet, not admin) -->
    <section v-if="token && !hasTeam && !isAdmin" class="team-bar-section">
      <div class="team-bar" style="justify-content: center;">
        <span class="team-bar-note">{{ t('team.no_team_yet') }}</span>
        <div class="team-bar-actions">
          <button class="steam-btn steam-btn-dark steam-btn-sm" @click="showJoinTeam = true">
            {{ t('team.join_team') }}
          </button>
          <button class="steam-btn steam-btn-accent steam-btn-sm" @click="showCreateTeam = true">
            {{ t('team.create') }}
          </button>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- MAIN CONTENT AREA -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="content-wrap">

      <section v-if="token && (topRatedProjects.length || loadingTopRated)" class="top-rated-section">
        <TopRatedCarousel
          v-if="topRatedProjects.length"
          :projects="topRatedProjects"
          @select="viewProjectDetail"
        />
        <div v-else-if="loadingTopRated" class="top-rated-loading">
          <span>{{ t('common.loading_top') }}</span>
        </div>
      </section>

      <!-- ── FILTER TOOLBAR ── -->
      <section v-if="token" class="filter-toolbar">
        <div class="filter-toolbar-top">
          <h2 class="section-heading">
            <span v-if="showingMyProjects">{{ t('filter.my_projects_title') }}</span>
            <span v-else>{{ t('filter.title') }}</span>
          </h2>
          <div class="filter-toolbar-actions">
            <button
              v-if="hasTeam && selectedTeam && !showingMyProjects && !isAdmin"
              class="steam-btn steam-btn-dark steam-btn-sm"
              @click="loadMyProjects"
            >
              {{ t('filter.my_projects_btn') }}
            </button>
            <button
              v-if="showingMyProjects && !isAdmin"
              class="steam-btn steam-btn-dark steam-btn-sm"
              @click="showAllProjects"
            >
              {{ t('filter.all_projects_btn') }}
            </button>
            <button
              v-if="hasActiveFilters"
              class="steam-btn steam-btn-ghost steam-btn-sm"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>

        <!-- Search -->
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

        <!-- Grid of filter dropdowns -->
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
            />
          </div>
          <div class="filter-item">
            <label class="filter-label">{{ t('filter.year_label') }}</label>
            <Dropdown
              v-model="filterYearOfStudy"
              :options="availableFilterYears"
              optionLabel="label"
              optionValue="value"
              :placeholder="t('filter.all_years')"
              class="filter-dropdown"
              :disabled="!filterSchoolType"
            />
            <small v-if="!filterSchoolType" class="filter-help-warning">{{ t('filter.select_school_type_first') }}</small>
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
            />
          </div>
        </div>
      </section>

      <!-- ── NOT LOGGED IN ── -->
      <section v-if="!token" class="landing-section">
        <div class="landing-card">
          <img
            :src="landingLogoSrc"
            alt="UCM Logo"
            class="landing-logo"
          />
          <h2 class="landing-heading">
            {{ t('landing.title') }}
            <span class="landing-heading-ucm">UCM</span>
            <span class="landing-heading-accent"> FPV</span>
          </h2>
          <p class="landing-text">{{ t('landing.desc') }}</p>
          <div class="landing-actions">
            <button class="steam-btn steam-btn-accent steam-btn-lg" @click="$router.push('/login')">
              {{ t('landing.login_btn') }}
            </button>
            <button class="steam-btn steam-btn-accent steam-btn-lg" @click="$router.push('/guest')">
              {{ t('landing.guest_btn') }}
            </button>
            <button class="steam-btn steam-btn-accent steam-btn-lg" @click="$router.push('/register')">
              {{ t('landing.register_btn') }}
            </button>
          </div>
        </div>
      </section>

      <!-- ── LOADING ── -->
      <div v-else-if="loadingGames" class="state-message">
        <span>{{ t('common.loading_projects') }}</span>
      </div>

      <!-- ── EMPTY STATE ── -->
      <div v-else-if="filteredGames.length === 0" class="state-message">
        <span>{{ t('common.no_projects') }}</span>
      </div>

      <!-- ── PROJECT GRID ── -->
      <section v-else class="project-grid">
        <div
          v-for="game in filteredGames"
          :key="game.id"
          class="project-card"
          @click="viewProjectDetail(game)"
        >
          <!-- Thumbnail -->
          <div class="card-thumb">
            <span v-if="!game.splash_screen_path" class="card-thumb-empty">{{ t('common.no_preview') }}</span>
            <img
              v-else
              :src="getSplashUrl(game.splash_screen_path)"
              :alt="game.title"
              class="card-thumb-img"
            />
          </div>

          <!-- Body -->
          <div class="card-body">
            <h3 class="card-title">{{ game.title }}</h3>

            <!-- Tags row -->
            <div class="card-tags">
              <span
                class="card-tag card-tag-accent"
                @click.stop="filterByType(game.type)"
              >
                {{ formatProjectType(game.type) }}
              </span>
              <span
                v-if="game.school_type"
                class="card-tag"
                @click.stop="filterBySchoolType(game.school_type)"
              >
                {{ getSchoolTypeLabel(game.school_type) }}
              </span>
              <span
                v-if="game.year_of_study"
                class="card-tag card-tag-static"
                @click.stop
                @keydown.enter.stop
                @keydown.space.stop
              >
                {{ game.year_of_study }}{{ t('common.year_suffix') }}
              </span>
              <span
                v-if="game.subject"
                class="card-tag"
                @click.stop="filterBySubject(game.subject)"
              >
                {{ game.subject }}
              </span>
            </div>

            <p class="card-desc">{{ game.description || t('common.no_description') }}</p>

            <!-- Footer meta -->
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
                <span
                  class="card-team-link"
                  @click.stop="goToTeam(game.team?.id)"
                >
                  {{ game.team?.name || t('common.unknown_team') }}
                </span>
                <span v-if="game.academic_year" class="card-year" @click.stop="filterByAcademicYear(game.academic_year.id)">
                  {{ game.academic_year.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── PAGINATION ── -->
      <div v-if="lastPage > 1" class="pagination-bar">
        <button class="page-btn page-btn-nav" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
          <i class="pi pi-arrow-left"></i>
        </button>
        <template v-for="p in pageNumbers" :key="p">
          <span v-if="p === '...'" class="page-ellipsis">…</span>
          <button v-else class="page-btn" :class="{ 'page-btn-active': p === currentPage }" @click="goToPage(p)">{{ p }}</button>
        </template>
        <button class="page-btn page-btn-nav" :disabled="currentPage === lastPage" @click="goToPage(currentPage + 1)">
          <i class="pi pi-arrow-right"></i>
        </button>
        <input
          v-model="pageJumpInput"
          type="number"
          class="page-jump-input"
          :placeholder="String(currentPage)"
          :min="1"
          :max="lastPage"
          @keyup.enter="jumpToPage"
        />
        <span class="page-info">{{ (currentPage - 1) * 21 + 1 }}–{{ Math.min(currentPage * 21, totalProjects) }} / {{ totalProjects }}</span>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- DIALOG: CREATE TEAM -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <Dialog v-model:visible="showCreateTeam" :modal="true" :closable="false" :draggable="false" :dismissableMask="true" :blockScroll="true" class="dialog-shell" :contentStyle="dialogContentStyle" :headerStyle="dialogHeaderStyle" :style="dialogStyle">
      <template #header>
        <div class="dlg-header">
          <span class="dlg-title" v-if="!teamCreatedSuccess">Vytvoriť Nový Tím</span>
          <button class="dlg-close" @click="closeCreateTeamDialog">×</button>
        </div>
      </template>

      <div v-if="!teamCreatedSuccess">
        <form @submit.prevent="createTeam" class="dlg-form">
          <div class="dlg-field">
            <label for="createTeamName">Názov tímu</label>
            <InputText id="createTeamName" name="teamName" v-model="teamName" placeholder="Zadajte názov tímu" required class="dlg-input" />
          </div>
          <div class="dlg-field">
            <label for="createTeamAcademicYear">Akademický rok</label>
            <Dropdown id="createTeamAcademicYear" name="academicYear" v-model="academicYear" :options="academicYears" optionLabel="name" optionValue="id" placeholder="Vyber akademický rok" class="dlg-dropdown" />
          </div>
          <div class="dlg-field">
            <label for="createTeamOccupation">Povolanie</label>
            <Dropdown id="createTeamOccupation" name="createTeamOccupation" v-model="createTeamOccupation" :options="occupations" optionLabel="label" optionValue="value" placeholder="Vyber povolanie" class="dlg-dropdown" />
          </div>
          <button type="submit" class="steam-btn steam-btn-accent w-full mt-3" :disabled="loadingCreate">
            {{ loadingCreate ? 'Vytváram...' : 'Vytvoriť Tím' }}
          </button>
        </form>
      </div>

      <div v-else class="dlg-success">
        <h3 class="mb-2">Tím bol úspešne vytvorený!</h3>
        <p class="text-green-400 font-semibold text-center mb-6 px-4">
          Pošli kód svojím kamarátom, aby sa vedeli pripojiť do tvojho tímu
        </p>
        <div class="dlg-code-box">
          <p class="dlg-code-label">Kód pre pripojenie členov:</p>
          <span class="dlg-code">{{ team.invite_code }}</span>
        </div>
        <button class="steam-btn steam-btn-dark w-full mb-3" @click="copyTeamCode(team.invite_code)">Kopírovať Kód</button>
        <button class="steam-btn steam-btn-ghost w-full" @click="closeCreateTeamDialog">Zavrieť</button>
      </div>

      <p v-if="teamMessage && !teamCreatedSuccess" :class="teamMessage.startsWith('✅') ? 'msg-ok' : 'msg-err'" class="dlg-msg">{{ teamMessage }}</p>
    </Dialog>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- DIALOG: JOIN TEAM -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <Dialog v-model:visible="showJoinTeam" :modal="true" :closable="false" :draggable="false" :dismissableMask="true" :blockScroll="true" class="dialog-shell dialog-sm" :contentStyle="dialogContentStyle" :headerStyle="dialogHeaderStyle" :style="dialogStyle">
      <template #header>
        <div class="dlg-header">
          <span class="dlg-title">Pripojiť sa k tímu</span>
          <button class="dlg-close" @click="showJoinTeam = false">×</button>
        </div>
      </template>

      <form @submit.prevent="joinTeam" class="dlg-form">
        <div class="dlg-field">
          <label>Kód tímu</label>
          <InputText v-model="joinTeamCode" placeholder="Napr. A1B2C3" required class="dlg-input text-center font-mono tracking-widest text-lg" :class="{ 'border-red-500': joinTeamError }" />
        </div>
        <div class="dlg-field">
          <label>Povolanie</label>
          <Dropdown v-model="joinTeamOccupation" :options="occupations" optionLabel="label" optionValue="value" placeholder="Vyber povolanie" class="dlg-dropdown" />
        </div>
        <button type="submit" class="steam-btn steam-btn-accent w-full" :disabled="loadingJoin">
          {{ loadingJoin ? 'Pripájam...' : 'Pripojiť sa' }}
        </button>
      </form>
      <p v-if="joinTeamError" class="msg-err dlg-msg">{{ joinTeamError }}</p>
    </Dialog>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- DIALOG: MY TEAMS -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <Dialog v-model:visible="showTeamStatusDialog" :modal="true" :closable="false" :draggable="false" :dismissableMask="true" :blockScroll="true" class="dialog-shell dialog-md" :contentStyle="dialogContentStyle" :headerStyle="dialogHeaderStyle" :style="dialogStyle">
      <template #header>
        <div class="dlg-header">
          <span class="dlg-title">Moje Tímy</span>
          <button class="dlg-close" @click="showTeamStatusDialog = false">×</button>
        </div>
      </template>

      <div v-if="teams.length > 0" class="dlg-teams-list">
        <div v-for="t in teams" :key="t.id" :class="['dlg-team-card', t.status === 'pending' ? 'dlg-team-pending' : t.status === 'suspended' ? 'dlg-team-suspended' : '']">
          <div class="dlg-team-head">
            <div>
              <h3 class="dlg-team-name">{{ t.name }}</h3>
              <div class="flex flex-wrap items-center gap-2 mt-1">
                <span v-if="t.academic_year" class="text-xs text-slate-500">{{ t.academic_year.name }}</span>
                <span v-if="t.status === 'pending'" class="steam-badge badge-pending">Čaká na schválenie</span>
                <span v-else-if="t.status === 'suspended'" class="steam-badge badge-suspended">Pozastavený</span>
                <span v-else-if="t.status === 'active'" class="steam-badge badge-active">Aktívny</span>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <span v-if="t.is_scrum_master" class="steam-badge badge-sm">SM</span>
              <button v-if="t.is_scrum_master" @click="openRenameTeamDialog(t)" class="rename-btn">
                <i class="pi pi-pencil"></i>
              </button>
            </div>
          </div>

          <div v-if="t.status === 'pending'" class="dlg-team-notice notice-pending">
            Tím čaká na schválenie. Kód pre pripojenie je zatiaľ neaktívny.
          </div>

          <div :class="['dlg-invite-row', t.status === 'pending' ? 'opacity-40' : '']">
            <span class="text-xs text-slate-500">Kód{{ t.status === 'pending' ? ' (neaktívny)' : '' }}:</span>
            <div class="flex items-center gap-2">
              <span :class="['dlg-invite-code', t.status === 'pending' ? 'line-through text-slate-600' : '']">{{ t.invite_code }}</span>
              <button v-if="t.status === 'active'" class="steam-btn steam-btn-ghost steam-btn-xs" @click="copyTeamCode(t.invite_code)">Kopírovať</button>
            </div>
          </div>

          <div class="dlg-members">
            <span class="text-xs text-slate-500 mb-1 block">Členovia ({{ t.members?.length || 0 }}/10):</span>
            <div v-for="member in t.members" :key="member.id" :class="['dlg-member-row', isUserDeactivated(member) ? 'dlg-member-row-deactivated' : '']">
              <div class="flex flex-col truncate flex-1">
                <div class="flex items-center gap-2">
                  <span :class="member.is_absolvent ? 'text-slate-600' : 'text-slate-200'" class="truncate text-sm">{{ member.name }}</span>
                  <span v-if="member.is_absolvent" class="steam-badge badge-muted">Absolvent</span>
                  <span v-if="getRoleLabel(t, member) === 'SM'" class="steam-badge badge-sm">SM</span>
                </div>
                <span class="text-xs text-slate-600 truncate">{{ member.pivot?.occupation ? formatOccupation(member.pivot.occupation) : 'Neurčené' }}</span>
              </div>
              <button v-if="t.is_scrum_master && member.id !== currentUserId && t.status === 'active'" class="steam-btn steam-btn-danger steam-btn-xs" @click="confirmRemoveMember(t, member)">Odstrániť</button>
              <button v-if="!t.is_scrum_master && member.id === currentUserId && t.status === 'active'" class="steam-btn steam-btn-warn steam-btn-xs" @click="confirmLeaveTeam(t)">Opustiť</button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="dlg-empty">
        <p>Nie ste členom žiadneho tímu</p>
      </div>
    </Dialog>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- DIALOG: RENAME TEAM -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <Dialog v-model:visible="showRenameTeamDialog" :modal="true" :closable="false" :draggable="false" :dismissableMask="true" :blockScroll="true" class="dialog-shell" :contentStyle="dialogContentStyle" :headerStyle="dialogHeaderStyle" :style="dialogStyle">
      <template #header>
        <div class="dlg-header">
          <span class="dlg-title">Zmeniť názov tímu</span>
          <button class="dlg-close" @click="showRenameTeamDialog = false">×</button>
        </div>
      </template>

      <form @submit.prevent="renameTeam" class="dlg-form">
        <div class="dlg-field">
          <label for="renameTeamInput">Nový názov tímu</label>
          <InputText id="renameTeamInput" v-model="newTeamName" placeholder="Zadajte názov tímu" required class="dlg-input" />
        </div>
        <button type="submit" class="steam-btn steam-btn-accent w-full mt-3" :disabled="loadingRename">
          {{ loadingRename ? 'Vytváram...' : 'Zmeniť názov' }}
        </button>
      </form>
    </Dialog>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- CUSTOM CONFIRM DIALOG                                  -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <Dialog v-model:visible="confirmDialog.visible" :modal="true" :closable="false" :draggable="false" :showHeader="false" :blockScroll="true" :style="{ borderRadius: '12px', overflow: 'hidden', width: '340px' }" :contentStyle="{ padding: '0', background: 'var(--color-surface)', border: '1px solid var(--color-border)', borderRadius: '12px' }">
      <div class="conf-dialog">
        <h3 class="conf-title">{{ confirmDialog.title }}</h3>
        <p class="conf-message">{{ confirmDialog.message }}</p>
        <div class="conf-actions">
          <button class="conf-btn conf-btn-ghost" @click="confirmDialog.visible = false">Zrušiť</button>
          <button class="conf-btn" :class="confirmDialog.danger ? 'conf-btn-danger' : 'conf-btn-warn'" @click="confirmDialog.onConfirm(); confirmDialog.visible = false">{{ confirmDialog.confirmLabel }}</button>
        </div>
      </div>
    </Dialog>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import { apiFetch } from '@/utils/apiFetch'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'
import TopRatedCarousel from '@/components/TopRatedCarousel.vue'
import { isUserDeactivated } from '../utils/userStatus.js'
import logoDark from '@/assets/logo/ucm_logoNOBG.png'
import logoLight from '@/assets/logo/ucm_logo_black.png'

const vTooltip = Tooltip

const API_URL = import.meta.env.VITE_API_URL
const { t } = useI18n()
const toast = useToast()
const router = useRouter()

const isLightTheme = ref(false)
const landingLogoSrc = computed(() => (isLightTheme.value ? logoLight : logoDark))
let themeObserver = null

function syncThemeFromDom() {
  const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark'
  isLightTheme.value = currentTheme === 'light'
}

// ── shared dialog styles (passed as props) ──
const dialogContentStyle = { backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem', border: 'none' }
const dialogHeaderStyle  = { backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '2px solid var(--color-accent)', padding: '1rem 1.5rem', position: 'relative' }
const dialogStyle         = { borderRadius: '4px', overflow: 'hidden' }

// -------------------------
// Global / User Status
// -------------------------
const token = ref(localStorage.getItem('access_token') || '')
const hasTeam = ref(false)
const teams = ref([])
const selectedTeam = ref(null)
const isAdmin = computed(() => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  return user.role === 'admin'
})

function setActiveTeam(team) {
  if (!team) {
    localStorage.removeItem('active_team_id')
    localStorage.removeItem('active_team_is_scrum_master')
    localStorage.removeItem('active_team_status')
  } else {
    localStorage.setItem('active_team_id', String(team.id))
    localStorage.setItem('active_team_is_scrum_master', team.is_scrum_master ? '1' : '0')
    localStorage.setItem('active_team_status', team.status || 'active')
    window.dispatchEvent(new CustomEvent('team-changed', { detail: { id: team.id, isScrumMaster: team.is_scrum_master, status: team.status || 'active' } }))
  }
}

const showTeamStatusDialog = ref(false)
const currentUserId = ref(null)
const removingMember = ref(false)

function getRoleLabel(team, member) {
  const pivotRole = member.pivot?.role_in_team
  if (pivotRole === 'scrum_master' || team.scrum_master_id === member.id) return 'SM'
  return ''
}

function getCurrentUserOccupation(team) {
  if (!team || !team.members || !currentUserId.value) return null
  const currentUser = team.members.find(m => m.id === currentUserId.value)
  const occupation = currentUser?.pivot?.occupation || null
  return occupation ? formatOccupation(occupation) : null
}

function formatOccupation(occupation) {
  if (!occupation) return null
  const normalized = occupation.toLowerCase().trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
  const occupationMap = {
    'programator': 'Programátor',
    'grafik 2d': 'Grafik 2D',
    'grafik 3d': 'Grafik 3D',
    'tester': 'Tester',
    'animator': 'Animátor'
  }
  return occupationMap[normalized] || null
}

// -------------------------
// Join Team
// -------------------------
const showJoinTeam = ref(false)
const joinTeamCode = ref('')
const joinTeamOccupation = ref(null)
const joinTeamError = ref('')
const loadingJoin = ref(false)

// -------------------------
// Rename Team
// -------------------------
const showRenameTeamDialog = ref(false)
const renameTeamObj = ref(null)
const newTeamName = ref('')
const loadingRename = ref(false)

function openRenameTeamDialog(team) {
  renameTeamObj.value = team
  newTeamName.value = team.name
  showRenameTeamDialog.value = true
}

async function renameTeam() {
  if (!newTeamName.value.trim() || !renameTeamObj.value) return
  loadingRename.value = true
  const tk = localStorage.getItem('access_token')

  try {
    const response = await apiFetch(`${API_URL}/api/teams/${renameTeamObj.value.id}/rename`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${tk}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ name: newTeamName.value.trim() })
    })
    
    if (response.ok) {
      toast.add({ severity: 'success', summary: 'Úspech', detail: 'Názov tímu bol zmenený.', life: 4000 })
      renameTeamObj.value.name = newTeamName.value.trim()
      showRenameTeamDialog.value = false
    } else {
      const data = await response.json()
      let msg = data.message || 'Nepodarilo sa zmeniť názov tímu.'
      if (data.errors && data.errors.name && data.errors.name[0].includes('taken')) {
        msg = 'Tento názov tímu je už obsadený.'
      }
      toast.add({ severity: 'warn', summary: 'Chyba', detail: msg, life: 4000 })
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Chyba', detail: 'Problém pri spojení so serverom.', life: 4000 })
  } finally {
    loadingRename.value = false
  }
}

async function joinTeam() {
  joinTeamError.value = ''
  if (!joinTeamCode.value) { joinTeamError.value = 'Kód tímu nemôže byť prázdny.'; return }
  if (!joinTeamOccupation.value) { joinTeamError.value = 'Povolanie je povinné.'; return }
  loadingJoin.value = true
  const cleanCode = joinTeamCode.value.trim()
  try {
    const res = await apiFetch(`${API_URL}/api/teams/join`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' },
      body: JSON.stringify({ invite_code: cleanCode, occupation: joinTeamOccupation.value })
    })
    const data = await res.json()
    if (res.ok && data.team) {
      toast.add({ severity: 'success', summary: 'Úspech', detail: `Pripojili ste sa k tímu "${data.team.name}".`, life: 5000 })
      hasTeam.value = true
      showJoinTeam.value = false
      joinTeamCode.value = ''
      joinTeamOccupation.value = null
      await loadTeamStatus()
      loadAllGames()
    } else {
      let errorMessage = data.message || 'Chyba pri pripájaní.'
      if (data.errors?.invite_code) joinTeamError.value = data.errors.invite_code.join(' ')
      else joinTeamError.value = errorMessage
      toast.add({ severity: 'error', summary: 'Chyba', detail: errorMessage, life: 6000 })
    }
  } catch (err) {
    joinTeamError.value = 'Server nedostupný.'
    toast.add({ severity: 'error', summary: 'Chyba Siete', detail: 'Server je nedostupný.', life: 10000 })
  } finally { loadingJoin.value = false }
}

// -------------------------
// Create Team
// -------------------------
const showCreateTeam = ref(false)

const teamName = ref('')
const academicYear = ref(null)
const createTeamOccupation = ref(null)
const academicYears = ref([])
const teamMessage = ref('')
const team = ref(null)
const teamCreatedSuccess = ref(false)
const loadingCreate = ref(false)

const occupations = ref([
  { label: 'Programátor', value: 'Programátor' },
  { label: 'Grafik 2D', value: 'Grafik 2D' },
  { label: 'Grafik 3D', value: 'Grafik 3D' },
  { label: 'Tester', value: 'Tester' },
  { label: 'Animátor', value: 'Animátor' }
])

async function createTeam() {
  teamMessage.value = ''
  if (!teamName.value && !academicYear.value) { teamMessage.value = '❌ Vyplňte názov tímu a vyberte akademický rok.'; return }
  if (!teamName.value) { teamMessage.value = '❌ Názov tímu je povinný.'; return }
  if (!academicYear.value) { teamMessage.value = '❌ Akademický rok je povinný.'; return }
  if (!createTeamOccupation.value) { teamMessage.value = '❌ Povolanie je povinné.'; return }
  loadingCreate.value = true
  try {
    const formData = new FormData()
    formData.append('name', teamName.value)
    formData.append('academic_year_id', academicYear.value)
    formData.append('occupation', createTeamOccupation.value)
    const res = await apiFetch(`${API_URL}/api/teams`, {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' },
      body: formData
    })
    const data = await res.json()
    if (res.ok && data.team) {
      team.value = data.team
      teamCreatedSuccess.value = true
      hasTeam.value = true
      toast.add({ severity: data.requires_approval ? 'info' : 'success', summary: 'Tím Vytvorený', detail: data.message || `Tím "${data.team.name}" bol vytvorený.`, life: 6000 })
      await loadTeamStatus()
      loadAllGames()
    } else {
      let isNameTaken = false;
      if (data.errors?.name?.[0]?.includes('taken')) {
        isNameTaken = true;
      }
      let msg = data.message || 'Chyba pri vytváraní tímu.';
      if (msg.includes('taken') || isNameTaken) {
        msg = 'Tento názov tímu je už obsadený.';
      } else if (data.errors) {
        msg += ' ' + Object.values(data.errors).map(e => e.join(', ')).join('. ');
      }
      
      teamMessage.value = '❌ ' + msg;
      toast.add({ severity: 'error', summary: 'Chyba', detail: msg, life: 8000 });
    }
  } catch (err) {
    teamMessage.value = 'Server nedostupný.'
    toast.add({ severity: 'error', summary: 'Chyba', detail: 'Server je nedostupný.', life: 10000 })
  } finally { loadingCreate.value = false }
}

const copyTeamCode = async (code) => {
  try { await navigator.clipboard.writeText(code); toast.add({ severity: 'info', summary: 'Skopírované', detail: 'Kód skopírovaný.', life: 3000 }) }
  catch { toast.add({ severity: 'warn', summary: 'Chyba', detail: 'Skopírujte kód ručne.', life: 3000 }) }
}

const closeCreateTeamDialog = () => {
  showCreateTeam.value = false
  teamCreatedSuccess.value = false
  team.value = null
  teamName.value = ''
  academicYear.value = null
  createTeamOccupation.value = null
}

// -------------------------
// Filters
// -------------------------
const search = ref('')
const types = ref([
  { label: 'Všetky', value: 'all' },
  { label: 'Hra', value: 'game' },
  { label: 'Web App', value: 'web_app' },
  { label: 'Mobile App', value: 'mobile_app' },
  { label: 'Knižnica', value: 'library' },
  { label: 'Iné', value: 'other' }
])
const selectedType = ref('all')
const games = ref([])
const topRatedProjects = ref([])
const loadingGames = ref(true)
const loadingTopRated = ref(false)
const showingMyProjects = ref(false)
const currentPage = ref(1)
const pageJumpInput = ref('')
const lastPage = ref(1)
const totalProjects = ref(0)
const searchDebounce = ref(null)

const filterSchoolType = ref(null)
const filterYearOfStudy = ref(null)
const filterSubject = ref(null)
const filterAcademicYear = ref(null)

const filterSchoolTypes = ref([
  { label: 'Všetky typy', value: null },
  { label: 'Základná Škola (ZŠ)', value: 'zs' },
  { label: 'Stredná škola (SŠ)', value: 'ss' },
  { label: 'Vysoká Škola (VŠ)', value: 'vs' }
])
const filterSubjects = ref([
  { label: 'Všetky predmety', value: null },
  { label: 'Slovenský jazyk', value: 'Slovenský jazyk' },
  { label: 'Matematika', value: 'Matematika' },
  { label: 'Dejepis', value: 'Dejepis' },
  { label: 'Geografia', value: 'Geografia' },
  { label: 'Informatika', value: 'Informatika' },
  { label: 'Grafika', value: 'Grafika' },
  { label: 'Chémia', value: 'Chémia' },
  { label: 'Fyzika', value: 'Fyzika' }
])
const availableFilterYears = computed(() => {
  if (!filterSchoolType.value) return [{ label: 'Všetky ročníky', value: null }]
  const maxYear = filterSchoolType.value === 'zs' ? 9 : 5
  return [
    { label: 'Všetky ročníky', value: null },
    ...Array.from({ length: maxYear }, (_, i) => ({ label: `${i + 1}. ročník`, value: i + 1 }))
  ]
})

const hasActiveFilters = computed(() =>
  filterSchoolType.value !== null || filterYearOfStudy.value !== null || filterSubject.value !== null || filterAcademicYear.value !== null || selectedType.value !== 'all' || search.value !== '' || showingMyProjects.value
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

function jumpToPage() {
  const p = parseInt(pageJumpInput.value)
  if (!isNaN(p)) goToPage(Math.max(1, Math.min(p, lastPage.value)))
  pageJumpInput.value = ''
}

function resetFilters() { filterSchoolType.value = null; filterYearOfStudy.value = null; filterSubject.value = null; filterAcademicYear.value = null; selectedType.value = 'all'; search.value = ''; showingMyProjects.value = false; currentPage.value = 1; loadAllGames() }
function filterByType(type) { selectedType.value = type; currentPage.value = 1; loadAllGames() }
function filterBySchoolType(st) { filterSchoolType.value = st; currentPage.value = 1; loadAllGames() }
function filterBySubject(sub) { filterSubject.value = sub; currentPage.value = 1; loadAllGames() }
function filterByAcademicYear(id) { filterAcademicYear.value = id; currentPage.value = 1; loadAllGames() }
const viewProjectDetail = (project) => { router.push({ name: 'ProjectDetail', params: { id: project.id } }) }

// -------------------------
// Data loading
// -------------------------
async function loadAcademicYears() {
  if (!token.value) return
  try { const res = await apiFetch(`${API_URL}/api/academic-years`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } }); if (res.ok) academicYears.value = await res.json() }
  catch {}
}

async function loadCurrentUser() {
  if (!token.value) return
  try { const res = await apiFetch(`${API_URL}/api/user`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } }); if (res.ok) { const data = await res.json(); currentUserId.value = data.id } }
  catch {}
}

async function loadTeamStatus() {
  if (!token.value) return
  try {
    const res = await apiFetch(`${API_URL}/api/user/team`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    let data = {}
    if (res.headers.get('content-type')?.includes('application/json')) data = await res.json()
    if (res.ok && data.teams?.length > 0) {
      hasTeam.value = true; teams.value = data.teams
      const storedId = localStorage.getItem('active_team_id')
      const found = storedId ? teams.value.find(t => String(t.id) === storedId) : null
      selectedTeam.value = found || teams.value[0]
      setActiveTeam(selectedTeam.value)
    } else { hasTeam.value = false; teams.value = []; selectedTeam.value = null; setActiveTeam(null) }
  } catch (err) {
    console.error('Team status load error', err)
    toast.add({ severity: 'error', summary: 'Chyba Siete', detail: 'Server je nedostupný.', life: 10000 })
  }
}

async function loadTopRatedProjects() {
  if (!token.value) return
  loadingTopRated.value = true
  try {
    const res = await apiFetch(`${API_URL}/api/projects/top-rated`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    if (res.ok) topRatedProjects.value = await res.json()
    else toast.add({ severity: 'warn', summary: 'Chyba', detail: `Nepodarilo sa načítať top projekty (${res.status}).`, life: 5000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Chyba Siete', detail: 'Server je nedostupný.', life: 10000 })
  } finally { loadingTopRated.value = false }
}

async function loadAllGames() {
  if (!token.value) { loadingGames.value = false; return }
  loadingGames.value = true
  try {
    const params = new URLSearchParams()
    if (selectedType.value && selectedType.value !== 'all') params.append('type', selectedType.value)
    if (search.value) params.append('search', search.value)
    if (filterSchoolType.value) params.append('school_type', filterSchoolType.value)
    if (filterYearOfStudy.value) params.append('year_of_study', filterYearOfStudy.value)
    if (filterSubject.value) params.append('subject', filterSubject.value)
    if (filterAcademicYear.value) params.append('academic_year_id', filterAcademicYear.value)
    if (showingMyProjects.value) {
      params.append('my_projects', '1')
      if (selectedTeam.value?.id) params.append('team_id', selectedTeam.value.id)
    }
    params.append('per_page', '21')
    params.append('page', currentPage.value)
    const query = params.toString() ? `?${params.toString()}` : ''
    const res = await apiFetch(`${API_URL}/api/projects${query}`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    if (res.ok) {
      const data = await res.json()
      games.value = data.data ?? data
      lastPage.value = data.last_page ?? 1
      totalProjects.value = data.total ?? games.value.length
    } else toast.add({ severity: 'error', summary: 'Chyba', detail: `Nepodarilo sa načítať projekty (${res.status}).`, life: 5000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Chyba Siete', detail: 'Server je nedostupný.', life: 10000 })
  } finally { loadingGames.value = false }
}

async function loadMyProjects() {
  if (!token.value || !selectedTeam.value) return
  showingMyProjects.value = true
  currentPage.value = 1
  await loadAllGames()
}

async function showAllProjects() {
  showingMyProjects.value = false
  currentPage.value = 1
  await loadAllGames()
}

const confirmDialog = ref({ visible: false, title: '', message: '', confirmLabel: '', danger: false, onConfirm: () => {} })

// Lock body scroll whenever any dialog is open
watch([showTeamStatusDialog, showJoinTeam, showCreateTeam, () => confirmDialog.value.visible], (vals) => {
  const locked = vals.some(Boolean)
  document.documentElement.classList.toggle('scroll-locked', locked)
  document.body.classList.toggle('scroll-locked', locked)
})

function confirmRemoveMember(team, member) {
  if (removingMember.value) return
  confirmDialog.value = {
    visible: true,
    title: 'Odstrániť člena',
    message: `Naozaj chcete odstrániť "${member.name}" z tímu "${team.name}"?`,
    confirmLabel: 'Odstrániť',
    danger: true,
    onConfirm: () => removeMember(team, member)
  }
}

function confirmLeaveTeam(team) {
  if (removingMember.value) return
  confirmDialog.value = {
    visible: true,
    title: 'Opustiť tím',
    message: `Naozaj chcete opustiť tím "${team.name}"?`,
    confirmLabel: 'Opustiť',
    danger: false,
    onConfirm: () => leaveTeam(team)
  }
}

async function removeMember(team, member) {
  removingMember.value = true
  try {
    const res = await apiFetch(`${API_URL}/api/teams/${team.id}/members/${member.id}`, { method: 'DELETE', headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    try { const data = await res.clone().json(); if (data?.team?.members) team.members = data.team.members } catch {}
    if (res.ok) toast.add({ severity: 'success', summary: 'Člen odstránený', detail: `${member.name} bol odstránený.`, life: 4000 })
    else toast.add({ severity: 'warn', summary: 'Zlyhalo', detail: 'Nepodarilo sa odstrániť člena.', life: 6000 })
  } catch { toast.add({ severity: 'error', summary: 'Chyba siete', life: 6000 }) }
  finally { removingMember.value = false }
}

async function leaveTeam(team) {
  removingMember.value = true
  try {
    const res = await apiFetch(`${API_URL}/api/teams/${team.id}/leave`, { method: 'POST', headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    if (res.ok) { toast.add({ severity: 'success', summary: 'Opustili ste tím', life: 4000 }); await loadTeamStatus(); setActiveTeam(teams.value[0] || null); showTeamStatusDialog.value = false }
    else toast.add({ severity: 'warn', summary: 'Zlyhalo', life: 6000 })
  } catch { toast.add({ severity: 'error', summary: 'Chyba siete', life: 6000 }) }
  finally { removingMember.value = false }
}

onMounted(() => {
  syncThemeFromDom()
  themeObserver = new MutationObserver(syncThemeFromDom)
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] })

  loadAcademicYears(); loadTeamStatus(); loadAllGames(); loadTopRatedProjects(); loadCurrentUser()
})
onBeforeUnmount(() => {
  if (themeObserver) {
    themeObserver.disconnect()
    themeObserver = null
  }
})
watch(selectedTeam, (val) => { setActiveTeam(val) })
watch(selectedTeam, () => {
  if (showingMyProjects.value) {
    currentPage.value = 1
    loadAllGames()
  }
})
watch(selectedType, () => { currentPage.value = 1; loadAllGames() })
watch(filterSchoolType, () => { filterYearOfStudy.value = null })
watch([filterSchoolType, filterYearOfStudy, filterSubject, filterAcademicYear], () => { currentPage.value = 1; loadAllGames() })
watch(search, () => { clearTimeout(searchDebounce.value); searchDebounce.value = setTimeout(() => { currentPage.value = 1; loadAllGames() }, 400) })

// ── Helpers ──
function getSchoolTypeLabel(type) { return { 'zs': 'ZŠ', 'ss': 'SŠ', 'vs': 'VŠ' }[type] || type }
function getSplashUrl(path) { if (!path) return ''; if (path.startsWith('http')) return path; return `${API_URL}/storage/${path}` }
function formatProjectType(type) {
  const normalizedType = String(type || '').trim().toLowerCase()
  const valid = ['game', 'web_app', 'mobile_app', 'library', 'webgl', 'other']
  return valid.includes(normalizedType) ? t(`project_types.${normalizedType}`) : t('project_types.other')
}
function goToTeam(teamId) { if (teamId) router.push(`/team/${teamId}`) }
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════ */
/* DESIGN TOKENS                                              */
/* ═══════════════════════════════════════════════════════════ */
/* Theme colors are driven by CSS variables in main.css. */

.home-root {
  min-height: 100%;
  position: relative;
  overflow-x: hidden;
}

/* ═══════════════════════════════════════════════════════════ */
/* ANIMATED BACKGROUND                                        */
/* ═══════════════════════════════════════════════════════════ */
.bg-canvas {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

/* Subtle dot grid */
.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(circle, rgba(var(--color-accent-rgb), 0.06) 1px, transparent 1px);
  background-size: 40px 40px;
  mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, black 30%, transparent 70%);
  -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, black 30%, transparent 70%);
}

/* Floating gradient orbs */
.bg-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.12;
  will-change: transform;
}
.bg-orb-1 {
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(var(--color-accent-rgb), 0.5), transparent 70%);
  top: -10%;
  left: -5%;
  animation: orb-drift-1 25s ease-in-out infinite;
}
.bg-orb-2 {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(91, 139, 230, 0.4), transparent 70%);
  top: 40%;
  right: -8%;
  animation: orb-drift-2 30s ease-in-out infinite;
}
.bg-orb-3 {
  width: 350px;
  height: 350px;
  background: radial-gradient(circle, rgba(var(--color-accent-rgb), 0.35), transparent 70%);
  bottom: -5%;
  left: 30%;
  animation: orb-drift-3 22s ease-in-out infinite;
}

@keyframes orb-drift-1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%      { transform: translate(60px, 40px) scale(1.05); }
  66%      { transform: translate(-30px, 70px) scale(0.95); }
}
@keyframes orb-drift-2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  40%      { transform: translate(-50px, -60px) scale(1.1); }
  70%      { transform: translate(30px, 30px) scale(0.9); }
}
@keyframes orb-drift-3 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50%      { transform: translate(70px, -50px) scale(1.08); }
}

/* Small floating particles */
.bg-particle {
  position: absolute;
  border-radius: 50%;
  background: rgba(var(--color-accent-rgb), 0.25);
  will-change: transform, opacity;
}
.bg-particle-1 {
  width: 4px; height: 4px;
  top: 15%; left: 20%;
  animation: particle-float 18s ease-in-out infinite;
}
.bg-particle-2 {
  width: 6px; height: 6px;
  top: 35%; left: 75%;
  animation: particle-float 22s ease-in-out infinite 3s;
}
.bg-particle-3 {
  width: 3px; height: 3px;
  top: 60%; left: 40%;
  animation: particle-float 15s ease-in-out infinite 6s;
}
.bg-particle-4 {
  width: 5px; height: 5px;
  top: 80%; left: 65%;
  animation: particle-float 20s ease-in-out infinite 2s;
}
.bg-particle-5 {
  width: 3px; height: 3px;
  top: 25%; left: 55%;
  animation: particle-float 17s ease-in-out infinite 8s;
}
.bg-particle-6 {
  width: 4px; height: 4px;
  top: 70%; left: 15%;
  animation: particle-float 24s ease-in-out infinite 5s;
}

@keyframes particle-float {
  0%, 100% { transform: translate(0, 0); opacity: 0.2; }
  25%      { transform: translate(30px, -40px); opacity: 0.6; }
  50%      { transform: translate(-20px, -80px); opacity: 0.3; }
  75%      { transform: translate(40px, -30px); opacity: 0.5; }
}

/* Make all real content sit above the background */
.home-root > :not(.bg-canvas) {
  position: relative;
  z-index: 1;
}

/* ═══════════════════════════════════════════════════════════ */
/* TEAM BAR                                                   */
/* ═══════════════════════════════════════════════════════════ */
.team-bar-section {
  max-width: 1280px;
  margin: 0 auto;
  padding: 16px 32px 0;
}
.team-bar {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.team-bar-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.team-bar-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}
.team-bar-note {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  margin-right: 8px;
}
.team-bar-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.team-bar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-left: auto;
}

/* Meta chips */
.meta-chip {
  font-size: 0.75rem;
  padding: 3px 10px;
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 3px;
  color: var(--color-text-muted);
  white-space: nowrap;
}
.meta-chip-accent {
  background: rgba(var(--color-accent-rgb), 0.1);
  border-color: rgba(var(--color-accent-rgb), 0.25);
  color: var(--color-accent);
}

/* Team warnings */
.team-warning {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 4px;
  margin-top: 12px;
  font-size: 0.85rem;
}
.team-warning strong { display: block; margin-bottom: 2px; }
.team-warning p { margin: 0; font-size: 0.8rem; opacity: 0.8; }
.team-warning-pending { background: rgba(var(--color-warning-rgb), 0.08); border: 1px solid rgba(var(--color-warning-rgb), 0.25); color: var(--color-warning); }
.team-warning-pending i { margin-top: 2px; }
.team-warning-suspended { background: rgba(var(--color-danger-rgb), 0.08); border: 1px solid rgba(var(--color-danger-rgb), 0.25); color: var(--color-danger); }
.team-warning-suspended i { margin-top: 2px; }

/* ═══════════════════════════════════════════════════════════ */
/* CONTENT WRAPPER                                            */
/* ═══════════════════════════════════════════════════════════ */
.content-wrap {
  max-width: 1280px;
  margin: 0 auto;
  padding: 24px 32px 48px;
}

/* ═══════════════════════════════════════════════════════════ */
/* FILTER TOOLBAR                                             */
/* ═══════════════════════════════════════════════════════════ */
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

/* Search */
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

/* Filter grid */
.filter-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
@media (max-width: 900px) {
  .filter-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 540px) {
  .filter-grid { grid-template-columns: 1fr; }
}
.filter-item { display: flex; flex-direction: column; gap: 4px; }
.filter-label { font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
.filter-help-warning {
  color: var(--color-warning);
  font-size: 0.72rem;
  margin-top: 2px;
}

/* ═══════════════════════════════════════════════════════════ */
/* TOP RATED CAROUSEL                                         */
/* ═══════════════════════════════════════════════════════════ */
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

/* ═══════════════════════════════════════════════════════════ */
/* LANDING (not logged in)                                    */
/* ═══════════════════════════════════════════════════════════ */
.landing-section {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 100px 32px;
}
.landing-card {
  text-align: center;
  width: 100%;
  max-width: 760px;
}
.landing-logo {
  height: 100px;
  width: auto;
  margin: 0 auto 32px;
  filter: drop-shadow(0 4px 20px rgba(var(--color-accent-rgb), 0.2));
}
.landing-heading {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--color-text);
  margin-bottom: 14px;
  letter-spacing: -0.02em;
}
.landing-heading-ucm {
  color: var(--color-text-strong);
  font-weight: 900;
}
.landing-heading-accent {
  color: var(--color-accent);
  font-weight: 900;
}
.landing-text {
  color: var(--color-text-muted);
  font-size: 0.95rem;
  margin-bottom: 32px;
  line-height: 1.6;
}
.landing-actions {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}
.landing-actions .steam-btn {
  width: 100%;
  border: 1px solid rgba(var(--color-accent-rgb), 0.7);
  box-shadow: 0 6px 20px rgba(var(--color-accent-rgb), 0.28);
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.12s, color 0.12s, opacity 0.12s;
}
.landing-actions .steam-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 10px 28px rgba(var(--color-accent-rgb), 0.35);
}
.landing-actions .steam-btn:active:not(:disabled) {
  transform: translateY(0);
}

/* ═══════════════════════════════════════════════════════════ */
/* STATE MESSAGES (loading / empty)                           */
/* ═══════════════════════════════════════════════════════════ */
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

/* ═══════════════════════════════════════════════════════════ */
/* PROJECT GRID  (Steam-style)                                */
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
.page-btn-nav {
  min-width: 36px;
  width: 36px;
  padding: 0;
  font-size: 0.8rem;
}
.page-ellipsis {
  color: var(--color-text-muted);
  padding: 0 4px;
  user-select: none;
}
.page-info {
  margin-left: 4px;
  font-size: 0.8rem;
  color: var(--color-text-muted);
}
.page-jump-input {
  width: 52px;
  height: 36px;
  padding: 0 6px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  color: var(--color-text);
  font-size: 0.875rem;
  text-align: center;
  outline: none;
  transition: border-color 0.15s;
  -moz-appearance: textfield;
}
.page-jump-input::-webkit-outer-spin-button,
.page-jump-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.page-jump-input:focus { border-color: var(--color-accent); }
.page-jump-input::placeholder { color: var(--color-text-muted); }

/* ── Card ── */
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

/* Thumbnail */
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
.project-card:hover .card-thumb-img {
  transform: scale(1.03);
}

/* Body */
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
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.project-card:hover .card-title { color: var(--color-text-strong); }

/* Tags */
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
.card-tag.card-tag-static {
  cursor: default;
}
.card-tag.card-tag-static:hover {
  background: var(--color-elevated);
  color: var(--color-text-muted);
}
.card-tag-accent {
  background: rgba(var(--color-accent-rgb), 0.12);
  border-color: rgba(var(--color-accent-rgb), 0.3);
  color: var(--color-accent);
}
.card-tag-accent:hover {
  background: rgba(var(--color-accent-rgb), 0.22);
}

.card-desc {
  font-size: 0.82rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 12px;
  flex: 1;
}

/* Card footer */
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
.card-team-link {
  font-size: 0.75rem;
  color: var(--color-accent);
  cursor: pointer;
  transition: color 0.12s;
}
.card-team-link:hover { color: var(--color-accent-hover); text-decoration: underline; }
.card-year {
  font-size: 0.7rem;
  color: var(--color-text-subtle);
  cursor: pointer;
}
.card-year:hover { color: var(--color-text-muted); }

/* ═══════════════════════════════════════════════════════════ */
/* BUTTONS  (Steam-flat style)                                */
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

/* Accent (green CTA) */
.steam-btn-accent {
  background: var(--color-accent);
  color: var(--color-accent-contrast);
}
.steam-btn-accent:hover:not(:disabled) { background: var(--color-accent-hover); }
.steam-btn-accent:active:not(:disabled) { background: var(--color-accent-strong); }

/* Dark */
.steam-btn-dark {
  background: var(--color-border);
  color: var(--color-text);
}
.steam-btn-dark:hover:not(:disabled) { background: var(--color-border-strong); color: var(--color-text-strong); }
.steam-btn-dark:active:not(:disabled) { background: var(--color-border-strong); }

/* Ghost */
.steam-btn-ghost {
  background: transparent;
  color: var(--color-text-muted);
}
.steam-btn-ghost:hover:not(:disabled) { color: var(--color-text); background: var(--color-hover-bg-soft); }

/* Danger */
.steam-btn-danger {
  background: rgba(var(--color-danger-rgb), 0.15);
  color: var(--color-danger);
}
.steam-btn-danger:hover:not(:disabled) { background: rgba(var(--color-danger-rgb), 0.25); }

/* Warning */
.steam-btn-warn {
  background: rgba(var(--color-warning-rgb), 0.15);
  color: var(--color-warning);
}
.steam-btn-warn:hover:not(:disabled) { background: rgba(var(--color-warning-rgb), 0.25); }

/* Sizes */
.steam-btn-sm { padding: 6px 12px; font-size: 0.8rem; }
.steam-btn-xs { padding: 4px 10px; font-size: 0.72rem; }
.steam-btn-lg { padding: 10px 22px; font-size: 0.95rem; }

/* ═══════════════════════════════════════════════════════════ */
/* BADGES                                                     */
/* ═══════════════════════════════════════════════════════════ */
.steam-badge {
  display: inline-block;
  padding: 2px 7px;
  font-size: 0.65rem;
  font-weight: 600;
  border-radius: 2px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.badge-pending  { background: rgba(var(--color-warning-rgb), 0.15); color: var(--color-warning); }
.badge-suspended{ background: rgba(var(--color-danger-rgb), 0.15); color: var(--color-danger); }
.badge-active   { background: rgba(var(--color-accent-rgb), 0.15); color: var(--color-accent); }
.badge-sm       { background: rgba(var(--color-accent-rgb), 0.12); color: var(--color-accent); }
.badge-muted    { background: rgba(var(--color-text-muted-rgb), 0.12); color: var(--color-text-muted); }

/* ═══════════════════════════════════════════════════════════ */
/* DIALOGS                                                    */
/* ═══════════════════════════════════════════════════════════ */
.dialog-shell { width: 92%; max-width: 460px; }
.dialog-sm    { max-width: 400px; }
.dialog-md    { max-width: 520px; }

.dlg-header {
  display: flex; align-items: center; justify-content: center; width: 100%; position: relative;
}
.dlg-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-accent);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.dlg-close {
  position: absolute; right: -8px; top: 50%; transform: translateY(-50%);
  width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
  color: var(--color-text-muted); background: transparent; border: none; border-radius: 3px; cursor: pointer; transition: all 0.15s;
}
.dlg-close:hover { color: var(--color-text); background: var(--color-hover-bg); }
.dlg-close i { font-size: 0.8rem; }

.dlg-form { display: flex; flex-direction: column; gap: 16px; }
.dlg-field { display: flex; flex-direction: column; gap: 6px; }
.dlg-field label { font-size: 0.8rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.04em; }

.dlg-msg { margin-top: 14px; text-align: center; font-size: 0.85rem; }
.msg-ok { color: var(--color-accent); }
.msg-err { color: var(--color-danger); }

/* Success state */
.dlg-success { display: flex; flex-direction: column; align-items: center; gap: 16px; text-align: center; }
.dlg-success h3 { font-size: 1.1rem; color: var(--color-text); }
.dlg-code-box {
  width: 100%; padding: 16px; border-radius: 4px;
  background: var(--color-surface-deep); border: 1px dashed var(--color-border);
  text-align: center;
}
.dlg-code-label { font-size: 0.8rem; color: var(--color-text-muted); margin-bottom: 6px; }
.dlg-code { font-size: 1.6rem; font-weight: 700; letter-spacing: 0.15em; color: var(--color-accent); font-family: monospace; }

/* My Teams dialog */
.dlg-teams-list { display: flex; flex-direction: column; gap: 14px; max-height: 70vh; overflow-y: auto; }
.dlg-team-card { border-radius: 4px; padding: 14px; border: 1px solid var(--color-border); background: var(--color-elevated); }
.dlg-team-pending { border-color: rgba(var(--color-warning-rgb), 0.3); background: rgba(var(--color-warning-rgb), 0.04); }
.dlg-team-suspended { border-color: rgba(var(--color-danger-rgb), 0.3); background: rgba(var(--color-danger-rgb), 0.04); }
.dlg-team-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid var(--color-border); }
.rename-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 4px;
  border: 1px solid rgba(var(--color-accent-rgb), 0.25);
  background: rgba(var(--color-accent-rgb), 0.07);
  color: var(--color-accent);
  font-size: 0.65rem;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
  flex-shrink: 0;
}
.rename-btn:hover { background: rgba(var(--color-accent-rgb), 0.18); border-color: rgba(var(--color-accent-rgb), 0.5); }
.dlg-team-name { font-size: 1rem; font-weight: 600; color: var(--color-text); }
.dlg-team-notice { font-size: 0.78rem; padding: 8px 12px; border-radius: 3px; margin-bottom: 10px; }
.notice-pending { background: rgba(var(--color-warning-rgb), 0.06); border: 1px solid rgba(var(--color-warning-rgb), 0.2); color: var(--color-warning); }
.dlg-invite-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 12px; background: var(--color-surface-deep); border-radius: 3px; margin-bottom: 10px; }
.dlg-invite-code { font-family: monospace; font-size: 1.05rem; font-weight: 600; letter-spacing: 0.1em; color: var(--color-accent); }
.dlg-members { margin-top: 4px; }
.dlg-member-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 10px; background: var(--color-surface-deep); border-radius: 3px; margin-top: 4px; }
.dlg-member-row-deactivated { opacity: 0.45; filter: grayscale(0.6); }
.dlg-empty { text-align: center; padding: 40px 0; color: var(--color-text-subtle); }

/* ═══════════════════════════════════════════════════════════ */
/* DROPDOWN OVERRIDES (PrimeVue)                              */
/* ═══════════════════════════════════════════════════════════ */
:deep(.team-dropdown),
:deep(.filter-dropdown),
:deep(.dlg-dropdown) {
  background: var(--color-elevated) !important;
  border: 1px solid var(--color-border) !important;
  border-radius: 3px !important;
  min-width: 200px;
}
:deep(.team-dropdown .p-select-label),
:deep(.team-dropdown .p-dropdown-label),
:deep(.filter-dropdown .p-select-label),
:deep(.filter-dropdown .p-dropdown-label),
:deep(.dlg-dropdown .p-select-label),
:deep(.dlg-dropdown .p-dropdown-label) {
  color: var(--color-text) !important;
  font-size: 0.85rem;
}
:deep(.team-dropdown:not(.p-disabled):hover),
:deep(.filter-dropdown:not(.p-disabled):hover),
:deep(.dlg-dropdown:not(.p-disabled):hover) {
  border-color: var(--color-border-strong) !important;
}
:deep(.team-dropdown:not(.p-disabled).p-focus),
:deep(.filter-dropdown:not(.p-disabled).p-focus),
:deep(.dlg-dropdown:not(.p-disabled).p-focus) {
  border-color: var(--color-accent) !important;
  box-shadow: none !important;
}

/* Input overrides */
:deep(.dlg-input) {
  background: var(--color-elevated) !important;
  border: 1px solid var(--color-border) !important;
  border-radius: 3px !important;
  color: var(--color-text) !important;
}
:deep(.dlg-input::placeholder) { color: var(--color-text-subtle) !important; }
:deep(.dlg-input:enabled:hover) { border-color: var(--color-border-strong) !important; }
:deep(.dlg-input:enabled:focus) { border-color: var(--color-accent) !important; box-shadow: none !important; }

/* ═══════════════════════════════════════════════════════════ */
/* RESPONSIVE TWEAKS                                          */
/* ═══════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
  .hero-banner-inner { padding: 24px 16px 20px; }
  .hero-logo-main { height: 52px; }
  .hero-heading { font-size: 1.2rem; }
  .hero-actions { width: 100%; justify-content: flex-start; }
  .content-wrap { padding: 16px 16px 40px; }
  .team-bar-section { padding: 12px 16px 0; }
  .filter-toolbar { padding: 16px; }
  .team-bar { flex-direction: column; align-items: flex-start; }
  .landing-logo { height: 72px; }
  .landing-heading { font-size: 1.4rem; }
}

@media (max-width: 640px) {
  .content-wrap {
    padding: 14px 12px 36px;
  }

  .team-bar-section {
    padding: 10px 12px 0;
  }

  .team-bar {
    padding: 12px;
    gap: 10px;
  }

  .team-bar-left,
  .team-bar-meta,
  .team-bar-actions {
    width: 100%;
  }

  .team-bar-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .team-bar-actions {
    margin-left: 0;
    justify-content: stretch;
  }

  .team-bar-actions .steam-btn {
    flex: 1 1 calc(50% - 4px);
  }

  .landing-section {
    padding: 48px 16px;
  }

  .landing-actions {
    flex-direction: column;
    align-items: stretch;
    display: flex;
  }

  .landing-actions .steam-btn {
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

  .dlg-invite-row,
  .dlg-member-row {
    flex-direction: column;
    align-items: flex-start;
  }

  :deep(.team-dropdown),
  :deep(.filter-dropdown),
  :deep(.dlg-dropdown) {
    min-width: 0;
    width: 100%;
  }
}

/* ═══════════════════════════════════════════════════════════
   CUSTOM CONFIRM DIALOG
   ═══════════════════════════════════════════════════════════ */
.conf-dialog {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 28px 24px 24px;
  text-align: center;
  gap: 8px;
}

.conf-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  margin-bottom: 4px;
}

.conf-icon-danger {
  background: rgba(var(--color-danger-rgb), 0.12);
  color: var(--color-danger);
}

.conf-icon-warn {
  background: rgba(var(--color-warning-rgb), 0.12);
  color: var(--color-warning);
}

.conf-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-accent);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0;
}

.conf-message {
  font-size: 0.84rem;
  color: var(--color-text-muted);
  margin: 0 0 8px;
  line-height: 1.5;
}

.conf-actions {
  display: flex;
  gap: 8px;
  width: 100%;
  margin-top: 4px;
}

.conf-btn {
  flex: 1;
  padding: 9px 16px;
  font-size: 0.84rem;
  font-weight: 600;
  border: none;
  border-radius: 7px;
  cursor: pointer;
  transition: background 0.15s, opacity 0.15s;
}

.conf-btn-ghost {
  background: var(--color-border);
  color: var(--color-text-muted);
}
.conf-btn-ghost:hover { opacity: 0.8; }

.conf-btn-danger {
  background: var(--color-danger);
  color: #fff;
}
.conf-btn-danger:hover { opacity: 0.88; }

.conf-btn-warn {
  background: rgba(var(--color-warning-rgb), 0.15);
  color: var(--color-warning);
  border: 1px solid rgba(var(--color-warning-rgb), 0.3);
}
.conf-btn-warn:hover { background: rgba(var(--color-warning-rgb), 0.25); }
</style>
