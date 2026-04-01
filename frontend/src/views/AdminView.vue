<template>
  <div class="steam-page steam-theme">
    <Toast />

    <!-- Dashboard Wrapping Container -->
    <div class="flex flex-col gap-6 mb-8">
      
      <!-- Master Dashboard Wrapper -->
      <div class="admin-surface-card backdrop-blur-sm rounded-[2rem] shadow-2xl p-6 sm:p-8 flex flex-col gap-6 sm:gap-8">
        
        <!-- Title & Refresh -->
        <div class="w-full flex flex-col sm:flex-row items-center justify-between gap-4 sm:gap-0 relative">
          <!-- Invisible spacer for symmetry on larger screens -->
          <div class="hidden sm:block sm:w-32"></div>
          
          <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black admin-text-strong tracking-widest uppercase m-0 absolute left-1/2 transform -translate-x-1/2 w-full sm:w-auto text-center">
            {{ t('admin.title') }}
          </h1>
          
          <div class="flex justify-end w-full sm:w-32 relative z-10">
            <Button 
              :label="t('admin.refresh_btn')"
              class="admin-action-btn-styled p-button-sm rounded-xl whitespace-nowrap w-full sm:w-auto"
              @click="loadData"
              :loading="loading"
            />
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap justify-center gap-3 w-full admin-elevated-card rounded-2xl p-5 shadow-lg">
          <Button 
            :label="t('admin.register_user_btn')"
            class="p-button-secondary p-button-outlined admin-action-btn-styled rounded-xl px-5"
            @click="showRegisterUserDialog = true"
          />
          <Button
            :label="t('admin.create_team_btn')"
            class="p-button-secondary p-button-outlined admin-action-btn-styled rounded-xl px-5"
            @click="openCreateTeamDialog"
          />
          <Button
            :label="t('admin.add_year_btn')"
            class="p-button-secondary p-button-outlined admin-action-btn-styled rounded-xl px-5"
            @click="openAddAcademicYearDialog"
          />
          <Button
            :label="t('admin.manage_users_btn')"
            class="p-button-secondary p-button-outlined admin-action-btn-styled rounded-xl px-5"
            @click="openUsersManagementDialog"
          />
          <Button
            :label="t('admin.import_csv_btn')"
            class="p-button-secondary p-button-outlined admin-action-btn-styled rounded-xl px-5"
            @click="showImportDialog = true"
            :disabled="!config.authorizationEnabled"
          />
        </div>

        <!-- Statistics Cells -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 w-full">
          <!-- Total Teams -->
          <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-5 border border-gray-700 shadow-lg hover:border-blue-500/50 hover:shadow-blue-500/10 transition-all duration-300 flex flex-col items-center justify-center text-center">
            <span class="text-gray-400 text-xs sm:text-sm font-bold tracking-wider uppercase mb-2">{{ t('admin.total_teams') }}</span>
            <span class="text-3xl sm:text-4xl font-black text-white tracking-tight">{{ stats.total_teams || 0 }}</span>
          </div>
          
          <!-- Total Projects -->
          <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-5 border border-gray-700 shadow-lg hover:border-emerald-500/50 hover:shadow-emerald-500/10 transition-all duration-300 flex flex-col items-center justify-center text-center">
            <span class="text-gray-400 text-xs sm:text-sm font-bold tracking-wider uppercase mb-2">{{ t('admin.total_projects') }}</span>
            <span class="text-3xl sm:text-4xl font-black text-white tracking-tight">{{ stats.total_projects || 0 }}</span>
          </div>

          <!-- Total Users -->
          <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-5 border border-gray-700 shadow-lg hover:border-purple-500/50 hover:shadow-purple-500/10 transition-all duration-300 flex flex-col items-center justify-center text-center">
            <span class="text-gray-400 text-xs sm:text-sm font-bold tracking-wider uppercase mb-2">{{ t('admin.total_users') }}</span>
            <span class="text-3xl sm:text-4xl font-black text-white tracking-tight">{{ stats.total_users || 0 }}</span>
          </div>

          <!-- Pending Teams -->
          <div 
            :class="[
              'rounded-2xl p-5 border shadow-lg transition-all duration-300 flex flex-col items-center justify-center text-center',
              (stats.pending_teams || 0) > 0 
                ? 'bg-gradient-to-br from-amber-900/40 to-orange-950/40 border-amber-500/50 shadow-amber-500/20 hover:border-amber-400/80 hover:shadow-amber-500/40' 
                : 'bg-gradient-to-br from-gray-800 to-gray-900 border-gray-700 hover:border-amber-500/50 hover:shadow-amber-500/10'
            ]"
          >
            <span class="text-gray-400 text-xs sm:text-sm font-bold tracking-wider uppercase mb-2" :class="{ 'text-amber-500': (stats.pending_teams || 0) > 0 }">{{ t('admin.pending_teams_stat') }}</span>
            <span class="text-3xl sm:text-4xl font-black tracking-tight" :class="[(stats.pending_teams || 0) > 0 ? 'text-amber-400 animate-pulse' : 'text-white']">{{ stats.pending_teams || 0 }}</span>
          </div>
        </div>

        <!-- Search and Filters Cell -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-5 border border-gray-700 shadow-lg w-full flex justify-center">
          <div class="w-full max-w-4xl flex flex-col sm:flex-row gap-5 items-center justify-center">
            <InputText 
              v-model="searchQuery" 
              :placeholder="t('admin.search_placeholder')" 
              class="w-full sm:flex-1 rounded-xl text-center bg-gray-900/50 border-gray-600 focus:border-gray-500 focus:ring-0 shadow-inner"
              @input="filterTeams"
            />
            <Dropdown
              v-model="statusFilter"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              :placeholder="t('admin.filter_by_status')"
              class="w-full sm:w-64 rounded-xl text-center shadow-inner"
              panelClass="text-center"
              @change="filterTeams"
            />
          </div>
        </div>
      </div>

      <!-- Pending Teams Section (if any) -->
      <div v-if="pendingTeams.length > 0" class="animate-fade-in w-full">
        <div class="bg-gradient-to-br from-amber-900/40 to-orange-950/40 rounded-2xl p-6 border border-amber-500/50 shadow-lg shadow-amber-500/10">
          <div class="flex flex-col items-center mb-6">
            <h2 class="text-xl font-black text-amber-500 tracking-wider uppercase flex items-center gap-3">
              <span>{{ t('admin.pending_approval_header') }}</span>
              <span class="px-3 py-0.5 bg-amber-500 text-amber-950 rounded-full text-sm font-black animate-pulse">
                {{ pendingTeams.length }}
              </span>
            </h2>
          </div>
          <div class="grid gap-3 max-w-5xl mx-auto">
            <div 
              v-for="team in pendingTeams" 
              :key="team.id"
              class="flex flex-col sm:flex-row items-center justify-between bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-700 hover:border-amber-500/30 transition-all gap-4 text-center sm:text-left shadow-sm"
            >
              <div class="flex flex-col sm:flex-row items-center gap-3">
                <span class="text-lg font-bold text-white">{{ team.name }}</span>
                <span class="text-gray-400 text-sm">
                  ({{ team.scrum_master?.name || t('admin.unknown') }})
                </span>
                <span v-if="team.academic_year" class="px-2.5 py-1 bg-gray-700/50 border border-gray-600 rounded-md text-gray-300 text-xs font-semibold">
                  {{ team.academic_year.name }}
                </span>
              </div>
              <div class="flex gap-2 w-full sm:w-auto justify-center">
                <Button 
                  :label="t('admin.approve_btn')"
                  class="p-button-sm p-button-success rounded-lg w-full sm:w-auto"
                  @click="approveTeam(team)"
                />
                <Button 
                  :label="t('admin.reject_btn')"
                  class="p-button-sm p-button-danger p-button-outlined rounded-lg w-full sm:w-auto"
                  @click="showRejectDialog(team)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Teams Overview Table with Accordion -->
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border border-gray-700 overflow-hidden shadow-lg">
      <div class="p-6 border-b border-gray-700 flex justify-center">
        <h2 class="text-xl sm:text-2xl font-black text-white tracking-widest uppercase m-0">
          {{ t('admin.teams_overview') }}
        </h2>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="mt-4 text-gray-400">{{ t('common.loading') }}</p>
      </div>

      <div v-else-if="filteredTeams.length === 0" class="p-8 text-center text-gray-400">
        <p>{{ t('admin.no_teams_found') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="admin-table-head">
            <tr>
              <th class="px-5 py-5 text-left text-sm font-black admin-text-muted uppercase tracking-wider w-10"></th>
              <th class="px-5 py-5 text-center text-sm font-black admin-text-muted uppercase tracking-wider">{{ t('admin.table_team') }}</th>
              <th class="px-5 py-5 text-center text-sm font-black admin-text-muted uppercase tracking-wider">{{ t('admin.table_year') }}</th>
              <th class="px-5 py-5 text-center text-sm font-black admin-text-muted uppercase tracking-wider">{{ t('admin.table_members') }}</th>
              <th class="px-5 py-5 text-center text-sm font-black admin-text-muted uppercase tracking-wider">{{ t('admin.table_projects') }}</th>
              <th class="px-5 py-5 text-center text-sm font-black admin-text-muted uppercase tracking-wider">{{ t('admin.table_status') }}</th>
              <th class="px-5 py-5 text-center text-sm font-black admin-text-muted uppercase tracking-wider">{{ t('admin.table_actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-700/40">
            <template v-for="team in filteredTeams" :key="team.id">
              <tr 
                class="hover:bg-gray-800/30 transition cursor-pointer"
                @click="toggleTeamProjects(team)"
              >
                <td class="px-4 py-4 text-center">
                  <span class="text-gray-400">
                    {{ expandedTeamId === team.id ? 'v' : '>' }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <div>
                    <div class="font-semibold text-white">{{ team.name }}</div>
                    <div class="text-xs text-gray-500">{{ team.invite_code }}</div>
                  </div>
                </td>
                <td class="px-4 py-4 text-gray-300 text-center">
                  {{ team.academic_year?.name || '-' }}
                </td>
                <td class="px-4 py-4 text-center text-gray-300">
                  {{ team.members_count }}
                </td>
                <td class="px-4 py-4 text-center text-gray-300">
                  {{ team.projects_count }}
                </td>
                <td class="px-4 py-4 text-center">
                  <span 
                    :class="getStatusClass(team.status)"
                    class="px-2 py-1 rounded-md text-xs font-semibold"
                  >
                    {{ getStatusLabel(team.status) }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center" @click.stop>
                  <div class="flex justify-center gap-1">
                    <Button 
                      :label="t('common.detail')"
                      class="p-button-sm p-button-text p-button-rounded"
                      @click="showTeamDetails(team)"
                    />
                    <Button 
                      :label="t('common.edit')"
                      class="p-button-sm p-button-text p-button-rounded"
                      @click="showEditDialog(team)"
                    />
                    <Button 
                      :label="t('common.delete')"
                      class="p-button-sm p-button-text p-button-rounded p-button-danger"
                      @click="confirmDeleteTeam(team)"
                    />
                  </div>
                </td>
              </tr>

              <!-- Expandable Projects Section -->
              <tr v-if="expandedTeamId === team.id">
                <td colspan="7" class="bg-gray-900/50 p-0">
                  <div class="p-6">
                    <!-- Loading State -->
                    <div v-if="loadingTeamProjects[team.id]" class="text-center py-8">
                      <p class="mt-3 text-gray-400">{{ t('admin.loading_team_projects') }}</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!teamProjects[team.id] || teamProjects[team.id].length === 0" class="text-center py-8">
                      <p class="text-gray-400">{{ t('admin.no_team_projects') }}</p>
                    </div>

                    <!-- Projects List -->
                    <div v-else class="space-y-3">
                      <div
                        v-for="project in teamProjects[team.id]"
                        :key="project.id"
                        class="bg-gray-800/50 border border-gray-700 rounded-lg hover:border-gray-600/50 transition-all duration-200"
                      >
                        <div class="p-4">
                          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4">
                            <!-- Left side: Project info -->
                            <div class="flex-1 min-w-0">
                              <h4 class="font-semibold text-white text-lg mb-2">{{ project.title }}</h4>
                              
                              <div class="flex flex-wrap gap-2">
                                <span class="px-2.5 py-1 bg-blue-900/50 text-blue-300 rounded-md text-xs border border-blue-700/50 font-medium">
                                  {{ project.type.replace('_', ' ') }}
                                </span>
                                <span v-if="project.school_type" class="px-2.5 py-1 bg-gray-700 text-gray-300 rounded-md text-xs font-medium">
                                  {{ project.school_type.toUpperCase() }}
                                </span>
                                <span v-if="project.year_of_study" class="px-2.5 py-1 bg-gray-700 text-gray-300 rounded-md text-xs font-medium">
                                  {{ project.year_of_study }}. ročník
                                </span>
                                <span v-if="project.subject" class="px-2.5 py-1 bg-gray-700 text-gray-300 rounded-md text-xs font-medium">
                                  {{ project.subject }}
                                </span>
                              </div>

                              <p v-if="project.description" class="text-sm text-gray-400 mt-2 line-clamp-2">
                                {{ project.description }}
                              </p>
                            </div>

                            <!-- Right side: Actions -->
                            <div class="flex gap-1 self-end sm:self-auto">
                              <Button 
                                :label="t('common.detail')"
                                class="p-button-sm p-button-text p-button-rounded"
                                @click="viewProjectDetail(project.id)"
                              />
                              <Button 
                                :label="t('common.edit')"
                                class="p-button-sm p-button-text p-button-rounded"
                                @click="editProject(project.id)"
                              />
                              <Button 
                                :label="t('common.delete')"
                                class="p-button-sm p-button-text p-button-rounded p-button-danger"
                                @click="confirmDeleteProject(project)"
                              />
                            </div>
                          </div>
                        </div>

                        <!-- Project Attributes Grid -->
                        <div class="border-t border-gray-700 bg-gray-900/30 px-4 py-3">
                          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                            <!-- Splash Screen -->
                            <div 
                              :class="[
                                'admin-attr',
                                project.has_splash ? 'admin-attr-ok' : 'admin-attr-missing'
                              ]"
                            >
                              <span :class="['text-xs font-semibold', project.has_splash ? 'text-emerald-300' : 'text-rose-300']">
                                Splash
                              </span>
                              <span :class="['text-xs mt-1', project.has_splash ? 'text-emerald-300' : 'text-rose-300']">
                                {{ project.has_splash ? 'OK' : 'Nie' }}
                              </span>
                            </div>

                            <!-- Video -->
                            <div 
                              :class="[
                                'admin-attr',
                                project.has_video ? 'admin-attr-ok' : 'admin-attr-missing'
                              ]"
                            >
                              <span :class="['text-xs font-semibold', project.has_video ? 'text-emerald-300' : 'text-rose-300']">
                                Video
                              </span>
                              <span :class="['text-xs mt-1', project.has_video ? 'text-emerald-300' : 'text-rose-300']">
                                {{ project.has_video ? 'OK' : 'Nie' }}
                              </span>
                            </div>

                            <!-- Documentation -->
                            <div 
                              :class="[
                                'admin-attr',
                                project.has_documentation ? 'admin-attr-ok' : 'admin-attr-missing'
                              ]"
                            >
                              <span :class="['text-xs font-semibold', project.has_documentation ? 'text-emerald-300' : 'text-rose-300']">
                                Dokumentácia
                              </span>
                              <span :class="['text-xs mt-1', project.has_documentation ? 'text-emerald-300' : 'text-rose-300']">
                                {{ project.has_documentation ? 'OK' : 'Nie' }}
                              </span>
                            </div>

                            <!-- Presentation -->
                            <div 
                              :class="[
                                'admin-attr',
                                project.has_presentation ? 'admin-attr-ok' : 'admin-attr-missing'
                              ]"
                            >
                              <span :class="['text-xs font-semibold', project.has_presentation ? 'text-emerald-300' : 'text-rose-300']">
                                Prezentácia
                              </span>
                              <span :class="['text-xs mt-1', project.has_presentation ? 'text-emerald-300' : 'text-rose-300']">
                                {{ project.has_presentation ? 'OK' : 'Nie' }}
                              </span>
                            </div>

                            <!-- Source Code -->
                            <div 
                              :class="[
                                'admin-attr',
                                project.has_source_code ? 'admin-attr-ok' : 'admin-attr-missing'
                              ]"
                            >
                              <span :class="['text-xs font-semibold', project.has_source_code ? 'text-emerald-300' : 'text-rose-300']">
                                Zdrojový kód
                              </span>
                              <span :class="['text-xs mt-1', project.has_source_code ? 'text-emerald-300' : 'text-rose-300']">
                                {{ project.has_source_code ? 'OK' : 'Nie' }}
                              </span>
                            </div>

                            <!-- Export -->
                            <div 
                              :class="[
                                'admin-attr',
                                project.has_export ? 'admin-attr-ok' : 'admin-attr-missing'
                              ]"
                            >
                              <span :class="['text-xs font-semibold', project.has_export ? 'text-emerald-300' : 'text-rose-300']">
                                Export
                              </span>
                              <span :class="['text-xs mt-1', project.has_export ? 'text-emerald-300' : 'text-rose-300']">
                                {{ project.has_export ? 'OK' : 'Nie' }}
                              </span>
                            </div>

                            <!-- Rating -->
                            <div 
                              :class="[
                                'admin-attr',
                                project.rating ? 'admin-attr-ok' : 'admin-attr-missing'
                              ]"
                            >
                              <span :class="['text-xs font-semibold', project.rating ? 'text-emerald-300' : 'text-rose-300']">
                                {{ project.rating ? Number(project.rating).toFixed(1) : 'Bez hodnotenia' }}
                              </span>
                              <span :class="['text-xs mt-1', project.rating ? 'text-emerald-300' : 'text-rose-300']">
                                {{ project.rating ? 'OK' : 'Nie' }}
                              </span>
                            </div>

                            <!-- Views -->
                            <div 
                              class="admin-attr admin-attr-neutral"
                            >
                              <span class="text-xs font-semibold text-gray-400 flex items-center gap-1">
                                <i class="pi pi-eye" aria-hidden="true"></i>
                                <span>{{ project.views || 0 }}</span>
                              </span>
                              <span class="text-xs text-gray-500 mt-1">zobrazení</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Team Details Dialog -->
    <Dialog 
      v-model:visible="showDetailsDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-2/3 lg:w-1/2 admin-dialog-shell admin-edit-team-dialog"
        :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem' }"
        :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <span class="text-lg font-semibold text-center">Detail tímu: {{ selectedTeam?.name }}</span>
      </template>

      <div v-if="selectedTeamDetails" class="space-y-6">
        <!-- Team Info -->
        <div class="grid grid-cols-2 gap-4">
          <div class="admin-item-card p-3">
            <div class="admin-text-muted text-sm">Kód pre pripojenie</div>
            <div class="admin-text-strong font-mono text-lg">{{ selectedTeamDetails.team.invite_code }}</div>
          </div>
          <div class="admin-item-card p-3">
            <div class="admin-text-muted text-sm">Akademický rok</div>
            <div class="admin-text-strong">{{ selectedTeamDetails.team.academic_year?.name || '-' }}</div>
          </div>
        </div>

        <!-- Members -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold admin-text-strong">Členovia ({{ selectedTeamDetails.members.length }})</h3>
            <Dropdown
              v-model="selectedNewScrumMaster"
              :options="scrumMasterCandidates"
              optionLabel="name"
              optionValue="id"
              placeholder="Zmeniť Scrum Mastera"
              class="w-64"
              @change="confirmChangeScrumMaster"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">Zmeniť SM</span>
                <span v-else>Zmeniť Scrum Mastera</span>
              </template>
            </Dropdown>
          </div>
          <div class="space-y-2">
            <div 
              v-for="member in selectedTeamDetails.members" 
              :key="member.id"
              class="flex items-center justify-between admin-item-card p-3"
            >
              <div>
                <div class="flex items-center gap-2">
                  <span :class="member.is_absolvent ? 'admin-text-muted opacity-60' : 'admin-text-strong'" class="font-medium">{{ member.name }}</span>
                  <span
                    v-if="member.is_absolvent"
                    class="px-1.5 py-0.5 border rounded text-xs" style="background: var(--color-surface); border-color: var(--color-border); color: var(--color-text-muted);"
                  >
                    Absolvent
                  </span>
                  <span 
                    :class="getUserStatusClass(member.status || 'active')"
                    class="px-2 py-0.5 rounded text-xs font-semibold"
                  >
                    {{ getUserStatusLabel(member.status || 'active') }}
                  </span>
                </div>
                <span class="admin-text-subtle text-sm">{{ member.email }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span v-if="member.occupation" class="px-2 py-1 bg-indigo-900 text-indigo-300 rounded text-xs">
                  {{ formatOccupation(member.occupation) || 'Neurčené' }}
                </span>
                <span 
                  v-if="member.role_in_team === 'scrum_master'"
                  class="px-2 py-1 bg-blue-900 text-blue-300 rounded text-xs font-semibold"
                >
                  SM
                </span>
                <Button 
                  v-if="(member.status || 'active') === 'active'"
                  label="Deaktivovať"
                  class="p-button-sm p-button-text p-button-rounded p-button-danger"
                  @click="deactivateTeamMember(member)"
                  :loading="saving"
                />
                <Button 
                  v-else
                  label="Aktivovať"
                  class="p-button-sm p-button-text p-button-rounded p-button-success"
                  @click="activateTeamMember(member)"
                  :loading="saving"
                />
                <Button 
                  label="Presunúť"
                  class="p-button-sm p-button-text p-button-rounded p-button-warning"
                  @click="openMoveToTeamDialog(member)"
                />
                <Button 
                  label="Odstrániť"
                  class="p-button-sm p-button-text p-button-rounded p-button-danger"
                  @click="confirmRemoveMember(member)"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Projects -->
        <div>
          <h3 class="text-lg font-semibold admin-text-strong mb-3">Projekty ({{ selectedTeamDetails.projects.length }})</h3>
          <div v-if="selectedTeamDetails.projects.length === 0" class="admin-text-muted text-center py-4">
            Žiadne projekty
          </div>
          <div v-else class="space-y-2">
            <div 
              v-for="project in selectedTeamDetails.projects" 
              :key="project.id"
              class="admin-item-card p-3"
            >
              <div class="flex items-center justify-between">
                <div>
                  <span class="admin-text-strong font-medium">{{ project.title }}</span>
                  <span class="admin-text-muted text-xs ml-2">({{ project.type }})</span>
                </div>
                <div class="flex items-center gap-3 text-xs">
                  <span class="flex items-center gap-1">
                    <span v-for="s in 5" :key="s" :class="s <= Math.round(Number(project.rating || 0)) ? 'text-yellow-400' : 'admin-text-muted opacity-50'" style="font-size: 0.65rem;">★</span>
                    <span class="admin-text-muted text-xs ml-1">{{ Number(project.rating || 0).toFixed(1) }}</span>
                  </span>
                  <span class="flex items-center gap-1 admin-text-muted">
                    <i class="pi pi-eye text-xs" aria-hidden="true"></i>
                    <span class="text-xs">{{ project.views || 0 }}</span>
                  </span>
                </div>
              </div>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 text-xs">
                <span :class="project.has_splash ? 'px-2 py-1 bg-emerald-900/20 border border-emerald-600/50 text-emerald-300 rounded text-center' : 'px-2 py-1 bg-rose-900/20 border border-rose-600/50 text-rose-300 rounded text-center'">
                  Náhľad {{ project.has_splash ? '✓' : '✗' }}
                </span>
                <span :class="project.has_video ? 'px-2 py-1 bg-emerald-900/20 border border-emerald-600/50 text-emerald-300 rounded text-center' : 'px-2 py-1 bg-rose-900/20 border border-rose-600/50 text-rose-300 rounded text-center'">
                  Video {{ project.has_video ? '✓' : '✗' }}
                </span>
                <span :class="project.has_documentation ? 'px-2 py-1 bg-emerald-900/20 border border-emerald-600/50 text-emerald-300 rounded text-center' : 'px-2 py-1 bg-rose-900/20 border border-rose-600/50 text-rose-300 rounded text-center'">
                  Dokumentácia {{ project.has_documentation ? '✓' : '✗' }}
                </span>
                <span :class="project.has_presentation ? 'px-2 py-1 bg-emerald-900/20 border border-emerald-600/50 text-emerald-300 rounded text-center' : 'px-2 py-1 bg-rose-900/20 border border-rose-600/50 text-rose-300 rounded text-center'">
                  Prezentácia {{ project.has_presentation ? '✓' : '✗' }}
                </span>
                <span :class="project.has_source_code ? 'px-2 py-1 bg-emerald-900/20 border border-emerald-600/50 text-emerald-300 rounded text-center' : 'px-2 py-1 bg-rose-900/20 border border-rose-600/50 text-rose-300 rounded text-center'">
                  Zdrojový kód {{ project.has_source_code ? '✓' : '✗' }}
                </span>
                <span :class="project.has_export ? 'px-2 py-1 bg-emerald-900/20 border border-emerald-600/50 text-emerald-300 rounded text-center' : 'px-2 py-1 bg-rose-900/20 border border-rose-600/50 text-rose-300 rounded text-center'">
                  Export {{ project.has_export ? '✓' : '✗' }}
                </span>
                <span class="px-2 py-1 bg-yellow-900/20 border border-yellow-600/50 text-yellow-300 rounded text-center">
                  ⭐ {{ Number(project.rating || 0).toFixed(1) }}
                </span>
                <span class="px-2 py-1 border rounded text-center" style="background: var(--color-surface); border-color: var(--color-border); color: var(--color-text-muted);">
                  <i class="pi pi-eye" aria-hidden="true"></i> {{ project.views || 0 }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.close')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="showDetailsDialog = false" />
        </div>
      </template>
    </Dialog>

    <!-- Edit Team Dialog -->
    <Dialog 
      v-model:visible="showEditTeamDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-1/3 admin-dialog-shell admin-edit-team-dialog"
        :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem' }"
        :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <span class="text-lg font-semibold">Upraviť tím</span>
      </template>

      <div class="space-y-4">
        <div>
          <label class="block text-gray-300 mb-1">Názov tímu</label>
          <InputText v-model="editForm.name" class="w-full" />
        </div>
        <div>
          <label class="block text-gray-300 mb-1">Stav</label>
          <Dropdown
            v-model="editForm.status"
            :options="statusOptions.filter(s => s.value)"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="showEditTeamDialog = false" />
          <Button :label="t('common.save')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="saveTeamEdit" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Reject Team Dialog -->
    <Dialog 
      v-model:visible="showRejectTeamDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-1/3 admin-dialog-shell"
        :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem' }"
        :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <span class="text-lg font-semibold">Zamietnuť tím</span>
      </template>

      <div class="space-y-4">
        <p class="text-gray-300">Naozaj chcete zamietnuť tím <strong>{{ selectedTeam?.name }}</strong>?</p>
        <div>
          <label class="block text-gray-300 mb-1">Dôvod zamietnutia (voliteľné)</label>
          <Textarea v-model="rejectReason" rows="3" class="w-full" placeholder="Uveďte dôvod..." />
        </div>
      </div>

      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="showRejectTeamDialog = false" />
          <Button label="Zamietnuť" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="rejectTeam" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog 
      v-model:visible="showDeleteDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-1/3 admin-dialog-shell admin-edit-team-dialog"
        :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem' }"
        :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <span class="text-lg font-semibold admin-text-strong">Zmazať tím</span>
      </template>

      <div class="text-center">
        <p class="admin-text-strong">
          Naozaj chcete zmazať tím <strong class="admin-text-strong">{{ selectedTeam?.name }}</strong>?
        </p>
        <p class="admin-text-muted text-sm mt-2">Táto akcia je nevratná.</p>
      </div>

      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="showDeleteDialog = false" />
          <Button label="Zmazať" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="deleteTeam" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Remove Member Confirmation Dialog -->
    <Dialog 
      v-model:visible="showRemoveMemberDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-1/3 admin-dialog-shell"
        :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem' }"
        :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <span class="text-lg font-semibold text-red-400">Odstrániť člena</span>
      </template>

      <div class="text-center">
        <p class="text-gray-300">
          Naozaj chcete odstrániť člena <strong class="text-white">{{ selectedMemberToRemove?.name }}</strong> z tímu?
        </p>
        <p v-if="selectedMemberToRemove?.role_in_team === 'scrum_master'" class="text-orange-400 text-sm mt-2">
          Upozornenie: Tento člen je Scrum Master tímu.
        </p>
      </div>

      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="showRemoveMemberDialog = false" />
          <Button label="Odstrániť" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="removeMemberFromTeam" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Change Scrum Master Confirmation Dialog -->
    <Dialog 
      v-model:visible="showChangeSMDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-1/3 admin-dialog-shell"
        :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem' }"
        :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <span class="text-lg font-semibold text-blue-400">Zmeniť Scrum Mastera</span>
      </template>

      <div class="text-center">
        <p class="text-gray-300">
          Naozaj chcete zmeniť Scrum Mastera na <strong class="text-white">{{ getSelectedNewSMName() }}</strong>?
        </p>
        <p class="text-gray-400 text-sm mt-2">Súčasný Scrum Master bude zmenený na člena.</p>
      </div>

      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="showChangeSMDialog = false" />
          <Button label="Zmeniť" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="changeScrumMasterInTeam" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Delete Project Confirmation Dialog -->
    <Dialog 
      v-model:visible="showDeleteProjectDialog" 
      :modal="true"
      :draggable="false"
      class="w-11/12 md:w-1/3 admin-dialog-shell"
        :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem' }"
        :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <span class="text-lg font-semibold text-red-400">Zmazať projekt</span>
      </template>

      <div class="text-center">
        <p class="text-gray-300">
          Naozaj chcete zmazať projekt <strong class="text-white">{{ selectedProject?.title }}</strong>?
        </p>
        <p class="text-gray-400 text-sm mt-2">Táto akcia je nevratná.</p>
      </div>

      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="showDeleteProjectDialog = false" />
          <Button label="Zmazať" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="deleteProject" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Register User Dialog -->
    <Dialog 
      v-model:visible="showRegisterUserDialog" 
      :modal="true"
      :closable="false"
      :draggable="false"
      class="w-11/12 md:w-1/2 lg:w-[560px] admin-dialog-shell"
        :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.25rem 1.5rem 1.5rem' }"
        :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <div class="admin-dlg-header">
          <span class="admin-dlg-title">Registrovať nového používateľa</span>
          <button class="admin-dlg-close" @click="closeRegisterDialog" type="button" aria-label="Zavrieť">×</button>
        </div>
      </template>

      <div class="admin-dlg-content">
        <div class="admin-dlg-field">
          <label class="admin-dlg-label">Meno *</label>
          <InputText v-model="registerForm.name" class="w-full" placeholder="Celé meno" />
        </div>
        
        <div class="admin-dlg-field">
          <label class="admin-dlg-label">Email *</label>
          <InputText v-model="registerForm.email" type="email" class="w-full" placeholder="email@example.com" />
        </div>
        
        <div class="admin-dlg-field">
          <label class="admin-dlg-label">Heslo *</label>
          <InputText v-model="registerForm.password" type="password" class="w-full" placeholder="Minimálne 8 znakov" />
        </div>

        <div class="admin-dlg-field">
          <label class="admin-dlg-label">Typ študenta *</label>
          <Dropdown
            v-model="registerForm.student_type"
            :options="studentTypeOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Vyberte typ študenta"
            class="w-full"
          />
        </div>

        <div class="admin-dlg-note">
          <p>
            Email bude automaticky overený. Používateľ sa môže ihneď prihlásiť.
          </p>
        </div>
      </div>

      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="closeRegisterDialog" />
          <Button label="Registrovať" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="registerUser" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Create Team Dialog -->
    <Dialog
      v-model:visible="showCreateTeamDialog"
      modal
      :closable="false"
      :draggable="false"
      class="w-11/12 md:w-1/2 lg:w-[560px] admin-dialog-shell"
      :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.25rem 1.5rem 1.5rem' }"
      :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <div class="admin-dlg-header">
          <span class="admin-dlg-title">Vytvoriť nový tím</span>
          <button class="admin-dlg-close" @click="closeCreateTeamDialog" type="button" aria-label="Zavrieť">×</button>
        </div>
      </template>

      <div class="admin-dlg-content">
        <div class="admin-dlg-field">
          <label class="admin-dlg-label">Názov tímu</label>
          <InputText v-model="createTeamForm.name" class="w-full" placeholder="Napr. Super Devs" />
        </div>
        <div class="admin-dlg-field">
          <label class="admin-dlg-label">Akademický rok</label>
          <Dropdown 
            v-model="createTeamForm.academic_year_id" 
            :options="academicYearOptions" 
            optionLabel="label" 
            optionValue="value"
            placeholder="Vyberte akademický rok"
            class="w-full"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="admin-dlg-field">
            <label class="admin-dlg-label">Typ tímu</label>
            <Dropdown 
              v-model="createTeamForm.team_type" 
              :options="teamTypeOptions" 
              optionLabel="label" 
              optionValue="value"
              class="w-full"
            />
          </div>
          <div class="admin-dlg-field">
            <label class="admin-dlg-label">Stav</label>
            <Dropdown 
              v-model="createTeamForm.status" 
              :options="teamStatusOptions" 
              optionLabel="label" 
              optionValue="value"
              class="w-full"
            />
          </div>
        </div>
        <div class="admin-dlg-note">
          <p>Typ tímu určuje, či sa môžu pripojiť len denní alebo externí študenti.</p>
        </div>
      </div>
      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="closeCreateTeamDialog" />
          <Button label="Vytvoriť" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="createTeam" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Add Academic Year Dialog -->
    <Dialog
      v-model:visible="showAddAcademicYearDialog"
      modal
      :closable="false"
      :draggable="false"
      class="w-11/12 md:w-1/2 lg:w-[560px] admin-dialog-shell"
      :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.25rem 1.5rem 1.5rem' }"
      :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <div class="admin-dlg-header">
          <span class="admin-dlg-title">Pridať akademický rok</span>
          <button class="admin-dlg-close" @click="closeAddAcademicYearDialog" type="button" aria-label="Zavrieť">×</button>
        </div>
      </template>

      <div class="admin-dlg-content">
        <div class="admin-dlg-field">
          <label class="admin-dlg-label">Navrhovaný akademický rok</label>
          <span class="font-semibold">{{ academicYearSuggestion || '-' }}</span>
        </div>
        <div class="admin-dlg-field">
          <label class="admin-dlg-label">Akademický rok (YYYY/YYYY)</label>
          <InputText v-model="createAcademicYearForm.name" class="w-full" placeholder="2026/2027" />
        </div>
        <div class="admin-dlg-note">
          <p>Formát musí byť YYYY/YYYY a druhý rok musí byť o 1 vyšší.</p>
        </div>
      </div>
      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="closeAddAcademicYearDialog" />
          <Button label="Pridať" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="createAcademicYear" :loading="creatingAcademicYear" />
        </div>
      </template>
    </Dialog>

    <!-- Move to Team Dialog -->
    <Dialog
      v-model:visible="showMoveToTeamDialog"
      modal
      header="Presunúť používateľa do iného tímu"
      :style="{ width: '520px' }"
      class="admin-dialog-shell"
      :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem' }"
      :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm text-gray-300 mb-1">Používateľ</label>
          <div class="bg-gray-800 rounded-lg p-3 text-gray-200">
            {{ selectedMemberToMove?.name || '-' }} ({{ selectedMemberToMove?.email || '-' }})
          </div>
        </div>
        <div>
          <label class="block text-sm text-gray-300 mb-1">Zdrojový tím</label>
          <div class="bg-gray-800 rounded-lg p-3 text-gray-200">
            {{ selectedTeam?.name || '-' }}
          </div>
        </div>
        <div>
          <label class="block text-sm text-gray-300 mb-1">Cieľový tím *</label>
          <Dropdown 
            v-model="moveToTeamForm.to_team_id" 
            :options="availableTeamsForMove" 
            optionLabel="name" 
            optionValue="id"
            placeholder="Vyberte cieľový tím"
            class="w-full"
          />
        </div>
        <div>
          <label class="block text-sm text-gray-300 mb-1">Povolanie v novom tíme *</label>
          <Dropdown
            v-model="moveToTeamForm.occupation"
            :options="[
              { label: 'Programátor', value: 'Programátor' },
              { label: 'Grafik 2D', value: 'Grafik 2D' },
              { label: 'Grafik 3D', value: 'Grafik 3D' },
              { label: 'Tester', value: 'Tester' },
              { label: 'Animátor', value: 'Animátor' }
            ]"
            optionLabel="label"
            optionValue="value"
            placeholder="Vyberte povolanie"
            class="w-full"
          />
        </div>
        <div class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-3">
          <p class="text-yellow-300 text-sm">
            <span v-if="selectedMemberToMove?.role_in_team === 'scrum_master'">
              Tento používateľ je Scrum Master. Bude automaticky degradovaný na člena a najstarší člen zdrojového tímu sa stane novým Scrum Masterom.
            </span>
            <span v-else>
              Používateľ bude presunul ako člen do novej tímu.
            </span>
          </p>
        </div>
      </div>
      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="closeMoveToTeamDialog" />
          <Button label="Presunúť" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="moveUserToTeam" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Users Management Dialog -->
    <Dialog 
      v-model:visible="showUsersManagementDialog" 
      :modal="true"
      :closable="false"
      :draggable="false"
      class="w-11/12 md:w-3/4 lg:w-2/3 admin-dialog-shell"
      :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.5rem', maxHeight: '70vh', overflow: 'auto' }"
      :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <div class="admin-dlg-header">
          <span class="admin-dlg-title">Správa používateľov ({{ allUsers.length }})</span>
          <button class="admin-dlg-close" @click="showUsersManagementDialog = false" type="button" aria-label="Zavrieť">×</button>
        </div>
      </template>

      <div class="admin-dlg-content">
        <!-- Search and Filter -->
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1 admin-dlg-field">
            <label class="admin-dlg-label">Vyhľadávanie</label>
            <InputText 
              v-model="userSearchQuery" 
              placeholder="Hľadať podľa mena alebo emailu..." 
              class="w-full"
            />
          </div>
          <div class="admin-dlg-field w-full sm:w-56">
            <label class="admin-dlg-label">Stav používateľa</label>
            <Dropdown
              v-model="userStatusFilter"
              :options="userStatusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter podľa stavu"
              class="w-full"
            />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loadingUsers" class="text-center py-8">
          <p class="mt-4 text-gray-400">Načítavam používateľov...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredUsers.length === 0" class="text-center py-8 text-gray-400">
          <p>Žiadni používatelia nenájdení</p>
        </div>

        <!-- Users List -->
        <div v-else class="space-y-2 max-h-96 overflow-y-auto">
          <div 
            v-for="user in filteredUsers" 
            :key="user.id"
            class="flex items-center justify-between admin-item-card p-4"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span :class="user.is_absolvent ? 'admin-text-muted opacity-60' : 'admin-text-strong'" class="font-semibold">{{ user.name }}</span>
                <span
                  v-if="user.is_absolvent"
                  class="px-1.5 py-0.5 border rounded text-xs" style="background: var(--color-surface); border-color: var(--color-border); color: var(--color-text-muted);"
                >
                  Absolvent
                </span>
                <span 
                  :class="getUserStatusClass(user.status || 'active')"
                  class="px-2 py-0.5 rounded text-xs font-semibold"
                >
                  {{ getUserStatusLabel(user.status || 'active') }}
                </span>
                <span v-if="user.student_type" class="px-2 py-0.5 bg-indigo-900 text-indigo-300 rounded text-xs">
                  {{ user.student_type === 'denny' ? 'Denný' : 'Externý' }}
                </span>
              </div>
              <div class="admin-text-subtle text-sm mt-1 truncate">{{ user.email }}</div>
              <div class="admin-text-muted text-xs mt-1">
                Registrovaný: {{ new Date(user.created_at).toLocaleDateString('sk-SK') }}
                <span v-if="!user.email_verified_at" class="text-yellow-400 ml-2">
                  Neoverený email
                </span>
              </div>
            </div>
            <div class="flex gap-2 ml-4">
              <Button 
                v-if="(user.status || 'active') === 'active'"
                label="Deaktivovať"
                class="p-button-sm p-button-danger p-button-outlined"
                @click="deactivateUser(user)"
                :loading="saving"
              />
              <Button 
                v-else
                label="Aktivovať"
                class="p-button-sm p-button-success"
                @click="activateUser(user)"
                :loading="saving"
              />
            </div>
          </div>
        </div>

        <!-- Info Note -->
        <div class="admin-dlg-note">
          <p>
            Neaktívni používatelia sa nemôžu prihlásiť do portálu. Pri deaktivácii budú okamžite odhlásení.
          </p>
        </div>
      </div>

      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.close')" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="showUsersManagementDialog = false" />
          <Button label="Obnoviť" class="admin-action-btn-styled px-4 py-2 rounded-xl" @click="loadAllUsers" :loading="loadingUsers" />
        </div>
      </template>
    </Dialog>

    <!-- Import Authorized Students Dialog -->
    <Dialog 
      v-model:visible="showImportDialog" 
      :modal="true"
      :closable="false"
      :draggable="false"
      class="w-11/12 md:w-3/4 lg:w-[720px] admin-dialog-shell"
      :contentStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', padding: '1.25rem 1.5rem 1.5rem' }"
      :headerStyle="{ backgroundColor: 'var(--color-bg)', color: 'var(--color-text)', borderBottom: '1px solid var(--color-border)' }"
    >
      <template #header>
        <div class="admin-dlg-header">
          <span class="admin-dlg-title">Importovať oprávnených študentov (CSV)</span>
          <button class="admin-dlg-close" @click="closeImportDialog" type="button" aria-label="Zavrieť">×</button>
        </div>
      </template>

      <div class="admin-dlg-content">
        <div class="admin-dlg-alert admin-dlg-alert-warning">
          <p><strong>POZNÁMKA:</strong> Táto funkcia je momentálne <strong>DEAKTIVOVANÁ</strong>.</p>
          <p>Môžete nahrať CSV pre prípravu, ale validácia pri registrácii zatiaľ neprebehne.</p>
          <p>Pre aktiváciu nastavte <code class="bg-gray-800 px-2 py-1 rounded">REQUIRE_AUTHORIZED_STUDENTS=true</code> v backend .env súbore.</p>
        </div>
        
        <div class="admin-dlg-field">
          <label class="admin-dlg-label">CSV Súbor</label>
          <input 
            type="file" 
            accept=".csv,.txt"
            @change="onCsvFileSelect"
            ref="csvFileInput"
            class="block w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer bg-gray-800 border border-gray-700 rounded-lg"
          />
          <small class="text-gray-400 block mt-2">
            <strong>Požadované stĺpce:</strong> student_id, name, email, student_type
          </small>
          <small class="text-gray-500 block mt-1">
            Príklad: <code class="bg-gray-800 px-1">1234567,Ján Novák,1234567@ucm.sk,denny</code>
          </small>
        </div>
        
        <div v-if="csvImportResult" class="space-y-2 animate-fade-in">
          <div :class="csvImportResult.errors?.length > 0 ? 'admin-dlg-alert admin-dlg-alert-warning' : 'admin-dlg-alert admin-dlg-alert-success'">
            <p>{{ csvImportResult.message }}</p>
          </div>
          
          <div v-if="csvImportResult.errors?.length > 0" class="admin-dlg-alert admin-dlg-alert-danger">
            <p class="font-semibold mb-2 text-red-300">Chyby počas importu:</p>
            <ul class="list-disc pl-5 text-sm text-red-200 space-y-1">
              <li v-for="(error, idx) in csvImportResult.errors.slice(0, 10)" :key="idx">
                {{ error }}
              </li>
              <li v-if="csvImportResult.errors.length > 10" class="text-gray-400 italic">
                ... a {{ csvImportResult.errors.length - 10 }} ďalších chýb
              </li>
            </ul>
          </div>
        </div>
      </div>
      
      <template #footer>
        <div class="admin-dlg-actions">
          <Button :label="t('common.cancel')" @click="closeImportDialog" class="admin-action-btn-styled px-4 py-2 rounded-xl" />
          <Button 
            label="Nahrať a importovať" 
            @click="importCsvFile" 
            :loading="importing"
            :disabled="!selectedCsvFile"
            class="admin-action-btn-styled px-4 py-2 rounded-xl"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Dialog from 'primevue/dialog'

const API_URL = import.meta.env.VITE_API_URL
const router = useRouter()
const toast = useToast()
const { t } = useI18n()

// State
const loading = ref(true)
const saving = ref(false)
const stats = ref({})
const teams = ref([])
const searchQuery = ref('')
const statusFilter = ref(null)

// Accordion state for team projects
const expandedTeamId = ref(null)
const teamProjects = ref({})
const loadingTeamProjects = ref({})

// Dialogs
const showDetailsDialog = ref(false)
const showEditTeamDialog = ref(false)
const showRejectTeamDialog = ref(false)
const showDeleteDialog = ref(false)
const showRemoveMemberDialog = ref(false)
const showChangeSMDialog = ref(false)
const showDeleteProjectDialog = ref(false)
const showRegisterUserDialog = ref(false)
const showCreateTeamDialog = ref(false)
const showAddAcademicYearDialog = ref(false)
const showMoveToTeamDialog = ref(false)
const showUsersManagementDialog = ref(false)
const showImportDialog = ref(false)
const allUsers = ref([])
const loadingUsers = ref(false)
const userSearchQuery = ref('')
const userStatusFilter = ref(null)
const selectedCsvFile = ref(null)
const csvImportResult = ref(null)
const importing = ref(false)
const csvFileInput = ref(null)
const config = ref({
  authorizationEnabled: false
})
const selectedTeam = ref(null)
const selectedTeamDetails = ref(null)
const selectedProject = ref(null)
const selectedMemberToMove = ref(null)
const moveToTeamForm = ref({
  to_team_id: null,
  occupation: null
})
const editForm = ref({ name: '', status: '' })
const rejectReason = ref('')
const selectedMemberToRemove = ref(null)
const selectedNewScrumMaster = ref(null)
const registerForm = ref({
  name: '',
  email: '',
  password: '',
  student_type: 'denny'
})
const createTeamForm = ref({
  name: '',
  academic_year_id: null,
  team_type: 'denny',
  status: 'active'
})
const createAcademicYearForm = ref({
  name: ''
})
const creatingAcademicYear = ref(false)
const academicYearSuggestion = ref('')

// Helper function to format occupation with proper diacritics and canonical labels
function formatOccupation(occupation) {
  if (!occupation) return null
  const normalized = occupation.toLowerCase().trim()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
  const occupationMap = {
    'programator': 'Programátor',
    'grafik 2d': 'Grafik 2D',
    'grafik 3d': 'Grafik 3D',
    'tester': 'Tester',
    'animator': 'Animátor'
  }
  return occupationMap[normalized] || null
}

// Options
const statusOptions = ref([
  { label: 'Všetky', value: null },
  { label: 'Aktívne', value: 'active' },
  { label: 'Čakajúce', value: 'pending' },
  { label: 'Pozastavené', value: 'suspended' },
])

const studentTypeOptions = ref([
  { label: 'Denný študent', value: 'denny' },
  { label: 'Externý študent', value: 'externy' },
])
const userStatusOptions = ref([
  { label: 'Všetci', value: null },
  { label: 'Aktívni', value: 'active' },
  { label: 'Neaktívni', value: 'inactive' },
])
const teamTypeOptions = ref([
  { label: 'Denný tím', value: 'denny' },
  { label: 'Externý tím', value: 'externy' },
  { label: 'Medzinárodný tím (SPE)', value: 'international' },
])
const teamStatusOptions = ref([
  { label: 'Aktívny', value: 'active' },
  { label: 'Čakajúci', value: 'pending' },
  { label: 'Pozastavený', value: 'suspended' },
])
const academicYears = ref([])
const academicYearOptions = computed(() => (academicYears.value || []).map(y => ({ label: y.name, value: y.id })))

// Computed - available teams for moving (exclude current team)
const availableTeamsForMove = computed(() => {
  if (!selectedTeam.value) return []
  return teams.value.filter(t => t.id !== selectedTeam.value.id)
})

const currentScrumMasterId = computed(() => selectedTeamDetails.value?.team?.scrum_master_id || null)

const scrumMasterCandidates = computed(() => {
  const members = selectedTeamDetails.value?.members || []
  if (!currentScrumMasterId.value) return members
  return members.filter(member => member.id !== currentScrumMasterId.value)
})

// Computed
const pendingTeams = computed(() => teams.value.filter(t => t.status === 'pending'))

const filteredTeams = computed(() => {
  let result = teams.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(t => t.name.toLowerCase().includes(query))
  }

  if (statusFilter.value) {
    result = result.filter(t => t.status === statusFilter.value)
  }

  return result
})

