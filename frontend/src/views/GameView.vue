<!-- DEPRECATED VIEW: GameView (replaced by ProjectView). Retained for rollback; do not extend. -->
<template>
  <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8 bg-gray-900 min-h-screen">
    <Toast />

    <!-- Loading State -->
    <div v-if="loadingGame" class="text-center p-8">
      <i class="pi pi-spin pi-spinner text-4xl text-blue-400"></i>
      <p class="mt-4 text-gray-300">Načítavam detail hry...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center p-8 bg-gray-800 rounded-xl border border-gray-700 shadow-lg">
      <i class="pi pi-exclamation-triangle text-4xl text-red-400"></i>
      <p class="mt-4 text-gray-200 font-semibold">{{ error }}</p>
      <Button 
        label="Späť na zoznam hier" 
        icon="pi pi-arrow-left"
        class="mt-4 p-button-outlined"
        @click="goBack"
      />
    </div>

    <!-- Game Detail -->
    <div v-else-if="game" class="space-y-6">
      <!-- Header with Back Button -->
      <div class="flex items-center justify-between mb-6">
        <Button 
          label="Späť" 
          icon="pi pi-arrow-left"
          class="p-button-text p-button-secondary"
          @click="goBack"
        />
      </div>

      <!-- Game Title and Category -->
      <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
          <h1 class="text-4xl font-bold text-gray-100">{{ game.title }}</h1>
          <span class="px-4 py-2 rounded-lg border border-gray-600 bg-gray-700 text-gray-200 font-medium text-lg shadow-lg">
            {{ game.category }}
          </span>
        </div>
        
        <!-- Rating and Views (Large) -->
        <div class="flex items-center gap-6 mb-4 pb-4 border-b border-gray-700">
          <!-- Star Rating -->
          <div class="flex flex-col">
            <div class="flex items-center gap-2">
              <i 
                v-for="star in 5" 
                :key="star" 
                @click="submitRating(star)"
                :class="ratingStarClass(star)"
                class="cursor-pointer transition-transform"
                :title="'Hodnotiť ' + star + ' / 5'"
              ></i>
              <span class="ml-2 text-gray-200 font-bold text-xl">{{ formatRating(game.rating) }} / 5</span>
            </div>
            <small class="text-gray-400 mt-1" v-if="userHasRated">Ďakujeme za hodnotenie (hlasov: {{ game.rating_count || 0 }}).</small>
            <small class="text-gray-400 mt-1" v-else> Kliknite na hviezdu pre hodnotenie (iba raz). (hlasov: {{ game.rating_count || 0 }})</small>
          </div>
          <!-- Views Counter -->
          <div class="flex items-center gap-2 text-gray-300">
            <span class="font-bold text-xl">{{ game.views || 0 }} zobrazení</span>
          </div>
        </div>
        
        <div class="flex flex-wrap gap-2.5 text-sm">
          <span 
            class="px-3.5 py-1.5 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg cursor-pointer hover:bg-gray-600 transition"
            @click="goToTeam(game.team?.id)"
          >
            {{ game.team?.name || 'Neznámy' }}
          </span>
          <span v-if="game.academic_year" class="px-3.5 py-1.5 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg">
            {{ game.academic_year.name }}
          </span>
          <span v-if="game.release_date" class="px-3.5 py-1.5 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg">
            {{ formatDate(game.release_date) }}
          </span>
        </div>
      </div>

      <!-- Trailer Section with Splash Screen Poster -->
      <div v-if="game.trailer_path || game.splash_screen_path" class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">{{ game.trailer_path ? 'Trailer' : 'Ukážka' }}</h2>
        
        <!-- YouTube Video with Custom Thumbnail -->
        <div v-if="isYouTubeUrl(game.trailer_path)" class="aspect-video w-full relative rounded-lg overflow-hidden">
          <div v-if="!youtubePlayerStarted" 
               class="absolute inset-0 cursor-pointer group"
               @click="startYouTubePlayer">
            <img 
              v-if="game.splash_screen_path"
              :src="getImageUrl(game.splash_screen_path)" 
              :alt="game.title"
              class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-black bg-opacity-30 group-hover:bg-opacity-40 transition-all duration-300"></div>
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="w-20 h-20 rounded-full bg-white bg-opacity-50 group-hover:bg-opacity-70 transition-all duration-300 flex items-center justify-center">
                <i class="pi pi-play text-gray-900 text-3xl ml-1"></i>
              </div>
            </div>
          </div>
          <iframe 
            v-else
            :src="getYouTubeEmbedUrl(game.trailer_path) + '?autoplay=1'"
            class="w-full h-full rounded-lg"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>
        </div>
        
        <!-- Local Video File with Splash Poster and Improved Controls -->
        <div v-else-if="game.trailer_path" class="aspect-video w-full relative rounded-lg overflow-hidden bg-black group" ref="videoContainer" tabindex="0">
          <video 
            ref="videoPlayer"
            class="w-full h-full rounded-lg cursor-pointer"
            :src="getVideoUrl(game.trailer_path)"
            :poster="game.splash_screen_path ? getImageUrl(game.splash_screen_path) : ''"
            playsinline
            preload="metadata"
            @loadedmetadata="onLoadedMetadata"
            @loadeddata="onLoadedData"
            @canplay="onCanPlay"
            @timeupdate="onTimeUpdate"
            @seeking="onSeeking"
            @seeked="onSeeked"
            @play="videoPlaying = true"
            @pause="videoPlaying = false"
            @ended="videoPlaying = false"
            @click="togglePlay"
          >
            Váš prehliadač nepodporuje prehrávanie videa.
          </video>

          <!-- Center Play Indicator (when paused) -->
          <div v-if="!videoPlaying" class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="w-20 h-20 rounded-full bg-black/50 flex items-center justify-center backdrop-blur-sm">
              <i class="pi pi-play text-white text-3xl ml-1"></i>
            </div>
          </div>

          <!-- Control Panel -->
          <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent px-4 pt-4 pb-3 flex flex-col gap-2 text-xs transition-opacity duration-300"
               :class="videoPlaying ? 'opacity-0 group-hover:opacity-100' : 'opacity-100'">
            <!-- Progress -->
            <div class="flex items-center gap-3 w-full">
              <span class="text-gray-300 font-mono">{{ formatTime(currentTime) }}</span>
              <div class="flex-1 relative h-1.5 bg-gray-600 rounded-lg cursor-pointer group/progress">
                <!-- Buffered/Loaded portion (optional visual enhancement) -->
                <div class="absolute left-0 top-0 h-full bg-gray-500 rounded-lg" :style="{ width: '100%' }"></div>
                <!-- Progress bar (played portion) -->
                <div class="absolute left-0 top-0 h-full bg-teal-400 rounded-lg transition-all duration-150" :style="{ width: (duration > 0 ? (currentTime / duration * 100) : 0) + '%' }"></div>
                <!-- Input slider (invisible but interactive) -->
                <input
                  type="range"
                  min="0"
                  :max="duration"
                  :value="currentTime"
                  @input="onSeekInput"
                  @change="onSeekChange"
                  class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                />
                <!-- Thumb indicator -->
                <div class="absolute top-1/2 -translate-y-1/2 w-4 h-4 bg-teal-400 rounded-full transition-all group-hover/progress:scale-125 shadow-lg"
                     :style="{ left: (duration > 0 ? (currentTime / duration * 100) : 0) + '%', transform: 'translateX(-50%) translateY(-50%)' }"></div>
              </div>
              <span class="text-gray-300 font-mono">{{ formatTime(duration) }}</span>
            </div>
            <!-- Buttons Row -->
            <div class="flex items-center justify-between w-full mt-1">
              <div class="flex items-center gap-4">
                <button @click.stop="togglePlay" class="text-white hover:text-teal-400 transition p-1" :title="videoPlaying ? 'Pause' : 'Play'">
                  <i :class="videoPlaying ? 'pi pi-pause' : 'pi pi-play'" class="text-xl"></i>
                </button>
                <div class="flex items-center gap-2">
                  <button @click.stop="skip(-10)" class="text-gray-300 hover:text-white transition p-1" title="Dozadu 10s">
                    <i class="pi pi-replay text-lg"></i> <span class="text-[10px]">-10s</span>
                  </button>
                  <button @click.stop="skip(10)" class="text-gray-300 hover:text-white transition p-1" title="Dopredu 10s">
                    <span class="text-[10px]">+10s</span> <i class="pi pi-refresh text-lg"></i>
                  </button>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 group/vol">
                  <button @click.stop="toggleMute" class="text-white hover:text-teal-400 transition" :title="isMuted || volume===0 ? 'Zapnúť zvuk' : 'Stlmiť zvuk'">
                    <i :class="(isMuted || volume===0) ? 'pi pi-volume-off' : 'pi pi-volume-up'" class="text-lg"></i>
                  </button>
                  <input
                    type="range"
                    min="0"
                    max="1"
                    step="0.05"
                    :value="isMuted ? 0 : volume"
                    @input="changeVolume"
                    class="w-20 h-1 bg-gray-600 rounded-lg appearance-none cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-3 [&::-webkit-slider-thumb]:h-3 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:rounded-full"
                  />
                </div>
                <div class="w-px h-4 bg-gray-600 mx-1"></div>
                <button @click.stop="toggleFullscreen" class="text-white hover:text-teal-400 transition" :title="isFullscreen ? 'Ukončiť celú obrazovku' : 'Celá obrazovka'">
                  <i :class="isFullscreen ? 'pi pi-window-minimize' : 'pi pi-window-maximize'" class="text-lg"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Only Splash Screen (no video) -->
        <div v-else-if="game.splash_screen_path" class="aspect-video w-full rounded-lg overflow-hidden">
          <img 
            :src="getImageUrl(game.splash_screen_path)" 
            :alt="game.title"
            class="w-full h-full object-cover"
          />
        </div>
      </div>

      <!-- Description -->
      <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">Popis hry</h2>
        <p class="text-gray-300 text-base leading-relaxed whitespace-pre-wrap">
          {{ game.description || 'Popis nebol poskytnutý.' }}
        </p>
      </div>

      <!-- Team Members -->
      <div v-if="game.team?.members && game.team.members.length > 0" class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">Členovia tímu</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          <div 
            v-for="member in game.team.members" 
            :key="member.id"
            class="flex items-center justify-between gap-3 p-3 bg-gray-700 rounded-lg border border-gray-600 transition hover:border-gray-500 shadow-lg"
          >
            <div class="flex flex-col">
              <span class="text-gray-200 font-medium">{{ member.name }}</span>
              <span v-if="member.pivot?.role_in_team === 'scrum_master'" class="text-xs text-teal-400">Scrum Master</span>
              <span v-else class="text-xs text-gray-500">Člen</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Download Links -->
      <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">Na stiahnutie</h2>
        <div class="flex flex-col gap-3">
          <Button 
            v-if="game.export_path"
            label="Stiahnuť hru" 
            icon="pi pi-download"
            class="p-button-outlined p-button-lg"
            @click="downloadFile(game.export_path, 'export')"
          />
          <Button 
            v-if="game.source_code_path"
            label="Stiahnuť zdrojový kód" 
            icon="pi pi-file-code"
            class="p-button-outlined p-button-lg"
            @click="downloadFile(game.source_code_path, 'source_code')"
          />
          <p v-if="!game.export_path && !game.source_code_path" class="text-gray-400 text-center py-4">
            Žiadne súbory na stiahnutie.
          </p>
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

