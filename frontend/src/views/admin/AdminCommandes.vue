<!-- ===================================
ADMIN / VENDEUR - GESTION DES COMMANDES
File: src/views/admin/AdminCommandes.vue
=================================== -->
<!--
  Filtres et vue (en cours / historique) gardés dans l'URL ; ?commande=ID ouvre directement le
  détail (liens du tableau de bord). Liste dans TableauDonnees : cartes sur mobile, tableau dès md.
-->
<template>
  <div class="container mx-auto max-w-6xl pb-6 pt-6 md:pt-8">
    <EnTetePage :titre="isAdmin ? 'Gestion des commandes' : 'Mes commandes à traiter'">
      <template #sous-titre>
        {{ estHistorique
          ? 'Commandes terminées : annulées, ou livrées et payées.'
          : 'Faites avancer chaque commande et confirmez les paiements reçus.' }}
      </template>
      <template #actions>
        <div class="flex gap-1.5" role="group" aria-label="Commandes affichées">
          <button type="button" class="puce" :aria-pressed="!estHistorique" @click="mettreAJour({ historique: '', page: 1 })">
            En cours
          </button>
          <button type="button" class="puce" :aria-pressed="estHistorique" @click="mettreAJour({ historique: '1', page: 1 })">
            Historique
          </button>
        </div>
        <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" :loading="loading" @click="fetchCommandes">
          Actualiser
        </Button>
      </template>
    </EnTetePage>

    <!-- Repères du vendeur (l'admin dispose du tableau de bord) -->
    <dl v-if="!isAdmin && statsVendeur" class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
      <TuileStat
        v-for="carte in cartesStats"
        :key="carte.cle"
        :libelle="carte.label"
        :valeur="carte.valeur"
        :alerte="Boolean(carte.alerte)"
      />
    </dl>

    <Card padding="md" class="mb-6">
      <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-4">
        <ChampRecherche
          id="filtre-recherche"
          v-model="filtres.search"
          libelle="Recherche"
          libelle-visible
          placeholder="Numéro, client, téléphone…"
          class="md:col-span-2"
          @rechercher="mettreAJour({ page: 1 })"
        />
        <div>
          <label class="label" for="filtre-statut">Statut</label>
          <select id="filtre-statut" v-model="filtres.statut" class="input" @change="mettreAJour({ page: 1 })">
            <option value="">Tous</option>
            <option v-for="(infos, valeur) in STATUTS_COMMANDE" :key="valeur" :value="valeur">
              {{ infos.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="label" for="filtre-paiement">Paiement</label>
          <select id="filtre-paiement" v-model="filtres.statut_paiement" class="input" @change="mettreAJour({ page: 1 })">
            <option value="">Tous</option>
            <option v-for="(infos, valeur) in STATUTS_PAIEMENT" :key="valeur" :value="valeur">
              {{ infos.label }}
            </option>
          </select>
        </div>
        <div v-if="isAdmin" class="md:col-span-2">
          <label class="label" for="filtre-vendeur">Vendeur</label>
          <select id="filtre-vendeur" v-model="filtres.vendeur_id" class="input" @change="mettreAJour({ page: 1 })">
            <option value="">Tous les vendeurs</option>
            <option v-for="vendeur in vendeurs" :key="vendeur.id" :value="String(vendeur.id)">
              {{ vendeur.nom_complet }}
            </option>
          </select>
        </div>
        <div>
          <label class="label" for="filtre-debut">Du</label>
          <input id="filtre-debut" v-model="filtres.date_debut" type="date" class="input" @change="mettreAJour({ page: 1 })" />
        </div>
        <div>
          <label class="label" for="filtre-fin">Au</label>
          <input id="filtre-fin" v-model="filtres.date_fin" type="date" class="input" @change="mettreAJour({ page: 1 })" />
        </div>
        <div class="flex md:col-span-4 md:justify-end">
          <Button variant="outline" size="sm" :disabled="!filtresActifs" @click="reinitialiserFiltres">
            Réinitialiser les filtres
          </Button>
        </div>
      </div>
    </Card>

    <TableauDonnees
      libelle="Commandes"
      :chargement="loading"
      :erreur="error"
      :vide="commandes.length === 0"
      :icone="estHistorique ? Archive : CheckCheck"
      :titre-vide="filtresActifs ? 'Aucune commande trouvée' : estHistorique ? 'Aucune commande archivée' : 'Aucune commande à traiter'"
      :texte-vide="filtresActifs ? 'Aucune commande ne correspond à ces filtres.' : estHistorique ? '' : 'Tout est à jour.'"
      @reessayer="fetchCommandes"
    >
      <template #barre>
        <p class="text-sm text-gray-600">
          {{ totalCommandes }} commande{{ totalCommandes > 1 ? 's' : '' }}{{ estHistorique ? ` archivée${totalCommandes > 1 ? 's' : ''}` : '' }}
        </p>
      </template>

      <!-- Mobile : une carte par commande -->
      <template #cartes>
        <li v-for="commande in commandes" :key="commande.id" class="space-y-3 p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-semibold text-gray-900">{{ commande.numero_commande }}</p>
              <p class="text-sm text-gray-600">
                {{ formatDate(commande.created_at) }}, {{ commande.type_livraison === 'livraison' ? 'livraison' : 'retrait' }}
              </p>
            </div>
            <p class="price flex-shrink-0 text-base">{{ formatPrice(commande.montant_total) }} FCFA</p>
          </div>
          <div class="text-sm text-gray-700">
            {{ commande.user?.nom_complet || 'Client' }}
            <a v-if="commande.user?.telephone" :href="`tel:${commande.user.telephone}`" class="lien ml-1 whitespace-nowrap">
              {{ commande.user.telephone }}
            </a>
            <p v-if="isAdmin && commande.vendeur" class="text-sm text-gray-600">
              Vendeur : {{ commande.vendeur.nom_complet }}
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <BadgeStatut :statut="commande.statut" />
            <BadgeStatut v-if="commande.statut !== 'annulee'" :statut="commande.statut_paiement" type="paiement" />
          </div>
          <div class="flex flex-wrap gap-2">
            <select
              v-if="!isReadOnlyCommande(commande)"
              class="input min-w-[10rem] flex-1 py-2 text-sm"
              :disabled="updatingStatusId === commande.id"
              :value="commande.statut"
              :aria-label="`Changer le statut de ${commande.numero_commande}`"
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
              Détails<span class="sr-only"> de {{ commande.numero_commande }}</span>
            </Button>
          </div>
        </li>
      </template>

      <!-- Desktop : tableau -->
      <template #entete>
        <th scope="col" class="px-4 py-3 font-semibold">Commande</th>
        <th scope="col" class="px-4 py-3 font-semibold">Client</th>
        <th v-if="isAdmin" scope="col" class="px-4 py-3 font-semibold">Vendeur</th>
        <th scope="col" class="px-4 py-3 font-semibold">Statut</th>
        <th scope="col" class="px-4 py-3 font-semibold">Paiement</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Total</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Actions</th>
      </template>
      <template #lignes>
        <tr v-for="commande in commandes" :key="commande.id" class="align-top">
          <td class="px-4 py-3">
            <p class="font-semibold text-gray-900">{{ commande.numero_commande }}</p>
            <p class="text-xs text-gray-600">
              {{ formatDate(commande.created_at) }}, {{ commande.type_livraison === 'livraison' ? 'livraison' : 'retrait' }}
            </p>
          </td>
          <td class="px-4 py-3">
            <p class="font-semibold text-gray-900">{{ commande.user?.nom_complet || 'Client' }}</p>
            <p class="text-xs text-gray-600">{{ commande.user?.telephone }}</p>
          </td>
          <td v-if="isAdmin" class="px-4 py-3 text-gray-700">
            {{ commande.vendeur?.nom_complet || '—' }}
          </td>
          <td class="px-4 py-3">
            <div class="flex flex-col gap-2">
              <BadgeStatut :statut="commande.statut" class="self-start" />
              <select
                v-if="!isReadOnlyCommande(commande)"
                class="input w-auto min-w-[9rem] py-2 text-sm"
                :disabled="updatingStatusId === commande.id"
                :value="commande.statut"
                :aria-label="`Changer le statut de ${commande.numero_commande}`"
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
              <BadgeStatut
                v-if="commande.statut !== 'annulee'"
                :statut="commande.statut_paiement"
                type="paiement"
                class="self-start"
              />
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
          <td class="whitespace-nowrap px-4 py-3 text-right">
            <span class="price text-base">{{ formatPrice(commande.montant_total) }} FCFA</span>
          </td>
          <td class="px-4 py-3 text-right">
            <Button variant="outline" size="sm" @click="openDetail(commande)">
              Détails<span class="sr-only"> de {{ commande.numero_commande }}</span>
            </Button>
          </td>
        </tr>
      </template>

      <template v-if="totalPages > 1" #pied>
        <Pagination
          :page="filtres.page"
          :derniere="totalPages"
          :desactive="loading"
          libelle="Pages de commandes"
          @update:page="(page) => mettreAJour({ page })"
        />
      </template>
    </TableauDonnees>

    <!-- Détail commande -->
    <BaseModal
      :ouvert="showDetail"
      :titre="selectedCommande?.numero_commande ? `Commande ${selectedCommande.numero_commande}` : 'Détail de la commande'"
      variante="feuille"
      taille="xl"
      @fermer="closeDetail"
    >
      <div v-if="detailLoading" class="space-y-4" aria-busy="true">
        <div class="skeleton h-6 w-1/2"></div>
        <div class="skeleton h-24"></div>
        <div class="skeleton h-32"></div>
      </div>

      <AlertMessage v-else-if="detailError" type="error">{{ detailError }}</AlertMessage>

      <div v-else-if="selectedCommande" class="space-y-6">
        <!-- En-tête -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div class="text-sm text-gray-600">
            Passée le {{ formatDateHeure(selectedCommande.created_at) }}
            <span v-if="isAdmin && selectedCommande.vendeur"> · Vendeur : {{ selectedCommande.vendeur.nom_complet }}</span>
          </div>
          <div class="flex flex-wrap gap-2">
            <BadgeStatut :statut="selectedCommande.statut" />
            <!-- Une commande annulée n'affiche plus son statut de paiement -->
            <BadgeStatut v-if="selectedCommande.statut !== 'annulee'" :statut="selectedCommande.statut_paiement" type="paiement" />
          </div>
        </div>

        <!-- Facture générée automatiquement à la confirmation -->
        <div
          v-if="selectedCommande.facture"
          class="flex flex-wrap items-center justify-between gap-3 p-3 rounded-lg border border-gold-200 bg-gold-50"
        >
          <div class="text-sm text-gray-700">
            Facture <span class="font-semibold">{{ selectedCommande.facture.numero_facture }}</span>
            <span v-if="selectedCommande.facture.envoyee_le" class="text-gray-500">
              · envoyée au client le {{ formatDateHeure(selectedCommande.facture.envoyee_le) }}
            </span>
            <span v-else class="text-gray-500">· non envoyée (client sans e-mail)</span>
          </div>
          <Button variant="outline" size="sm" :icon="FileDown" :icon-size="16" :loading="telechargementFacture" @click="telechargerFacture">
            Télécharger
          </Button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Client & livraison -->
          <Card padding="md">
            <h3 class="mb-2 font-body text-sm font-semibold text-gray-600">Client</h3>
            <div class="text-sm text-gray-800 font-semibold">{{ selectedCommande.user?.nom_complet || 'Client' }}</div>
            <a
              v-if="selectedCommande.user?.telephone"
              :href="`tel:${selectedCommande.user.telephone}`"
              class="text-sm text-gold-600"
            >
              {{ selectedCommande.user.telephone }}
            </a>

            <h3 class="mb-2 mt-4 font-body text-sm font-semibold text-gray-600">
              {{ selectedCommande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait en boutique' }}
            </h3>
            <template v-if="selectedCommande.type_livraison === 'livraison'">
              <div v-if="selectedCommande.adresse_livraison" class="text-sm text-gray-700 space-y-1">
                <div>
                  <span v-if="selectedCommande.adresse_livraison.libelle" class="font-medium">
                    {{ selectedCommande.adresse_livraison.libelle }} —
                  </span>
                  <template v-if="selectedCommande.adresse_livraison.zone">{{ selectedCommande.adresse_livraison.zone }}, </template>
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
                <Phone :size="14" class="mr-1 inline" aria-hidden="true" />{{ selectedCommande.telephone_livraison }}
              </a>
            </template>
            <div v-if="selectedCommande.instructions_speciales" class="text-sm text-gray-700 mt-2 p-2 rounded-lg bg-gold-50">
              {{ selectedCommande.instructions_speciales }}
            </div>
          </Card>

          <!-- Paiement & montants -->
          <Card padding="md">
            <h3 class="mb-2 font-body text-sm font-semibold text-gray-600">Paiement</h3>
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

            <h3 class="mb-2 mt-4 font-body text-sm font-semibold text-gray-600">Montants</h3>
            <RecapMontants
              :produits="selectedCommande.montant_produits"
              :livraison="selectedCommande.montant_livraison"
              :total="selectedCommande.montant_total"
              :afficher-livraison="selectedCommande.type_livraison === 'livraison'"
            />
          </Card>
        </div>

        <!-- Articles -->
        <div class="space-y-3">
          <h3 class="font-body text-sm font-semibold text-gray-600">Articles</h3>
          <LignesCommande :lignes="selectedCommande.ligne_commandes || []" compact />
        </div>

        <!-- Historique -->
        <div v-if="selectedCommande.historiques?.length" class="space-y-2">
          <h3 class="font-body text-sm font-semibold text-gray-600">Historique</h3>
          <ChronologieCommande :historiques="selectedCommande.historiques" afficher-auteur />
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import { useFiltresUrl } from '@/composables/useFiltresUrl'
import { formatVille } from '@/utils/villes'
import api, { messageErreur, lireErreurBlob } from '@/services/api'
import Card from '@/components/common/Card.vue'
import BadgeStatut from '@/components/common/BadgeStatut.vue'
import LignesCommande from '@/components/commande/LignesCommande.vue'
import ChronologieCommande from '@/components/commande/ChronologieCommande.vue'
import RecapMontants from '@/components/commande/RecapMontants.vue'
import Button from '@/components/common/Button.vue'
import EnTetePage from '@/components/common/EnTetePage.vue'
import BaseModal from '@/components/common/BaseModal.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import TableauDonnees from '@/components/common/TableauDonnees.vue'
import Pagination from '@/components/common/Pagination.vue'
import ChampRecherche from '@/components/common/ChampRecherche.vue'
import TuileStat from '@/components/common/TuileStat.vue'
import { telechargerBlob } from '@/utils/telechargement'
import { FileDown, Phone, RefreshCw, Archive, CheckCheck } from 'lucide-vue-next'
import {
  STATUTS_COMMANDE,
  STATUTS_PAIEMENT,
  formatPrice,
  formatDate,
  formatDateHeure,
  libelleStatut,
  libelleMoyenPaiement,
} from '@/utils/format'

const PAR_PAGE = 15
const FILTRES_VIDES = { search: '', statut: '', statut_paiement: '', vendeur_id: '', date_debut: '', date_fin: '' }

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()
const { demander } = useConfirm()

const commandes = ref([])
const vendeurs = ref([])
const statsVendeur = ref(null)
const loading = ref(false)
const error = ref('')

const totalCommandes = ref(0)
const updatingStatusId = ref(null)
const confirmingPaymentId = ref(null)
const telechargementFacture = ref(false)

// Filtres, vue (historique=1) et page dans l'URL
const { filtres, mettreAJour } = useFiltresUrl({ ...FILTRES_VIDES, historique: '', page: 1 }, () => fetchCommandes())

const estHistorique = computed(() => filtres.historique === '1')
const filtresActifs = computed(() => Object.keys(FILTRES_VIDES).some((cle) => filtres[cle] !== ''))
const totalPages = computed(() => Math.max(1, Math.ceil(totalCommandes.value / PAR_PAGE)))
const isAdmin = computed(() => authStore.isAdmin)

const reinitialiserFiltres = () => mettreAJour({ ...FILTRES_VIDES, page: 1 })

const telechargerFacture = async () => {
  const commande = selectedCommande.value
  if (!commande?.facture) return

  telechargementFacture.value = true
  try {
    const response = await api.admin.commandes.facture(commande.id)
    telechargerBlob(response, `${commande.facture.numero_facture}.pdf`)
  } catch (err) {
    await lireErreurBlob(err)
    toastStore.erreur(messageErreur(err, 'Impossible de télécharger la facture.'))
  } finally {
    telechargementFacture.value = false
  }
}

const showDetail = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const selectedCommande = ref(null)

const notifyVendeurBadgeRefresh = () => {
  window.dispatchEvent(new CustomEvent('vendeur-commandes-updated'))
}

const isCommandeArchivee = (commande) => {
  return commande?.statut === 'annulee'
    || (commande?.statut === 'livree' && commande?.statut_paiement === 'paye')
}

const isReadOnlyCommande = (commande) => {
  return estHistorique.value || isCommandeArchivee(commande) || optionsStatut(commande).length <= 1
}

const peutConfirmerPaiement = (commande) => {
  return !estHistorique.value && commande.statut_paiement === 'en_attente' && commande.statut !== 'annulee'
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
    { cle: 'a_traiter', label: 'À traiter', valeur: stats.a_traiter ?? 0, alerte: (stats.a_traiter ?? 0) > 0 },
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
    const params = { page: filtres.page, per_page: PAR_PAGE }
    Object.keys(FILTRES_VIDES).forEach((cle) => {
      if (filtres[cle]) params[cle] = filtres[cle]
    })
    if (!isAdmin.value) delete params.vendeur_id
    if (estHistorique.value) params.historique = 1

    const response = await api.admin.commandes.getAll(params)
    if (response.data.success) {
      const pagination = response.data.data || {}
      commandes.value = pagination.data || []
      totalCommandes.value = Number(pagination.total || 0)

      // Page vidée (dernière commande archivée, lien trop loin) : retour à la dernière page remplie
      if (commandes.value.length === 0 && filtres.page > 1 && totalCommandes.value > 0) {
        mettreAJour({ page: Math.max(1, Number(pagination.last_page) || filtres.page - 1) })
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
  // Détail ouvert par un lien (?commande=ID) : le paramètre ne doit pas le rouvrir au retour
  if (route.query.commande) {
    const query = { ...route.query }
    delete query.commande
    router.replace({ query })
  }
}

onMounted(() => {
  fetchVendeurs()
  fetchStatsVendeur()
  // Lien du tableau de bord : /admin/commandes?commande=12
  const idDemande = Number.parseInt(route.query.commande, 10)
  if (Number.isFinite(idDemande) && idDemande > 0) {
    openDetail({ id: idDemande })
  }
})
</script>
