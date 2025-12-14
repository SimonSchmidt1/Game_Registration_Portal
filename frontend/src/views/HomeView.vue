<template>
  <div class="min-h-full p-4 sm:p-6 lg:p-8">
    <div class="max-w-6xl mx-auto">

    <Toast />

    <div class="flex flex-col gap-4 mb-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-3xl font-bold bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
          Zoznam Registrovaných Projektov 
        </h2>
        
        <div v-if="token && !isAdmin" class="flex gap-3 flex-wrap items-center">
          <!-- TLAČIDLO: Info o tíme (viditeľné len ak je používateľ v tíme) -->
          <button 
            v-if="hasTeam"
            class="btn-secondary"
            @click="showTeamStatusDialog = true" 
          >
            Moje Tímy
          </button>
          <!-- Tlačidlá -->
          <button 
            class="btn-secondary"
            @click="showJoinTeam = true" 
          >
            Pripojiť sa k tímu
          </button>

          <button 
            class="btn-primary"
            @click="showCreateTeam = true" 
          >
            Vytvoriť Tím
          </button>
        </div>
      </div>

      <!-- Team Selector (minimalistic) -->
      <div v-if="hasTeam && teams.length > 0 && !isAdmin" class="glass-card p-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
          <div class="flex items-center gap-3 flex-1">
            <div class="flex-1">
              <label class="text-sm font-semibold text-slate-300 mb-2 block">Aktívny Tím:</label>
              <Dropdown
                v-model="selectedTeam"
                :options="teams"
                optionLabel="name"
                placeholder="Vyberte tím"
                class="w-full sm:w-80 custom-dropdown"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value" class="flex items-center gap-2">
                    <span :class="['font-semibold', slotProps.value.status === 'pending' ? 'text-amber-400' : slotProps.value.status === 'suspended' ? 'text-rose-400' : 'text-white']">{{ slotProps.value.name }}</span>
                    <span v-if="slotProps.value.status === 'pending'" class="status-badge status-pending">Čaká</span>
                    <span v-else-if="slotProps.value.status === 'suspended'" class="status-badge status-suspended">Pozast.</span>
                  </div>
                </template>
                <template #option="slotProps">
                  <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                      <span :class="['font-semibold', slotProps.option.status === 'pending' ? 'text-amber-400' : slotProps.option.status === 'suspended' ? 'text-rose-400' : 'text-white']">{{ slotProps.option.name }}</span>
                      <span v-if="slotProps.option.status === 'pending'" class="status-badge status-pending">Čaká na schválenie</span>
                      <span v-else-if="slotProps.option.status === 'suspended'" class="status-badge status-suspended">Pozastavený</span>
                    </div>
                    <div class="text-xs text-slate-400" v-if="slotProps.option.academic_year">
                      {{ slotProps.option.academic_year.name }}
                    </div>
                  </div>
                </template>
              </Dropdown>
            </div>
          </div>
          <div v-if="selectedTeam" class="flex flex-wrap items-center gap-2 text-xs sm:text-sm">
            <div class="info-chip">
              <span class="font-medium text-slate-200">{{ selectedTeam.academic_year?.name || 'N/A' }}</span>
            </div>
            <div class="info-chip">
              <span class="font-medium text-slate-200">{{ selectedTeam.members?.length || 0 }} členov</span>
            </div>
            <div v-if="selectedTeam.is_scrum_master" class="info-chip-accent">
              <span class="font-semibold text-indigo-200">S</span>
            </div>
            <div v-if="getCurrentUserOccupation(selectedTeam)" class="info-chip-purple">
              <span class="font-medium text-violet-200">{{ getCurrentUserOccupation(selectedTeam) }}</span>
            </div>
            <!-- Status badge -->
            <div v-if="selectedTeam.status === 'pending'" class="info-chip-warning">
              <span class="font-semibold text-amber-100">Čaká na schválenie</span>
            </div>
            <div v-else-if="selectedTeam.status === 'suspended'" class="info-chip-danger">
              <span class="font-semibold text-rose-100">Pozastavený</span>
            </div>
          </div>
        </div>
        
        <!-- Pending team warning -->
        <div v-if="selectedTeam && selectedTeam.status === 'pending'" class="mt-4 p-4 bg-amber-950/40 border border-amber-700/50 rounded-xl backdrop-blur-sm">
          <div class="flex items-start gap-3">
            <i class="pi pi-exclamation-triangle text-amber-400 text-xl mt-0.5"></i>
            <div>
              <h4 class="font-semibold text-amber-200">Tím čaká na schválenie</h4>
              <p class="text-sm text-amber-300/80 mt-1">Váš tím bol vytvorený a čaká na schválenie administrátorom. Kód pre pripojenie je neaktívny a nie je možné spravovať tím ani publikovať projekty.</p>
            </div>
          </div>
        </div>
        
        <!-- Suspended team warning -->
        <div v-if="selectedTeam && selectedTeam.status === 'suspended'" class="mt-4 p-4 bg-rose-950/40 border border-rose-700/50 rounded-xl backdrop-blur-sm">
          <div class="flex items-start gap-3">
            <i class="pi pi-ban text-rose-400 text-xl mt-0.5"></i>
            <div>
              <h4 class="font-semibold text-rose-200">Tím bol pozastavený</h4>
              <p class="text-sm text-rose-300/80 mt-1">Váš tím bol pozastavený administrátorom. Kontaktujte administrátora pre viac informácií.</p>
            </div>
          </div>
        </div>
      </div>
    </div>



    <div v-if="token" class="glass-card p-5 mb-8">
      <!-- Vyhľadávanie -->
      <div class="flex-grow mb-4">
        <div class="relative">
          <i class="pi pi-search absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
          <input 
            type="text"
            v-model="search" 
            placeholder="Vyhľadať podľa názvu projektu..."
            class="search-input"
          />
        </div>
      </div>

      <!-- Nový multi-purpose filter systém -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <!-- Typ školy -->
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-2">Typ školy</label>
          <Dropdown
            v-model="filterSchoolType"
            :options="filterSchoolTypes"
            optionLabel="label"
            optionValue="value"
            placeholder="Všetky typy"
            class="w-full custom-dropdown"
            @change="applyFilters"
          />
        </div>

        <!-- Ročník -->
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-2">Ročník</label>
          <Dropdown
            v-model="filterYearOfStudy"
            :options="filterYears"
            optionLabel="label"
            optionValue="value"
            placeholder="Všetky ročníky"
            class="w-full custom-dropdown"
            @change="applyFilters"
          />
        </div>

        <!-- Predmet -->
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-2">Predmet</label>
          <Dropdown
            v-model="filterSubject"
            :options="filterSubjects"
            optionLabel="label"
            optionValue="value"
            placeholder="Všetky predmety"
            class="w-full custom-dropdown"
            @change="applyFilters"
          />
        </div>

        <!-- Typ projektu -->
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-2">Typ projektu</label>
          <Dropdown
            v-model="selectedType"
            :options="types"
            optionLabel="label"
            optionValue="value"
            placeholder="Všetky typy"
            class="w-full custom-dropdown"
            @change="applyFilters"
          />
        </div>
      </div>

      <!-- Tlačidlá a reset -->
      <div class="flex flex-wrap items-center gap-2">
        <button 
          v-if="hasTeam && selectedTeam && !showingMyProjects && !isAdmin" 
          class="btn-secondary btn-sm"
          @click="loadMyProjects" 
        >
          Moje Projekty
        </button>
        <button 
          v-if="showingMyProjects && !isAdmin" 
          class="btn-secondary btn-sm"
          @click="loadAllGames" 
        >
          Všetky Projekty
        </button>
        <button 
          v-if="hasActiveFilters"
          class="btn-ghost btn-sm"
          @click="resetFilters" 
        >
          Resetovať filtre
        </button>
      </div>
    </div>

    <!-- 🛑 SEKCIA: Dynamické Zobrazenie Hier z DB (s loadingom a prázdnym stavom) -->
    <!-- Not logged in message -->
    <div v-if="!token" class="glass-card text-center p-16">
      <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center">
        <i class="pi pi-lock text-4xl text-slate-400"></i>
      </div>
      <h3 class="text-2xl font-bold text-white mb-4">Prihláste sa aby ste videli projekty v systéme</h3>
      <p class="text-slate-400 mb-8 max-w-md mx-auto">Pre zobrazenie projektov a funkcionalitu systému sa musíte prihlásiť.</p>
      <div class="flex gap-3 justify-center">
        <button 
          class="btn-primary"
          @click="$router.push('/login')"
        >
          Prihlásiť sa
        </button>
        <button 
          class="btn-secondary"
          @click="$router.push('/register')"
        >
          Registrovať sa
        </button>
      </div>
    </div>

    <!-- Logged in - show projects -->
    <div v-else>
      <div v-if="loadingGames" class="flex items-center justify-center p-20">
        <div class="flex items-center gap-3 text-xl text-indigo-400">
          <i class="pi pi-spin pi-spinner text-4xl"></i>
          <span>Načítavam projekty...</span>
        </div>
      </div>
      <div v-else-if="filteredGames.length === 0" class="glass-card text-center p-12">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center">
          <i class="pi pi-inbox text-3xl text-slate-400"></i>
        </div>
        <p class="text-lg text-slate-300">Zatiaľ nebol pridaný žiadny projekt.</p>
      </div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="game in filteredGames"
        :key="game.id"
        class="project-card group"
      >
        <div class="aspect-video bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl mb-4 overflow-hidden flex items-center justify-center">
          <span v-if="!game.splash_screen_path" class="text-xs text-slate-500">Bez náhľadu</span>
          <img 
            v-else 
            :src="getSplashUrl(game.splash_screen_path)" 
            :alt="game.title" 
            class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105" 
          />
        </div>

        <h3 class="text-lg font-semibold text-white mb-3 line-clamp-2 group-hover:text-indigo-300 transition-colors">{{ game.title }}</h3>
        
        <div class="flex flex-wrap gap-2 text-xs mb-3">
          <span 
            class="tag tag-primary cursor-pointer"
            @click.stop="filterByType(game.type)"
            title="Kliknite pre filtrovanie podľa typu projektu"
          >
            {{ game.type.replace('_', ' ') }}
          </span>
          <span 
            v-if="game.school_type" 
            class="tag tag-default cursor-pointer"
            @click.stop="filterBySchoolType(game.school_type)"
            title="Kliknite pre filtrovanie podľa typu školy"
          >
            {{ getSchoolTypeLabel(game.school_type) }}
          </span>
          <span 
            v-if="game.year_of_study" 
            class="tag tag-default"
            title="Ročník nie je klikateľný"
          >
            {{ game.year_of_study }}. ročník
          </span>
          <span 
            v-if="game.subject" 
            class="tag tag-default cursor-pointer"
            @click.stop="filterBySubject(game.subject)"
            title="Kliknite pre filtrovanie podľa predmetu"
          >
            {{ game.subject }}
          </span>
          <span 
            class="tag tag-default cursor-pointer"
            @click.stop="goToTeam(game.team?.id)"
            title="Kliknite pre zobrazenie tímu"
          >
            {{ game.team?.name || 'Neznámy' }}
          </span>
          <span 
            v-if="game.academic_year" 
            class="tag tag-default cursor-pointer"
            @click.stop="filterByAcademicYear(game.academic_year.id)"
            title="Kliknite pre filtrovanie podľa akademického roka"
          >
            {{ game.academic_year.name }}
          </span>
        </div>
        
        <p class="text-slate-400 text-sm leading-relaxed line-clamp-3 mb-4">{{ game.description || 'Popis nebol poskytnutý.' }}</p>

        <div class="mt-auto">
          <div class="flex items-center justify-between mb-4 text-xs text-slate-400 pb-3 border-b border-slate-700/50">
            <div class="flex items-center gap-1">
              <i 
                v-for="star in 5" 
                :key="star" 
                :class="star <= Math.round(Number(game.rating || 0)) ? 'pi pi-star-fill text-amber-400' : 'pi pi-star text-slate-600'"
                class="text-sm"
              ></i>
              <span class="font-semibold text-slate-300 ml-1">{{ Number(game.rating || 0).toFixed(1) }}</span>
            </div>
            <div class="flex items-center gap-1">Zobrazenia: <span class="font-semibold text-slate-300">{{ game.views || 0 }}</span></div>
          </div>

          <button 
            class="btn-secondary btn-sm w-full group-hover:bg-indigo-600 group-hover:border-indigo-500 group-hover:text-white transition-all"
            @click="viewProjectDetail(game)" 
          >
            <span>Zobraziť detail</span>
            <i class="pi pi-arrow-right transition-transform group-hover:translate-x-1"></i>
          </button>
        </div>
      </div>
      </div>
    </div>
  </div>
  </div>

  <!-- DIALÓG PRE VYTVORENIE TÍMU (Zostáva nezmenený) -->
  <Dialog v-model:visible="showCreateTeam" :modal="true" :closable="false" :draggable="false" class="w-11/12 md:w-1/3" :contentStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', padding: '1.5rem', border: 'none' }" :headerStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', borderBottom: '1px solid rgba(71, 85, 105, 0.5)', padding: '1rem 1.5rem', position: 'relative' }">
    <template #header>
      <div class="dialog-header-custom">
        <span class="dialog-title-centered">Vytvoriť Nový Tím</span>
        <button class="dialog-close-btn" @click="showCreateTeam = false">
          <i class="pi pi-times"></i>
        </button>
      </div>
    </template>
    <div v-if="!teamCreatedSuccess">
        <form @submit.prevent="createTeam" class="flex flex-col gap-5">
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Názov tímu</label>
            <InputText v-model="teamName" placeholder="Zadajte názov tímu" required class="w-full custom-input" />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Akademický rok</label>
            <Dropdown
                v-model="academicYear"
                :options="academicYears"
                optionLabel="name"
                optionValue="id"
                placeholder="Vyber akademický rok"
                class="w-full custom-dropdown"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Povolanie</label>
            <Dropdown
                v-model="createTeamOccupation"
                :options="occupations"
                optionLabel="label"
                optionValue="value"
                placeholder="Vyber povolanie"
                class="w-full custom-dropdown"
            />
          </div>
          
          <button 
              type="submit" 
              class="btn-primary w-full mt-2"
              :disabled="loadingCreate"
          >
            <i :class="loadingCreate ? 'pi pi-spin pi-spinner' : 'pi pi-check'"></i>
            {{ loadingCreate ? 'Vytváram...' : 'Vytvoriť Tím' }}
          </button>
        </form>
    </div>
    <div v-else class="flex flex-col items-center gap-4 text-center">
        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
          <i class="pi pi-check text-4xl text-white"></i>
        </div>
        <h3 class="text-xl font-semibold text-white">Tím bol úspešne vytvorený!</h3>
        
        <div class="w-full p-4 rounded-xl bg-slate-800/50 border-2 border-dashed border-slate-600">
          <p class="text-sm text-slate-400 mb-2">Váš unikátny kód pre pripojenie členov:</p>
          <span class="text-3xl font-bold tracking-widest text-indigo-400 select-all">
            {{ team.invite_code }}
          </span>
        </div>

        <button 
          class="btn-secondary w-full"
          @click="copyTeamCode(team.invite_code)"
        >
          Kopírovať Kód
        </button>
        
        <button 
          class="btn-ghost w-full"
          @click="closeCreateTeamDialog" 
        >
          Zavrieť a pokračovať
        </button>
    </div>
    
    <p v-if="teamMessage && !teamCreatedSuccess" :class="teamMessage.startsWith('✅') ? 'text-emerald-400 font-semibold' : 'text-rose-400 font-semibold'" class="mt-4 text-center text-sm">
      {{ teamMessage }}
    </p>
  </Dialog>

  <!-- DIALÓG PRE PRIPOJENIE K TÍMU (Zostáva nezmenený) -->
  <Dialog 
    v-model:visible="showJoinTeam" 
    :modal="true" 
    :closable="false" 
    :draggable="false"
    class="w-11/12 md:w-96"
    :contentStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', padding: '1.5rem', border: 'none' }" 
    :headerStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', borderBottom: '1px solid rgba(71, 85, 105, 0.5)', padding: '1rem 1.5rem', position: 'relative' }"
    :style="{ borderRadius: '16px', overflow: 'hidden' }"
  >
    <template #header>
      <div class="dialog-header-custom">
        <span class="dialog-title-centered">Pripojiť sa k tímu</span>
        <button class="dialog-close-btn" @click="showJoinTeam = false">
          <i class="pi pi-times"></i>
        </button>
      </div>
    </template>
    
    <div class="flex flex-col items-center gap-5 text-center">
        
        <form @submit.prevent="joinTeam" class="flex flex-col gap-4 w-full">
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Kód tímu</label>
            <InputText
                v-model="joinTeamCode"
                placeholder="Napr. A1B2C3"
                required
                :class="{ 'border-rose-500': joinTeamError }"
                class="w-full custom-input text-center font-mono tracking-widest text-lg"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Povolanie</label>
            <Dropdown
                v-model="joinTeamOccupation"
                :options="occupations"
                optionLabel="label"
                optionValue="value"
                placeholder="Vyber povolanie"
                class="w-full custom-dropdown"
            />
          </div>
          <button
              type="submit"
              class="btn-primary w-full"
              :disabled="loadingJoin"
          >
            <i :class="loadingJoin ? 'pi pi-spin pi-spinner' : 'pi pi-sign-in'"></i>
            {{ loadingJoin ? 'Pripájam...' : 'Pripojiť sa' }}
          </button>
        </form>

        <p v-if="joinTeamError" class="text-rose-400 font-semibold text-sm">{{ joinTeamError }}</p>
    </div>
