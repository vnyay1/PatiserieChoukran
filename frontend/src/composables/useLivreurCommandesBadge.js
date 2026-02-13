import { onBeforeUnmount, onMounted, readonly, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const livreurCommandesCount = ref(0)
let livreurBadgeInterval = null
let badgeConsumers = 0
let badgeFetchPromise = null
let badgeListenerRegistered = false

const canTrackLivreurCommandes = (authStore) => {
  return authStore.isAuthenticated && authStore.isLivreur
}

const formatBadgeCount = (count) => {
  return count > 99 ? '99+' : count
}

const clearLivreurBadgeInterval = () => {
  if (livreurBadgeInterval) {
    clearInterval(livreurBadgeInterval)
    livreurBadgeInterval = null
  }
}

const fetchLivreurCommandesCount = async (authStore) => {
  if (!canTrackLivreurCommandes(authStore)) {
    livreurCommandesCount.value = 0
    return
  }

  if (badgeFetchPromise) {
    await badgeFetchPromise
    return
  }

  badgeFetchPromise = (async () => {
    try {
      const response = await api.admin.commandes.getAll({ per_page: 1, badge_only: 1 })
      if (response.data?.success) {
        livreurCommandesCount.value = Number(response.data?.data?.total || 0)
      }
    } catch (error) {
      console.error('Erreur chargement compteur commandes livreur:', error)
    }
  })()

  try {
    await badgeFetchPromise
  } finally {
    badgeFetchPromise = null
  }
}

const syncLivreurBadgePolling = async (authStore) => {
  clearLivreurBadgeInterval()

  if (!canTrackLivreurCommandes(authStore)) {
    livreurCommandesCount.value = 0
    return
  }

  await fetchLivreurCommandesCount(authStore)
  livreurBadgeInterval = setInterval(() => fetchLivreurCommandesCount(authStore), 30000)
}

const onLivreurCommandesUpdated = () => {
  const authStore = useAuthStore()
  if (authStore.isLivreur) {
    fetchLivreurCommandesCount(authStore)
  }
}

const registerBadgeListener = () => {
  if (badgeListenerRegistered) {
    return
  }

  window.addEventListener('livreur-commandes-updated', onLivreurCommandesUpdated)
  badgeListenerRegistered = true
}

const unregisterBadgeListener = () => {
  if (!badgeListenerRegistered) {
    return
  }

  window.removeEventListener('livreur-commandes-updated', onLivreurCommandesUpdated)
  badgeListenerRegistered = false
}

export const useLivreurCommandesBadge = () => {
  const route = useRoute()
  const authStore = useAuthStore()

  const showLivreurCommandesBadge = (itemName) => {
    return authStore.isLivreur && itemName === 'admin-commandes' && livreurCommandesCount.value > 0
  }

  onMounted(() => {
    badgeConsumers += 1
    registerBadgeListener()
    syncLivreurBadgePolling(authStore)
  })

  watch(
    () => [authStore.isAuthenticated, authStore.user?.role],
    () => {
      syncLivreurBadgePolling(authStore)
    }
  )

  watch(
    () => route.fullPath,
    () => {
      if (authStore.isLivreur) {
        fetchLivreurCommandesCount(authStore)
      }
    }
  )

  onBeforeUnmount(() => {
    badgeConsumers = Math.max(0, badgeConsumers - 1)

    if (badgeConsumers === 0) {
      clearLivreurBadgeInterval()
      unregisterBadgeListener()
      badgeFetchPromise = null
      livreurCommandesCount.value = 0
    }
  })

  return {
    livreurCommandesCount: readonly(livreurCommandesCount),
    formatBadgeCount,
    showLivreurCommandesBadge,
  }
}