const route = useRoute()
const router = useRouter()
const toast = useToast()

const API_URL = import.meta.env.VITE_API_URL
const token = ref(localStorage.getItem('access_token') || '')
const game = ref(null)
const loadingGame = ref(true)
const error = ref('')
const userHasRated = ref(false)
const userRatingValue = ref(null)
const videoContainer = ref(null)
const videoPlayer = ref(null)
const videoPlaying = ref(false)
const youtubePlayerStarted = ref(false)
// Custom video control state
const currentTime = ref(0)
const duration = ref(0)
const isMuted = ref(false)
const isFullscreen = ref(false)
const volume = ref(1) // 0 - 1
const seeking = ref(false)

// Load single game detail via /api/games/{id}
async function loadGameDetail() {
  loadingGame.value = true
  error.value = ''
  const gameId = route.params.id
  if (!gameId) {
    error.value = 'ID hry nebolo poskytnuté.'
    loadingGame.value = false
    return
  }
  console.log('[GameView] Fetching single game', gameId)
  try {
    const res = await fetch(`${API_URL}/api/games/${gameId}`, {
      headers: {
        'Authorization': 'Bearer ' + token.value,
        'Accept': 'application/json'
      }
    })
    if (res.ok) {
      const data = await res.json()
      game.value = data.game
      if (!game.value) {
        error.value = 'Hra nebola nájdená.'
      } else {
        console.log('[GameView] Game loaded, incrementing views')
        await incrementViews(gameId)
      }
    } else {
      error.value = `Nepodarilo sa načítať detail hry (status ${res.status}).`
    }
  } catch (err) {
    console.error('[GameView] Error loading game detail:', err)
    error.value = 'Chyba pri načítaní hry. Skontrolujte pripojenie k serveru.'
  } finally {
    loadingGame.value = false
  }
}