</Dialog>


  <!-- NOVÝ, MINIMALISTICKÝ DIALÓG PRE ZOBRAZENIE STAVU TÍMU -->
  <Dialog 
    v-model:visible="showTeamStatusDialog" 
    :modal="true" 
    :closable="false" 
    :draggable="false"
    class="w-11/12 md:w-[480px]"
    :contentStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', padding: '1.5rem', border: 'none' }" 
    :headerStyle="{ backgroundColor: '#0f172a', color: '#f1f5f9', borderBottom: '1px solid rgba(71, 85, 105, 0.5)', padding: '1rem 1.5rem', position: 'relative' }"
    :style="{ borderRadius: '16px', overflow: 'hidden' }"
  >
    <template #header>
      <div class="dialog-header-custom">
        <span class="dialog-title-centered">Moje Tímy</span>
        <button class="dialog-close-btn" @click="showTeamStatusDialog = false">
          <i class="pi pi-times"></i>
        </button>
      </div>
    </template>
    <div v-if="teams.length > 0" class="flex flex-col gap-4 max-h-[70vh] overflow-y-auto custom-scrollbar">
        <!-- Zobrazenie všetkých tímov -->
        <div v-for="team in teams" :key="team.id" :class="['rounded-xl p-4 border backdrop-blur-sm', team.status === 'pending' ? 'bg-amber-950/20 border-amber-700/50' : team.status === 'suspended' ? 'bg-rose-950/20 border-rose-700/50' : 'bg-slate-800/50 border-slate-700/50']">
          <!-- Hlavička tímu -->
          <div class="flex justify-between items-start mb-3 pb-3 border-b border-slate-700/50">
            <div>
              <h3 :class="['text-lg font-semibold', team.status === 'pending' ? 'text-amber-400' : team.status === 'suspended' ? 'text-rose-400' : 'text-white']">{{ team.name }}</h3>
              <div class="mt-1 flex flex-wrap items-center gap-2">
                <span v-if="team.academic_year" class="text-xs text-slate-400">
                  {{ team.academic_year.name }}
                </span>
                <!-- Status badge -->
                <span v-if="team.status === 'pending'" class="status-badge status-pending">
                  Čaká na schválenie
                </span>
                <span v-else-if="team.status === 'suspended'" class="status-badge status-suspended">
                  Pozastavený
                </span>
                <span v-else-if="team.status === 'active'" class="status-badge status-active">
                  Aktívny
                </span>
              </div>
            </div>
            <div v-if="team.is_scrum_master" class="px-2 py-1 bg-indigo-900/50 text-indigo-300 rounded-lg text-xs font-semibold border border-indigo-700/50">
              S
            </div>
          </div>

          <!-- Pending team warning -->
          <div v-if="team.status === 'pending'" class="mb-3 p-3 bg-amber-950/30 border border-amber-700/30 rounded-lg">
            <p class="text-xs text-amber-300/80">Tím čaká na schválenie. Kód pre pripojenie je zatiaľ neaktívny a nie je možné spravovať členov ani vytvárať projekty.</p>
          </div>

          <!-- Kód pre pripojenie - disabled for pending teams -->
          <div :class="['flex flex-col items-center p-3 rounded-lg mb-3', team.status === 'pending' ? 'bg-slate-900/30 opacity-50' : 'bg-slate-900/50']">
            <p class="text-xs text-slate-400 mb-1">Kód pre pripojenie{{ team.status === 'pending' ? ' (neaktívny)' : '' }}:</p>
            <div class="flex items-center gap-2">
              <span :class="['text-xl font-mono tracking-widest select-all', team.status === 'pending' ? 'text-slate-500 line-through' : 'text-indigo-400']">
                {{ team.invite_code }}
              </span>
              <button 
                v-if="team.status === 'active'"
                class="btn-ghost btn-sm"
                @click="copyTeamCode(team.invite_code)" 
                v-tooltip.top="'Kopírovať kód'"
              >
                Kopírovať
              </button>
            </div>
          </div>

          <!-- Zoznam členov -->
          <div>
            <p class="text-xs text-slate-400 mb-2">Členovia ({{ team.members?.length || 0 }}/10):</p>
            <div class="grid grid-cols-1 gap-2">
              <div v-for="member in team.members" :key="member.id" class="flex items-center justify-between gap-2 text-slate-200 text-sm bg-slate-900/50 rounded-lg px-3 py-2">
                <div class="flex flex-col truncate flex-1">
                  <div class="flex items-center gap-2">
                    <span class="truncate">{{ member.name }}</span>
                    <span 
                      v-if="getRoleLabel(team, member) === 'S'"
                      class="px-2 py-0.5 bg-indigo-900/50 border border-indigo-700/50 text-indigo-300 rounded text-xs font-semibold flex-shrink-0"
                    >
                      S
                    </span>
                  </div>
                  <span v-if="member.pivot?.occupation" class="text-xs text-slate-500 truncate mt-0.5">{{ formatOccupation(member.pivot.occupation) }}</span>
                </div>
                <!-- Only show remove button for active teams -->
                <button
                  v-if="team.is_scrum_master && member.id !== currentUserId && team.status === 'active'"
                  class="btn-danger-ghost btn-sm"
                  @click="confirmRemoveMember(team, member)"
                >
                  Odstrániť
                </button>
                <!-- Only show leave button for active teams -->
                <button
                  v-if="!team.is_scrum_master && member.id === currentUserId && team.status === 'active'"
                  class="btn-warning-ghost btn-sm"
                  @click="confirmLeaveTeam(team)"
                >
                  Opustiť
                </button>
              </div>
            </div>
          </div>
        </div>

        <button 
            class="btn-ghost w-full mt-2"
            @click="showTeamStatusDialog = false" 
        >
          Zavrieť
        </button>
    </div>
    <div v-else class="text-center text-slate-400 py-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-800/50 flex items-center justify-center">
          <i class="pi pi-users text-3xl text-slate-500"></i>
        </div>
        <p class="text-sm">Nie ste členom žiadneho tímu</p>
    </div>
