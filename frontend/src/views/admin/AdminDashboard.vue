<!-- ===================================
ADMIN - TABLEAU DE BORD
File: src/views/admin/AdminDashboard.vue
=================================== -->
<!--
  Filtres (période, vendeur) sur une rangée au-dessus de tout ce qu'ils filtrent, gardés dans l'URL.
  Premier chargement : squelette calqué sur la grille ; actualisation : le contenu reste affiché,
  atténué (pas de saut). Tuiles homogènes (TuileStat), graphique des ventes (GraphiqueVentes),
  dernières commandes qui ouvrent leur détail dans la gestion des commandes.
-->
<template>
  <div class="container mx-auto max-w-6xl pb-6 pt-6 md:pt-8">
    <EnTetePage titre="Tableau de bord" :sous-titre="dashboardSubtitle">
      <template #actions>
        <!-- Les autres pages de gestion sont dans le menu « Administration » -->
        <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" :loading="loading" @click="fetchStats">
          Actualiser
        </Button>
        <Button to="/admin/commandes" variant="primary" size="sm">
          Gérer les commandes
        </Button>
      </template>
    </EnTetePage>

    <!-- Filtres -->
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end">
      <div class="flex flex-wrap gap-2" role="group" aria-label="Période">
        <button
          v-for="option in periodes"
          :key="option.value"
          type="button"
          class="puce"
          :aria-pressed="filtres.periode === option.value"
          @click="mettreAJour({ periode: option.value })"
        >
          {{ option.label }}
        </button>
      </div>

      <div class="w-full lg:ml-auto lg:w-72">
        <label for="filtre-vendeur-tableau" class="label">Vendeur</label>
        <select id="filtre-vendeur-tableau" v-model="filtres.vendeur_id" class="input" @change="mettreAJour()">
          <option value="">Tous les vendeurs</option>
          <option v-for="vendeur in vendeurs" :key="vendeur.id" :value="String(vendeur.id)">
            {{ vendeur.nom_complet }}
          </option>
        </select>
      </div>
    </div>

    <!-- Premier chargement : squelette calqué sur la grille réelle -->
    <div v-if="premierChargement" class="space-y-6" aria-busy="true">
      <span class="sr-only">Chargement du tableau de bord…</span>
      <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
        <div v-for="n in 10" :key="n" class="skeleton h-[6.5rem] rounded-elegant"></div>
      </div>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="skeleton h-[26rem] rounded-elegant lg:col-span-2"></div>
        <div class="skeleton h-[26rem] rounded-elegant"></div>
      </div>
      <div class="skeleton h-72 rounded-elegant"></div>
    </div>

    <AlertMessage v-else-if="error" type="error">
      {{ error }}
      <button type="button" class="lien ml-1" @click="fetchStats">Réessayer</button>
    </AlertMessage>

    <!-- Actualisation : le contenu garde sa place, atténué -->
    <div v-else class="space-y-6 transition-opacity duration-200" :class="{ 'opacity-60': loading }" :aria-busy="loading">
      <!-- Statistiques : un point d'attention (en attente, stock faible) est signalé -->
      <dl class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
        <TuileStat
          v-for="carte in statCards"
          :key="carte.key"
          :libelle="carte.label"
          :valeur="carte.format === 'currency' ? `${formatPrice(carte.value)} FCFA` : formatPrice(carte.value)"
          :aide="carte.helper"
          :alerte="Boolean(carte.alerte) && carte.value > 0"
        />
      </dl>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <Card padding="md" class="lg:col-span-2">
          <GraphiqueVentes :ventes="chartData" :actualisation="loading" />
        </Card>

        <Card padding="md">
          <section aria-labelledby="titre-top-produits">
            <h2 id="titre-top-produits" class="text-xl">Produits les plus commandés</h2>
            <p class="mt-0.5 text-sm text-gray-600">Les cinq premiers</p>

            <p v-if="topProduits.length === 0" class="mt-4 text-sm text-gray-600">
              Aucune commande sur la période.
            </p>

            <ol v-else class="mt-4 space-y-3">
              <li v-for="produit in topProduits" :key="produit.id" class="flex items-center gap-3">
                <img
                  loading="lazy"
                  :src="resolveImageUrl(produit.image_principale)"
                  alt=""
                  class="h-12 w-12 flex-shrink-0 rounded-xl bg-gray-100 object-cover"
                  @error="onImageError"
                />
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-semibold text-gray-900">{{ produit.nom }}</p>
                  <p class="text-xs text-gray-600">
                    {{ formatPrice(produit.nombre_commandes || 0) }} commande{{ (produit.nombre_commandes || 0) > 1 ? 's' : '' }}
                  </p>
                </div>
              </li>
            </ol>
          </section>
        </Card>
      </div>

      <!-- Dernières commandes : chacune ouvre son détail -->
      <Card padding="md">
        <section aria-labelledby="titre-dernieres-commandes">
          <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 id="titre-dernieres-commandes" class="text-xl">Dernières commandes</h2>
              <p class="mt-0.5 text-sm text-gray-600">Les dix plus récentes</p>
            </div>
            <router-link to="/admin/commandes" class="lien inline-flex min-h-11 items-center text-sm">
              Toutes les commandes
            </router-link>
          </div>

          <p v-if="dernieresCommandes.length === 0" class="text-sm text-gray-600">
            Aucune commande récente.
          </p>

          <ul v-else class="divide-y divide-gray-200">
            <li
              v-for="commande in dernieresCommandes"
              :key="commande.id"
              class="relative flex flex-col gap-2 py-3 transition-colors first:pt-0 last:pb-0 hover:bg-gray-50 md:flex-row md:items-center md:justify-between"
            >
              <div class="min-w-0">
                <router-link
                  :to="{ name: 'admin-commandes', query: { commande: commande.id } }"
                  class="font-semibold text-gray-900 after:absolute after:inset-0 after:content-[''] hover:underline"
                >
                  {{ commande.numero_commande }}
                </router-link>
                <p class="text-sm text-gray-600">
                  {{ commande.user?.nom_complet || 'Client' }}, le {{ formatDate(commande.created_at) }}
                </p>
              </div>
              <div class="flex flex-wrap items-center gap-2 md:justify-end">
                <BadgeStatut :statut="commande.statut" />
                <BadgeStatut :statut="commande.statut_paiement" type="paiement" />
                <span class="price ml-1 text-base">{{ formatPrice(commande.montant_total) }} FCFA</span>
              </div>
            </li>
          </ul>
        </section>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '@/services/api'
