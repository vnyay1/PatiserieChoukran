// ===================================
// 5. STORE PANIER
// File: src/stores/panier.js
// ===================================

import { defineStore } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

export const usePanierStore = defineStore('panier', {
  state: () => ({
    items: [],
    loading: false,
    error: null,
    ownerUserId: null,
  }),

  getters: {
    // Nombre de produits distincts dans le panier
    itemCount: (state) => state.items.length,
    total: (state) => state.items.reduce((sum, item) => sum + parseFloat(item.sous_total), 0),
    isEmpty: (state) => state.items.length === 0,
  },

  actions: {
    // Réinitialiser l'état local du panier (sans appel API)
    reset() {
      this.items = []
      this.loading = false
      this.error = null
      this.ownerUserId = null
    },

    // Charger le panier
    async fetch() {
      this.loading = true
      const authStore = useAuthStore()
      try {
        const response = await api.panier.get()
        if (response.data.success) {
          this.items = response.data.data.items
          this.ownerUserId = authStore.user?.id || null
        }
      } catch (error) {
        this.error = 'Erreur lors du chargement du panier'
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    // Ajouter au panier
    async addItem(produitId, quantite = 1) {
      try {
        const response = await api.panier.add({ produit_id: produitId, quantite })
        if (response.data.success) {
          await this.fetch() // Recharger le panier
          return { success: true, message: 'Produit ajouté au panier' }
        }
        return {
          success: false,
          message: response.data?.message || 'Erreur lors de l\'ajout au panier'
        }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Erreur lors de l\'ajout au panier' 
        }
      }
    },

    // Mettre à jour la quantité
    async updateQuantity(itemId, quantite) {
      try {
        const response = await api.panier.update(itemId, { quantite })
        if (response.data.success) {
          await this.fetch()
          return { success: true }
        }
      } catch (error) {
        return { success: false, message: 'Erreur lors de la mise à jour' }
      }
    },

    // Retirer un article
    async removeItem(itemId) {
      try {
        const response = await api.panier.remove(itemId)
        if (response.data.success) {
          await this.fetch()
          return { success: true, message: 'Article retiré du panier' }
        }
      } catch (error) {
        return { success: false, message: 'Erreur lors de la suppression' }
      }
    },

    // Vider le panier
    async clear() {
      try {
        const response = await api.panier.clear()
        if (response.data.success) {
          this.items = []
          return { success: true, message: 'Panier vidé' }
        }
      } catch (error) {
        return { success: false, message: 'Erreur' }
      }
    }
  }
})