</Dialog>

</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'; 

const vTooltip = Tooltip; 

const API_URL = import.meta.env.VITE_API_URL
const toast = useToast()
const router = useRouter()

// -------------------------
// Global/User Status
// -------------------------
const token = ref(localStorage.getItem('access_token') || '')
const hasTeam = ref(false) 
const teams = ref([]) // All teams user is part of
const selectedTeam = ref(null) // Currently selected team
const isAdmin = computed(() => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  return user.role === 'admin'
})
// Persist active team selection for cross-view authorization (AddGameView, Navbar)
function setActiveTeam(team) {
  if (!team) {
    localStorage.removeItem('active_team_id')
    localStorage.removeItem('active_team_is_scrum_master')
    localStorage.removeItem('active_team_status')
  } else {
    localStorage.setItem('active_team_id', String(team.id))
    localStorage.setItem('active_team_is_scrum_master', team.is_scrum_master ? '1' : '0')
    localStorage.setItem('active_team_status', team.status || 'active')
    // Broadcast change so Navbar / other views can react without reload
    window.dispatchEvent(new CustomEvent('team-changed', { detail: { id: team.id, isScrumMaster: team.is_scrum_master, status: team.status || 'active' } }))
  }
}
const showTeamStatusDialog = ref(false) 
const currentUserId = ref(null)
const removingMember = ref(false)

