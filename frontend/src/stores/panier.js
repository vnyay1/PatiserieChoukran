// ===================================
// 5. STORE PANIER
// File: src/stores/panier.js
// ===================================
// Chaque action renvoie le panier complet : un seul appel réseau par action. Le panier
// est vidé par le serveur après une durée d'inactivité réglée par l'admin ; le store se
// recharge à cette échéance pour que le badge et la page reflètent le panier vidé.

import { defineStore } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import api, { messageErreur } from '@/services/api'

let minuterieExpiration = null

const annulerMinuterie = () => {
  clearTimeout(minuterieExpiration)
  minuterieExpiration = null
}

export const usePanierStore = defineStore('panier', {
  state: () => ({
    items: [],
    loading: false,
    error: null,
    ownerUserId: null,
    // Date ISO à laquelle le panier sera vidé sans nouvelle modification (null : panier vide)
    expireLe: null,
    dureeMinutes: null,
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
      annulerMinuterie()
      this.items = []
      this.loading = false
      this.error = null
      this.ownerUserId = null
      this.expireLe = null
      this.dureeMinutes = null
    },

    // Réponse du backend (GET /panier et toutes les actions) : { items, expire_le, duree_minutes }
    appliquer(data) {
      const authStore = useAuthStore()
      this.items = data?.items || []
      this.expireLe = data?.expire_le || null
      this.dureeMinutes = data?.duree_minutes ?? null
      this.ownerUserId = authStore.user?.id || null
      this.programmerExpiration()
    },

    programmerExpiration() {
      annulerMinuterie()
      if (!this.expireLe) return

      // setTimeout est plafonné à ~24,8 jours : au-delà, la page sera rechargée entre-temps
      const delai = Math.min(new Date(this.expireLe).getTime() - Date.now() + 1000, 2 ** 31 - 1)
      minuterieExpiration = setTimeout(() => this.fetch(), Math.max(delai, 0))
    },

    // Charger le panier
    async fetch() {
      this.loading = true
      try {
        const response = await api.panier.get()
        if (response.data.success) {
          this.appliquer(response.data.data)
        }
      } catch (error) {
        this.error = 'Erreur lors du chargement du panier'
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async executer(requete, messageSucces, messageEchec) {
      try {
        const response = await requete()
        if (response.data.success) {
          this.appliquer(response.data.data)
          return { success: true, message: messageSucces }
        }
        return { success: false, message: response.data?.message || messageEchec }
      } catch (error) {
        return { success: false, message: messageErreur(error, messageEchec) }
      }
    },

    addItem(produitId, quantite = 1) {
      return this.executer(
        () => api.panier.add({ produit_id: produitId, quantite }),
        'Produit ajouté au panier',
        'Erreur lors de l\'ajout au panier'
      )
    },

    updateQuantity(itemId, quantite) {
      return this.executer(() => api.panier.update(itemId, { quantite }), null, 'Erreur lors de la mise à jour')
    },

    removeItem(itemId) {
      return this.executer(() => api.panier.remove(itemId), 'Article retiré du panier', 'Erreur lors de la suppression')
    },

    clear() {
      return this.executer(() => api.panier.clear(), 'Panier vidé', 'Impossible de vider le panier')
    },
  }
})
