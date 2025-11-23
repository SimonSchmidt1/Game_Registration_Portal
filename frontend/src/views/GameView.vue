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
          <span class="px-3.5 py-1.5 rounded-md border border-gray-600 bg-gray-700 text-gray-200 font-medium shadow-lg">
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

      <!-- Splash Screen / Cover Image -->
      <div v-if="game.splash_screen_path" class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl overflow-hidden border border-gray-700">
        <img 
          :src="getImageUrl(game.splash_screen_path)" 
          :alt="game.title"
          class="w-full h-auto object-cover max-h-96"
        />
      </div>

      <!-- Trailer Section -->
      <div v-if="game.trailer_path" class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 border border-gray-700">
        <h2 class="text-2xl font-bold text-gray-100 mb-4">Trailer</h2>
        
        <!-- YouTube Video -->
        <div v-if="isYouTubeUrl(game.trailer_path)" class="aspect-video w-full">
          <iframe 
            :src="getYouTubeEmbedUrl(game.trailer_path)"
            class="w-full h-full rounded-lg"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>
        </div>
        
        <!-- Video File -->
        <div v-else class="aspect-video w-full bg-gray-100 rounded-lg flex items-center justify-center">
          <video 
            controls 
            class="w-full h-full rounded-lg"
            :src="getVideoUrl(game.trailer_path)"
          >
            Váš prehliadač nepodporuje prehrávanie videa.
          </video>
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
            class="flex items-center gap-3 p-3 bg-gray-700 rounded-lg border border-gray-600 transition hover:border-gray-500 shadow-lg"
          >
            <span class="text-gray-200 font-medium">{{ member.name }}</span>
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
import { ref, onMounted } from 'vue'
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

// Get video URL from storage
function getVideoUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${API_URL}/storage/${path}`
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

// Go back to home
function goBack() {
  router.push('/')
}

onMounted(() => {
  loadGameDetail()
  loadUserRating(route.params.id)
})
</script>

<style scoped>
/* Additional styles if needed */
</style>
