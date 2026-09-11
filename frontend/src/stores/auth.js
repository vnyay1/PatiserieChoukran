// ===================================
// 4. STORE AUTH
// File: src/stores/auth.js
// ===================================

import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    isVendeur: (state) => state.user?.role === 'vendeur',
    isClient: (state) => state.user?.role === 'client',
    canManageCatalogue: (state) => ['admin', 'vendeur'].includes(state.user?.role),
    userName: (state) => state.user?.nom_complet || '',
  },

  actions: {
    clearSession() {
      this.user = null
      this.token = null
      localStorage.removeItem('token')
    },

    // Connexion
    async login(credentials) {
      this.loading = true
      this.error = null

      try {
        const response = await api.auth.login(credentials)

        if (response.data.success) {
          this.token = response.data.data.token
          this.user = response.data.data.user
          localStorage.setItem('token', this.token)
          return { success: true }
        }

        this.error = response.data?.message || 'Erreur de connexion'
        return { success: false, error: this.error }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur de connexion'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Inscription
    async register(userData) {
      this.loading = true
      this.error = null

      try {
        const response = await api.auth.register(userData)

        if (response.data.success) {
          this.token = response.data.data.token
          this.user = response.data.data.user
          localStorage.setItem('token', this.token)
          return { success: true }
        }

        this.error = response.data?.message || 'Erreur d\'inscription'
        return { success: false, error: this.error }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur d\'inscription'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Récupérer les infos utilisateur
    async fetchUser() {
      if (!this.token) return

      try {
        const response = await api.auth.getUser()
        if (response.data.success) {
          this.user = response.data.data
        }
      } catch (error) {
        console.error('Erreur récupération utilisateur:', error)
        this.clearSession()
      }
    },

    // Déconnexion
    async logout(options = {}) {
      const { callApi = true } = options

      try {
        if (callApi && this.token) {
          await api.auth.logout()
        }
      } catch (error) {
        console.error('Erreur déconnexion:', error)
      } finally {
        this.clearSession()
      }
    },

    // Initialiser l'authentification au démarrage (la garde du router a
    // généralement déjà chargé l'utilisateur : pas de second appel)
    async initialize() {
      if (this.token && !this.user) {
        await this.fetchUser()
      }
    }
  }
})