// Computed - filtered users for user management
const filteredUsers = computed(() => {
  let result = allUsers.value

  if (userSearchQuery.value) {
    const query = userSearchQuery.value.toLowerCase()
    result = result.filter(u => 
      u.name.toLowerCase().includes(query) || 
      u.email.toLowerCase().includes(query)
    )
  }

  if (userStatusFilter.value) {
    result = result.filter(u => (u.status || 'active') === userStatusFilter.value)
  }

  return result
})

// Methods
async function loadData() {
  loading.value = true
  const token = localStorage.getItem('access_token')

  try {
    // Load stats and teams in parallel
    const [statsRes, teamsRes] = await Promise.all([
      fetch(`${API_URL}/api/admin/stats`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        cache: 'no-store'
      }),
      fetch(`${API_URL}/api/admin/teams`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        cache: 'no-store'
      })
    ])

    if (statsRes.ok) {
      const statsData = await statsRes.json()
      stats.value = statsData
      console.log('✅ Stats loaded:', statsData)
    } else {
      const errorData = await statsRes.json().catch(() => ({}))
      console.error('❌ Stats error:', statsRes.status, errorData)
      toast.add({ 
        severity: 'error', 
        summary: 'Chyba štatistík', 
        detail: errorData.error || errorData.message || `HTTP ${statsRes.status}`, 
        life: 5000 
      })
    }

    if (teamsRes.ok) {
      const data = await teamsRes.json()
      teams.value = data.teams || []
      console.log('✅ Teams loaded:', data.teams?.length || 0)
    } else if (teamsRes.status === 403) {
      toast.add({ severity: 'error', summary: t('admin.access_denied'), detail: 'Nemáte oprávnenie na prístup k admin panelu', life: 5000 })
      router.push('/')
    } else {
      const errorData = await teamsRes.json().catch(() => ({}))
      console.error('❌ Teams error:', teamsRes.status, errorData)
      toast.add({ 
        severity: 'error', 
        summary: 'Chyba načítania tímov', 
        detail: errorData.error || errorData.message || `HTTP ${teamsRes.status}`, 
        life: 5000 
      })
    }
  } catch (err) {
    console.error('Error loading admin data:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Nepodarilo sa načítať dáta', life: 4000 })
  } finally {
    loading.value = false
  }
}

