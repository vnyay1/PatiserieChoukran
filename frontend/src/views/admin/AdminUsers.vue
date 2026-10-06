<!-- ===================================
ADMIN - GESTION DES UTILISATEURS
File: src/views/admin/AdminUsers.vue
=================================== -->
<!--
  Filtres et page dans l'URL, TableauDonnees (cartes sur mobile). Rôle et statut se changent dans
  la ligne (confirmation par useConfirm) ; l'admin connecté ne peut pas modifier son propre compte.
-->
<template>
  <div class="container mx-auto max-w-6xl pb-6 pt-6 md:pt-8">
    <EnTetePage titre="Utilisateurs" sous-titre="Gérer les rôles et les statuts, consulter les informations des clients.">
      <template #actions>
        <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" :loading="loading" @click="fetchUsers">
          Actualiser
        </Button>
      </template>
    </EnTetePage>

    <Card padding="md" class="mb-6">
      <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-4">
        <ChampRecherche
          id="filtre-utilisateurs-recherche"
          v-model="filtres.search"
          libelle="Recherche"
          libelle-visible
          placeholder="Nom, e-mail ou téléphone…"
          class="md:col-span-2"
          @rechercher="mettreAJour({ page: 1 })"
        />
        <div>
          <label for="filtre-utilisateurs-role" class="label">Rôle</label>
          <select id="filtre-utilisateurs-role" v-model="filtres.role" class="input" @change="mettreAJour({ page: 1 })">
            <option value="">Tous</option>
            <option v-for="(libelle, valeur) in ROLES" :key="valeur" :value="valeur">{{ libelle }}</option>
          </select>
        </div>
        <div>
          <label for="filtre-utilisateurs-statut" class="label">Statut</label>
          <select id="filtre-utilisateurs-statut" v-model="filtres.statut" class="input" @change="mettreAJour({ page: 1 })">
            <option value="">Tous</option>
            <option v-for="(infos, valeur) in STATUTS_COMPTE" :key="valeur" :value="valeur">{{ infos.label }}</option>
          </select>
        </div>
        <div class="flex flex-wrap items-center gap-3 md:col-span-4">
          <label class="mr-auto inline-flex min-h-11 cursor-pointer items-center gap-2">
            <input
              type="checkbox"
              class="h-5 w-5 flex-shrink-0 rounded"
              :checked="filtres.vedette === '1'"
              @change="mettreAJour({ vedette: $event.target.checked ? '1' : '', page: 1 })"
            />
            <span class="text-sm text-gray-800">Vendeurs en vedette uniquement</span>
          </label>
          <Button variant="outline" size="sm" :disabled="!filtresActifs" @click="reinitialiserFiltres">
            Réinitialiser les filtres
          </Button>
        </div>
      </div>
    </Card>

    <TableauDonnees
      libelle="Utilisateurs"
      :chargement="loading"
      :erreur="error"
      :vide="users.length === 0"
      :icone="Users"
      :titre-vide="filtresActifs ? 'Aucun utilisateur trouvé' : 'Aucun utilisateur'"
      :texte-vide="filtresActifs ? 'Aucun utilisateur ne correspond à ces filtres.' : ''"
      @reessayer="fetchUsers"
    >
      <template #barre>
        <p class="text-sm text-gray-600">{{ totalUsers }} utilisateur{{ totalUsers > 1 ? 's' : '' }}</p>
      </template>

      <!-- Mobile : une carte par utilisateur -->
      <template #cartes>
        <li v-for="user in users" :key="user.id" class="space-y-3 p-4">
          <div class="flex items-start gap-3">
            <Avatar :nom="user.nom_complet" />
            <div class="min-w-0 flex-1">
              <p class="font-semibold text-gray-900">{{ user.nom_complet }}</p>
              <p class="break-all text-sm text-gray-600">{{ user.email }}</p>
              <p v-if="user.telephone" class="text-sm text-gray-600">{{ user.telephone }}</p>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <BadgeStatut :statut="user.statut" type="compte" />
            <span v-if="user.role === 'vendeur' && user.est_vendeur_vedette" class="badge badge-primary">Vedette</span>
            <span v-if="user.role === 'vendeur' && !user.profil_vendeur_complet" class="badge badge-orange">Profil boutique à compléter</span>
            <span class="text-sm text-gray-600">
              {{ formatNumber(user.commandes_count || 0) }} commande{{ (user.commandes_count || 0) > 1 ? 's' : '' }}, inscrit le {{ formatDate(user.created_at) }}
            </span>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label :for="`role-carte-${user.id}`" class="label">Rôle</label>
              <select
                :id="`role-carte-${user.id}`"
                class="input py-2 text-sm"
                :disabled="updatingRoleId === user.id || user.id === authStore.user?.id"
                :value="user.role"
                @change="onRoleChange(user, $event)"
              >
                <option v-for="(libelle, valeur) in ROLES" :key="valeur" :value="valeur">{{ libelle }}</option>
              </select>
            </div>
            <div>
              <label :for="`statut-carte-${user.id}`" class="label">Statut</label>
              <select
                :id="`statut-carte-${user.id}`"
                class="input py-2 text-sm"
                :disabled="updatingStatusId === user.id || user.id === authStore.user?.id"
                :value="user.statut"
                @change="onStatusChange(user, $event)"
              >
                <option v-for="(infos, valeur) in STATUTS_COMPTE" :key="valeur" :value="valeur">{{ infos.label }}</option>
              </select>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button
              v-if="user.role === 'vendeur'"
              variant="ghost"
              size="sm"
              :icon="Star"
              :icon-size="16"
              :loading="updatingVedetteId === user.id"
              @click="toggleVedette(user)"
            >
              {{ user.est_vendeur_vedette ? 'Retirer de la vedette' : 'Mettre en vedette' }}
            </Button>
            <Button variant="secondary" size="sm" @click="openDetail(user)">
              Détails<span class="sr-only"> de {{ user.nom_complet }}</span>
            </Button>
          </div>
        </li>
      </template>

      <!-- Desktop : tableau -->
      <template #entete>
        <th scope="col" class="px-4 py-3 font-semibold">Utilisateur</th>
        <th scope="col" class="px-4 py-3 font-semibold">Rôle</th>
        <th scope="col" class="px-4 py-3 font-semibold">Statut</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Commandes</th>
        <th scope="col" class="px-4 py-3 font-semibold">Inscription</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Actions</th>
      </template>
      <template #lignes>
        <tr v-for="user in users" :key="user.id">
          <td class="px-4 py-3">
            <p class="font-semibold text-gray-900">{{ user.nom_complet }}</p>
            <p class="text-xs text-gray-600">{{ user.email }}</p>
            <p v-if="user.telephone" class="text-xs text-gray-600">{{ user.telephone }}</p>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <select
                class="input w-auto py-2 text-sm"
                :aria-label="`Rôle de ${user.nom_complet}`"
                :disabled="updatingRoleId === user.id || user.id === authStore.user?.id"
                :value="user.role"
                @change="onRoleChange(user, $event)"
              >
                <option v-for="(libelle, valeur) in ROLES" :key="valeur" :value="valeur">{{ libelle }}</option>
              </select>
              <!-- Vedette : les produits du vendeur passent en tête du catalogue -->
              <button
                v-if="user.role === 'vendeur'"
                type="button"
                class="btn-icone"
                :class="user.est_vendeur_vedette ? 'text-gold-600' : 'text-gray-600 hover:text-gold-600'"
                :aria-pressed="user.est_vendeur_vedette"
                :aria-label="`Vendeur vedette : ${user.nom_complet}`"
                :disabled="updatingVedetteId === user.id"
                @click="toggleVedette(user)"
              >
                <Star :size="18" :fill="user.est_vendeur_vedette ? 'currentColor' : 'none'" aria-hidden="true" />
              </button>
            </div>
            <p v-if="user.role === 'vendeur' && !user.profil_vendeur_complet" class="mt-1 text-xs font-medium text-orange-700">
              Profil boutique à compléter
            </p>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <BadgeStatut :statut="user.statut" type="compte" />
              <select
                class="input w-auto py-2 text-sm"
                :aria-label="`Statut de ${user.nom_complet}`"
                :disabled="updatingStatusId === user.id || user.id === authStore.user?.id"
                :value="user.statut"
                @change="onStatusChange(user, $event)"
              >
                <option v-for="(infos, valeur) in STATUTS_COMPTE" :key="valeur" :value="valeur">{{ infos.label }}</option>
              </select>
            </div>
          </td>
          <td class="px-4 py-3 text-right tabular-nums">{{ formatNumber(user.commandes_count || 0) }}</td>
          <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ formatDate(user.created_at) }}</td>
          <td class="px-4 py-3 text-right">
            <Button variant="outline" size="sm" @click="openDetail(user)">
              Détails<span class="sr-only"> de {{ user.nom_complet }}</span>
            </Button>
          </td>
        </tr>
      </template>

      <template v-if="totalPages > 1" #pied>
        <Pagination
          :page="filtres.page"
          :derniere="totalPages"
          :desactive="loading"
          libelle="Pages d'utilisateurs"
          @update:page="(page) => mettreAJour({ page })"
        />
      </template>
    </TableauDonnees>

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
            <h3 class="mb-2 font-body text-sm font-semibold text-gray-600">Informations</h3>
            <div class="text-lg font-semibold text-gray-800">{{ selectedUser.nom_complet }}</div>
            <div class="text-sm text-gray-600">{{ selectedUser.email }}</div>
            <div v-if="selectedUser.telephone" class="text-sm text-gray-600">
              {{ selectedUser.telephone }}
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <span class="badge badge-neutral">
                {{ getRoleLabel(selectedUser.role) }}
              </span>
              <BadgeStatut :statut="selectedUser.statut" type="compte" />
              <span v-if="selectedUser.role === 'vendeur' && selectedUser.est_vendeur_vedette" class="badge badge-primary">
                Vedette
              </span>
              <span v-if="selectedUser.role === 'vendeur' && !selectedUser.profil_vendeur_complet" class="badge badge-orange">
                Profil boutique incomplet
              </span>
            </div>
            <div class="text-xs text-gray-500 mt-3">
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
            <h3 class="mb-2 font-body text-sm font-semibold text-gray-600">Statistiques client</h3>
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
            <h3 class="mb-2 font-body text-sm font-semibold text-gray-600">Statistiques vendeur</h3>
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
          <h3 class="font-body text-sm font-semibold text-gray-600">Adresses</h3>
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
                Repère : {{ adresse.point_repere }}
              </div>
            </Card>
          </div>
        </div>

        <div class="space-y-3">
          <h3 class="font-body text-sm font-semibold text-gray-600">Dernières commandes</h3>
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
                <p class="text-xs text-gray-600">{{ formatDate(commande.created_at) }}</p>
              </div>
              <div class="flex items-center gap-2">
                <BadgeStatut :statut="commande.statut" />
                <span class="price text-base">{{ formatPrice(commande.montant_total) }} FCFA</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api, { messageErreur } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import { useFiltresUrl } from '@/composables/useFiltresUrl'
