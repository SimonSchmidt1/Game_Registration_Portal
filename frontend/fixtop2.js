const fs = require('fs');
let c = fs.readFileSync('src/views/AdminView.vue', 'utf8');

const sIdx = c.indexOf('    <!-- Dashboard Wrapping Container -->');
const eIdx = c.indexOf('      <!-- Master Teams View Data Table -->');

if (sIdx > -1 && eIdx > -1) {
  const goodText = \    <!-- Dashboard Wrapping Container -->
    <div class="flex flex-col gap-6 mb-8">
      
      <!-- Master Dashboard Wrapper -->
      <div class="bg-gray-900/40 backdrop-blur-sm rounded-[2rem] border border-gray-700/50 shadow-2xl p-6 sm:p-8 flex flex-col gap-6 sm:gap-8">
        
        <!-- Action Buttons -->
        <div class="flex flex-wrap justify-center gap-3 w-full bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-5 border border-gray-700 shadow-lg">
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
    </div>

\;

  const badStr = c.substring(sIdx, eIdx);
  c = c.replace(badStr, goodText);
  fs.writeFileSync('src/views/AdminView.vue', c, 'utf8');
  console.log('Successfully replaced text module.');
} else {
  console.log('Could not find boundaries.', sIdx, eIdx);
}
