import { onBeforeUnmount, onMounted, readonly, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import { creerSondagePartage } from '@/utils/sondagePartage'

// Badge « commandes à traiter » du vendeur : même sondage partagé que les notifications
// (un appel toutes les 2 minutes au plus pour tous les onglets, rien en arrière-plan).
// Utilisé par le Header et la BottomNav : un seul sondage pour les deux.
const INTERVALLE_SONDAGE_MS = 2 * 60 * 1000

const vendeurCommandesCount = ref(0)
let consommateurs = 0
let sondage = null
let sondageUserId = null

const formatBadgeCount = (count) => {
  return count > 99 ? '99+' : count
}

const arreterSondage = () => {
  sondage?.arreter({ oublier: true })
  sondage = null
  sondageUserId = null
  vendeurCommandesCount.value = 0
}

const synchroniser = (authStore) => {
  const userId = authStore.isAuthenticated && authStore.isVendeur ? authStore.user?.id : null

  if (!userId) {
    arreterSondage()
    return
  }
  if (sondage && sondageUserId === userId) return

  arreterSondage()
  sondageUserId = userId
  sondage = creerSondagePartage({
    cle: `choukrane:commandes-a-traiter:${userId}`,
    intervalleMs: INTERVALLE_SONDAGE_MS,
    charger: async () => {
      const response = await api.admin.commandes.getAll({ per_page: 1, badge_only: 1 })
      if (!response.data?.success) throw new Error('Compteur indisponible')
      return Number(response.data?.data?.total || 0)
    },
    appliquer: (total) => {
      vendeurCommandesCount.value = total
    },
  })
  sondage.demarrer()
}

// Une action du vendeur sur ses commandes (AdminCommandes) : le badge est rafraîchi tout de suite
const surCommandesModifiees = () => {
  sondage?.rafraichir({ force: true })
}

export const useVendeurCommandesBadge = () => {
  const authStore = useAuthStore()

  const showVendeurCommandesBadge = (itemName) => {
    return authStore.isVendeur && itemName === 'admin-commandes' && vendeurCommandesCount.value > 0
  }

  onMounted(() => {
    consommateurs += 1
    if (consommateurs === 1) {
      window.addEventListener('vendeur-commandes-updated', surCommandesModifiees)
    }
    synchroniser(authStore)
  })

  watch(
    () => [authStore.isAuthenticated, authStore.user?.id, authStore.user?.role],
    () => synchroniser(authStore)
  )

  onBeforeUnmount(() => {
    consommateurs = Math.max(0, consommateurs - 1)
    if (consommateurs === 0) {
      window.removeEventListener('vendeur-commandes-updated', surCommandesModifiees)
      arreterSondage()
    }
  })

  return {
    vendeurCommandesCount: readonly(vendeurCommandesCount),
    formatBadgeCount,
    showVendeurCommandesBadge,
  }
}