function filterTeams() {
  // Filtering is handled by computed property
}

async function toggleTeamProjects(team) {
  if (expandedTeamId.value === team.id) {
    // Collapse if already expanded
    expandedTeamId.value = null
  } else {
    // Expand and load projects
    expandedTeamId.value = team.id
    
    // If projects not already loaded for this team, fetch them
    if (!teamProjects.value[team.id]) {
      loadingTeamProjects.value[team.id] = true
      const token = localStorage.getItem('access_token')
      
      try {
        const res = await fetch(`${API_URL}/api/admin/teams/${team.id}/projects`, {
          headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        })
        
        if (res.ok) {
          const data = await res.json()
          teamProjects.value[team.id] = Array.isArray(data) ? data : (data.projects || data.data || [])
        } else {
          teamProjects.value[team.id] = []
          toast.add({ severity: 'warn', summary: t('common.error'), detail: 'Nepodarilo sa načítať projekty tímu.', life: 4000 })
        }
      } catch (err) {
        teamProjects.value[team.id] = []
        console.error('Error loading team projects:', err)
        toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
      } finally {
        loadingTeamProjects.value[team.id] = false
      }
    }
  }
}

function getStatusClass(status) {
  switch (status) {
    case 'active': return 'bg-green-900 text-green-300 border border-green-700'
    case 'pending': return 'bg-yellow-900 text-yellow-300 border border-yellow-700'
    case 'suspended': return 'bg-red-900 text-red-300 border border-red-700'
    default: return 'bg-gray-700 text-gray-300 border border-gray-600'
  }
}

