<!-- ===================================
ADMIN / VENDEUR - GESTION DES COMMANDES
File: src/views/admin/AdminCommandes.vue
=================================== -->

<template>
  <div class="admin-commandes-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            {{ isAdmin ? 'Gestion des commandes' : 'Mes commandes à traiter' }}
          </h1>
          <p class="text-gray-600 text-sm">
            {{ isHistoriqueMode
              ? 'Commandes terminées : annulées, ou livrées et payées.'
              : 'Faites avancer chaque commande et confirmez les paiements reçus.' }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button variant="secondary" size="sm" @click="toggleHistoriqueMode">
            {{ isHistoriqueMode ? 'Commandes en cours' : 'Historique' }}
          </Button>
          <Button variant="outline" size="sm" :loading="loading" @click="fetchCommandes">
            Actualiser
          </Button>
        </div>
      </div>

      <!-- Repères du vendeur (l'admin dispose du tableau de bord) -->
      <div v-if="!isAdmin && statsVendeur" class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <Card v-for="carte in cartesStats" :key="carte.cle" padding="md">
          <div class="text-xs text-gray-500">{{ carte.label }}</div>
          <div class="font-display text-2xl font-bold text-gold-600">{{ carte.valeur }}</div>
        </Card>
      </div>

      <Card padding="md" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2" for="filtre-recherche">Recherche</label>
            <input
              id="filtre-recherche"
              v-model="filters.search"
              type="search"
              placeholder="Numéro, client, téléphone..."
              class="input"
              @input="handleSearch"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="filtre-statut">Statut</label>
            <select id="filtre-statut" v-model="filters.statut" class="input" @change="applyFilters">
              <option value="">Tous</option>
              <option v-for="(infos, valeur) in STATUTS_COMMANDE" :key="valeur" :value="valeur">
                {{ infos.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="filtre-paiement">Paiement</label>
            <select id="filtre-paiement" v-model="filters.statut_paiement" class="input" @change="applyFilters">
              <option value="">Tous</option>
              <option v-for="(infos, valeur) in STATUTS_PAIEMENT" :key="valeur" :value="valeur">
                {{ infos.label }}
              </option>
            </select>
          </div>
          <div v-if="isAdmin" class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2" for="filtre-vendeur">Vendeur</label>
            <select id="filtre-vendeur" v-model="filters.vendeur_id" class="input" @change="applyFilters">
              <option value="">Tous les vendeurs</option>
              <option v-for="vendeur in vendeurs" :key="vendeur.id" :value="String(vendeur.id)">
                {{ vendeur.nom_complet }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="filtre-debut">Du</label>
            <input id="filtre-debut" v-model="filters.date_debut" type="date" class="input" @change="applyFilters" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="filtre-fin">Au</label>
            <input id="filtre-fin" v-model="filters.date_fin" type="date" class="input" @change="applyFilters" />
          </div>
          <div class="md:col-span-4 flex justify-end">
            <Button variant="outline" size="sm" @click="resetFilters">
              Réinitialiser les filtres
            </Button>
          </div>
        </div>
      </Card>

      <Card v-if="error" padding="md" class="mb-6 border border-red-200 bg-red-50">
        <p class="text-red-600 text-sm">{{ error }}</p>
      </Card>

      <Card padding="none">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <div class="text-sm text-gray-600">
            {{ totalCommandes }} commande{{ totalCommandes > 1 ? 's' : '' }}{{ isHistoriqueMode ? ' archivée' + (totalCommandes > 1 ? 's' : '') : '' }}
          </div>
          <div class="text-xs text-gray-500">
            Page {{ currentPage }} / {{ totalPages }}
          </div>
        </div>

        <div v-if="loading" class="p-6 text-center text-gray-500">Chargement...</div>
        <div v-else-if="commandes.length === 0" class="p-6 text-center text-gray-500">
          {{ isHistoriqueMode ? 'Aucune commande archivée.' : 'Aucune commande à traiter. 🎉' }}
        </div>

        <template v-else>
          <!-- Mobile : une carte par commande -->
          <div class="md:hidden divide-y divide-gray-100">
            <div v-for="commande in commandes" :key="commande.id" class="p-4 space-y-3">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="font-semibold text-gray-800">{{ commande.numero_commande }}</div>
                  <div class="text-xs text-gray-500">
                    {{ formatDate(commande.created_at) }} · {{ commande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait' }}
                  </div>
                </div>
                <div class="price text-base">{{ formatPrice(commande.montant_total) }} FCFA</div>
              </div>
              <div class="text-sm text-gray-700">
                {{ commande.user?.nom_complet || 'Client' }}
                <a v-if="commande.user?.telephone" :href="`tel:${commande.user.telephone}`" class="text-gold-600 ml-1">
                  {{ commande.user.telephone }}
                </a>
                <div v-if="isAdmin && commande.vendeur" class="text-xs text-gray-500">
                  Vendeur : {{ commande.vendeur.nom_complet }}
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="badge" :class="classeStatut(commande.statut)">{{ libelleStatut(commande.statut) }}</span>
                <span v-if="commande.statut !== 'annulee'" class="badge" :class="classePaiement(commande.statut_paiement)">
                  {{ libellePaiement(commande.statut_paiement) }}
                </span>
              </div>
              <div class="flex flex-wrap gap-2">
                <select
                  v-if="!isReadOnlyCommande(commande)"
                  class="input py-2 text-sm flex-1 min-w-[10rem]"
                  :disabled="updatingStatusId === commande.id"
                  :value="commande.statut"
                  aria-label="Changer le statut"
                  @change="onStatusChange(commande, $event)"
                >
                  <option v-for="statut in optionsStatut(commande)" :key="statut" :value="statut">
                    {{ statut === commande.statut ? libelleStatut(statut) : `→ ${libelleStatut(statut)}` }}
                  </option>
                </select>
                <Button
                  v-if="peutConfirmerPaiement(commande)"
                  variant="outline"
                  size="sm"
                  :loading="confirmingPaymentId === commande.id"
                  @click="confirmPayment(commande)"
                >
                  Paiement reçu
                </Button>
                <Button variant="secondary" size="sm" @click="openDetail(commande)">
                  Détails
                </Button>
              </div>
            </div>
          </div>

          <!-- Desktop : tableau -->
          <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 text-gray-600">
                <tr>
                  <th class="text-left font-semibold px-4 py-3">Commande</th>
                  <th class="text-left font-semibold px-4 py-3">Client</th>
                  <th v-if="isAdmin" class="text-left font-semibold px-4 py-3">Vendeur</th>
                  <th class="text-left font-semibold px-4 py-3">Statut</th>
                  <th class="text-left font-semibold px-4 py-3">Paiement</th>
                  <th class="text-left font-semibold px-4 py-3">Total</th>
                  <th class="text-right font-semibold px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="commande in commandes" :key="commande.id" class="border-t border-gray-100 align-top">
                  <td class="px-4 py-3">
                    <div class="font-semibold text-gray-800">{{ commande.numero_commande }}</div>
                    <div class="text-xs text-gray-500">
                      {{ formatDate(commande.created_at) }} · {{ commande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait' }}
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="font-semibold text-gray-800">{{ commande.user?.nom_complet || 'Client' }}</div>
                    <div class="text-xs text-gray-500">{{ commande.user?.telephone }}</div>
                  </td>
                  <td v-if="isAdmin" class="px-4 py-3 text-gray-700">
                    {{ commande.vendeur?.nom_complet || '—' }}
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex flex-col gap-2">
                      <span class="badge self-start" :class="classeStatut(commande.statut)">
                        {{ libelleStatut(commande.statut) }}
                      </span>
                      <select
                        v-if="!isReadOnlyCommande(commande)"
                        class="text-xs border border-gray-200 rounded-lg px-2 py-1"
                        :disabled="updatingStatusId === commande.id"
                        :value="commande.statut"
                        aria-label="Changer le statut"
                        @change="onStatusChange(commande, $event)"
                      >
                        <option v-for="statut in optionsStatut(commande)" :key="statut" :value="statut">
                          {{ statut === commande.statut ? 'Changer…' : `→ ${libelleStatut(statut)}` }}
                        </option>
                      </select>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex flex-col gap-2">
                      <span
                        v-if="commande.statut !== 'annulee'"
                        class="badge self-start"
                        :class="classePaiement(commande.statut_paiement)"
                      >
                        {{ libellePaiement(commande.statut_paiement) }}
                      </span>
                      <Button
                        v-if="peutConfirmerPaiement(commande)"
                        variant="outline"
                        size="sm"
                        :loading="confirmingPaymentId === commande.id"
                        @click="confirmPayment(commande)"
                      >
                        Paiement reçu
                      </Button>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="price text-base">{{ formatPrice(commande.montant_total) }} FCFA</div>
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
        </template>

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
        @click.self="closeDetail"
      >
        <div class="bg-white w-full max-w-4xl rounded-elegant shadow-card overflow-hidden flex flex-col max-h-full">
          <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-display text-xl font-bold text-gray-800">
              {{ selectedCommande?.numero_commande || 'Détails commande' }}
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

          <div v-else-if="selectedCommande" class="p-6 space-y-6 overflow-y-auto">
            <!-- En-tête -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
              <div class="text-sm text-gray-600">
                Passée le {{ formatDateHeure(selectedCommande.created_at) }}
                <span v-if="isAdmin && selectedCommande.vendeur"> · Vendeur : {{ selectedCommande.vendeur.nom_complet }}</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <span class="badge" :class="classeStatut(selectedCommande.statut)">
                  {{ libelleStatut(selectedCommande.statut) }}
                </span>
                <!-- Une commande annulée n'affiche plus son statut de paiement -->
                <span
                  v-if="selectedCommande.statut !== 'annulee'"
                  class="badge"
                  :class="classePaiement(selectedCommande.statut_paiement)"
                >
                  {{ libellePaiement(selectedCommande.statut_paiement) }}
                </span>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Client & livraison -->
              <Card padding="md">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Client</div>
                <div class="text-sm text-gray-800 font-semibold">{{ selectedCommande.user?.nom_complet || 'Client' }}</div>
                <a
                  v-if="selectedCommande.user?.telephone"
                  :href="`tel:${selectedCommande.user.telephone}`"
                  class="text-sm text-gold-600"
                >
                  {{ selectedCommande.user.telephone }}
                </a>

                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mt-4 mb-2">
                  {{ selectedCommande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait en boutique' }}
                </div>
                <template v-if="selectedCommande.type_livraison === 'livraison'">
                  <div v-if="selectedCommande.adresse_livraison" class="text-sm text-gray-700 space-y-1">
                    <div>
                      <span v-if="selectedCommande.adresse_livraison.libelle" class="font-medium">
                        {{ selectedCommande.adresse_livraison.libelle }} —
                      </span>
                      {{ selectedCommande.adresse_livraison.quartier }},
                      {{ formatVille(selectedCommande.adresse_livraison.ville) }}
                    </div>
                    <div v-if="selectedCommande.adresse_livraison.point_repere">
                      Repère : {{ selectedCommande.adresse_livraison.point_repere }}
                    </div>
                    <div v-if="selectedCommande.adresse_livraison.complement_adresse">
                      {{ selectedCommande.adresse_livraison.complement_adresse }}
                    </div>
                  </div>
                  <div v-else class="text-sm text-gray-500">Adresse supprimée par le client.</div>
                  <a
                    v-if="selectedCommande.telephone_livraison"
                    :href="`tel:${selectedCommande.telephone_livraison}`"
                    class="text-sm text-gold-600 block mt-1"
                  >
                    📞 {{ selectedCommande.telephone_livraison }}
                  </a>
                </template>
                <div v-if="selectedCommande.date_livraison_souhaitee" class="text-sm text-gray-700 mt-2">
                  🕒 {{ formatDateLongue(selectedCommande.date_livraison_souhaitee) }}
                  <span v-if="selectedCommande.heure_livraison_souhaitee">à {{ formatHeure(selectedCommande.heure_livraison_souhaitee) }}</span>
                </div>
                <div v-if="selectedCommande.instructions_speciales" class="text-sm text-gray-700 mt-2 p-2 rounded-lg bg-gold-50">
                  {{ selectedCommande.instructions_speciales }}
                </div>
              </Card>

              <!-- Paiement & montants -->
              <Card padding="md">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Paiement</div>
                <div class="text-sm text-gray-700">{{ libelleMoyenPaiement(selectedCommande.moyen_paiement) }}</div>
                <div v-if="selectedCommande.telephone_paiement" class="text-sm text-gray-700">
                  Numéro : {{ selectedCommande.telephone_paiement }}
                </div>
                <div v-if="selectedCommande.reference_paiement" class="text-sm text-gray-700">
                  Référence : {{ selectedCommande.reference_paiement }}
                </div>
                <div v-if="selectedCommande.date_paiement" class="text-sm text-gray-700">
                  Reçu le {{ formatDateHeure(selectedCommande.date_paiement) }}
                </div>

                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mt-4 mb-2">Montants</div>
                <div class="text-sm text-gray-700 flex justify-between">
                  <span>Produits</span><span>{{ formatPrice(selectedCommande.montant_produits) }} FCFA</span>
                </div>
                <div class="text-sm text-gray-700 flex justify-between">
                  <span>Livraison</span><span>{{ formatPrice(selectedCommande.montant_livraison) }} FCFA</span>
                </div>
                <div class="text-sm font-semibold text-gray-800 flex justify-between mt-1">
                  <span>Total</span><span>{{ formatPrice(selectedCommande.montant_total) }} FCFA</span>
                </div>
              </Card>
            </div>

            <!-- Articles -->
            <div class="space-y-3">
              <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Articles</div>
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
                      @error="onImageError"
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

            <!-- Historique -->
            <div v-if="selectedCommande.historiques?.length" class="space-y-2">
              <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Historique</div>
              <ol class="border-l-2 border-gold-200 pl-4 space-y-3">
                <li v-for="etape in historiqueTrie" :key="etape.id" class="text-sm">
                  <div class="font-medium text-gray-800">{{ libelleStatut(etape.nouveau_statut) }}</div>
                  <div class="text-xs text-gray-500">
                    {{ formatDateHeure(etape.created_at) }}
                    <span v-if="etape.modifie_par"> · {{ etape.modifie_par.nom_complet }}</span>
                  </div>
                  <div v-if="etape.commentaire" class="text-xs text-gray-600 mt-0.5">{{ etape.commentaire }}</div>
                </li>
              </ol>
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
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import { formatVille } from '@/composables/useLivraisonVendeurs'
import api, { messageErreur } from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { resolveImageUrl, onImageError } from '@/utils/images'
import {
  STATUTS_COMMANDE,
  STATUTS_PAIEMENT,
  formatPrice,
  formatDate,
  formatDateLongue,
  formatDateHeure,
  formatHeure,
  libelleStatut,
  classeStatut,
  libellePaiement,
  classePaiement,
  libelleMoyenPaiement,
} from '@/utils/format'

const authStore = useAuthStore()
const toastStore = useToastStore()
const { demander } = useConfirm()

const commandes = ref([])
const vendeurs = ref([])
const statsVendeur = ref(null)
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
  vendeur_id: '',
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

const historiqueTrie = computed(() => {
  return [...(selectedCommande.value?.historiques || [])]
    .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

const notifyVendeurBadgeRefresh = () => {
  window.dispatchEvent(new CustomEvent('vendeur-commandes-updated'))
}

const isCommandeArchivee = (commande) => {
  return commande?.statut === 'annulee'
    || (commande?.statut === 'livree' && commande?.statut_paiement === 'paye')
}

const isReadOnlyCommande = (commande) => {
  return isHistoriqueMode.value || isCommandeArchivee(commande) || optionsStatut(commande).length <= 1
}

const peutConfirmerPaiement = (commande) => {
  return !isHistoriqueMode.value && commande.statut_paiement === 'en_attente' && commande.statut !== 'annulee'
}

// Statut actuel + étapes suivantes autorisées (fournies par l'API)
const optionsStatut = (commande) => {
  const suivants = Array.isArray(commande?.statuts_suivants)
    ? commande.statuts_suivants
    : Object.keys(STATUTS_COMMANDE).filter((statut) => statut !== commande?.statut)
  return [commande?.statut, ...suivants].filter(Boolean)
}

const cartesStats = computed(() => {
  const stats = statsVendeur.value
  if (!stats) return []

  return [
    { cle: 'a_traiter', label: 'À traiter', valeur: stats.a_traiter ?? 0 },
    { cle: 'aujourd_hui', label: "Reçues aujourd'hui", valeur: stats.aujourd_hui ?? 0 },
    { cle: 'livrees_ce_mois', label: 'Livrées ce mois', valeur: stats.livrees_ce_mois ?? 0 },
    { cle: 'encaisse_ce_mois', label: 'Encaissé ce mois', valeur: `${formatPrice(stats.encaisse_ce_mois)} FCFA` },
  ]
})

const fetchStatsVendeur = async () => {
  if (isAdmin.value) return
  try {
    const response = await api.vendeur.stats()
    if (response.data.success) {
      statsVendeur.value = response.data.data
    }
  } catch (err) {
    console.error('Erreur chargement des statistiques vendeur:', err)
  }
}

const fetchVendeurs = async () => {
  if (!isAdmin.value) return
  try {
    const response = await api.admin.users.getAll({ role: 'vendeur', per_page: 100 })
    if (response.data.success) {
      vendeurs.value = response.data.data.data || []
    }
  } catch (err) {
    console.error('Erreur chargement vendeurs:', err)
  }
}

const fetchCommandes = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    Object.entries(filters.value).forEach(([cle, valeur]) => {
      if (valeur) params[cle] = valeur
    })
    if (!isAdmin.value) delete params.vendeur_id
    if (isHistoriqueMode.value) params.historique = 1

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
    error.value = messageErreur(err, 'Erreur lors du chargement des commandes.')
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
  fetchCommandes()
}

const toggleHistoriqueMode = () => {
  isHistoriqueMode.value = !isHistoriqueMode.value
  currentPage.value = 1
  fetchCommandes()
}

const resetFilters = () => {
  filters.value = {
    search: '',
    statut: '',
    statut_paiement: '',
    vendeur_id: '',
    date_debut: '',
    date_fin: '',
  }
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
  const nextStatus = event.target.value
  if (nextStatus === commande.statut) return

  // Commentaire facultatif : visible dans l'historique de la commande
  const commentaire = await demander({
    titre: nextStatus === 'annulee' ? 'Annuler la commande' : 'Changer le statut',
    message: `${commande.numero_commande} : « ${libelleStatut(commande.statut)} » → « ${libelleStatut(nextStatus)} »`
      + (nextStatus === 'annulee' ? '\nLe stock des produits sera remis en vente.' : ''),
    champ: 'Commentaire (facultatif)',
    placeholder: nextStatus === 'annulee' ? 'Motif de l\'annulation' : 'Ex. livreur en route',
    libelleConfirmer: nextStatus === 'annulee' ? 'Annuler la commande' : 'Confirmer',
    danger: nextStatus === 'annulee',
  })
  if (commentaire === null) {
    event.target.value = commande.statut
    return
  }

  updatingStatusId.value = commande.id
  try {
    const response = await api.admin.commandes.updateStatus(commande.id, {
      statut: nextStatus,
      commentaire: commentaire.trim() || null,
    })
    if (response.data.success) {
      toastStore.succes(`${commande.numero_commande} : ${libelleStatut(nextStatus)}.`)
      if (selectedCommande.value?.id === commande.id) {
        closeDetail()
      }
      await fetchCommandes()
      fetchStatsVendeur()
      notifyVendeurBadgeRefresh()
    }
  } catch (err) {
    event.target.value = commande.statut
    toastStore.erreur(messageErreur(err, 'Erreur lors de la mise à jour du statut.'))
  } finally {
    updatingStatusId.value = null
  }
}

const confirmPayment = async (commande) => {
  const reference = await demander({
    titre: 'Confirmer le paiement',
    message: `${commande.numero_commande} — ${formatPrice(commande.montant_total)} FCFA (${libelleMoyenPaiement(commande.moyen_paiement)})`,
    champ: 'Référence de paiement (facultatif)',
    placeholder: 'Ex. identifiant de la transaction',
    libelleConfirmer: 'Paiement reçu',
  })
  if (reference === null) return

  confirmingPaymentId.value = commande.id
  try {
    const response = await api.admin.commandes.confirmPayment(commande.id, {
      reference_paiement: reference.trim() || null,
    })
    if (response.data.success) {
      toastStore.succes(`Paiement de ${commande.numero_commande} confirmé.`)
      if (selectedCommande.value?.id === commande.id) {
        closeDetail()
      }
      await fetchCommandes()
      fetchStatsVendeur()
      notifyVendeurBadgeRefresh()
    }
  } catch (err) {
    toastStore.erreur(messageErreur(err, 'Erreur lors de la confirmation du paiement.'))
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
    detailError.value = messageErreur(err, 'Erreur lors du chargement des détails.')
  } finally {
    detailLoading.value = false
  }
}

const closeDetail = () => {
  showDetail.value = false
  selectedCommande.value = null
}

onMounted(() => {
  fetchVendeurs()
  fetchStatsVendeur()
  fetchCommandes()
})
</script>
