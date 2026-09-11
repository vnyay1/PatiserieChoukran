import { defineStore } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

// Compteur de notifications non lues : rafraîchi toutes les minutes quand l'onglet est
// visible, au retour sur l'onglet et à la navigation (au plus une fois toutes les 15 s).
const POLLING_INTERVAL_MS = 60000
const DELAI_MIN_ENTRE_REQUETES_MS = 15000

let pollingHandle = null
let dernierRafraichissement = 0

const surChangementVisibilite = () => {
  if (document.visibilityState === 'visible') {
    useNotificationsStore().fetchUnreadCount({ force: true })
  }
}

const getDefaultPagination = () => ({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: null,
  to: null,
})

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    items: [],
    pagination: getDefaultPagination(),
    loading: false,
    error: null,
    unreadCount: 0,
  }),

  getters: {
    hasUnread: (state) => state.unreadCount > 0,
  },

  actions: {
    reset() {
      this.items = []
      this.pagination = getDefaultPagination()
      this.loading = false
      this.error = null
      this.unreadCount = 0
    },

    applyPaginatedPayload(payload) {
      this.items = payload?.data || []
      this.pagination = {
        current_page: payload?.current_page || 1,
        last_page: payload?.last_page || 1,
        per_page: payload?.per_page || 20,
        total: payload?.total || 0,
        from: payload?.from ?? null,
        to: payload?.to ?? null,
      }
    },

    async fetchNotifications(params = {}) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) {
        this.reset()
        return { success: false, message: 'Utilisateur non authentifié.' }
      }

      this.loading = true
      this.error = null

      try {
        const response = await api.notifications.getAll(params)
        if (response.data?.success) {
          this.applyPaginatedPayload(response.data.data)
          return { success: true }
        }

        this.error = response.data?.message || 'Erreur lors du chargement des notifications.'
        return { success: false, message: this.error }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des notifications.'
        return { success: false, message: this.error }
      } finally {
        this.loading = false
      }
    },

    async fetchUnreadCount({ force = false } = {}) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) {
        this.unreadCount = 0
        return { success: false, count: 0 }
      }

      // Évite les rafales (navigation rapide entre pages)
      const maintenant = Date.now()
      if (!force && maintenant - dernierRafraichissement < DELAI_MIN_ENTRE_REQUETES_MS) {
        return { success: true, count: this.unreadCount }
      }
      dernierRafraichissement = maintenant

      try {
        const response = await api.notifications.unreadCount()
        if (response.data?.success) {
          this.unreadCount = Number(response.data?.data?.count || 0)
          return { success: true, count: this.unreadCount }
        }

        return { success: false, count: this.unreadCount }
      } catch (error) {
        return { success: false, count: this.unreadCount, message: error.response?.data?.message }
      }
    },

    async markAsRead(id) {
      const item = this.items.find((entry) => entry.id === id)
      const wasUnread = Boolean(item && !item.est_lu)
      const previousEstLu = item?.est_lu
      const previousDateLecture = item?.date_lecture ?? null

      if (item) {
        item.est_lu = true
        item.date_lecture = item.date_lecture || new Date().toISOString()
      }
      if (wasUnread) {
        this.unreadCount = Math.max(0, this.unreadCount - 1)
      }

      try {
        const response = await api.notifications.markAsRead(id)
        if (response.data?.success && response.data?.data) {
          const updated = response.data.data
          const index = this.items.findIndex((entry) => entry.id === id)
          if (index !== -1) {
            this.items[index] = { ...this.items[index], ...updated }
          }
        }
        return { success: true }
      } catch (error) {
        if (item) {
          item.est_lu = previousEstLu
          item.date_lecture = previousDateLecture
        }
        if (wasUnread) {
          this.unreadCount += 1
        }
        return {
          success: false,
          message: error.response?.data?.message || 'Erreur lors de la mise à jour.'
        }
      }
    },

    async markAllAsRead() {
      const previousItems = this.items.map((entry) => ({ ...entry }))
      const previousUnreadCount = this.unreadCount

      this.items = this.items.map((entry) => ({
        ...entry,
        est_lu: true,
        date_lecture: entry.date_lecture || new Date().toISOString(),
      }))
      this.unreadCount = 0

      try {
        const response = await api.notifications.markAllAsRead()
        if (response.data?.success) {
          return { success: true }
        }
        this.items = previousItems
        this.unreadCount = previousUnreadCount
        return {
          success: false,
          message: response.data?.message || 'Erreur lors de la mise à jour.'
        }
      } catch (error) {
        this.items = previousItems
        this.unreadCount = previousUnreadCount
        return {
          success: false,
          message: error.response?.data?.message || 'Erreur lors de la mise à jour.'
        }
      }
    },

    async remove(id) {
      const index = this.items.findIndex((entry) => entry.id === id)
      const item = index !== -1 ? this.items[index] : null

      try {
        const response = await api.notifications.remove(id)
        if (!response.data?.success) {
          return {
            success: false,
            message: response.data?.message || 'Erreur lors de la suppression.'
          }
        }

        if (index !== -1) {
          this.items.splice(index, 1)
          this.pagination.total = Math.max(0, this.pagination.total - 1)
        }
        if (item && !item.est_lu) {
          this.unreadCount = Math.max(0, this.unreadCount - 1)
        }
        return { success: true }
      } catch (error) {
        return {
          success: false,
          message: error.response?.data?.message || 'Erreur lors de la suppression.'
        }
      }
    },

    async clearRead() {
      const readIds = this.items.filter((entry) => entry.est_lu).map((entry) => entry.id)

      if (readIds.length === 0) {
        return { success: true }
      }

      try {
        const response = await api.notifications.clearRead()
        if (!response.data?.success) {
          throw new Error(response.data?.message || 'Erreur lors de la suppression.')
        }

        this.items = this.items.filter((entry) => !entry.est_lu)
        this.pagination.total = Math.max(0, this.pagination.total - readIds.length)
        return { success: true }
      } catch (error) {
        // Fallback: supprimer les notifications lues une par une.
        const results = await Promise.allSettled(readIds.map((id) => api.notifications.remove(id)))
        const succeededIds = results
          .map((result, index) => ({ result, id: readIds[index] }))
          .filter(({ result }) => result.status === 'fulfilled' && result.value?.data?.success)
          .map(({ id }) => id)

        if (succeededIds.length === 0) {
          return {
            success: false,
            message: error.response?.data?.message || 'Erreur lors de la suppression.'
          }
        }

        this.items = this.items.filter((entry) => !succeededIds.includes(entry.id))
        this.pagination.total = Math.max(0, this.pagination.total - succeededIds.length)

        return {
          success: true,
          partial: succeededIds.length < readIds.length,
          message: succeededIds.length < readIds.length ? 'Certaines notifications n\'ont pas pu être supprimées.' : null
        }
      }
    },

    async startPolling() {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) {
        this.stopPolling()
        this.unreadCount = 0
        return
      }

      await this.fetchUnreadCount({ force: true })

      if (pollingHandle) {
        return
      }

      // Onglet en arrière-plan : aucune requête (rafraîchi au retour sur l'onglet)
      pollingHandle = setInterval(() => {
        if (document.visibilityState === 'visible') {
          this.fetchUnreadCount({ force: true })
        }
      }, POLLING_INTERVAL_MS)
      document.addEventListener('visibilitychange', surChangementVisibilite)
    },

    stopPolling() {
      if (!pollingHandle) {
        return
      }

      clearInterval(pollingHandle)
      pollingHandle = null
      document.removeEventListener('visibilitychange', surChangementVisibilite)
    },
  }
})