// Load user rating state
async function loadUserRating(gameId) {
  try {
    const res = await fetch(`${API_URL}/api/games/${gameId}/user-rating`, {
      headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }
    })
    if (res.ok) {
      const data = await res.json()
      userHasRated.value = data.hasRated
      userRatingValue.value = data.rating
      if (game.value) {
        game.value.rating = Number(data.average)
        game.value.rating_count = data.rating_count
      }
      console.log('[GameView] User rating status loaded', data)
    }
  } catch (err) {
    // Silent fail
    console.warn('[GameView] Failed to load user rating', err)
  }
}

// Increment view count
async function incrementViews(gameId) {
  try {
    const res = await fetch(`${API_URL}/api/games/${gameId}/views`, {
      method: 'POST',
      headers: { 
        'Authorization': 'Bearer ' + token.value, 
        'Accept': 'application/json' 
      }
    })

    if (res.ok) {
      const data = await res.json()
      if (game.value) {
        game.value.views = data.views
      }
      console.log('[GameView] Views incremented to', data.views)
    }
  } catch (err) {
    // Silent fail - view tracking is not critical
    console.warn('[GameView] Error incrementing views:', err)
  }
}

function formatRating(val) {
  return Number(val || 0).toFixed(1)
}

function ratingStarClass(star) {
  const currentAvgRounded = Math.round(Number(game.value?.rating) || 0)
  if (userHasRated.value) {
    return star <= currentAvgRounded ? 'pi pi-star-fill text-yellow-400 text-2xl' : 'pi pi-star text-gray-300 text-2xl'
  } else {
    return star <= currentAvgRounded ? 'pi pi-star-fill text-yellow-400 text-2xl hover:scale-110' : 'pi pi-star text-gray-300 text-2xl hover:scale-110'
  }
}