import { formatPrice, formatPrice as formatNumber, formatDate, libelleCompte, STATUTS_COMPTE } from '@/utils/format'
import BadgeStatut from '@/components/common/BadgeStatut.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import EnTetePage from '@/components/common/EnTetePage.vue'
import BaseModal from '@/components/common/BaseModal.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import TableauDonnees from '@/components/common/TableauDonnees.vue'
import Pagination from '@/components/common/Pagination.vue'
import ChampRecherche from '@/components/common/ChampRecherche.vue'
import Avatar from '@/components/common/Avatar.vue'
import { Star, RefreshCw, Users } from 'lucide-vue-next'
import { formatVille } from '@/utils/villes'

const PAR_PAGE = 15
const FILTRES_VIDES = { search: '', role: '', statut: '', vedette: '' }
const ROLES = { client: 'Client', vendeur: 'Vendeur', admin: 'Admin' }

const users = ref([])
const loading = ref(false)
const error = ref('')
const totalUsers = ref(0)
const updatingStatusId = ref(null)
const updatingRoleId = ref(null)
const updatingVedetteId = ref(null)

const authStore = useAuthStore()
const toastStore = useToastStore()
const { confirmer } = useConfirm()

// Filtres (vedette=1) et page dans l'URL
const { filtres, mettreAJour, recharger } = useFiltresUrl({ ...FILTRES_VIDES, page: 1 }, (estActuel) => fetchUsers(estActuel))
const filtresActifs = computed(() => Object.keys(FILTRES_VIDES).some((cle) => filtres[cle] !== ''))
const reinitialiserFiltres = () => mettreAJour({ ...FILTRES_VIDES, page: 1 })
const totalPages = computed(() => Math.max(1, Math.ceil(totalUsers.value / PAR_PAGE)))

