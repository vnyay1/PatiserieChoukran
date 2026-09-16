// ===================================
// STORE TOAST (notifications à l'écran)
// File: src/stores/toast.js
// ===================================

import { defineStore } from 'pinia'

let prochainId = 1

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [],
  }),

  actions: {
    afficher(message, type = 'info', duree = 4000) {
      if (!message) return null

      // Pas de doublon : le même message déjà affiché n'est pas empilé
      const existant = this.toasts.find((toast) => toast.message === message && toast.type === type)
      if (existant) return existant.id

      const id = prochainId++
      this.toasts.push({ id, message, type })

      if (duree > 0) {
        setTimeout(() => this.retirer(id), duree)
      }
      return id
    },

    succes(message, duree) {
      return this.afficher(message, 'success', duree)
    },

    erreur(message, duree = 6000) {
      return this.afficher(message, 'error', duree)
    },

    info(message, duree) {
      return this.afficher(message, 'info', duree)
    },

    retirer(id) {
      this.toasts = this.toasts.filter((toast) => toast.id !== id)
    },
  },
})