async function submitRating(star) {
  if (userHasRated.value) {
    toast.add({ severity: 'warn', summary: 'Už hodnotené', detail: 'Hru môžeš hodnotiť iba raz.', life: 4000 })
    return
  }
  try {
    const res = await fetch(`${API_URL}/api/games/${route.params.id}/rate`, {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token.value,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ rating: star })
    })
    const data = await res.json()
    if (res.ok) {
      userHasRated.value = true
      userRatingValue.value = star
      if (game.value) {
        game.value.rating = Number(data.rating)
        game.value.rating_count = data.rating_count
      }
      toast.add({ severity: 'success', summary: 'Hodnotenie uložené', detail: `Dal si ${star} hviezd.`, life: 4000 })
    } else {
      toast.add({ severity: 'error', summary: 'Chyba', detail: data.message || 'Nepodarilo sa uložiť hodnotenie.', life: 4000 })
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Chyba siete', detail: 'Nepodarilo sa odoslať hodnotenie.', life: 4000 })
  }
}

// Format date
function formatDate(dateString) {
  if (!dateString) return 'Neznámy dátum'
  const date = new Date(dateString)
  return date.toLocaleDateString('sk-SK', { year: 'numeric', month: 'long', day: 'numeric' })
}

// Check if URL is YouTube
function isYouTubeUrl(url) {
  return url && (url.includes('youtube.com') || url.includes('youtu.be'))
}

