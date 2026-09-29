import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Backend visé par le proxy : :8000 par défaut ; VITE_CIBLE_API (shell ou .env.local)
  // branche le SPA sur un autre serveur, par exemple une API de démonstration
  const cibleApi = loadEnv(mode, process.cwd(), '').VITE_CIBLE_API || 'http://127.0.0.1:8000'

  return {
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
          target: cibleApi,
          changeOrigin: true,
        },
        '/storage': {
          target: cibleApi,
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
  }
})
