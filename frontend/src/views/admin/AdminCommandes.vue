<!-- ===================================
ADMIN - GESTION DES COMMANDES
File: src/views/admin/AdminCommandes.vue
=================================== -->

<template>
  <div class="admin-commandes-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            Administration Commandes
          </h1>
          <p class="text-gray-600 text-sm">
            {{ isHistoriqueMode ? 'Historique complet des commandes (admin uniquement).' : 'Consulter et mettre à jour le statut des commandes.' }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button v-if="isAdmin" variant="secondary" size="sm" @click="toggleHistoriqueMode">
            {{ isHistoriqueMode ? 'Voir commandes actives' : 'Voir historique' }}
          </Button>
          <Button variant="outline" size="sm" :loading="loading" @click="fetchCommandes">
            Actualiser
          </Button>
        </div>
      </div>

      <Card padding="md" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
            <input
              v-model="filters.search"
              type="search"
              placeholder="Numéro, client, téléphone..."
              class="input"
              @input="handleSearch"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
            <select v-model="filters.statut" class="input">
              <option value="">Tous</option>
              <option value="en_attente">En attente</option>
              <option value="confirmee">Confirmée</option>
              <option value="en_preparation">En préparation</option>
              <option value="prete">Prête</option>
              <option value="en_livraison">En livraison</option>
              <option value="livree">Livrée</option>
              <option value="annulee">Annulée</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Paiement</label>
            <select v-model="filters.statut_paiement" class="input">
              <option value="">Tous</option>
              <option value="en_attente">À payer</option>
              <option value="paye">Payé</option>
              <option value="echec">Échec</option>
              <option value="rembourse">Remboursé</option>
            </select>
          </div>
          <div class="md:col-span-5 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
              <input v-model="filters.date_debut" type="date" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
              <input v-model="filters.date_fin" type="date" class="input" />
            </div>
            <div class="flex items-end gap-3">
              <Button variant="outline" @click="resetFilters">
                Réinitialiser
              </Button>
              <Button variant="primary" @click="applyFilters">
                Appliquer
              </Button>
            </div>
          </div>
        </div>
      </Card>

      <Card v-if="error" padding="md" class="mb-6 border border-red-200 bg-red-50">
        <p class="text-red-600 text-sm">{{ error }}</p>
      </Card>

      <Card padding="none">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <div class="text-sm text-gray-600">
            {{ totalCommandes }} commande{{ totalCommandes > 1 ? 's' : '' }} {{ isHistoriqueMode ? 'archivée' : '' }}
          </div>
          <div class="text-xs text-gray-500">
            Page {{ currentPage }} / {{ totalPages }}
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="text-left font-semibold px-4 py-3">Commande</th>
                <th class="text-left font-semibold px-4 py-3">Client</th>
                <th class="text-left font-semibold px-4 py-3">Statut</th>
                <th class="text-left font-semibold px-4 py-3">Paiement</th>
                <th class="text-left font-semibold px-4 py-3">Total</th>
                <th class="text-left font-semibold px-4 py-3">Date</th>
                <th class="text-right font-semibold px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr>
                <td colspan="7" class="p-6 text-center text-gray-500">Chargement...</td>
              </tr>
            </tbody>
            <tbody v-else-if="commandes.length === 0">
              <tr>
                <td colspan="7" class="p-6 text-center text-gray-500">Aucune commande trouvée.</td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="commande in commandes" :key="commande.id" class="border-t border-gray-100">
                <td class="px-4 py-3">
                  <div class="font-semibold text-gray-800">{{ commande.numero_commande }}</div>
                  <div class="text-xs text-gray-500">
                    {{ commande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait' }}
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="font-semibold text-gray-800">{{ commande.user?.nom_complet || 'Client' }}</div>
                  <div class="text-xs text-gray-500">{{ commande.user?.telephone }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <span class="badge" :class="getBadgeClass(commande.statut)">
                      {{ getStatutLabel(commande.statut) }}
                    </span>
                    <select
                      class="text-xs border border-gray-200 rounded-lg px-2 py-1"
                      :disabled="isReadOnlyCommande(commande) || updatingStatusId === commande.id"
                      :value="commande.statut"
                      @change="onStatusChange(commande, $event)"
                    >
                      <option value="en_attente">En attente</option>
                      <option value="confirmee">Confirmée</option>
                      <option value="en_preparation">En préparation</option>
                      <option value="prete">Prête</option>
                      <option value="en_livraison">En livraison</option>
                      <option value="livree">Livrée</option>
                      <option value="annulee">Annulée</option>
                    </select>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex flex-col gap-2">
                    <span class="badge" :class="getPaymentBadgeClass(commande.statut_paiement)">
                      {{ getPaymentLabel(commande.statut_paiement) }}
                    </span>
                    <Button
                      v-if="!isHistoriqueMode && commande.statut_paiement === 'en_attente' && commande.statut !== 'annulee'"
                      variant="outline"
                      size="sm"
                      :loading="confirmingPaymentId === commande.id"
                      @click="confirmPayment(commande)"
                    >
                      Confirmer paiement
                    </Button>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="price text-base">{{ formatPrice(commande.montant_total) }} FCFA</div>
                </td>
                <td class="px-4 py-3">
                  {{ formatDate(commande.created_at) }}
                </td>
                <td class="px-4 py-3 text-right">
                  <Button variant="outline" size="sm" @click="openDetail(commande)">
                    Détails
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t border-gray-100 flex items-center justify-between">
          <div class="text-xs text-gray-500">
            Page {{ currentPage }} / {{ totalPages }}
          </div>
          <div class="flex gap-2">
            <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="changePage(currentPage - 1)">
              Précédent
            </Button>
            <Button variant="outline" size="sm" :disabled="currentPage >= totalPages" @click="changePage(currentPage + 1)">
              Suivant
            </Button>
          </div>
        </div>
      </Card>

      <!-- Détail commande -->
      <div
        v-if="showDetail"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4 py-6"
      >
        <div class="bg-white w-full max-w-4xl rounded-elegant shadow-card overflow-hidden">
          <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-display text-xl font-bold text-gray-800">
              Détails commande
            </h2>
            <button class="text-sm text-gray-500 hover:text-gray-700" @click="closeDetail">
              Fermer
            </button>
          </div>

          <div v-if="detailLoading" class="p-6 space-y-4">
            <div class="skeleton h-6 w-1/2"></div>
            <div class="skeleton h-24"></div>
            <div class="skeleton h-32"></div>
          </div>

          <div v-else-if="detailError" class="p-6 text-sm text-red-600">
            {{ detailError }}
          </div>

          <div v-else-if="selectedCommande" class="p-6 space-y-6">
            <Card padding="md">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                  <div class="font-semibold text-gray-800">{{ selectedCommande.numero_commande }}</div>
                  <div class="text-xs text-gray-500">
                    {{ selectedCommande.user?.nom_complet || 'Client' }} · {{ formatDate(selectedCommande.created_at) }}
                  </div>
                </div>
                <div class="flex flex-wrap gap-2">
                  <span class="badge" :class="getBadgeClass(selectedCommande.statut)">
                    {{ getStatutLabel(selectedCommande.statut) }}
                  </span>
                  <span class="badge" :class="getPaymentBadgeClass(selectedCommande.statut_paiement)">
                    {{ getPaymentLabel(selectedCommande.statut_paiement) }}
                  </span>
                </div>
              </div>
            </Card>

            <div class="space-y-3">
              <div class="text-xs text-gray-500">Articles</div>
              <div v-if="!selectedCommande.ligne_commandes?.length" class="text-sm text-gray-500">
                Aucun article.
              </div>
              <div v-else class="space-y-2">
                <div
                  v-for="ligne in selectedCommande.ligne_commandes"
                  :key="ligne.id"
                  class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-100 bg-white"
                >
                  <div class="flex items-center gap-3">
                    <img
                      :src="resolveImageUrl(ligne.produit?.image_principale)"
                      :alt="ligne.nom_produit"
                      class="h-12 w-12 rounded-lg object-cover border"
                    />
                    <div>
                      <div class="font-semibold text-gray-800">{{ ligne.nom_produit }}</div>
                      <div class="text-xs text-gray-500">
                        {{ ligne.quantite }} × {{ formatPrice(ligne.prix_unitaire) }} FCFA
                      </div>
                    </div>
                  </div>
                  <div class="price text-base">{{ formatPrice(ligne.sous_total) }} FCFA</div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <Card padding="md">
                <div class="text-xs text-gray-500 mb-2">Livraison</div>
                <div class="text-sm text-gray-700">
                  {{ selectedCommande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait en boutique' }}
                </div>
                <div v-if="selectedCommande.adresse_livraison" class="text-xs text-gray-500 mt-1">
                  {{ selectedCommande.adresse_livraison.quartier }}, {{ selectedCommande.adresse_livraison.ville }}
                </div>
              </Card>
              <Card padding="md">
                <div class="text-xs text-gray-500 mb-2">Montants</div>
                <div class="text-sm text-gray-700">
                  Produits: {{ formatPrice(selectedCommande.montant_produits) }} FCFA
                </div>
                <div class="text-sm text-gray-700">
                  Livraison: {{ formatPrice(selectedCommande.montant_livraison) }} FCFA
                </div>
                <div class="text-sm font-semibold text-gray-800 mt-1">
                  Total: {{ formatPrice(selectedCommande.montant_total) }} FCFA
                </div>
              </Card>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'

const authStore = useAuthStore()
const commandes = ref([])
const loading = ref(false)
const error = ref('')

const currentPage = ref(1)
const perPage = ref(15)
const totalCommandes = ref(0)
const updatingStatusId = ref(null)
const confirmingPaymentId = ref(null)
const isHistoriqueMode = ref(false)

const filters = ref({
  search: '',
  statut: '',
  statut_paiement: '',
  date_debut: '',
  date_fin: '',
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalCommandes.value / perPage.value))
})
const isAdmin = computed(() => authStore.isAdmin)

const showDetail = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const selectedCommande = ref(null)

const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
const apiOrigin = (() => {
  try {
    return new URL(apiBase).origin
  } catch {
    return ''
  }
})()

const resolveImageUrl = (path) => {
  if (!path) return '/placeholder-product.jpg'
  if (path.startsWith('http') || path.startsWith('/')) return path
  return apiOrigin ? `${apiOrigin}/storage/${path}` : `/storage/${path}`
}

const formatPrice = (value) => new Intl.NumberFormat('fr-FR').format(value || 0)

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const getStatutLabel = (statut) => {
  const labels = {
    en_attente: 'En attente',
    confirmee: 'Confirmée',
    en_preparation: 'En préparation',
    prete: 'Prête',
    en_livraison: 'En livraison',
    livree: 'Livrée',
    annulee: 'Annulée',
  }
  return labels[statut] || statut
}

const getBadgeClass = (statut) => {
  const classes = {
    en_attente: 'bg-yellow-100 text-yellow-700',
    confirmee: 'bg-blue-100 text-blue-700',
    en_preparation: 'bg-purple-100 text-purple-700',
    prete: 'bg-indigo-100 text-indigo-700',
    en_livraison: 'bg-orange-100 text-orange-700',
    livree: 'bg-green-100 text-green-700',
    annulee: 'bg-red-100 text-red-700',
  }
  return classes[statut] || 'bg-gray-100 text-gray-700'
}

const getPaymentLabel = (statut) => {
  const labels = {
    en_attente: 'À payer',
    paye: 'Payé',
    echec: 'Échec',
    rembourse: 'Remboursé',
  }
  return labels[statut] || statut
}

const getPaymentBadgeClass = (statut) => {
  const classes = {
    en_attente: 'bg-yellow-100 text-yellow-700',
    paye: 'bg-green-100 text-green-700',
    echec: 'bg-red-100 text-red-700',
    rembourse: 'bg-gray-100 text-gray-700',
  }
  return classes[statut] || 'bg-gray-100 text-gray-700'
}

const notifyLivreurBadgeRefresh = () => {
  window.dispatchEvent(new CustomEvent('livreur-commandes-updated'))
}

const isCommandeArchivee = (commande) => {
  return commande?.statut === 'annulee'
    || (commande?.statut === 'livree' && commande?.statut_paiement === 'paye')
}

const isReadOnlyCommande = (commande) => {
  return isHistoriqueMode.value || isCommandeArchivee(commande)
}

const fetchCommandes = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.statut) params.statut = filters.value.statut
    if (filters.value.statut_paiement) params.statut_paiement = filters.value.statut_paiement
    if (filters.value.date_debut) params.date_debut = filters.value.date_debut
    if (filters.value.date_fin) params.date_fin = filters.value.date_fin
    if (isAdmin.value && isHistoriqueMode.value) params.historique = 1

    const response = await api.admin.commandes.getAll(params)
    if (response.data.success) {
      const pagination = response.data.data || {}
      commandes.value = pagination.data || []
      totalCommandes.value = Number(pagination.total || 0)
      currentPage.value = Number(pagination.current_page || currentPage.value)
      perPage.value = Number(pagination.per_page || perPage.value)

      // Si la page courante devient vide après archivage, revenir à la page précédente.
      if (commandes.value.length === 0 && currentPage.value > 1 && totalCommandes.value > 0) {
        currentPage.value -= 1
        await fetchCommandes()
      }
    } else {
      error.value = 'Impossible de charger les commandes.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des commandes.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
  fetchCommandes()
}

const toggleHistoriqueMode = () => {
  if (!isAdmin.value) return
  isHistoriqueMode.value = !isHistoriqueMode.value
  currentPage.value = 1
  fetchCommandes()
}

const resetFilters = () => {
  filters.value.search = ''
  filters.value.statut = ''
  filters.value.statut_paiement = ''
  filters.value.date_debut = ''
  filters.value.date_fin = ''
  applyFilters()
}

let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 400)
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchCommandes()
}

const onStatusChange = async (commande, event) => {
  if (commande.statut === 'annulee') {
    event.target.value = commande.statut
    return
  }
  const nextStatus = event.target.value
  if (nextStatus === commande.statut) return

  const confirmed = confirm(`Changer le statut en "${getStatutLabel(nextStatus)}" ?`)
  if (!confirmed) {
    event.target.value = commande.statut
    return
  }

  updatingStatusId.value = commande.id
  try {
    const response = await api.admin.commandes.updateStatus(commande.id, { statut: nextStatus })
    if (response.data.success) {
      const updated = response.data.data
      commande.statut = updated.statut
      if (selectedCommande.value?.id === commande.id) {
        selectedCommande.value.statut = updated.statut
      }

      if (!isHistoriqueMode.value && isCommandeArchivee(commande)) {
        closeDetail()
      }

      await fetchCommandes()
      notifyLivreurBadgeRefresh()
    }
  } catch (err) {
    event.target.value = commande.statut
    alert(err.response?.data?.message || 'Erreur lors de la mise à jour du statut')
  } finally {
    updatingStatusId.value = null
  }
}

const confirmPayment = async (commande) => {
  const reference = prompt('Référence de paiement (optionnel)') || ''
  confirmingPaymentId.value = commande.id
  try {
    const response = await api.admin.commandes.confirmPayment(commande.id, {
      reference_paiement: reference || null,
    })
    if (response.data.success) {
      const updated = response.data.data
      commande.statut_paiement = updated.statut_paiement
      commande.date_paiement = updated.date_paiement
      if (selectedCommande.value?.id === commande.id) {
        selectedCommande.value.statut_paiement = updated.statut_paiement
      }

      if (!isHistoriqueMode.value && isCommandeArchivee(commande)) {
        closeDetail()
      }

      await fetchCommandes()
      notifyLivreurBadgeRefresh()
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de la confirmation du paiement')
  } finally {
    confirmingPaymentId.value = null
  }
}

const openDetail = async (commande) => {
  showDetail.value = true
  detailLoading.value = true
  detailError.value = ''
  selectedCommande.value = null

  try {
    const response = await api.admin.commandes.getOne(commande.id)
    if (response.data.success) {
      selectedCommande.value = response.data.data
    } else {
      detailError.value = 'Impossible de charger les détails.'
    }
  } catch (err) {
    detailError.value = err.response?.data?.message || 'Erreur lors du chargement des détails.'
  } finally {
    detailLoading.value = false
  }
}

const closeDetail = () => {
  showDetail.value = false
  selectedCommande.value = null
}

onMounted(() => {
  fetchCommandes()
})
</script>