// Get YouTube embed URL
function getYouTubeEmbedUrl(url) {
  if (!url) return ''
  
  // Extract video ID from various YouTube URL formats
  let videoId = ''
  if (url.includes('youtube.com/watch?v=')) {
    videoId = url.split('v=')[1]?.split('&')[0]
  } else if (url.includes('youtu.be/')) {
    videoId = url.split('youtu.be/')[1]?.split('?')[0]
  } else if (url.includes('youtube.com/embed/')) {
    return url
  }
  
  return videoId ? `https://www.youtube.com/embed/${videoId}` : url
}

// Get image URL from storage
function getImageUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${API_URL}/storage/${path}`
}

// Get video URL from storage with byte-range support
function getVideoUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${API_URL}/video/${path}`
}

// Download file
function downloadFile(path, type) {
  if (!path) return
  
  const url = path.startsWith('http') ? path : `${API_URL}/storage/${path}`
  const link = document.createElement('a')
  link.href = url
  link.download = ''
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  
  toast.add({ 
    severity: 'success', 
    summary: 'Sťahovanie', 
    detail: 'Súbor sa sťahuje...', 
    life: 3000 
  })
}

// Video controls
function playVideo() {
  if (videoPlayer.value) {
    videoPlayer.value.play()
    videoPlaying.value = true
  }
}

function togglePlay() {
  if (!videoPlayer.value) return
  console.log('[VideoControl] togglePlay - current paused:', videoPlayer.value.paused)
  if (videoPlayer.value.paused) {
    videoPlayer.value.play().catch(e => console.error('Play failed', e))
  } else {
    videoPlayer.value.pause()
  }
}

function skip(seconds) {
  const video = videoPlayer.value
  if (!video) return

  const current = video.currentTime
  const vidDuration = (video.duration && !isNaN(video.duration)) ? video.duration : Infinity
  const target = Math.min(Math.max(0, current + seconds), vidDuration)

  console.log('[VideoControl] BEFORE skip - currentTime:', current, 'target:', target, 'duration:', vidDuration)
  console.log('[VideoControl] Video paused:', video.paused, 'readyState:', video.readyState)
  console.log('[VideoControl] Seekable:', video.seekable.length, video.seekable.length > 0 ? `start: ${video.seekable.start(0)}, end: ${video.seekable.end(0)}` : 'none')
  
  video.currentTime = target
  currentTime.value = target
  
  console.log('[VideoControl] AFTER setting currentTime to', target, '=> actual value is now:', video.currentTime)
}

function onSeekInput(evt) {
  if (!videoPlayer.value) return
  seeking.value = true
  const val = parseFloat(evt.target.value)
  console.log('[VideoControl] onSeekInput:', val)
  currentTime.value = val
}

function onSeekChange(evt) {
  const video = videoPlayer.value
  if (!video) return
  
  const val = parseFloat(evt.target.value)
  const vidDuration = (video.duration && !isNaN(video.duration)) ? video.duration : Infinity
  const target = Math.min(Math.max(0, val), vidDuration)
  
  console.log('[VideoControl] onSeekChange - seeking to:', target)
  
  video.currentTime = target
  currentTime.value = target
  seeking.value = false
}

