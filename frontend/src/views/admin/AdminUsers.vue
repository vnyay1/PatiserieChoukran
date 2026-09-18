<!-- ===================================
ADMIN - GESTION DES UTILISATEURS
File: src/views/admin/AdminUsers.vue
=================================== -->

<template>
  <div class="admin-users-page pb-6">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1>
            Utilisateurs
          </h1>
          <p class="text-gray-600 text-sm">
            Gérer les rôles, statuts et consulter les informations clients.
          </p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" :loading="loading" @click="fetchUsers">
            Actualiser
          </Button>
        </div>
      </div>

      <Card padding="md" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div class="md:col-span-2">
            <label for="admin-users-1" class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
            <div class="relative">
              <Search class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500" :size="18" aria-hidden="true" />
              <input
                id="admin-users-1"
                v-model="filters.search"
                type="search"
                placeholder="Nom, email ou téléphone..."
                class="input pl-10"
                @input="handleSearch"
              />
            </div>
          </div>

          <div>
            <label for="admin-users-2" class="block text-sm font-medium text-gray-700 mb-2">Rôle</label>
            <select id="admin-users-2" v-model="filters.role" class="input">
              <option value="">Tous</option>
              <option value="client">Client</option>
              <option value="admin">Admin</option>
              <option value="vendeur">Vendeur</option>
            </select>
          </div>

          <div>
            <label for="admin-users-3" class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
            <select id="admin-users-3" v-model="filters.statut" class="input">
              <option value="">Tous</option>
              <option value="actif">Actif</option>
              <option value="inactif">Inactif</option>
              <option value="suspendu">Suspendu</option>
            </select>
          </div>

          <div class="md:col-span-4 flex flex-wrap items-center gap-3">
            <label class="inline-flex items-center gap-2 mr-auto">
              <input
                v-model="filters.vedette"
                type="checkbox"
                class="h-5 w-5 flex-shrink-0 rounded"
                @change="applyFilters"
              />
              <span class="text-sm text-gray-700">Vendeurs en vedette uniquement</span>
            </label>
            <Button variant="outline" @click="resetFilters">
              Réinitialiser
            </Button>
            <Button variant="primary" @click="applyFilters">
              Appliquer
            </Button>
          </div>
        </div>
      </Card>

      <Card v-if="error" padding="md" class="mb-6 border border-red-200 bg-red-50" role="alert">
        <p class="text-red-600 text-sm">{{ error }}</p>
      </Card>

      <Card padding="none">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <div class="text-sm text-gray-600">
            {{ totalUsers }} utilisateur{{ totalUsers > 1 ? 's' : '' }}
          </div>
          <div class="text-xs text-gray-500">
            Page {{ currentPage }} / {{ totalPages }}
          </div>
        </div>

        <div class="overflow-x-auto" role="region" aria-label="Liste des utilisateurs" tabindex="0">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th scope="col" class="text-left font-semibold px-4 py-3">Utilisateur</th>
                <th scope="col" class="text-left font-semibold px-4 py-3">Rôle</th>
                <th scope="col" class="text-left font-semibold px-4 py-3">Statut</th>
                <th scope="col" class="text-left font-semibold px-4 py-3">Commandes</th>
                <th scope="col" class="text-left font-semibold px-4 py-3">Inscription</th>
                <th scope="col" class="text-right font-semibold px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr>
                <td colspan="6" class="p-6 text-center text-gray-500">Chargement...</td>
              </tr>
            </tbody>
            <tbody v-else-if="users.length === 0">
              <tr>
                <td colspan="6" class="p-6 text-center text-gray-500">Aucun utilisateur trouvé.</td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="user in users" :key="user.id" class="border-t border-gray-100">
                <td class="px-4 py-3">
                  <div class="font-semibold text-gray-800">{{ user.nom_complet }}</div>
                  <div class="text-xs text-gray-500">{{ user.email }}</div>
                  <div v-if="user.telephone" class="text-xs text-gray-500">{{ user.telephone }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <span class="badge bg-gray-100 text-gray-700">
                      {{ getRoleLabel(user.role) }}
                    </span>
                    <select
                      class="input min-h-9 w-auto py-1 pl-3 pr-8 text-sm"
                      :aria-label="`Rôle de ${user.nom_complet}`"
                      :disabled="updatingRoleId === user.id || user.id === authStore.user?.id"
                      :value="user.role"
                      @change="onRoleChange(user, $event)"
                    >
                      <option value="client">Client</option>
                      <option value="admin">Admin</option>
                      <option value="vendeur">Vendeur</option>
                    </select>
                    <!-- Vedette : les produits du vendeur passent en tête du catalogue -->
                    <button
                      v-if="user.role === 'vendeur'"
                      type="button"
                      class="btn-icone h-9 w-9"
                      :class="user.est_vendeur_vedette ? 'text-gold-600' : 'text-gray-500 hover:text-gold-600'"
                      :aria-pressed="user.est_vendeur_vedette"
                      :title="user.est_vendeur_vedette ? 'En vedette : cliquer pour retirer' : 'Mettre en vedette'"
                      :aria-label="`Vendeur vedette : ${user.nom_complet}`"
                      :disabled="updatingVedetteId === user.id"
                      @click="toggleVedette(user)"
                    >
                      <Star :size="18" :fill="user.est_vendeur_vedette ? 'currentColor' : 'none'" aria-hidden="true" />
                    </button>
                  </div>
                  <div v-if="user.role === 'vendeur' && !user.profil_vendeur_complet" class="text-xs text-orange-600 mt-1">
                    Profil boutique à compléter
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <span class="badge" :class="getStatutClass(user.statut)">
                      {{ getStatutLabel(user.statut) }}
                    </span>
                    <select
                      class="input min-h-9 w-auto py-1 pl-3 pr-8 text-sm"
                      :aria-label="`Statut de ${user.nom_complet}`"
                      :disabled="updatingStatusId === user.id || user.id === authStore.user?.id"
                      :title="user.id === authStore.user?.id ? 'Vous ne pouvez pas modifier votre propre statut' : undefined"
                      :value="user.statut"
                      @change="onStatusChange(user, $event)"
                    >
                      <option value="actif">Actif</option>
                      <option value="inactif">Inactif</option>
                      <option value="suspendu">Suspendu</option>
                    </select>
                  </div>
                </td>
                <td class="px-4 py-3">
                  {{ formatNumber(user.commandes_count || 0) }}
                </td>
                <td class="px-4 py-3">
                  {{ formatDate(user.created_at) }}
                </td>
                <td class="px-4 py-3 text-right">
                  <Button variant="outline" size="sm" @click="openDetail(user)">
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

      <!-- Détail utilisateur -->
      <BaseModal
        :ouvert="showDetail"
        :titre="selectedUser?.nom_complet || 'Détail de l\'utilisateur'"
        variante="feuille"
        taille="lg"
        @fermer="closeDetail"
      >
        <div v-if="detailLoading" class="space-y-4" aria-busy="true">
          <div class="skeleton h-6 w-1/2"></div>
          <div class="skeleton h-24"></div>
          <div class="skeleton h-32"></div>
        </div>

        <AlertMessage v-else-if="detailError" type="error">{{ detailError }}</AlertMessage>

        <div v-else-if="selectedUser" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card padding="md">
              <div class="text-xs text-gray-500 mb-2">Informations</div>
              <div class="text-lg font-semibold text-gray-800">{{ selectedUser.nom_complet }}</div>
              <div class="text-sm text-gray-600">{{ selectedUser.email }}</div>
              <div v-if="selectedUser.telephone" class="text-sm text-gray-600">
                {{ selectedUser.telephone }}
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="badge bg-gray-100 text-gray-700">
                  {{ getRoleLabel(selectedUser.role) }}
                </span>
                <span class="badge" :class="getStatutClass(selectedUser.statut)">
                  {{ getStatutLabel(selectedUser.statut) }}
                </span>
                <span v-if="selectedUser.role === 'vendeur' && selectedUser.est_vendeur_vedette" class="badge badge-primary">
                  Vedette
                </span>
                <span v-if="selectedUser.role === 'vendeur' && !selectedUser.profil_vendeur_complet" class="badge bg-orange-100 text-orange-700">
                  Profil boutique incomplet
                </span>
              </div>
              <div class="text-xs text-gray-400 mt-3">
                Inscrit le {{ formatDate(selectedUser.created_at) }}
              </div>
              <div v-if="selectedUser.role === 'vendeur'" class="mt-4 flex flex-wrap gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  :icon="Star"
                  :icon-size="16"
                  :loading="updatingVedetteId === selectedUser.id"
                  @click="toggleVedette(selectedUser)"
                >
                  {{ selectedUser.est_vendeur_vedette ? 'Retirer de la vedette' : 'Mettre en vedette' }}
                </Button>
                <router-link
                  v-if="selectedUser.profil_vendeur_complet"
                  :to="{ name: 'vendeur-profil', params: { id: selectedUser.id } }"
                  class="btn-outline btn-sm"
                >
                  Page publique
                </router-link>
              </div>
            </Card>

            <Card v-if="selectedUser.stats && selectedUser.role === 'client'" padding="md">
              <div class="text-xs text-gray-500 mb-2">Statistiques client</div>
              <div class="space-y-2 text-sm text-gray-700">
                <div class="flex items-center justify-between">
                  <span>Total dépensé</span>
                  <span class="font-semibold">{{ formatPrice(selectedUser.stats.total_depense) }} FCFA</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>Commandes livrées</span>
                  <span class="font-semibold">{{ formatNumber(selectedUser.stats.commandes_livrees) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>Commande moyenne</span>
                  <span class="font-semibold">{{ formatPrice(selectedUser.stats.commande_moyenne) }} FCFA</span>
                </div>
              </div>
            </Card>

            <Card v-if="selectedUser.stats && selectedUser.role === 'vendeur'" padding="md">
              <div class="text-xs text-gray-500 mb-2">Statistiques vendeur</div>
              <div class="space-y-2 text-sm text-gray-700">
                <div class="flex items-center justify-between">
                  <span>Produits au catalogue</span>
                  <span class="font-semibold">{{ formatNumber(selectedUser.stats.produits) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>Commandes reçues</span>
                  <span class="font-semibold">{{ formatNumber(selectedUser.stats.commandes_recues) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>En cours</span>
                  <span class="font-semibold">{{ formatNumber(selectedUser.stats.commandes_en_cours) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>Encaissé</span>
                  <span class="font-semibold">{{ formatPrice(selectedUser.stats.chiffre_affaires) }} FCFA</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>Villes livrées</span>
                  <span class="font-semibold">{{ selectedUser.stats.villes_livraison?.map(formatVille).join(', ') || 'Aucune' }}</span>
                </div>
              </div>
            </Card>
          </div>

          <div v-if="selectedUser.adresses?.length" class="space-y-3">
            <div class="text-xs text-gray-500">Adresses</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <Card v-for="adresse in selectedUser.adresses" :key="adresse.id" padding="md">
                <div class="flex items-center justify-between mb-2">
                  <div class="font-semibold text-gray-800">{{ adresse.libelle || 'Adresse' }}</div>
                  <span v-if="adresse.est_principale" class="badge badge-primary">Principale</span>
                </div>
                <div class="text-sm text-gray-600">
                  {{ formatAdresse(adresse) }}
                </div>
                <div v-if="adresse.telephone_contact" class="text-xs text-gray-500 mt-1">
                  {{ adresse.telephone_contact }}
                </div>
                <div v-if="adresse.point_repere" class="text-xs text-gray-500 mt-1">
                  Repère: {{ adresse.point_repere }}
                </div>
              </Card>
            </div>
          </div>

          <div class="space-y-3">
            <div class="text-xs text-gray-500">Dernières commandes</div>
            <div v-if="lastCommandes.length === 0" class="text-sm text-gray-500">
              Aucune commande.
            </div>
            <div v-else class="space-y-2">
              <div
                v-for="commande in lastCommandes"
                :key="commande.id"
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 p-3 rounded-lg border border-gray-100 bg-surface"
              >
                <div>
                  <div class="font-semibold text-gray-800">{{ commande.numero_commande }}</div>
                  <div class="text-xs text-gray-500">{{ formatDate(commande.created_at) }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="badge" :class="getStatutClass(commande.statut)">
                    {{ getStatutLabel(commande.statut) }}
                  </span>
                  <span class="price text-base">{{ formatPrice(commande.montant_total) }} FCFA</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </BaseModal>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api, { messageErreur } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import { formatPrice, formatPrice as formatNumber, formatDate } from '@/utils/format'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import BaseModal from '@/components/common/BaseModal.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import { Search, Star } from 'lucide-vue-next'
import { formatVille } from '@/utils/villes'

const users = ref([])
const loading = ref(false)
const error = ref('')
const currentPage = ref(1)
const perPage = ref(15)
const totalUsers = ref(0)
const updatingStatusId = ref(null)
const updatingRoleId = ref(null)
const updatingVedetteId = ref(null)

const authStore = useAuthStore()
const toastStore = useToastStore()
const { confirmer } = useConfirm()

const filters = ref({
  search: '',
  role: '',
  statut: '',
  vedette: false,
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalUsers.value / perPage.value))
})

const showDetail = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const selectedUser = ref(null)

const lastCommandes = computed(() => {
  if (!selectedUser.value?.commandes) return []
  return [...selectedUser.value.commandes]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 5)
})

const getRoleLabel = (role) => {
  const labels = {
    admin: 'Admin',
    client: 'Client',
    vendeur: 'Vendeur',
  }
  return labels[role] || role
}

const getStatutLabel = (statut) => {
  const labels = {
    actif: 'Actif',
    inactif: 'Inactif',
    suspendu: 'Suspendu',
  }
  return labels[statut] || statut
}

const getStatutClass = (statut) => {
  const classes = {
    actif: 'bg-green-100 text-green-700',
    inactif: 'bg-gray-100 text-gray-700',
    suspendu: 'bg-red-100 text-red-700',
  }
  return classes[statut] || 'bg-gray-100 text-gray-700'
}

const formatAdresse = (adresse) => {
  return [adresse.zone, adresse.quartier, formatVille(adresse.ville), adresse.complement_adresse].filter(Boolean).join(', ')
}

const fetchUsers = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.role) params.role = filters.value.role
    if (filters.value.statut) params.statut = filters.value.statut
    if (filters.value.vedette) params.vedette = 1

    const response = await api.admin.users.getAll(params)
    if (response.data.success) {
      users.value = response.data.data.data
      totalUsers.value = response.data.data.total
    } else {
      error.value = 'Impossible de charger les utilisateurs.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des utilisateurs.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
  fetchUsers()
}

const resetFilters = () => {
  filters.value.search = ''
  filters.value.role = ''
  filters.value.statut = ''
  filters.value.vedette = false
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
  fetchUsers()
}

const onStatusChange = async (user, event) => {
  const nextStatus = event.target.value
  if (nextStatus === user.statut) return

  if (user.id === authStore.user?.id) {
    event.target.value = user.statut
    return
  }

  const confirmed = await confirmer({
    titre: 'Changer le statut',
    message: nextStatus === 'actif'
      ? `${user.nom_complet} pourra de nouveau se connecter.`
      : `${user.nom_complet} passera en « ${getStatutLabel(nextStatus)} » et sera déconnecté de tous ses appareils.`,
    libelleConfirmer: 'Confirmer',
    danger: nextStatus !== 'actif',
  })
  if (!confirmed) {
    event.target.value = user.statut
    return
  }

  updatingStatusId.value = user.id
  try {
    const response = await api.admin.users.updateStatus(user.id, { statut: nextStatus })
    if (response.data.success) {
      user.statut = response.data.data.statut
      if (selectedUser.value?.id === user.id) {
        selectedUser.value.statut = response.data.data.statut
      }
      toastStore.succes('Statut mis à jour.')
    }
  } catch (err) {
    event.target.value = user.statut
    toastStore.erreur(messageErreur(err, 'Erreur lors de la mise à jour du statut.'))
  } finally {
    updatingStatusId.value = null
  }
}

const onRoleChange = async (user, event) => {
  const nextRole = event.target.value
  if (nextRole === user.role) return

  if (user.id === authStore.user?.id) {
    event.target.value = user.role
    return
  }

  const confirmed = await confirmer({
    titre: 'Changer le rôle',
    message: nextRole === 'vendeur'
      ? `${user.nom_complet} deviendra « Vendeur ». Avant de pouvoir vendre, il devra compléter son profil boutique (e-mail, logo, description et conditions).`
      : `${user.nom_complet} deviendra « ${getRoleLabel(nextRole)} ».`,
    libelleConfirmer: 'Confirmer',
  })
  if (!confirmed) {
    event.target.value = user.role
    return
  }

  updatingRoleId.value = user.id
  try {
    const response = await api.admin.users.updateRole(user.id, { role: nextRole })
    if (response.data.success) {
      const { role, est_vendeur_vedette: vedette, profil_vendeur_complet: complet } = response.data.data
      Object.assign(user, { role, est_vendeur_vedette: vedette, profil_vendeur_complet: complet })
      if (selectedUser.value?.id === user.id) {
        Object.assign(selectedUser.value, { role, est_vendeur_vedette: vedette, profil_vendeur_complet: complet })
      }
      toastStore.succes('Rôle mis à jour.')
    }
  } catch (err) {
    event.target.value = user.role
    toastStore.erreur(messageErreur(err, 'Erreur lors de la mise à jour du rôle.'))
  } finally {
    updatingRoleId.value = null
  }
}

const toggleVedette = async (user) => {
  const nouvelleValeur = !user.est_vendeur_vedette
  updatingVedetteId.value = user.id

  try {
    const response = await api.admin.users.updateVedette(user.id, { est_vendeur_vedette: nouvelleValeur })
    const vedette = Boolean(response.data.data?.est_vendeur_vedette)
    // La ligne du tableau et la fenêtre de détail sont deux objets distincts
    const ligne = users.value.find((item) => item.id === user.id)
    if (ligne) ligne.est_vendeur_vedette = vedette
    if (selectedUser.value?.id === user.id) selectedUser.value.est_vendeur_vedette = vedette
    toastStore.succes(response.data.message || 'Mise en avant mise à jour.')
    if (filters.value.vedette && !vedette) {
      fetchUsers()
    }
  } catch (err) {
    toastStore.erreur(messageErreur(err, 'Impossible de modifier la mise en avant.'))
  } finally {
    updatingVedetteId.value = null
  }
}

const openDetail = async (user) => {
  showDetail.value = true
  detailLoading.value = true
  detailError.value = ''
  selectedUser.value = null

  try {
    const response = await api.admin.users.getOne(user.id)
    if (response.data.success) {
      selectedUser.value = response.data.data
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
  selectedUser.value = null
}

onMounted(() => {
  fetchUsers()
})
</script>
