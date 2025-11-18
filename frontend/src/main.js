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

app.mount('#app')
