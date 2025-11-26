<template>
  <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8 bg-gray-900 min-h-screen">
    <Toast />
    <div v-if="loading" class="text-center p-8">
      <i class="pi pi-spin pi-spinner text-4xl text-blue-400"></i>
      <p class="mt-4 text-gray-300">Načítavam detail projektu...</p>
    </div>
    <div v-else-if="error" class="text-center p-8 bg-gray-800 rounded-xl border border-gray-700 shadow-lg">
      <i class="pi pi-exclamation-triangle text-4xl text-red-400"></i>
      <p class="mt-4 text-gray-200 font-semibold">{{ error }}</p>
      <Button label="Späť" icon="pi pi-arrow-left" class="mt-4 p-button-outlined" @click="goBack" />
    </div>
    <div v-else-if="project" class="space-y-6">
      <div class="flex items-center justify-between mb-6">
        <Button label="Späť" icon="pi pi-arrow-left" class="p-button-text p-button-secondary" @click="goBack" />
      </div>
      <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
          <h1 class="text-4xl font-bold text-gray-100">{{ project.title }}</h1>
          <span class="px-4 py-2 rounded-lg border border-gray-600 bg-gray-700 text-gray-200 font-medium text-lg shadow-lg">{{ project.category }}</span>
        </div>
        <div class="flex items-center gap-6 mb-4 pb-4 border-b border-gray-700">
          <div class="flex flex-col">
            <div class="flex items-center gap-2">
              <i v-for="star in 5" :key="star" @click="submitRating(star)" :class="ratingStarClass(star)" class="cursor-pointer transition-transform"></i>
              <span class="ml-2 text-gray-200 font-bold text-xl">{{ formatRating(project.rating) }} / 5</span>
            </div>
            <small class="text-gray-400 mt-1" v-if="userHasRated">Ďakujeme za hodnotenie (hlasov: {{ project.rating_count || 0 }}).</small>
            <small class="text-gray-400 mt-1" v-else>Kliknite na hviezdu pre hodnotenie (hlasov: {{ project.rating_count || 0 }}).</small>
          </div>
          <div class="flex items-center gap-2 text-gray-300">
            <span class="font-bold text-xl">{{ project.views || 0 }} zobrazení</span>
          </div>
        </div>
        <div class="flex flex-wrap gap-2.5 text-sm">
          <span 
            class="px-3.5 py-1.5 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg cursor-pointer hover:bg-gray-600 transition"
            @click="goToTeam(project.team?.id)"
          >{{ project.team?.name || 'Neznámy tím' }}</span>
          <span v-if="project.academic_year" class="px-3.5 py-1.5 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg">{{ project.academic_year.name }}</span>
          <span v-if="project.release_date" class="px-3.5 py-1.5 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg">{{ formatDate(project.release_date) }}</span>
          <span class="px-3.5 py-1.5 rounded-md border border-teal-600 bg-teal-700 text-teal-100 font-medium shadow-lg uppercase">{{ project.type.replace('_', ' ') }}</span>
        </div>
      </div>
      <!-- Video/Splash -->
      <div v-if="project.video_path || project.splash_screen_path" class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">Video</h2>
        <div v-if="isYouTubeUrl(project.video_url)" class="aspect-video w-full relative rounded-lg overflow-hidden">
          <iframe :src="getYouTubeEmbedUrl(project.video_url)" class="w-full h-full rounded-lg" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
        <div v-else-if="project.video_path" class="aspect-video w-full relative rounded-lg overflow-hidden bg-black group" ref="videoContainer" tabindex="0">
          <video ref="videoPlayer" class="w-full h-full rounded-lg cursor-pointer" :src="getVideoUrl(project.video_path)" :poster="project.splash_screen_path ? getImageUrl(project.splash_screen_path) : ''" playsinline preload="metadata" @loadedmetadata="onLoadedMetadata" @timeupdate="onTimeUpdate" @play="videoPlaying = true" @pause="videoPlaying = false" @click="togglePlay"></video>
          <div v-if="!videoPlaying" class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="w-20 h-20 rounded-full bg-black/50 flex items-center justify-center backdrop-blur-sm">
              <i class="pi pi-play text-white text-3xl ml-1"></i>
            </div>
          </div>
          <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent px-4 pt-4 pb-3 flex flex-col gap-2 text-xs">
            <div class="flex items-center gap-3 w-full">
              <span class="text-gray-300 font-mono">{{ formatTime(currentTime) }}</span>
              <div class="flex-1 relative h-1.5 bg-gray-600 rounded-lg cursor-pointer">
                <div class="absolute left-0 top-0 h-full bg-teal-400 rounded-lg transition-all duration-150" :style="{ width: (duration > 0 ? (currentTime / duration * 100) : 0) + '%' }"></div>
                <input type="range" min="0" :max="duration" :value="currentTime" @input="onSeekInput" @change="onSeekChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                <div class="absolute top-1/2 -translate-y-1/2 w-4 h-4 bg-teal-400 rounded-full shadow-lg" :style="{ left: (duration > 0 ? (currentTime / duration * 100) : 0) + '%', transform: 'translateX(-50%) translateY(-50%)' }"></div>
              </div>
              <span class="text-gray-300 font-mono">{{ formatTime(duration) }}</span>
            </div>
            <div class="flex items-center justify-between w-full mt-1">
              <div class="flex items-center gap-4">
                <button @click.stop="togglePlay" class="text-white hover:text-teal-400 transition p-1">
                  <i :class="videoPlaying ? 'pi pi-pause' : 'pi pi-play'" class="text-xl"></i>
                </button>
                <div class="flex items-center gap-2">
                  <button @click.stop="skip(-10)" class="text-gray-300 hover:text-white transition p-1"><i class="pi pi-replay text-lg"></i><span class="text-[10px]">-10s</span></button>
                  <button @click.stop="skip(10)" class="text-gray-300 hover:text-white transition p-1"><span class="text-[10px]">+10s</span><i class="pi pi-refresh text-lg"></i></button>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                  <button @click.stop="toggleMute" class="text-white hover:text-teal-400 transition">
                    <i :class="(isMuted || volume===0) ? 'pi pi-volume-off' : 'pi pi-volume-up'" class="text-lg"></i>
                  </button>
                  <input type="range" min="0" max="1" step="0.05" :value="isMuted ? 0 : volume" @input="changeVolume" class="w-20 h-1 bg-gray-600 rounded-lg appearance-none cursor-pointer" />
                </div>
                <div class="w-px h-4 bg-gray-600 mx-1"></div>
                <button @click.stop="toggleFullscreen" class="text-white hover:text-teal-400 transition">
                  <i :class="isFullscreen ? 'pi pi-window-minimize' : 'pi pi-window-maximize'" class="text-lg"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="project.splash_screen_path" class="aspect-video w-full rounded-lg overflow-hidden">
          <img :src="getImageUrl(project.splash_screen_path)" :alt="project.title" class="w-full h-full object-cover" />
        </div>
      </div>
      <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">Popis</h2>
        <p class="text-gray-300 text-base leading-relaxed whitespace-pre-wrap">{{ project.description || 'Popis nebol poskytnutý.' }}</p>
      </div>
      <!-- Metadata Section -->
      <div v-if="project.metadata && hasAnyMetadata" class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">Projekt</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div v-if="project.metadata.live_url" class="flex flex-col gap-1">
            <span class="text-xs uppercase tracking-wide text-gray-400">Live URL</span>
            <a :href="project.metadata.live_url" target="_blank" rel="noopener" class="text-teal-400 hover:text-teal-300 break-all">{{ project.metadata.live_url }}</a>
          </div>
          <div v-if="project.metadata.github_url" class="flex flex-col gap-1">
            <span class="text-xs uppercase tracking-wide text-gray-400">GitHub</span>
            <a :href="project.metadata.github_url" target="_blank" rel="noopener" class="text-teal-400 hover:text-teal-300 break-all">{{ project.metadata.github_url }}</a>
          </div>
          <div v-if="project.metadata.npm_url" class="flex flex-col gap-1">
            <span class="text-xs uppercase tracking-wide text-gray-400">NPM</span>
            <a :href="project.metadata.npm_url" target="_blank" rel="noopener" class="text-teal-400 hover:text-teal-300 break-all">{{ project.metadata.npm_url }}</a>
          </div>
          <div v-if="project.metadata.package_name" class="flex flex-col gap-1">
            <span class="text-xs uppercase tracking-wide text-gray-400">Balík / Package</span>
            <span class="text-gray-200 break-all">{{ project.metadata.package_name }}</span>
          </div>
          <div v-if="project.metadata.platform" class="flex flex-col gap-1">
            <span class="text-xs uppercase tracking-wide text-gray-400">Platforma</span>
            <span class="text-gray-200">{{ project.metadata.platform }}</span>
          </div>
          <div v-if="techStack.length" class="flex flex-col gap-1 sm:col-span-2">
            <span class="text-xs uppercase tracking-wide text-gray-400">Tech Stack</span>
            <div class="flex flex-wrap gap-2">
              <span v-for="t in techStack" :key="t" class="px-2 py-1 rounded-md text-xs bg-gray-700 border border-gray-600 text-gray-100">{{ t }}</span>
            </div>
          </div>
        </div>
        <p v-if="!hasAnyMetadata" class="text-gray-400 text-sm">Žiadne metadata.</p>
      </div>
      <div v-if="project.team?.members && project.team.members.length > 0" class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">Členovia tímu</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          <div v-for="member in project.team.members" :key="member.id" class="flex items-center justify-between gap-3 p-3 bg-gray-700 rounded-lg border border-gray-600">
            <div class="flex flex-col">
              <span class="text-gray-200 font-medium">{{ member.name }}</span>
              <span v-if="member.pivot?.role_in_team === 'scrum_master'" class="text-xs text-teal-400">Scrum Master</span>
              <span v-else class="text-xs text-gray-500">Člen</span>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">Na stiahnutie</h2>
        <div class="flex flex-col gap-3">
          <Button v-if="project.files?.export" label="Stiahnuť export" icon="pi pi-download" class="p-button-outlined p-button-lg" @click="downloadFile(project.files.export)" />
          <Button v-if="project.files?.source_code" label="Stiahnuť zdrojový kód" icon="pi pi-file-code" class="p-button-outlined p-button-lg" @click="downloadFile(project.files.source_code)" />
          <Button v-if="project.files?.documentation" label="Stiahnuť dokumentáciu" icon="pi pi-book" class="p-button-outlined p-button-lg" @click="downloadFile(project.files.documentation)" />
          <p v-if="!project.files || (!project.files.export && !project.files.source_code && !project.files.documentation)" class="text-gray-400 text-center py-4">Žiadne súbory na stiahnutie.</p>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'

