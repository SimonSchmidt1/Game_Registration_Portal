import './assets/main.css'
import { createApp } from 'vue'
import App from './App.vue'
import i18n from './i18n'

import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import Aura from '@primevue/themes/aura' 
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'
import FileUpload from 'primevue/fileupload' 
import Tabs from 'primevue/tabs' 
import TabPanel from 'primevue/tabpanel' 
import Toast from 'primevue/toast'
import axios from 'axios'
import 'primeicons/primeicons.css'
import router from './router'

const storedTheme = localStorage.getItem('theme_preference')
const initialTheme = storedTheme === 'light' ? 'light' : 'dark'
document.documentElement.setAttribute('data-theme', initialTheme)

// --- PATCH FETCH FOR SANCTUM CSRF (Because AdminView uses native fetch instead of axios) ---
const originalFetch = window.fetch
window.fetch = async function(url, options = {}) {
  options.credentials = 'include'
  
  if (options.method && !['GET', 'HEAD', 'OPTIONS', 'TRACE'].includes(options.method.toUpperCase())) {
    options.headers = options.headers || {}
    // Only set header if it's a plain object (not Headers instance, but fetch allows raw objects usually)
    if (!(options.headers instanceof Headers)) {
      const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]*)'))
      if (match) {
        options.headers['X-XSRF-TOKEN'] = decodeURIComponent(match[2])
      }
      options.headers['Accept'] = 'application/json'
    } else {
      const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]*)'))
      if (match) {
        options.headers.append('X-XSRF-TOKEN', decodeURIComponent(match[2]))
      }
      options.headers.append('Accept', 'application/json')
    }
  }
  return originalFetch(url, options)
}
// ------------------------------------------------------------------------------------------

// Use environment variable for API URL (no hardcoding)
const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000'
axios.defaults.baseURL = `${API_URL}/api`
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.timeout = 12000 // 12 second timeout

// Add critical Sanctum CSRF settings
axios.defaults.withCredentials = true
axios.defaults.withXSRFToken = true

// Set auth token if available
const token = localStorage.getItem('access_token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

const app = createApp(App)

// Global error handler (Vue error boundary)
app.config.errorHandler = (err, instance, info) => {
  console.error('Vue Error:', err)
  console.error('Component:', instance)
  console.error('Error Info:', info)
  
  // Show user-friendly error toast if available
  const toast = instance?.$root?.$toast || instance?.appContext?.config?.globalProperties?.$toast
  if (toast) {
    toast.add({
      severity: 'error',
      summary: 'Chyba aplikácie',
      detail: 'Vyskytla sa nečakaná chyba. Skúste obnoviť stránku.',
      life: 8000
    })
  }
}

app.use(i18n)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: 'html[data-theme="dark"]'
    }
  }
})
app.use(ToastService) 

app.use(router)

// v-click-outside directive
app.directive('click-outside', {
  mounted(el, binding) {
    el._clickOutsideHandler = (e) => { if (!el.contains(e.target)) binding.value(e) }
    document.addEventListener('click', el._clickOutsideHandler)
  },
  unmounted(el) { document.removeEventListener('click', el._clickOutsideHandler) }
})

// Registrácia komponentov
app.component('Button', Button)
app.component('InputText', InputText)
app.component('DataTable', DataTable)
app.component('Column', Column)
app.component('Dropdown', Dropdown)
app.component('Calendar', Calendar)
app.component('Textarea', Textarea)
app.component('FileUpload', FileUpload)
app.component('Tabs', Tabs) 
app.component('TabPanel', TabPanel) 
app.component('Toast', Toast) // len raz!

// Axios interceptor for auth errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    if (status === 401) {
      // Clear auth and redirect only if user was logged in
      if (localStorage.getItem('access_token')) {
        localStorage.removeItem('access_token')
        localStorage.removeItem('user')
        window.dispatchEvent(new Event('logout'))
        if (router.currentRoute.value.path !== '/login') {
          router.push('/login')
        }
      }
    }
    return Promise.reject(error)
  }
)

app.mount('#app')
