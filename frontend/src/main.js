import './assets/main.css'
import { createApp } from 'vue'
import App from './App.vue'

import PrimeVue from 'primevue/config'
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

import 'primeicons/primeicons.css'

import router from './router'

const app = createApp(App)

app.use(PrimeVue, {
  theme: {
    preset: Aura,
  },
})

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

app.mount('#app')