import Card from '@/components/common/Card.vue'
import BadgeStatut from '@/components/common/BadgeStatut.vue'
import Button from '@/components/common/Button.vue'
import EnTetePage from '@/components/common/EnTetePage.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import TuileStat from '@/components/common/TuileStat.vue'
import GraphiqueVentes from '@/components/admin/GraphiqueVentes.vue'
import { useFiltresUrl } from '@/composables/useFiltresUrl'
import { RefreshCw } from 'lucide-vue-next'
import { resolveImageUrl, onImageError } from '@/utils/images'
import { dateIso, formatDate, formatPrice, SEUIL_STOCK_FAIBLE } from '@/utils/format'

const loading = ref(false)
const premierChargement = ref(true)
const error = ref('')

const vendeurs = ref([])
const periodes = [
  { value: 'aujourd_hui', label: "Aujourd'hui" },
  { value: 'semaine', label: 'Semaine' },
  { value: 'mois', label: 'Mois' },
  { value: 'annee', label: 'Année' },
]

const stats = ref({
  total_commandes: 0,
  commandes_en_attente: 0,
  commandes_en_preparation: 0,
  commandes_livrees: 0,
  revenus_total: 0,
  revenus_aujourd_hui: 0,
  total_clients: 0,
  nouveaux_clients: 0,
  total_produits: 0,
  produits_stock_faible: 0,
})

