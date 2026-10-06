import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  // Backend visé par le proxy : :8000 par défaut ; VITE_CIBLE_API (shell ou .env.local)
  // branche le SPA sur un autre serveur, par exemple une API de démonstration
  const cibleApi = env.VITE_CIBLE_API || 'http://127.0.0.1:8000'
  // Noms d'hôte acceptés en plus de localhost (tunnel ngrok…), séparés par des virgules :
  // le serveur de dev n'est exposé que sur demande, jamais par défaut
  const hotesAutorises = (env.VITE_HOTES_AUTORISES || '').split(',').map((hote) => hote.trim()).filter(Boolean)
  // Adresse publique du site (https://…) : rend absolue l'image de partage d'index.html ;
  // vide par défaut, l'adresse reste relative
  const urlPublique = (env.VITE_URL_PUBLIQUE || '').replace(/\/+$/, '')

  return {
    plugins: [
      vue(),
      {
        name: 'url-publique',
        // Avant le traitement HTML de Vite, qui signalerait %URL_PUBLIQUE% comme variable inconnue
        transformIndexHtml: {
          order: 'pre',
          handler: (html) => html.replaceAll('%URL_PUBLIQUE%', urlPublique),
        },
      },
    ],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
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
      allowedHosts: hotesAutorises,
    },
    css: {
      postcss: './postcss.config.js',
    },
    build: {
      // Polices jamais inlinées en data: URI (Vite inline les fichiers de moins de 4 Kio) : la CSP
      // de production (font-src 'self') les refuserait. En fichiers, elles ne sont téléchargées que
      // si leur plage de caractères (unicode-range) sert sur la page.
      assetsInlineLimit: (fichier) => (/\.(woff2?|ttf|otf)$/.test(fichier) ? false : undefined),
      rolldownOptions: {
        output: {
          // Bibliothèques dans un fichier à part : il reste en cache d'un déploiement à l'autre
          manualChunks(id) {
            if (/[\\/]node_modules[\\/](vue|@vue|vue-router|pinia|axios)[\\/]/.test(id)) {
              return 'vendor'
            }
          },
        },
      },
    },
  }
})
