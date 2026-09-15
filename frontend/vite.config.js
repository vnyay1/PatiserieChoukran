import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 5173,
    watch: {
      usePolling: true,
    },
    // L'API passe par le serveur Vite : même origine (pas de requête CORS préalable)
    // et 127.0.0.1 plutôt que localhost, que Windows tente d'abord en IPv6 (~200 ms par connexion)
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/storage': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
    open: true,
    allowedHosts: [
      'unclotted-arian-semimechanical.ngrok-free.dev'
    ],
  },
  css: {
    postcss: './postcss.config.js',
  },
  build: {
    rollupOptions: {
      output: {
        // Bibliothèques dans un fichier à part : il reste en cache d'un déploiement à l'autre
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia', 'axios'],
        },
      },
    },
  },
})
