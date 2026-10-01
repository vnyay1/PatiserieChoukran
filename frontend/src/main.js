// ===================================
// 1. MAIN.JS - Point d'entrée
// File: src/main.js
// ===================================
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useThemeStore } from './stores/theme'
// Polices servies par le site lui-même (pas de Google Fonts : rendu non bloqué, CSP sans
// domaine tiers) ; seules les graisses utilisées sont chargées
import '@fontsource/manrope/400.css'
import '@fontsource/manrope/500.css'
import '@fontsource/manrope/600.css'
import '@fontsource/manrope/700.css'
import '@fontsource/playfair-display/400.css'
import '@fontsource/playfair-display/400-italic.css'
import '@fontsource/playfair-display/600.css'
import '@fontsource/playfair-display/700.css'
import './assets/styles/tailwind.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

useThemeStore(pinia).initialiser()

app.mount('#app')