function getStatusLabel(status) {
  switch (status) {
    case 'active': return 'Aktívny'
    case 'pending': return 'Čakajúci'
    case 'suspended': return 'Pozastavený'
    default: return status || 'Aktívny'
  }
}

async function showTeamDetails(team) {
  selectedTeam.value = team
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${team.id}`, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    if (res.ok) {
      selectedTeamDetails.value = await res.json()
      showDetailsDialog.value = true
    } else {
      toast.add({ severity: 'error', summary: t('common.error'), detail: 'Nepodarilo sa načítať detail tímu', life: 4000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  }
}

function showEditDialog(team) {
  selectedTeam.value = team
  editForm.value = { name: team.name, status: team.status || 'active' }
  showEditTeamDialog.value = true
}

async function saveTeamEdit() {
  if (!editForm.value.name.trim()) {
    toast.add({ severity: 'warn', summary: 'Upozornenie', detail: 'Názov tímu je povinný', life: 3000 })
    return
  }

  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${selectedTeam.value.id}`, {
      method: 'PUT',
      headers: { 
        'Authorization': `Bearer ${token}`, 
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(editForm.value)
    })

    if (res.ok) {
      toast.add({ severity: 'success', summary: t('common.success'), detail: 'Tím bol aktualizovaný', life: 3000 })
      showEditTeamDialog.value = false
      loadData()
    } else {
      const data = await res.json()
      toast.add({ severity: 'error', summary: t('common.error'), detail: data.error || 'Nepodarilo sa aktualizovať tím', life: 4000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

function confirmDeleteTeam(team) {
  selectedTeam.value = team
  showDeleteDialog.value = true
}

async function deleteTeam() {
  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${selectedTeam.value.id}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    const data = await res.json().catch(() => ({}))

    if (res.ok) {
      console.log('✅ Team deleted successfully:', data)
      toast.add({ severity: 'success', summary: t('common.success'), detail: data.message || 'Tím bol zmazaný', life: 3000 })
      showDeleteDialog.value = false
      selectedTeam.value = null
      // Reload data to refresh the list
      await loadData()
    } else {
      console.error('❌ Delete failed:', res.status, data)
      toast.add({ 
        severity: 'error', 
        summary: t('common.error'), 
        detail: data.error || data.message || `Nepodarilo sa zmazať tím (HTTP ${res.status})`, 
        life: 5000 
      })
    }
  } catch (err) {
    console.error('❌ Delete error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

async function approveTeam(team) {
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${team.id}/approve`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    if (res.ok) {
      toast.add({ severity: 'success', summary: t('common.success'), detail: `Tím '${team.name}' bol schválený`, life: 4000 })
      loadData()
    } else {
      const data = await res.json()
      toast.add({ severity: 'error', summary: t('common.error'), detail: data.error || data.message || 'Nepodarilo sa schváliť tím', life: 4000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  }
}

function showRejectDialog(team) {
  selectedTeam.value = team
  rejectReason.value = ''
  showRejectTeamDialog.value = true
}

async function rejectTeam() {
  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/teams/${selectedTeam.value.id}/reject`, {
      method: 'POST',
      headers: { 
        'Authorization': `Bearer ${token}`, 
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ reason: rejectReason.value })
    })

    if (res.ok) {
      toast.add({ severity: 'success', summary: t('common.success'), detail: `Tím '${selectedTeam.value.name}' bol zamietnutý`, life: 4000 })
      showRejectTeamDialog.value = false
      loadData()
    } else {
      const data = await res.json()
      toast.add({ severity: 'error', summary: t('common.error'), detail: data.error || data.message || 'Nepodarilo sa zamietnuť tím', life: 4000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

// Member management functions
function confirmRemoveMember(member) {
  selectedMemberToRemove.value = member
  showRemoveMemberDialog.value = true
}

async function removeMemberFromTeam() {
  if (!selectedMemberToRemove.value || !selectedTeam.value) return

  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(
      `${API_URL}/api/admin/teams/${selectedTeam.value.id}/members/${selectedMemberToRemove.value.id}`,
      {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
      }
    )

    const data = await res.json()

    if (res.ok) {
      toast.add({ 
        severity: 'success', 
        summary: t('common.success'), 
        detail: data.message || 'Člen bol odstránený z tímu', 
        life: 4000 
      })
      showRemoveMemberDialog.value = false
      selectedMemberToRemove.value = null
      
      // Refresh team details
      await showTeamDetails(selectedTeam.value)
      // Refresh main data
      await loadData()
    } else {
      toast.add({ 
        severity: 'error', 
        summary: t('common.error'), 
        detail: data.error || data.message || 'Nepodarilo sa odstrániť člena', 
        life: 4000 
      })
    }
  } catch (err) {
    console.error('Remove member error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

function openMoveToTeamDialog(member) {
  selectedMemberToMove.value = member
  moveToTeamForm.value = {
    to_team_id: null,
    occupation: member.occupation || null
  }
  showMoveToTeamDialog.value = true
}

function closeMoveToTeamDialog() {
  showMoveToTeamDialog.value = false
  selectedMemberToMove.value = null
  moveToTeamForm.value = {
    to_team_id: null,
    occupation: null
  }
}

async function moveUserToTeam() {
  if (!selectedMemberToMove.value || !selectedTeam.value || !moveToTeamForm.value.to_team_id) {
    toast.add({ severity: 'warn', summary: 'Chýbajúce údaje', detail: 'Vyberte cieľový tím', life: 3000 })
    return
  }

  if (!moveToTeamForm.value.occupation) {
    toast.add({ severity: 'warn', summary: 'Chýbajúce údaje', detail: 'Vyberte povolanie', life: 3000 })
    return
  }

  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(
      `${API_URL}/api/admin/users/${selectedMemberToMove.value.id}/move-team`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          from_team_id: selectedTeam.value.id,
          to_team_id: moveToTeamForm.value.to_team_id,
          occupation: moveToTeamForm.value.occupation
        })
      }
    )

    const data = await res.json()

    if (res.ok) {
      const targetTeam = teams.value.find(t => t.id === moveToTeamForm.value.to_team_id)
      toast.add({
        severity: 'success',
        summary: t('common.success'),
        detail: `${selectedMemberToMove.value.name} bol presunul do tímu ${targetTeam?.name || 'neznámeho'}`,
        life: 4000
      })
      closeMoveToTeamDialog()
      
      // Refresh team details
      await showTeamDetails(selectedTeam.value)
      // Refresh main data
      await loadData()
    } else {
      toast.add({
        severity: 'error',
        summary: t('common.error'),
        detail: data.error || data.message || 'Nepodarilo sa presunúť používateľa',
        life: 5000
      })
    }
  } catch (err) {
    console.error('Move user error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

function confirmChangeScrumMaster() {
  if (!selectedNewScrumMaster.value) return

  if (selectedNewScrumMaster.value === currentScrumMasterId.value) {
    toast.add({
      severity: 'warn',
      summary: t('common.warning') || 'Upozornenie',
      detail: 'Vybraný používateľ je už Scrum Masterom tímu.',
      life: 3500
    })
    selectedNewScrumMaster.value = null
    return
  }

  showChangeSMDialog.value = true
}

function getSelectedNewSMName() {
  if (!selectedNewScrumMaster.value || !selectedTeamDetails.value) return ''
  const member = selectedTeamDetails.value.members.find(m => m.id === selectedNewScrumMaster.value)
  return member?.name || ''
}

async function changeScrumMasterInTeam() {
  if (!selectedNewScrumMaster.value || !selectedTeam.value) return

  if (selectedNewScrumMaster.value === currentScrumMasterId.value) {
    toast.add({
      severity: 'warn',
      summary: t('common.warning') || 'Upozornenie',
      detail: 'Vybraný používateľ je už Scrum Masterom tímu.',
      life: 3500
    })
    selectedNewScrumMaster.value = null
    showChangeSMDialog.value = false
    return
  }

  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(
      `${API_URL}/api/admin/teams/${selectedTeam.value.id}/scrum-master`,
      {
        method: 'POST',
        headers: { 
          'Authorization': `Bearer ${token}`, 
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ user_id: selectedNewScrumMaster.value })
      }
    )

    const data = await res.json()

    if (res.ok) {
      toast.add({ 
        severity: 'success', 
        summary: t('common.success'), 
        detail: data.message || 'Scrum Master bol zmenený', 
        life: 4000 
      })
      showChangeSMDialog.value = false
      selectedNewScrumMaster.value = null
      
      // Refresh team details
      await showTeamDetails(selectedTeam.value)
      // Refresh main data
      await loadData()
    } else {
      toast.add({ 
        severity: 'error', 
        summary: t('common.error'), 
        detail: data.error || data.message || 'Nepodarilo sa zmeniť Scrum Mastera', 
        life: 4000 
      })
    }
  } catch (err) {
    console.error('Change SM error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

// Project management functions
function viewProjectDetail(projectId) {
  router.push(`/project/${projectId}`)
}

function editProject(projectId) {
  router.push(`/edit-project/${projectId}`)
}

function confirmDeleteProject(project) {
  selectedProject.value = project
  showDeleteProjectDialog.value = true
}

async function deleteProject() {
  if (!selectedProject.value) return

  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(
      `${API_URL}/api/admin/projects/${selectedProject.value.id}`,
      {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
      }
    )

    const data = await res.json().catch(() => ({}))

    if (res.ok) {
      toast.add({ 
        severity: 'success', 
        summary: t('common.success'), 
        detail: data.message || 'Projekt bol zmazaný', 
        life: 4000 
      })
      showDeleteProjectDialog.value = false
      selectedProject.value = null
      
      // Refresh the expanded team's projects
      if (expandedTeamId.value) {
        const team = teams.value.find(t => t.id === expandedTeamId.value)
        if (team) {
          await toggleTeamProjects(team)
          await toggleTeamProjects(team) // Re-expand to refresh
        }
      }
      
      // Refresh main data
      await loadData()
    } else {
      toast.add({ 
        severity: 'error', 
        summary: t('common.error'), 
        detail: data.error || data.message || 'Nepodarilo sa zmazať projekt', 
        life: 4000 
      })
    }
  } catch (err) {
    console.error('Delete project error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

// User registration functions
function closeRegisterDialog() {
  showRegisterUserDialog.value = false
  registerForm.value = {
    name: '',
    email: '',
    password: '',
    student_type: 'denny'
  }
}

// User management functions
async function openUsersManagementDialog() {
  showUsersManagementDialog.value = true
  await loadAllUsers()
}

async function loadAllUsers() {
  loadingUsers.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/users`, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
      cache: 'no-store'
    })

    if (res.ok) {
      const data = await res.json()
      allUsers.value = data.users || []
    } else {
      toast.add({ severity: 'error', summary: t('common.error'), detail: 'Nepodarilo sa načítať používateľov', life: 4000 })
    }
  } catch (err) {
    console.error('Error loading users:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    loadingUsers.value = false
  }
}

async function deactivateUser(user) {
  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/users/${user.id}/deactivate`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    const data = await res.json()

    if (res.ok) {
      toast.add({ severity: 'success', summary: t('common.success'), detail: data.message || 'Používateľ bol deaktivovaný', life: 4000 })
      // Update user in local list
      const idx = allUsers.value.findIndex(u => u.id === user.id)
      if (idx !== -1) {
        allUsers.value[idx].status = 'inactive'
      }
    } else {
      toast.add({ severity: 'error', summary: t('common.error'), detail: data.error || 'Nepodarilo sa deaktivovať používateľa', life: 4000 })
    }
  } catch (err) {
    console.error('Deactivate user error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

async function activateUser(user) {
  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/users/${user.id}/activate`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    const data = await res.json()

    if (res.ok) {
      toast.add({ severity: 'success', summary: t('common.success'), detail: data.message || 'Používateľ bol aktivovaný', life: 4000 })
      // Update user in local list
      const idx = allUsers.value.findIndex(u => u.id === user.id)
      if (idx !== -1) {
        allUsers.value[idx].status = 'active'
      }
    } else {
      toast.add({ severity: 'error', summary: t('common.error'), detail: data.error || 'Nepodarilo sa aktivovať používateľa', life: 4000 })
    }
  } catch (err) {
    console.error('Activate user error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

function getUserStatusClass(status) {
  return status === 'inactive' 
    ? 'bg-red-900 text-red-300 border border-red-700' 
    : 'bg-green-900 text-green-300 border border-green-700'
}

function getUserStatusLabel(status) {
  return status === 'inactive' ? 'Neaktívny' : 'Aktívny'
}

// Team member status management (from team details dialog)
async function deactivateTeamMember(member) {
  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/users/${member.id}/deactivate`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    const data = await res.json()

    if (res.ok) {
      toast.add({ severity: 'success', summary: t('common.success'), detail: data.message || 'Používateľ bol deaktivovaný', life: 4000 })
      // Update member in team details
      const idx = selectedTeamDetails.value.members.findIndex(m => m.id === member.id)
      if (idx !== -1) {
        selectedTeamDetails.value.members[idx].status = 'inactive'
      }
      // Also update in allUsers if loaded
      const userIdx = allUsers.value.findIndex(u => u.id === member.id)
      if (userIdx !== -1) {
        allUsers.value[userIdx].status = 'inactive'
      }
    } else {
      toast.add({ severity: 'error', summary: t('common.error'), detail: data.error || 'Nepodarilo sa deaktivovať používateľa', life: 4000 })
    }
  } catch (err) {
    console.error('Deactivate team member error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

async function activateTeamMember(member) {
  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/users/${member.id}/activate`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    })

    const data = await res.json()

    if (res.ok) {
      toast.add({ severity: 'success', summary: t('common.success'), detail: data.message || 'Používateľ bol aktivovaný', life: 4000 })
      // Update member in team details
      const idx = selectedTeamDetails.value.members.findIndex(m => m.id === member.id)
      if (idx !== -1) {
        selectedTeamDetails.value.members[idx].status = 'active'
      }
      // Also update in allUsers if loaded
      const userIdx = allUsers.value.findIndex(u => u.id === member.id)
      if (userIdx !== -1) {
        allUsers.value[userIdx].status = 'active'
      }
    } else {
      toast.add({ severity: 'error', summary: t('common.error'), detail: data.error || 'Nepodarilo sa aktivovať používateľa', life: 4000 })
    }
  } catch (err) {
    console.error('Activate team member error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

function parseAcademicYearName(name) {
  if (!name || typeof name !== 'string') return null
  const match = name.match(/^(\d{4})\/(\d{4})$/)
  if (!match) return null
  const start = Number(match[1])
  const end = Number(match[2])
  if (!Number.isFinite(start) || !Number.isFinite(end)) return null
  if (end !== start + 1) return null
  return { start, end }
}

function isValidAcademicYearName(name) {
  return !!parseAcademicYearName(name)
}

function getSafeAcademicYearSuggestion() {
  const parsed = (academicYears.value || [])
    .map(y => parseAcademicYearName(y?.name))
    .filter(Boolean)

  if (parsed.length > 0) {
    const maxEnd = Math.max(...parsed.map(p => p.end))
    return `${maxEnd}/${maxEnd + 1}`
  }

  const currentYear = new Date().getFullYear()
  return `${currentYear}/${currentYear + 1}`
}

async function loadAcademicYears() {
  const token = localStorage.getItem('access_token')
  try {
    const res = await fetch(`${API_URL}/api/academic-years`, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
      cache: 'no-store'
    })
    const data = await res.json().catch(() => ([]))
    academicYears.value = Array.isArray(data) ? data : []
  } catch (err) {
    academicYears.value = []
  }
}

async function openCreateTeamDialog() {
  showCreateTeamDialog.value = true
  if (!academicYears.value || academicYears.value.length === 0) {
    await loadAcademicYears()
  }
}

async function openAddAcademicYearDialog() {
  await loadAcademicYears()
  academicYearSuggestion.value = getSafeAcademicYearSuggestion()
  createAcademicYearForm.value = { name: academicYearSuggestion.value }
  showAddAcademicYearDialog.value = true
}

function closeAddAcademicYearDialog() {
  showAddAcademicYearDialog.value = false
  createAcademicYearForm.value = { name: '' }
  academicYearSuggestion.value = ''
}

function closeCreateTeamDialog() {
  showCreateTeamDialog.value = false
  createTeamForm.value = {
    name: '',
    academic_year_id: null,
    team_type: 'denny',
    status: 'active'
  }
}

async function createTeam() {
  // Validate form
  if (!createTeamForm.value.name || !createTeamForm.value.academic_year_id) {
    toast.add({ severity: 'warn', summary: 'Chýbajúce údaje', detail: 'Vyplňte názov a akademický rok', life: 4000 })
    return
  }

  saving.value = true
  const token = localStorage.getItem('access_token')
  try {
    const res = await fetch(`${API_URL}/api/admin/teams`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(createTeamForm.value)
    })
    const data = await res.json().catch(() => ({}))
    if (res.ok) {
      toast.add({ severity: 'success', summary: t('common.success'), detail: data.message || 'Tím vytvorený', life: 4000 })
      closeCreateTeamDialog()
      await loadData()
    } else {
      toast.add({ severity: 'error', summary: t('common.error'), detail: data.error || data.message || 'Nepodarilo sa vytvoriť tím', life: 5000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

async function createAcademicYear() {
  const name = (createAcademicYearForm.value.name || '').trim()

  if (!isValidAcademicYearName(name)) {
    toast.add({
      severity: 'warn',
      summary: 'Neplatný formát',
      detail: 'Zadajte akademický rok vo formáte YYYY/YYYY (napr. 2026/2027).',
      life: 4000
    })
    return
  }

  creatingAcademicYear.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/academic-years`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ name })
    })

    const data = await res.json().catch(() => ({}))

    if (res.ok) {
      toast.add({ severity: 'success', summary: t('common.success'), detail: data.message || 'Akademický rok bol pridaný', life: 4000 })
      closeAddAcademicYearDialog()
      await loadAcademicYears()
    } else {
      toast.add({ severity: 'error', summary: t('common.error'), detail: data.error || data.message || 'Nepodarilo sa pridať akademický rok', life: 5000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    creatingAcademicYear.value = false
  }
}

async function registerUser() {
  // Validate required fields
  if (!registerForm.value.name || !registerForm.value.email || !registerForm.value.password) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Chýbajúce údaje', 
      detail: 'Vyplňte všetky povinné polia', 
      life: 4000 
    })
    return
  }

  // Validate email format
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(registerForm.value.email)) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Neplatný email', 
      detail: 'Zadajte platný email', 
      life: 4000 
    })
    return
  }

  // Validate password length
  if (registerForm.value.password.length < 8) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Slabé heslo', 
      detail: 'Heslo musí mať aspoň 8 znakov', 
      life: 4000 
    })
    return
  }

  saving.value = true
  const token = localStorage.getItem('access_token')

  try {
    const res = await fetch(`${API_URL}/api/admin/users`, {
      method: 'POST',
      headers: { 
        'Authorization': `Bearer ${token}`, 
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(registerForm.value)
    })

    const data = await res.json()

    if (res.ok) {
      toast.add({ 
        severity: 'success', 
        summary: t('common.success'), 
        detail: data.message || 'Používateľ bol úspešne registrovaný', 
        life: 4000 
      })
      closeRegisterDialog()
      // Optimistic stats update with cache-safe refresh
      const previousTotal = stats.value?.total_users || 0
      await loadData()
      if ((stats.value?.total_users || 0) === previousTotal) {
        stats.value.total_users = previousTotal + 1
      }
    } else {
      toast.add({ 
        severity: 'error', 
        summary: t('common.error'), 
        detail: data.error || data.message || 'Nepodarilo sa registrovať používateľa', 
        life: 4000 
      })
    }
  } catch (err) {
    console.error('Register user error:', err)
    toast.add({ severity: 'error', summary: t('common.error'), detail: 'Chyba pri komunikácii so serverom', life: 4000 })
  } finally {
    saving.value = false
  }
}

// CSV Import Functions
function onCsvFileSelect(event) {
  selectedCsvFile.value = event.target.files[0]
  csvImportResult.value = null
}

function closeImportDialog() {
  showImportDialog.value = false
  selectedCsvFile.value = null
  csvImportResult.value = null
  if (csvFileInput.value) {
    csvFileInput.value.value = ''
  }
}

async function importCsvFile() {
  if (!selectedCsvFile.value) return
  
  importing.value = true
  csvImportResult.value = null
  
  try {
    const token = localStorage.getItem('access_token')
    const formData = new FormData()
    formData.append('csv_file', selectedCsvFile.value)
    
    const response = await fetch(`${API_URL}/api/admin/authorized-students/import`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`
      },
      body: formData
    })
    
    const data = await response.json()
    
    if (response.ok) {
      csvImportResult.value = data
      toast.add({ 
        severity: 'success', 
        summary: 'Import dokončený', 
        detail: data.message, 
        life: 5000 
      })
      
      // Clear file selection after successful import
      selectedCsvFile.value = null
      if (csvFileInput.value) {
        csvFileInput.value.value = ''
      }
    } else {
      toast.add({ 
        severity: 'error', 
        summary: t('common.error'), 
        detail: data.error || 'Import zlyhal', 
        life: 5000 
      })
      csvImportResult.value = data
    }
  } catch (error) {
    console.error('Import error:', error)
    toast.add({ 
      severity: 'error', 
      summary: t('common.error'), 
      detail: 'Chyba pripojenia', 
      life: 3000 
    })
  } finally {
    importing.value = false
  }
}

// Check if user is admin on mount
onMounted(async () => {
  const token = localStorage.getItem('access_token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  if (!token || user.role !== 'admin') {
    toast.add({ severity: 'error', summary: t('admin.access_denied'), detail: 'Táto stránka je dostupná len pre administrátorov', life: 5000 })
    router.push('/')
    return
  }

  loadData()
  
  // Load config to check if authorization is enabled
  try {
    const configResponse = await fetch(`${API_URL}/api/admin/config`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    if (configResponse.ok) {
      config.value = await configResponse.json()
    }
  } catch (error) {
    console.log('Could not load config:', error)
  }
})
</script>

<style scoped>
/* Dialog Item Styling */
.admin-item-card {
  background-color: var(--color-elevated);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  transition: all 0.2s ease;
}
.admin-item-card:hover {
  background-color: var(--color-hover-bg-strong);
  border-color: var(--color-border-strong);
}
.admin-text-strong { color: var(--color-text-strong) !important; }
.admin-text-muted { color: var(--color-text-muted) !important; }
.admin-text-subtle { color: var(--color-text-subtle) !important; }

/* Dashboard Adaptive Wrappers */
.admin-surface-card {
  background-color: rgba(var(--color-surface-rgb), 0.5);
  border: 1px solid rgba(var(--color-border-rgb), 0.5);
}
.admin-elevated-card {
  background: var(--color-elevated);
  border: 1px solid var(--color-border);
}
.admin-table-head {
  background-color: rgba(var(--color-surface-rgb), 0.8);
  border-bottom: 2px solid var(--color-border);
}

/* ═══════════════════════════════════════════════════════════ */
/* PAGE + THEME OVERRIDES                                     */
/* ═══════════════════════════════════════════════════════════ */
.steam-page {
  max-width: 1280px;
  margin: 0 auto;
  padding: 24px 32px 48px;
}

.admin-top-shell {
  background: linear-gradient(180deg, rgba(var(--color-surface-rgb), 0.92), rgba(var(--color-surface-rgb), 0.7));
  border: 1px solid var(--color-border);
  border-radius: 14px;
  padding: 18px;
}

.admin-heading {
  letter-spacing: -0.02em;
  line-height: 1.15;
}

.admin-refresh-btn {
  min-width: 130px;
}

.admin-actions-row {
  align-items: stretch;
}

.admin-action-btn {
  min-height: 38px;
}

.admin-action-btn-styled {
  background: transparent !important;
  border: 1px solid var(--color-border) !important;
  color: var(--color-text) !important;
  transition: all 0.2s ease;
  box-shadow: none !important;
  outline: none !important;
}

.admin-action-btn-styled:not(:disabled):hover {
  background: var(--color-hover-bg-soft) !important;
  border-color: var(--color-border-strong) !important;
  color: var(--color-text-strong) !important;
}

.admin-action-btn-styled:focus,
.admin-action-btn-styled:focus-visible,
.admin-action-btn-styled:enabled:focus {
  outline: none !important;
  box-shadow: none !important;
}

.admin-action-btn-styled:disabled {
  background: transparent !important;
  border-color: rgba(var(--color-border-rgb), 0.6) !important;
  color: rgba(var(--color-text-muted-rgb), 0.75) !important;
  cursor: not-allowed;
}

.admin-stat-card {
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.admin-pending-shell {
  backdrop-filter: blur(4px);
}

.admin-filter-shell {
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
}

.admin-attr {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0.75rem;
  border-radius: 0.5rem;
  border: 2px solid;
  transition: background-color 0.12s ease, border-color 0.12s ease;
}

.admin-attr-ok {
  background: rgba(var(--color-accent-rgb), 0.12);
  border-color: rgba(var(--color-accent-rgb), 0.45);
}

.admin-attr-ok:hover {
  background: rgba(var(--color-accent-rgb), 0.16);
}

.admin-attr-missing {
  background: rgba(var(--color-danger-rgb), 0.12);
  border-color: rgba(var(--color-danger-rgb), 0.45);
}

.admin-attr-missing:hover {
  background: rgba(var(--color-danger-rgb), 0.16);
}

.admin-attr-neutral {
  background: rgba(var(--color-border-strong-rgb), 0.16);
  border-color: rgba(var(--color-border-strong-rgb), 0.45);
}

.admin-attr-neutral:hover {
  background: rgba(var(--color-border-strong-rgb), 0.22);
}

/* Top-action dialog unification */
.admin-dialog-shell {
  border-radius: 4px;
}

.admin-dlg-header {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.admin-dlg-title {
  font-size: 1.03rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--color-text-strong);
  text-align: center;
}

.admin-dlg-close {
  position: absolute;
  right: -8px;
  top: 50%;
  transform: translateY(-50%);
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 3px;
  background: transparent;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: 1.2rem;
  line-height: 1;
  transition: all 0.12s;
}

.admin-dlg-close:hover {
  color: var(--color-text-strong);
  background: var(--color-hover-bg-soft);
}

.admin-dlg-content {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.admin-dlg-field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.admin-dlg-label {
  font-size: 0.78rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-muted);
}

.admin-dlg-note {
  padding: 10px 12px;
  border-radius: 4px;
  border: 1px solid rgba(var(--color-accent-rgb), 0.25);
  background: rgba(var(--color-accent-rgb), 0.08);
  color: var(--color-text);
  font-size: 0.85rem;
}

.admin-dlg-alert {
  padding: 10px 12px;
  border-radius: 4px;
  border: 1px solid var(--color-border);
  font-size: 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.admin-dlg-alert-warning {
  border-color: rgba(var(--color-warning-rgb), 0.4);
  background: rgba(var(--color-warning-rgb), 0.1);
  color: var(--color-warning);
}

.admin-dlg-alert-success {
  border-color: rgba(var(--color-accent-rgb), 0.4);
  background: rgba(var(--color-accent-rgb), 0.1);
  color: var(--color-accent);
}

.admin-dlg-alert-danger {
  border-color: rgba(var(--color-danger-rgb), 0.4);
  background: rgba(var(--color-danger-rgb), 0.1);
  color: var(--color-danger);
}

.admin-dlg-actions {
  width: 100%;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  padding-top: 8px;
}

.admin-dialog-shell :deep(.p-inputtext),
.admin-dialog-shell :deep(.p-dropdown),
.admin-dialog-shell :deep(.p-select),
.admin-dialog-shell :deep(textarea),
.admin-dialog-shell :deep(input[type='text']),
.admin-dialog-shell :deep(input[type='email']),
.admin-dialog-shell :deep(input[type='password']) {
  background: var(--color-elevated) !important;
  border-color: var(--color-border) !important;
  color: var(--color-text) !important;
}

.admin-dialog-shell :deep(.p-dropdown:hover),
.admin-dialog-shell :deep(.p-select:hover),
.admin-dialog-shell :deep(.p-inputtext:enabled:hover) {
  border-color: var(--color-border-strong) !important;
}

.admin-dialog-shell :deep(.p-dropdown.p-focus),
.admin-dialog-shell :deep(.p-select.p-focus),
.admin-dialog-shell :deep(.p-inputtext:enabled:focus),
.admin-dialog-shell :deep(textarea:focus) {
  border-color: var(--color-accent) !important;
  box-shadow: none !important;
}

.admin-dialog-shell :deep(.admin-dlg-actions .p-button:not(.p-button-text)) {
  background: transparent !important;
  border: 1px solid var(--color-border) !important;
  color: var(--color-text) !important;
  box-shadow: none !important;
  border-radius: 6px !important;
  transition: all 0.2s ease;
}

.admin-dialog-shell :deep(.admin-dlg-actions .p-button:not(.p-button-text):not(:disabled):hover) {
  background: var(--color-hover-bg-soft) !important;
  border-color: var(--color-border-strong) !important;
  color: var(--color-text-strong) !important;
}

.admin-dialog-shell :deep(.admin-dlg-actions .p-button:focus),
.admin-dialog-shell :deep(.admin-dlg-actions .p-button:focus-visible),
.admin-dialog-shell :deep(.admin-dlg-actions .p-button:enabled:focus),
.admin-dialog-shell :deep(.admin-dlg-actions .p-button:enabled:focus-visible) {
  outline: none !important;
  box-shadow: none !important;
}

.admin-dialog-shell :deep(.admin-dlg-actions .p-button:not(.p-button-text):disabled) {
  background: transparent !important;
  border-color: rgba(var(--color-border-rgb), 0.6) !important;
  color: rgba(var(--color-text-muted-rgb), 0.75) !important;
}

.admin-dialog-shell :deep(.p-button.p-button-text) {
  background: transparent !important;
  color: var(--color-text-muted) !important;
  border-radius: 6px !important;
  transition: all 0.2s ease;
}

.admin-dialog-shell :deep(.p-button.p-button-text:hover) {
  background: var(--color-hover-bg-soft) !important;
  color: var(--color-text-strong) !important;
}

.admin-dialog-shell :deep(.p-button.p-button-text.p-button-danger:hover) {
  background: rgba(var(--color-danger-rgb), 0.1) !important;
  color: var(--color-danger) !important;
}

@media (max-width: 640px) {
  .admin-dlg-actions {
    flex-direction: column;
  }
}

/* Tailwind & PrimeVue overrides now live in main.css (global) */

@media (max-width: 768px) {
  .steam-page { padding: 16px 16px 40px; }

  .admin-top-shell {
    border-radius: 10px;
    padding: 14px;
  }

  .admin-heading {
    font-size: 1.65rem;
  }

  .admin-refresh-btn {
    width: 100%;
  }

  .admin-action-btn {
    flex: 1 1 calc(50% - 4px);
    justify-content: center;
  }

  .admin-pending-actions :deep(.p-button) {
    flex: 1;
  }
}

@media (max-width: 540px) {
  .admin-action-btn {
    flex: 1 1 100%;
  }
}
</style>

