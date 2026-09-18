// ===================================
// STORE TOAST (notifications à l'écran)
// File: src/stores/toast.js
// ===================================

import { defineStore } from 'pinia'

let prochainId = 1

// Minuteries hors de l'état réactif : { id: { reste, debut, minuterie } }
const minuteries = new Map()

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [],
  }),

  actions: {
    afficher(message, type = 'info', duree = 5000) {
      if (!message) return null

      // Pas de doublon : le même message déjà affiché n'est pas empilé
      const existant = this.toasts.find((toast) => toast.message === message && toast.type === type)
      if (existant) return existant.id

      const id = prochainId++
      this.toasts.push({ id, message, type })

      if (duree > 0) {
        minuteries.set(id, { reste: duree, debut: 0, minuterie: null })
        this.reprendre(id)
      }
      return id
    },

    succes(message, duree) {
      return this.afficher(message, 'success', duree)
    },

    erreur(message, duree = 8000) {
      return this.afficher(message, 'error', duree)
    },

    info(message, duree) {
      return this.afficher(message, 'info', duree)
    },

    // Survol ou focus : le message reste affiché le temps de le lire
    suspendre(id) {
      const suivi = minuteries.get(id)
      if (!suivi || !suivi.minuterie) return
      clearTimeout(suivi.minuterie)
      suivi.minuterie = null
      suivi.reste -= Date.now() - suivi.debut
    },

    reprendre(id) {
      const suivi = minuteries.get(id)
      if (!suivi || suivi.minuterie) return
      suivi.debut = Date.now()
      suivi.minuterie = setTimeout(() => this.retirer(id), Math.max(suivi.reste, 1500))
    },

    retirer(id) {
      const suivi = minuteries.get(id)
      if (suivi?.minuterie) clearTimeout(suivi.minuterie)
      minuteries.delete(id)
      this.toasts = this.toasts.filter((toast) => toast.id !== id)
    },
  },
})