function toggleMute() {
  if (!videoPlayer.value) return
  videoPlayer.value.muted = !videoPlayer.value.muted
  isMuted.value = videoPlayer.value.muted
}

function changeVolume(evt) {
  if (!videoPlayer.value) return
  const val = Number(evt.target.value)
  volume.value = Math.min(Math.max(val, 0), 1)
  videoPlayer.value.volume = volume.value
  if (volume.value === 0) {
    videoPlayer.value.muted = true
    isMuted.value = true
  } else if (isMuted.value) {
    videoPlayer.value.muted = false
    isMuted.value = false
  }
}

function onLoadedMetadata() {
  if (!videoPlayer.value) return
  const video = videoPlayer.value
  duration.value = video.duration || 0
  video.volume = volume.value
  
  console.log('[VideoControl] onLoadedMetadata - duration:', duration.value, 'readyState:', video.readyState)
  console.log('[VideoControl] Video src:', video.currentSrc)
  console.log('[VideoControl] Seekable ranges:', video.seekable.length, video.seekable.length > 0 ? `[${video.seekable.start(0)} - ${video.seekable.end(0)}]` : 'none')
}

function onLoadedData() {
  console.log('[VideoControl] onLoadedData - video data loaded, readyState:', videoPlayer.value?.readyState)
}

function onCanPlay() {
  console.log('[VideoControl] onCanPlay - video ready to play, readyState:', videoPlayer.value?.readyState)
}

function onSeeking() {
  console.log('[VideoControl] onSeeking - video is seeking to:', videoPlayer.value?.currentTime)
}

function onSeeked() {
  console.log('[VideoControl] onSeeked - video finished seeking at:', videoPlayer.value?.currentTime)
  if (videoPlayer.value) {
    currentTime.value = videoPlayer.value.currentTime
  }
}

function onTimeUpdate() {
  if (!videoPlayer.value) return
  if (!seeking.value) {
    currentTime.value = videoPlayer.value.currentTime
  }
}

function toggleFullscreen() {
  const el = videoContainer.value
  if (!el) return
  if (!document.fullscreenElement) {
    el.requestFullscreen?.().then(() => isFullscreen.value = true)
  } else {
    document.exitFullscreen?.().then(() => isFullscreen.value = false)
  }
}

function formatTime(t) {
  const sec = Math.floor(t)
  const m = Math.floor(sec / 60)
  const s = sec % 60
  return `${m}:${String(s).padStart(2,'0')}`
}

function handleKeydown(e) {
  // Avoid interfering with text inputs
  const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : ''
  if (['input','textarea','select','button'].includes(tag)) return
  if (!videoPlayer.value) return
  switch (e.code) {
    case 'Space':
      e.preventDefault()
      togglePlay()
      break
    case 'ArrowLeft':
      e.preventDefault()
      skip(-5)
      break
    case 'ArrowRight':
      e.preventDefault()
      skip(5)
      break
  }
}

let fsChangeHandler
onMounted(() => {
  loadGameDetail()
  loadUserRating(route.params.id)
  if (videoPlayer.value) {
    videoPlayer.value.volume = volume.value
  }
  fsChangeHandler = () => { isFullscreen.value = !!document.fullscreenElement }
  document.addEventListener('fullscreenchange', fsChangeHandler)
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  if (fsChangeHandler) document.removeEventListener('fullscreenchange', fsChangeHandler)
  document.removeEventListener('keydown', handleKeydown)
})

function startYouTubePlayer() {
  youtubePlayerStarted.value = true
}

// Navigate to team detail
function goToTeam(teamId) {
  if (teamId) {
    router.push(`/team/${teamId}`)
  }
}

// Go back to home
function goBack() {
  router.push('/')
}

// (Second onMounted removed; logic consolidated above)
</script>

<style scoped>
/* Additional styles if needed */
</style>