const route = useRoute();
const router = useRouter();
const toast = useToast();
const API_URL = import.meta.env.VITE_API_URL;

const token = ref(localStorage.getItem('access_token') || '')
const project = ref(null)
const loading = ref(true)
const error = ref('')
const userHasRated = ref(false)
const videoContainer = ref(null)
const videoPlayer = ref(null)
const videoPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const isMuted = ref(false)
const isFullscreen = ref(false)
const volume = ref(1)
const seeking = ref(false)
const techStack = ref([])
const hasAnyMetadata = ref(false)

async function loadProject() {
  loading.value = true
  error.value = ''
  const id = route.params.id
  if (!id) { error.value = 'ID projektu chýba.'; loading.value = false; return }
  try {
    const res = await fetch(`${API_URL}/api/projects/${id}`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    const data = await res.json()
    if (res.ok && data.project) {
      project.value = data.project
      prepareMetadata()
      await incrementViews(id)
      await loadUserRating(id)
    } else {
      error.value = data.message || 'Projekt nebol nájdený.'
    }
  } catch (e) { error.value = 'Chyba pri načítaní projektu.' } finally { loading.value = false }
}

async function loadUserRating(id) {
  try {
    const res = await fetch(`${API_URL}/api/projects/${id}/user-rating`, { headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' } })
    if (res.ok) {
      const data = await res.json();
      if (data.rating !== null) userHasRated.value = true
    }
  } catch (_) {}
}

async function incrementViews(id) {
  try { await fetch(`${API_URL}/api/projects/${id}/views`, { method: 'POST', headers: { 'Authorization': 'Bearer ' + token.value } }) } catch (_) {}
}

function formatRating(val) { return Number(val || 0).toFixed(1) }
function ratingStarClass(star) {
  const avg = Math.round(Number(project.value?.rating)||0)
  return star <= avg ? 'pi pi-star-fill text-yellow-400 text-2xl' : 'pi pi-star text-gray-300 text-2xl'
}
async function submitRating(star) {
  if (userHasRated.value) { toast.add({ severity: 'warn', summary: 'Už hodnotené', detail: 'Projekt môžeš hodnotiť iba raz.', life: 4000 }); return }
  try {
    const res = await fetch(`${API_URL}/api/projects/${route.params.id}/rate`, { method: 'POST', headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ rating: star }) })
    const data = await res.json();
    if (res.ok) {
      userHasRated.value = true
      project.value.rating = data.rating
      project.value.rating_count = data.rating_count
      toast.add({ severity: 'success', summary: 'Hodnotenie uložené', detail: `Dal si ${star} hviezd.`, life: 4000 })
    } else {
      toast.add({ severity: 'error', summary: 'Chyba', detail: data.message || 'Nepodarilo sa uložiť hodnotenie.', life: 4000 })
    }
  } catch (_) { toast.add({ severity: 'error', summary: 'Chyba siete', detail: 'Nepodarilo sa odoslať hodnotenie.', life: 4000 }) }
}
function formatDate(d) { if (!d) return 'Neznámy dátum'; return new Date(d).toLocaleDateString('sk-SK',{year:'numeric',month:'long',day:'numeric'}) }
function isYouTubeUrl(url){ return url && (url.includes('youtube.com')||url.includes('youtu.be')) }
function getYouTubeEmbedUrl(url){ if(!url) return ''; let id=''; if(url.includes('watch?v=')) id=url.split('v=')[1]?.split('&')[0]; else if(url.includes('youtu.be/')) id=url.split('youtu.be/')[1]?.split('?')[0]; else if(url.includes('embed/')) return url; return id?`https://www.youtube.com/embed/${id}`:url }
function getImageUrl(path){ if(!path) return ''; return path.startsWith('http')?path:`${API_URL}/storage/${path}` }
function getVideoUrl(path){ if(!path) return ''; return path.startsWith('http')?path:`${API_URL}/video/${path}` }
function downloadFile(path){ if(!path) return; const url=path.startsWith('http')?path:`${API_URL}/storage/${path}`; const a=document.createElement('a'); a.href=url; a.download=''; a.target='_blank'; document.body.appendChild(a); a.click(); document.body.removeChild(a); toast.add({severity:'success',summary:'Sťahovanie',detail:'Súbor sa sťahuje...',life:3000}) }
function togglePlay(){ if(!videoPlayer.value) return; if(videoPlayer.value.paused){ videoPlayer.value.play().catch(()=>{}); } else { videoPlayer.value.pause(); } }
function skip(sec){ const v=videoPlayer.value; if(!v) return; const target=Math.min(Math.max(0,v.currentTime+sec), v.duration||Infinity); v.currentTime=target; currentTime.value=target }
function onSeekInput(e){ if(!videoPlayer.value) return; seeking.value=true; currentTime.value=parseFloat(e.target.value) }
function onSeekChange(e){ const v=videoPlayer.value; if(!v) return; const val=parseFloat(e.target.value); const target=Math.min(Math.max(0,val), v.duration||Infinity); v.currentTime=target; currentTime.value=target; seeking.value=false }
function toggleMute(){ if(!videoPlayer.value) return; videoPlayer.value.muted=!videoPlayer.value.muted; isMuted.value=videoPlayer.value.muted }
function changeVolume(e){ if(!videoPlayer.value) return; volume.value=Number(e.target.value); videoPlayer.value.volume=volume.value; if(volume.value===0){ videoPlayer.value.muted=true; isMuted.value=true } else if(isMuted.value){ videoPlayer.value.muted=false; isMuted.value=false } }
function onLoadedMetadata(){ if(videoPlayer.value){ duration.value=videoPlayer.value.duration||0; videoPlayer.value.volume=volume.value } }
function onTimeUpdate(){ if(!videoPlayer.value) return; if(!seeking.value) currentTime.value=videoPlayer.value.currentTime }
function toggleFullscreen(){ const el=videoContainer.value; if(!el) return; if(!document.fullscreenElement){ el.requestFullscreen?.().then(()=> isFullscreen.value=true) } else { document.exitFullscreen?.().then(()=> isFullscreen.value=false) } }
function formatTime(t){ const s=Math.floor(t); const m=Math.floor(s/60); const r=s%60; return `${m}:${String(r).padStart(2,'0')}` }
function goToTeam(teamId){ if(teamId) router.push(`/team/${teamId}`) }
function goBack(){ router.push('/') }
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
onMounted(()=>{ loadProject(); fsHandler=()=>{ isFullscreen.value=!!document.fullscreenElement }; document.addEventListener('fullscreenchange',fsHandler); document.addEventListener('keydown',handleKey) })
onUnmounted(()=>{ if(fsHandler) document.removeEventListener('fullscreenchange',fsHandler); document.removeEventListener('keydown',handleKey) })
</script>
