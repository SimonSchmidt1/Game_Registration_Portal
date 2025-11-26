import './assets/main.css'
import { createApp } from 'vue'
import App from './App.vue'

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

axios.defaults.baseURL = 'http://127.0.0.1:8000/api'
axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('access_token')}`

// Automatické nastavenie tokenu
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

app.use(PrimeVue, { theme: { preset: Aura } })
app.use(ToastService) 

app.use(router)

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