const ventesParJour = ref([])
const topProduits = ref([])
const dernieresCommandes = ref([])

// Période et vendeur dans l'URL (?periode=semaine&vendeur_id=4)
const { filtres, mettreAJour } = useFiltresUrl({ periode: 'mois', vendeur_id: '' }, () => fetchStats())

const hasVendeurFilter = computed(() => Boolean(filtres.vendeur_id))
const selectedVendeur = computed(() => {
  return vendeurs.value.find((item) => String(item.id) === String(filtres.vendeur_id)) || null
})
const dashboardSubtitle = computed(() => {
  if (selectedVendeur.value) {
    return `Vue d'ensemble des ventes, clients et produits pour ${selectedVendeur.value.nom_complet}.`
  }
  return "Vue d'ensemble des ventes, clients et produits."
})

const statCards = computed(() => [
  { key: 'total_commandes', label: 'Commandes sur la période', value: stats.value.total_commandes },
  { key: 'commandes_en_attente', label: 'En attente', value: stats.value.commandes_en_attente, alerte: true },
  { key: 'commandes_en_preparation', label: 'En préparation', value: stats.value.commandes_en_preparation },
  { key: 'commandes_livrees', label: 'Livrées sur la période', value: stats.value.commandes_livrees },
  { key: 'revenus_total', label: 'Revenus sur la période', value: stats.value.revenus_total, format: 'currency' },
  { key: 'revenus_aujourd_hui', label: "Revenus aujourd'hui", value: stats.value.revenus_aujourd_hui, format: 'currency' },
  { key: 'total_clients', label: hasVendeurFilter.value ? 'Clients du vendeur' : 'Clients actifs', value: stats.value.total_clients },
  { key: 'nouveaux_clients', label: hasVendeurFilter.value ? 'Clients sur la période' : 'Nouveaux clients', value: stats.value.nouveaux_clients },
  { key: 'total_produits', label: 'Produits disponibles', value: stats.value.total_produits },
  { key: 'produits_stock_faible', label: 'Stock faible', value: stats.value.produits_stock_faible, helper: `${SEUIL_STOCK_FAIBLE} unités ou moins`, alerte: true },
])

// Les 7 derniers jours, jours sans vente compris (montant 0)
const chartData = computed(() => {
  const parJour = new Map((Array.isArray(ventesParJour.value) ? ventesParJour.value : []).map((item) => [
    item.date,
    { nombre: Number(item.nombre || 0), montant: Number(item.montant || 0) },
  ]))

  const jours = []
  for (let i = 6; i >= 0; i--) {
    const date = new Date()
    date.setDate(date.getDate() - i)
    // Date locale : toISOString donnerait la date UTC, décalée d'un jour en soirée
    const iso = dateIso(date)
    jours.push({ date: iso, nombre: parJour.get(iso)?.nombre || 0, montant: parJour.get(iso)?.montant || 0 })
  }
  return jours
})

const fetchStats = async () => {
  loading.value = true
  error.value = ''

  try {
    // Période inconnue dans l'URL : la valeur par défaut plutôt qu'un refus du serveur
    const periode = periodes.some((option) => option.value === filtres.periode) ? filtres.periode : 'mois'
    const params = { periode }
    if (filtres.vendeur_id) {
      params.vendeur_id = Number(filtres.vendeur_id)
    }

    const response = await api.admin.dashboard.stats(params)
    if (response.data.success) {
      stats.value = response.data.data.stats || stats.value
      ventesParJour.value = response.data.data.ventes_par_jour || []
      topProduits.value = response.data.data.top_produits || []
      dernieresCommandes.value = response.data.data.dernieres_commandes || []
      vendeurs.value = response.data.data.vendeurs || []
    } else {
      error.value = 'Impossible de charger les statistiques.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement du tableau de bord.'
  } finally {
    loading.value = false
    premierChargement.value = false
  }
}
</script>