const showDetail = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const selectedUser = ref(null)

// Le serveur ne renvoie que les 5 dernières commandes ; tri gardé par sécurité
const lastCommandes = computed(() => {
  if (!selectedUser.value?.commandes) return []
  return [...selectedUser.value.commandes]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 5)
})

const getRoleLabel = (role) => ROLES[role] || role

const formatAdresse = (adresse) => {
  return [adresse.zone, adresse.quartier, formatVille(adresse.ville), adresse.complement_adresse].filter(Boolean).join(', ')
}

const fetchUsers = async (estActuel = () => true) => {
  loading.value = true
  error.value = ''

  try {
    const params = { page: filtres.page, per_page: PAR_PAGE }
    if (filtres.search) params.search = filtres.search
    if (filtres.role) params.role = filtres.role
    if (filtres.statut) params.statut = filtres.statut
    if (filtres.vedette === '1') params.vedette = 1

    const response = await api.admin.users.getAll(params)
    if (!estActuel()) return
    if (response.data.success) {
      users.value = response.data.data.data
      totalUsers.value = response.data.data.total

      // Lien vers une page trop loin : dernière page remplie
      if (users.value.length === 0 && filtres.page > 1 && totalUsers.value > 0) {
        mettreAJour({ page: Math.max(1, Number(response.data.data.last_page) || filtres.page - 1) })
      }
    } else {
      error.value = 'Impossible de charger les utilisateurs.'
    }
  } catch (err) {
    if (!estActuel()) return
    error.value = messageErreur(err, 'Erreur lors du chargement des utilisateurs.')
  } finally {
    // Un chargement dépassé laisse l'indicateur au plus récent
    if (estActuel()) loading.value = false
  }
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
      : `${user.nom_complet} passera en « ${libelleCompte(nextStatus)} » et sera déconnecté de tous ses appareils.`,
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
    if (filtres.vedette === '1' && !vedette) {
      recharger()
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
    detailError.value = messageErreur(err, 'Erreur lors du chargement des détails.')
  } finally {
    detailLoading.value = false
  }
}

const closeDetail = () => {
  showDetail.value = false
  selectedUser.value = null
}
</script>
