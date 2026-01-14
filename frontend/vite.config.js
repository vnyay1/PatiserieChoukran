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
    open: true,
    allowedHosts: [
      'unclotted-arian-semimechanical.ngrok-free.dev'
    ],
  },
  css: {
    postcss: './postcss.config.js',
  },
})