// Helper: derive role label and class even if pivot missing
function getRoleLabel(team, member) {
  const pivotRole = member.pivot?.role_in_team
  if (pivotRole === 'scrum_master' || team.scrum_master_id === member.id) return 'S'
  return 'Člen'
}
function getRoleClass(team, member) {
  const isScrum = (member.pivot?.role_in_team === 'scrum_master') || (team.scrum_master_id === member.id)
  return isScrum ? 'text-teal-400' : 'text-gray-500'
}

function getCurrentUserOccupation(team) {
  if (!team || !team.members || !currentUserId.value) return null
  const currentUser = team.members.find(m => m.id === currentUserId.value)
  const occupation = currentUser?.pivot?.occupation || null
  return occupation ? formatOccupation(occupation) : null
}

// Helper function to format occupation with proper diacritics
function formatOccupation(occupation) {
  if (!occupation) return null
  // Normalize: lowercase, trim, and remove diacritics for comparison
  const normalized = occupation.toLowerCase().trim()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Remove diacritics for matching
  
  const occupationMap = {
    'programator': 'Programátor',
    'grafik 2d': 'Grafik 2D',
    'grafik 3d': 'Grafik 3D',
    'tester': 'Tester',
    'animator': 'Animátor'
  }
  return occupationMap[normalized] || occupation
}

