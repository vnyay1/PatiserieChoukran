// ===================================
// 1. MAIN.JS - Point d'entrée
// File: src/main.js
// ===================================
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useThemeStore } from './stores/theme'
import './assets/styles/tailwind.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

useThemeStore(pinia).initialiser()

app.mount('#app')
