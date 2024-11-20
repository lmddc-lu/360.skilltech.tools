import './assets/css/main.css'
import './assets/fonts/inter/stylesheet.css'
import './assets/fonts/jakarta/stylesheet.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

const app = createApp(App)

app.use(router)

app.mount('#app')