// -------------------------
// Logika Pripojenia k Tímu
// -------------------------
const showJoinTeam = ref(false)
const joinTeamCode = ref('')
const joinTeamOccupation = ref(null)
const joinTeamError = ref('')
const loadingJoin = ref(false)

async function joinTeam() {
    joinTeamError.value = ''
    
    if (!joinTeamCode.value) {
        joinTeamError.value = 'Kód tímu nemôže byť prázdny.'
        return
    }
    
    if (!joinTeamOccupation.value) {
        joinTeamError.value = 'Povolanie je povinné.'
        return
    }
    
    loadingJoin.value = true
    
    // Očistíme kód pre prípad, že ho používateľ skopíroval s bielymi znakmi
    const cleanCode = joinTeamCode.value.trim() 
    const payload = JSON.stringify({ 
      invite_code: cleanCode,
      occupation: joinTeamOccupation.value
    });

    try {
        const res = await fetch(`${API_URL}/api/teams/join`, { 
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', 
                'Authorization': 'Bearer ' + token.value,
                'Accept': 'application/json'
            },
            body: payload
        })

        const data = await res.json()
        
        if (res.ok && data.team) {
            toast.add({ severity: 'success', summary: 'Pripojenie Úspešné', detail: `Úspešne ste sa pripojili k tímu "${data.team.name}".`, life: 5000 })
            hasTeam.value = true
            showJoinTeam.value = false 
            joinTeamCode.value = ''
            joinTeamOccupation.value = null
            await loadTeamStatus() // Reload all teams
            loadAllGames() 
        } else {
            let errorMessage = data.message || 'Chyba pri pripájaní.'
            
            if (data.message && data.message.includes('Tím') && data.message.includes('dosiahol maximálny')) {
                // Konkrétna chyba z backendu pre max členov
                 toast.add({ severity: 'error', summary: 'Chyba Kapacity', detail: errorMessage, life: 6000 })
            }
            else if (errorMessage.includes('Už si v tíme') || errorMessage.includes('Už si členom tímu')) {
                 toast.add({ severity: 'warn', summary: 'Už ste v tíme', detail: errorMessage, life: 6000 })
            } else {
                 toast.add({ severity: 'error', summary: 'Chyba Pripojenia', detail: errorMessage, life: 6000 })
            }
            
            if (data.errors && data.errors.invite_code) {
                 joinTeamError.value = data.errors.invite_code.join(' ')
            } else {
                 joinTeamError.value = errorMessage
            }
        }
    } catch (err) {
        joinTeamError.value = 'Chyba siete/servera. (Server nedostupný)'
        toast.add({ severity: 'fatal', summary: 'Chyba Siete', detail: 'Server je nedostupný (Connection refused). Overte, či beží na porte 8000.', life: 10000 })
    } finally {
        loadingJoin.value = false
    }
}


// -------------------------
// Logika Vytvorenia Tímu
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
  // Extra validation and user feedback
  if (!teamName.value && !academicYear.value) {
    teamMessage.value = '❌ Vyplňte názov tímu a vyberte akademický rok.';
    toast.add({ severity: 'warn', summary: 'Upozornenie', detail: 'Vyplňte, prosím, názov tímu a akademický rok.', life: 4000 });
    return;
  }
  if (!teamName.value) {
    teamMessage.value = '❌ Názov tímu je povinný.';
    toast.add({ severity: 'warn', summary: 'Upozornenie', detail: 'Názov tímu je povinný.', life: 4000 });
    return;
  }
  if (!academicYear.value) {
    teamMessage.value = '❌ Akademický rok je povinný.';
    toast.add({ severity: 'warn', summary: 'Upozornenie', detail: 'Akademický rok je povinný.', life: 4000 });
    return;
  }
  if (!createTeamOccupation.value) {
    teamMessage.value = '❌ Povolanie je povinné.';
    toast.add({ severity: 'warn', summary: 'Upozornenie', detail: 'Povolanie je povinné.', life: 4000 });
    return;
  }
  loadingCreate.value = true;
  try {
    const formData = new FormData();
    formData.append('name', teamName.value);
    formData.append('academic_year_id', academicYear.value);
    formData.append('occupation', createTeamOccupation.value);

    const res = await fetch(`${API_URL}/api/teams`, {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' },
      body: formData,
    });

    const data = await res.json();

    if (res.ok && data.team) {
      team.value = data.team;
      teamCreatedSuccess.value = true;
      hasTeam.value = true;
      
      // Show appropriate message based on approval status
      if (data.requires_approval) {
        toast.add({ 
          severity: 'info', 
          summary: 'Tím Vytvorený', 
          detail: data.message || `Tím "${team.value.name}" bol vytvorený a čaká na schválenie administrátorom.`, 
          life: 8000 
        });
      } else {
        toast.add({ 
          severity: 'success', 
          summary: 'Tím Vytvorený', 
          detail: data.message || `Tím "${team.value.name}" bol úspešne vytvorený.`, 
          life: 5000 
        });
      }
      
      await loadTeamStatus(); // Reload all teams
      loadAllGames();
    } else {
      let errorMessage = data.message || 'Chyba pri vytváraní tímu.';
      if (data.errors) {
        errorMessage += ' ' + Object.values(data.errors).map(e => e.join(', ')).join('. ');
      }
      teamMessage.value = '❌ ' + errorMessage;
      toast.add({ severity: 'error', summary: 'Chyba Registrácie', detail: errorMessage, life: 8000 });
    }
  } catch (err) {
    teamMessage.value = 'Chyba pri spojení s backendom. Server nedostupný.';
    toast.add({ severity: 'fatal', summary: 'Chyba Pripojenia', detail: 'Server je nedostupný (Connection refused). Overte, či beží na porte 8000.', life: 10000 });
  } finally {
    loadingCreate.value = false;
  }
}

