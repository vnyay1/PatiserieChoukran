import { onBeforeUnmount, onMounted, readonly, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const vendeurCommandesCount = ref(0)
let vendeurBadgeInterval = null
let badgeConsumers = 0
let badgeFetchPromise = null
let badgeListenerRegistered = false

const canTrackVendeurCommandes = (authStore) => {
  return authStore.isAuthenticated && authStore.isVendeur
}

const formatBadgeCount = (count) => {
  return count > 99 ? '99+' : count
}

const clearVendeurBadgeInterval = () => {
  if (vendeurBadgeInterval) {
    clearInterval(vendeurBadgeInterval)
    vendeurBadgeInterval = null
  }
}

const fetchVendeurCommandesCount = async (authStore) => {
  if (!canTrackVendeurCommandes(authStore)) {
    vendeurCommandesCount.value = 0
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
        vendeurCommandesCount.value = Number(response.data?.data?.total || 0)
      }
    } catch (error) {
      console.error('Erreur chargement compteur commandes vendeur:', error)
    }
  })()

  try {
    await badgeFetchPromise
  } finally {
    badgeFetchPromise = null
  }
}

const syncVendeurBadgePolling = async (authStore) => {
  clearVendeurBadgeInterval()

  if (!canTrackVendeurCommandes(authStore)) {
    vendeurCommandesCount.value = 0
    return
  }

  await fetchVendeurCommandesCount(authStore)
  vendeurBadgeInterval = setInterval(() => fetchVendeurCommandesCount(authStore), 30000)
}

const onVendeurCommandesUpdated = () => {
  const authStore = useAuthStore()
  if (authStore.isVendeur) {
    fetchVendeurCommandesCount(authStore)
  }
}

const registerBadgeListener = () => {
  if (badgeListenerRegistered) {
    return
  }

  window.addEventListener('vendeur-commandes-updated', onVendeurCommandesUpdated)
  badgeListenerRegistered = true
}

const unregisterBadgeListener = () => {
  if (!badgeListenerRegistered) {
    return
  }

  window.removeEventListener('vendeur-commandes-updated', onVendeurCommandesUpdated)
  badgeListenerRegistered = false
}

export const useVendeurCommandesBadge = () => {
  const route = useRoute()
  const authStore = useAuthStore()

  const showVendeurCommandesBadge = (itemName) => {
    return authStore.isVendeur && itemName === 'admin-commandes' && vendeurCommandesCount.value > 0
  }

  onMounted(() => {
    badgeConsumers += 1
    registerBadgeListener()
    syncVendeurBadgePolling(authStore)
  })

  watch(
    () => [authStore.isAuthenticated, authStore.user?.role],
    () => {
      syncVendeurBadgePolling(authStore)
    }
  )

  watch(
    () => route.fullPath,
    () => {
      if (authStore.isVendeur) {
        fetchVendeurCommandesCount(authStore)
      }
    }
  )

  onBeforeUnmount(() => {
    badgeConsumers = Math.max(0, badgeConsumers - 1)

    if (badgeConsumers === 0) {
      clearVendeurBadgeInterval()
      unregisterBadgeListener()
      badgeFetchPromise = null
      vendeurCommandesCount.value = 0
    }
  })

  return {
    vendeurCommandesCount: readonly(vendeurCommandesCount),
    formatBadgeCount,
    showVendeurCommandesBadge,
  }
}