const copyTeamCode = async (code) => {
  try {
    // Používame moderné asynchrónne Clipboard API
    await navigator.clipboard.writeText(code);
    toast.add({ severity: 'info', summary: 'Kód skopírovaný', detail: 'Kód bol skopírovaný do schránky.', life: 3000 });
  } catch (err) {
    toast.add({ severity: 'warn', summary: 'Kopírovanie zlyhalo', detail: 'Nepodarilo sa skopírovať kód. Prosím, skopírujte ho ručne.', life: 3000 });
  }
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
// Statické Dáta a Filtrovanie
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
const loadingGames = ref(true)
const showingMyProjects = ref(false)

// Nový filter systém
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

const filterYears = ref([
  { label: 'Všetky ročníky', value: null },
  ...Array.from({ length: 9 }, (_, i) => ({
    label: `${i + 1}. ročník`,
    value: i + 1
  }))
])

const hasActiveFilters = computed(() => {
  return filterSchoolType.value !== null || 
         filterYearOfStudy.value !== null || 
         filterSubject.value !== null ||
         filterAcademicYear.value !== null ||
         selectedType.value !== 'all' ||
         search.value !== ''
})

const filteredGames = computed(() => {
  return games.value.filter(
    (g) => {
      const matchesSearch = !search.value || g.title.toLowerCase().includes(search.value.toLowerCase())
      const matchesSchoolType = !filterSchoolType.value || g.school_type === filterSchoolType.value
      const matchesYear = !filterYearOfStudy.value || g.year_of_study === filterYearOfStudy.value
      const matchesSubject = !filterSubject.value || g.subject === filterSubject.value
      const matchesAcademicYear = !filterAcademicYear.value || g.academic_year?.id === filterAcademicYear.value
      return matchesSearch && matchesSchoolType && matchesYear && matchesSubject && matchesAcademicYear
    }
  )
})

function resetFilters() {
  filterSchoolType.value = null
  filterYearOfStudy.value = null
  filterSubject.value = null
  filterAcademicYear.value = null
  selectedType.value = 'all'
  search.value = ''
  loadAllGames()
}

// Click handlers for filtering by attribute
function filterByType(type) {
  selectedType.value = type
  loadAllGames()
}

function filterBySchoolType(schoolType) {
  filterSchoolType.value = schoolType
  loadAllGames()
}

function filterBySubject(subject) {
  filterSubject.value = subject
  loadAllGames()
}

function filterByAcademicYear(academicYearId) {
  filterAcademicYear.value = academicYearId
  loadAllGames()
}

function applyFilters() {
  loadAllGames()
}
const viewProjectDetail = (project) => {
  router.push({ name: 'ProjectDetail', params: { id: project.id } })
}

// -------------------------
// Načítanie dát
// -------------------------
async function loadAcademicYears() {
    if (!token.value) return
    try {
        const res = await fetch(`${API_URL}/api/academic-years`, {
        headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
        })
        if (!res.ok) return
        academicYears.value = await res.json()
    } catch (err) {
        // Silent fail - academic years are optional for display
    }
}

// Načítanie aktuálneho používateľa (pre skrytie tlačidla odstránenia pri sebe)
async function loadCurrentUser() {
  if (!token.value) return
  try {
    const res = await fetch(`${API_URL}/api/user`, {
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    })
    if (res.ok) {
      const data = await res.json()
      currentUserId.value = data.id
    }
  } catch (_) {}
}

// 🛑 NOVÁ FUNKCIA pre /api/user/team
async function loadTeamStatus() {
    if (!token.value) return; 
    try {
        const res = await fetch(`${API_URL}/api/user/team`, { 
            headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
        })
        
        // Pokúsime sa načítať JSON aj pri chybovom stave (pre správy)
        let data = {};
        if (res.headers.get('content-type')?.includes('application/json')) {
            data = await res.json();
        }

        if (res.ok) {
            if (data.teams && data.teams.length > 0) {
                hasTeam.value = true
                teams.value = data.teams
                // Try restore previously selected team
                const storedId = localStorage.getItem('active_team_id')
                const found = storedId ? teams.value.find(t => String(t.id) === storedId) : null
                selectedTeam.value = found || teams.value[0] // Select restored or first team
                setActiveTeam(selectedTeam.value)
                console.log('✅ Používateľ je v tímoch:', data.teams.map(t => t.name).join(', '));
            } else {
                hasTeam.value = false;
                teams.value = [];
                selectedTeam.value = null;
                setActiveTeam(null); // Clear localStorage and notify Navbar
            }
        } else if (res.status === 404) {
            console.warn(`⚠️ Chyba 404: Endpoint /api/user/team nebol nájdený. Skontrolujte routes/api.php.`)
            hasTeam.value = false;
        } else if (res.status === 401) {
             console.warn(`⚠️ Chyba 401: Neautorizovaný prístup k stavu tímu. Token neplatný/vypršal.`)
             hasTeam.value = false;
        } else {
             console.error(`❌ Chyba ${res.status} pri načítaní stavu tímu.`, res)
        }
    } catch (err) {
        console.error('❌ FATÁLNA CHYBA SIETE pri načítaní stavu tímu. Server pravdepodobne nie je spustený alebo je nedostupný.', err)
        toast.add({ severity: 'fatal', summary: 'Chyba Siete', detail: 'Server je nedostupný (Connection refused). Overte, či beží na porte 8000.', life: 10000 })
    }
}

// Načítanie všetkých hier z DB s novými filtrami
async function loadAllGames() {
    showingMyProjects.value = false
    if (!token.value) {
        loadingGames.value = false
        return
    }
    loadingGames.value = true
    try {
        const params = new URLSearchParams()
        
        if (selectedType.value && selectedType.value !== 'all') {
            params.append('type', selectedType.value)
        }
        
        if (filterSchoolType.value) {
            params.append('school_type', filterSchoolType.value)
        }
        
        if (filterYearOfStudy.value) {
            params.append('year_of_study', filterYearOfStudy.value)
        }
        
        if (filterSubject.value) {
            params.append('subject', filterSubject.value)
        }
        
        if (filterAcademicYear.value) {
            params.append('academic_year_id', filterAcademicYear.value)
        }
        
        const query = params.toString() ? `?${params.toString()}` : ''
        const res = await fetch(`${API_URL}/api/projects${query}`, {
            headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
        })
        
        if (res.ok) {
            const data = await res.json()
            games.value = data
        } else if (res.status === 404) {
            toast.add({ severity: 'error', summary: 'Chyba Načítania Projektov (404)', detail: 'Chýba routa GET /api/projects. Pridajte ju, prosím, do routes/api.php.', life: 10000 })
        }
         else {
            toast.add({ severity: 'error', summary: 'Chyba Načítania Projektov', detail: `Nepodarilo sa načítať zoznam projektov zo servera. Status: ${res.status}`, life: 5000 })
        }
    } catch (err) {
        console.error('❌ FATÁLNA CHYBA SIETE pri načítaní všetkých projektov. Server pravdepodobne nie je spustený alebo je nedostupný.', err)
        toast.add({ severity: 'fatal', summary: 'Chyba Pripojenia', detail: 'Server je nedostupný (Connection refused). Problém s komunikáciou pri načítaní projektov.', life: 10000 })
    } finally {
        loadingGames.value = false
    }
}

function confirmRemoveMember(team, member) {
  if (removingMember.value) return
  const ok = window.confirm(`Odstrániť člena "${member.name}" z tímu "${team.name}"?`)
  if (ok) removeMember(team, member)
}

function confirmLeaveTeam(team) {
  if (removingMember.value) return
  const ok = window.confirm(`Naozaj chcete opustiť tím "${team.name}"?`)
  if (ok) leaveTeam(team)
}

// Load only projects for active team
async function loadMyProjects(){
  if(!token.value || !selectedTeam.value) return
  showingMyProjects.value = true
  loadingGames.value = true
  try {
    const res = await fetch(`${API_URL}/api/projects/my?team_id=${selectedTeam.value.id}`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    if(res.ok){
      const data = await res.json()
      games.value = data.projects || []
      const count = data.count || games.value.length
      if(count === 0){
        toast.add({ severity: 'info', summary: 'Žiadne projekty', detail: 'Váš tím zatiaľ nemá žiadne projekty.', life: 3000 })
      } else {
        toast.add({ severity: 'success', summary: 'Filtrované', detail: `Zobrazených ${count} projektov vášho tímu.`, life: 3000 })
      }
    } else {
      const errorData = await res.json().catch(() => ({}))
      toast.add({ severity: 'warn', summary: 'Chyba', detail: errorData.message || 'Nepodarilo sa načítať projekty tímu.', life: 4000 })
    }
  } catch(_) {
    toast.add({ severity: 'error', summary: 'Chyba siete', detail: 'Server je nedostupný.', life: 5000 })
  } finally {
    loadingGames.value = false
  }
}

async function removeMember(team, member) {
  removingMember.value = true
  try {
    const res = await fetch(`${API_URL}/api/teams/${team.id}/members/${member.id}`, {
      method: 'DELETE',
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    })
    let msg = 'Nepodarilo sa odstrániť člena.'
    try { const data = await res.clone().json(); if (data?.message) msg = data.message; if (data?.team?.members) team.members = data.team.members } catch (_) {}
    if (res.ok) {
      toast.add({ severity: 'success', summary: 'Člen odstránený', detail: `${member.name} bol odstránený.`, life: 4000 })
    } else {
      toast.add({ severity: 'warn', summary: 'Operácia zlyhala', detail: msg, life: 6000 })
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Chyba siete', detail: 'Server je nedostupný.', life: 6000 })
  } finally {
    removingMember.value = false
  }
}

async function leaveTeam(team) {
  removingMember.value = true
  try {
    const res = await fetch(`${API_URL}/api/teams/${team.id}/leave`, {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    })
    let msg = 'Nepodarilo sa opustiť tím.'
    try { const data = await res.clone().json(); if (data?.message) msg = data.message } catch (_) {}
    if (res.ok) {
      toast.add({ severity: 'success', summary: 'Tím opustený', detail: `Úspešne ste opustili tím ${team.name}.`, life: 4000 })
      await loadTeamStatus()
      setActiveTeam(teams.value[0] || null)
      showTeamStatusDialog.value = false
    } else {
      toast.add({ severity: 'warn', summary: 'Operácia zlyhala', detail: msg, life: 6000 })
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Chyba siete', detail: 'Server je nedostupný.', life: 6000 })
  } finally {
    removingMember.value = false
  }
}


onMounted(() => {
  loadAcademicYears()
  loadTeamStatus() 
  loadAllGames() 
  loadCurrentUser()
})

// React to user changing selected team via dropdown
watch(selectedTeam, (val) => {
  setActiveTeam(val)
})
watch(selectedType, () => { loadAllGames() })
watch([filterSchoolType, filterYearOfStudy, filterSubject, filterAcademicYear], () => { loadAllGames() })

// Helper function to get school type label
function getSchoolTypeLabel(type) {
  const map = {
    'zs': 'ZŠ',
    'ss': 'SŠ',
    'vs': 'VŠ'
  }
  return map[type] || type
}

// Helper to resolve splash image path (local storage or absolute URL)
function getSplashUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${API_URL}/storage/${path}`
}

function formatProjectType(type) {
  const typeMap = {
    game: 'Hra',
    web_app: 'Web App',
    mobile_app: 'Mobile App',
    library: 'Knižnica',
    other: 'Iné'
  }
  return typeMap[type] || type
}

function goToTeam(teamId) {
  if (teamId) {
    router.push(`/team/${teamId}`)
  }
}
</script>

<style scoped>
/* Glass Card Effect */
.glass-card {
  @apply bg-slate-900/60 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl;
  box-shadow: 
    0 4px 6px -1px rgba(0, 0, 0, 0.3),
    0 2px 4px -2px rgba(0, 0, 0, 0.2),
    inset 0 1px 0 0 rgba(255, 255, 255, 0.05);
}

/* Project Card */
.project-card {
  @apply bg-slate-900/60 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-5 shadow-xl flex flex-col;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 
    0 4px 6px -1px rgba(0, 0, 0, 0.3),
    0 2px 4px -2px rgba(0, 0, 0, 0.2);
}

.project-card:hover {
  @apply border-indigo-500/50;
  transform: translateY(-4px);
  box-shadow: 
    0 20px 25px -5px rgba(0, 0, 0, 0.4),
    0 8px 10px -6px rgba(0, 0, 0, 0.3),
    0 0 0 1px rgba(99, 102, 241, 0.2);
}

/* Primary Button */
.btn-primary {
  @apply inline-flex items-center justify-center gap-2 px-5 py-2.5 font-semibold text-white rounded-xl transition-all duration-200;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.4);
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  box-shadow: 0 6px 20px 0 rgba(99, 102, 241, 0.5);
  transform: translateY(-1px);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 2px 8px 0 rgba(99, 102, 241, 0.4);
}

.btn-primary:disabled {
  @apply opacity-50 cursor-not-allowed;
}

/* Secondary Button */
.btn-secondary {
  @apply inline-flex items-center justify-center gap-2 px-5 py-2.5 font-semibold text-slate-200 rounded-xl transition-all duration-200;
  background: rgba(51, 65, 85, 0.5);
  border: 1px solid rgba(100, 116, 139, 0.5);
  backdrop-filter: blur(8px);
}

.btn-secondary:hover:not(:disabled) {
  @apply text-white;
  background: rgba(71, 85, 105, 0.6);
  border-color: rgba(148, 163, 184, 0.5);
  box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.2);
  transform: translateY(-1px);
}

.btn-secondary:active:not(:disabled) {
  transform: translateY(0);
}

.btn-secondary:disabled {
  @apply opacity-50 cursor-not-allowed;
}

/* Ghost Button */
.btn-ghost {
  @apply inline-flex items-center justify-center gap-2 px-4 py-2 font-medium text-slate-400 rounded-xl transition-all duration-200;
  background: transparent;
}

.btn-ghost:hover:not(:disabled) {
  @apply text-slate-200;
  background: rgba(51, 65, 85, 0.3);
}

/* Danger Ghost Button */
.btn-danger-ghost {
  @apply inline-flex items-center justify-center gap-1.5 px-3 py-1.5 font-medium text-rose-400 rounded-lg transition-all duration-200;
  background: transparent;
}

.btn-danger-ghost:hover:not(:disabled) {
  @apply text-rose-300;
  background: rgba(244, 63, 94, 0.15);
}

/* Warning Ghost Button */
.btn-warning-ghost {
  @apply inline-flex items-center justify-center gap-1.5 px-3 py-1.5 font-medium text-amber-400 rounded-lg transition-all duration-200;
  background: transparent;
}

.btn-warning-ghost:hover:not(:disabled) {
  @apply text-amber-300;
  background: rgba(251, 191, 36, 0.15);
}

/* Small Button Variant */
.btn-sm {
  @apply px-3.5 py-2 text-sm;
}

/* Search Input */
.search-input {
  @apply w-full bg-slate-800/50 text-white placeholder-slate-400 px-4 py-3 pl-11 rounded-xl border border-slate-700/50 transition-all duration-200;
  backdrop-filter: blur(8px);
}

.search-input:focus {
  @apply outline-none border-indigo-500/50;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

/* Info Chips */
.info-chip {
  @apply px-3.5 py-1.5 bg-slate-800/70 rounded-lg border border-slate-700/50 shadow-sm;
}

.info-chip-accent {
  @apply px-3.5 py-1.5 bg-indigo-900/50 rounded-lg border border-indigo-600/50 shadow-sm;
}

.info-chip-purple {
  @apply px-3.5 py-1.5 bg-violet-900/50 rounded-lg border border-violet-600/50 shadow-sm;
}

.info-chip-warning {
  @apply px-3.5 py-1.5 bg-amber-900/50 rounded-lg border border-amber-600/50 shadow-sm;
}

.info-chip-danger {
  @apply px-3.5 py-1.5 bg-rose-900/50 rounded-lg border border-rose-600/50 shadow-sm;
}

/* Status Badges */
.status-badge {
  @apply px-2 py-0.5 rounded text-xs font-medium;
}

.status-pending {
  @apply bg-amber-900/60 text-amber-200 border border-amber-700/50;
}

.status-suspended {
  @apply bg-rose-900/60 text-rose-200 border border-rose-700/50;
}

.status-active {
  @apply bg-emerald-900/60 text-emerald-200 border border-emerald-700/50;
}

/* Tags */
.tag {
  @apply px-3 py-1 rounded-lg font-medium shadow-sm transition-all duration-200 uppercase text-xs;
}

.tag-primary {
  @apply bg-indigo-900/60 text-indigo-200 border border-indigo-600/50;
}

.tag-primary:hover {
  @apply bg-indigo-800/70 border-indigo-500/60;
}

.tag-default {
  @apply bg-slate-800/60 text-slate-300 border border-slate-600/50;
}

.tag-default:hover {
  @apply bg-slate-700/70 border-slate-500/60;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(30, 41, 59, 0.5);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(100, 116, 139, 0.5);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.5);
}

/* Dialog Header - Custom centered layout with close button */
.dialog-header-custom {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  position: relative;
}

.dialog-title-centered {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f1f5f9;
  text-align: center;
}

.dialog-close-btn {
  position: absolute;
  right: -0.5rem;
  top: 50%;
  transform: translateY(-50%);
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  background: transparent;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dialog-close-btn:hover {
  color: #f1f5f9;
  background: rgba(71, 85, 105, 0.5);
}

.dialog-close-btn i {
  font-size: 0.875rem;
}

/* Custom Dropdown Styling */
:deep(.custom-dropdown) {
  @apply bg-slate-800/50 border-slate-700/50 rounded-xl;
}

:deep(.custom-dropdown .p-dropdown-label) {
  @apply text-white;
}

:deep(.custom-dropdown:not(.p-disabled):hover) {
  @apply border-slate-600;
}

:deep(.custom-dropdown:not(.p-disabled).p-focus) {
  @apply border-indigo-500/50;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

/* Custom Input Styling */
:deep(.custom-input) {
  @apply bg-slate-800/50 border-slate-700/50 rounded-xl text-white;
}

:deep(.custom-input:enabled:hover) {
  @apply border-slate-600;
}

:deep(.custom-input:enabled:focus) {
  @apply border-indigo-500/50;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

:deep(.custom-input::placeholder) {
  @apply text-slate-400;
}
</style>
