<!-- ===================================
ADMIN - DASHBOARD
File: src/views/admin/AdminDashboard.vue
=================================== -->

<template>
  <div class="admin-dashboard-page pb-6">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            Dashboard Admin
          </h1>
          <p class="text-gray-600 text-sm">
            {{ dashboardSubtitle }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button variant="outline" size="sm" :loading="loading" @click="fetchStats">
            Actualiser
          </Button>
          <Button variant="secondary" size="sm" @click="$router.push('/admin/commandes')">
            Gérer les commandes
          </Button>
          <Button variant="secondary" size="sm" @click="$router.push('/admin/categories')">
            Gérer les catégories
          </Button>
          <Button variant="secondary" size="sm" @click="$router.push('/admin/parametres')">
            Paramètres du site
          </Button>
          <Button variant="primary" size="sm" @click="$router.push('/admin/produits')">
            Gérer les produits
          </Button>
          <Button variant="secondary" size="sm" @click="$router.push('/admin/users')">
            Gérer les utilisateurs
          </Button>
        </div>
      </div>

      <!-- Filtres -->
      <div class="flex flex-col xl:flex-row xl:items-end gap-4 mb-6">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="option in periodes"
            :key="option.value"
            class="px-4 py-2 rounded-full text-sm font-medium transition-colors"
            :class="periode === option.value ? 'bg-gold-500 text-on-gold' : 'bg-surface text-gray-700 hover:bg-gray-50'"
            @click="setPeriode(option.value)"
          >
            {{ option.label }}
          </button>
        </div>

        <div class="w-full xl:w-80">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Filtrer par vendeur
          </label>
          <select
            class="input"
            :value="selectedVendeurId"
            @change="setVendeur($event.target.value)"
          >
            <option value="">Tous les vendeurs</option>
            <option
              v-for="vendeur in vendeurs"
              :key="vendeur.id"
              :value="String(vendeur.id)"
            >
              {{ vendeur.nom_complet }}
            </option>
          </select>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
          <div v-for="n in 10" :key="n" class="skeleton h-24 rounded-elegant"></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="skeleton h-72 rounded-elegant lg:col-span-2"></div>
          <div class="skeleton h-72 rounded-elegant"></div>
        </div>
        <div class="skeleton h-96 rounded-elegant"></div>
      </div>

      <!-- Error -->
      <Card v-else-if="error" padding="md" class="border border-red-200 bg-red-50">
        <p class="text-red-600 text-sm">{{ error }}</p>
      </Card>

      <!-- Contenu -->
      <div v-else class="space-y-6">
        <!-- Statistiques -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
          <Card v-for="card in statCards" :key="card.key" padding="md">
            <div class="text-xs text-gray-500 mb-2">{{ card.label }}</div>
            <div class="text-2xl font-bold text-gray-800">
              <span v-if="card.format === 'currency'">
                {{ formatPrice(card.value) }} FCFA
              </span>
              <span v-else>
                {{ formatNumber(card.value) }}
              </span>
            </div>
            <div v-if="card.helper" class="text-xs text-gray-400 mt-1">
              {{ card.helper }}
            </div>
          </Card>
        </div>

        <!-- Ventes + Top produits -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <Card padding="md" class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-display text-xl font-bold text-gray-800">
                Ventes des 7 derniers jours
              </h2>
              <span class="text-xs text-gray-500">Payées uniquement</span>
            </div>

            <div v-if="chartData.length === 0" class="text-sm text-gray-500">
              Aucune donnée disponible.
            </div>

            <div v-else class="h-56 flex items-end gap-2">
              <div
                v-for="day in chartData"
                :key="day.date"
                class="flex-1 flex flex-col items-center gap-2"
              >
                <div class="w-full h-40 bg-gold-50 rounded-lg flex items-end overflow-hidden">
                  <div
                    class="w-full bg-gold-500 rounded-lg transition-all duration-300"
                    :style="{ height: `${getBarHeight(day.montant)}%` }"
                  ></div>
                </div>
                <div class="text-[11px] text-gray-600 text-center">
                  {{ day.label }}
                </div>
                <div class="text-[11px] text-gray-500">
                  {{ formatNumber(day.nombre) }} cmd
                </div>
              </div>
            </div>
          </Card>

          <Card padding="md">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-display text-xl font-bold text-gray-800">
                Top produits
              </h2>
              <span class="text-xs text-gray-500">Top 5</span>
            </div>

            <div v-if="topProduits.length === 0" class="text-sm text-gray-500">
              Aucun produit trouvé.
            </div>

            <div v-else class="space-y-3">
              <div v-for="produit in topProduits" :key="produit.id" class="flex items-center gap-3">
                <img
                  loading="lazy"
                  :src="resolveImageUrl(produit.image_principale)" :alt="produit.nom"
                  class="h-12 w-12 rounded-lg object-cover border"
                  @error="onImageError"
                />
                <div class="flex-1">
                  <div class="font-semibold text-gray-800 text-sm">{{ produit.nom }}</div>
                  <div class="text-xs text-gray-500">
                    {{ formatNumber(produit.nombre_commandes || 0) }} commande(s)
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Dernières commandes -->
        <Card padding="md">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-display text-xl font-bold text-gray-800">
              Dernières commandes
            </h2>
            <span class="text-xs text-gray-500">10 dernières</span>
          </div>

          <div v-if="dernieresCommandes.length === 0" class="text-sm text-gray-500">
            Aucune commande récente.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="commande in dernieresCommandes"
              :key="commande.id"
              class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 p-3 rounded-lg border border-gray-100 bg-surface"
            >
              <div>
                <div class="font-semibold text-gray-800">
                  {{ commande.numero_commande }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ commande.user?.nom_complet || 'Client' }} · {{ formatDateTime(commande.created_at) }}
                </div>
              </div>
              <div class="flex flex-col md:items-end gap-2">
                <div class="price text-lg">
                  {{ formatPrice(commande.montant_total) }} FCFA
                </div>
                <div class="flex flex-wrap gap-2">
                  <span class="badge" :class="getBadgeClass(commande.statut)">
                    {{ getStatutLabel(commande.statut) }}
                  </span>
                  <span class="badge" :class="getPaymentBadgeClass(commande.statut_paiement)">
                    {{ getPaymentLabel(commande.statut_paiement) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { resolveImageUrl, onImageError } from '@/utils/images'
import {
  dateIso,
  formatDate,
  formatPrice,
  formatPrice as formatNumber,
  libelleStatut as getStatutLabel,
  classeStatut as getBadgeClass,
  libellePaiement as getPaymentLabel,
  classePaiement as getPaymentBadgeClass,
} from '@/utils/format'

const loading = ref(false)
const error = ref('')

const periode = ref('mois')
const selectedVendeurId = ref('')
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

const hasVendeurFilter = computed(() => Boolean(selectedVendeurId.value))
const selectedVendeur = computed(() => {
  return vendeurs.value.find((item) => String(item.id) === String(selectedVendeurId.value)) || null
})
const dashboardSubtitle = computed(() => {
  if (selectedVendeur.value) {
    return `Vue d'ensemble des ventes, clients et produits pour ${selectedVendeur.value.nom_complet}.`
  }
  return "Vue d'ensemble des ventes, clients et produits."
})

const statCards = computed(() => [
  { key: 'total_commandes', label: 'Commandes (période)', value: stats.value.total_commandes },
  { key: 'commandes_en_attente', label: 'En attente', value: stats.value.commandes_en_attente },
  { key: 'commandes_en_preparation', label: 'En préparation', value: stats.value.commandes_en_preparation },
  { key: 'commandes_livrees', label: 'Livrées (période)', value: stats.value.commandes_livrees },
  { key: 'revenus_total', label: 'Revenus (période)', value: stats.value.revenus_total, format: 'currency' },
  { key: 'revenus_aujourd_hui', label: "Revenus aujourd'hui", value: stats.value.revenus_aujourd_hui, format: 'currency' },
  { key: 'total_clients', label: hasVendeurFilter.value ? 'Clients du vendeur' : 'Clients actifs', value: stats.value.total_clients },
  { key: 'nouveaux_clients', label: hasVendeurFilter.value ? 'Clients (période)' : 'Nouveaux clients', value: stats.value.nouveaux_clients },
  { key: 'total_produits', label: 'Produits disponibles', value: stats.value.total_produits },
  { key: 'produits_stock_faible', label: 'Stock faible', value: stats.value.produits_stock_faible, helper: '≤ 5 unités' },
])

const chartData = computed(() => {
  if (!Array.isArray(ventesParJour.value)) return []

  const map = new Map(
    ventesParJour.value.map((item) => [
      item.date,
      {
        nombre: Number(item.nombre || 0),
        montant: Number(item.montant || 0),
      },
    ])
  )

  const days = []
  for (let i = 6; i >= 0; i--) {
    const date = new Date()
    date.setDate(date.getDate() - i)
    // Date locale : toISOString donnerait la date UTC, décalée d'un jour en soirée
    const iso = dateIso(date)
    const data = map.get(iso)
    days.push({
      date: iso,
      label: formatDateShort(iso),
      nombre: data?.nombre || 0,
      montant: data?.montant || 0,
    })
  }

  return days
})

const maxMontant = computed(() => {
  if (chartData.value.length === 0) return 0
  return Math.max(...chartData.value.map((item) => item.montant))
})

const getBarHeight = (value) => {
  if (!maxMontant.value || value <= 0) return 0
  return Math.max(4, (value / maxMontant.value) * 100)
}

const formatDateShort = (date) => formatDate(date, { day: '2-digit', month: 'short' })
const formatDateTime = (date) => formatDate(date)

const setPeriode = (value) => {
  if (periode.value === value) return
  periode.value = value
  fetchStats()
}

const setVendeur = (value) => {
  const normalized = value ? String(value) : ''
  if (selectedVendeurId.value === normalized) return
  selectedVendeurId.value = normalized
  fetchStats()
}

const fetchStats = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = { periode: periode.value }
    if (selectedVendeurId.value) {
      params.vendeur_id = Number(selectedVendeurId.value)
    }

    const response = await api.admin.dashboard.stats(params)
    if (response.data.success) {
      stats.value = response.data.data.stats || stats.value
      ventesParJour.value = response.data.data.ventes_par_jour || []
      topProduits.value = response.data.data.top_produits || []
      dernieresCommandes.value = response.data.data.dernieres_commandes || []
      vendeurs.value = response.data.data.vendeurs || []

      const vendeurIdFromApi = response.data.data.selected_vendeur_id
      selectedVendeurId.value = vendeurIdFromApi ? String(vendeurIdFromApi) : ''
    } else {
      error.value = 'Impossible de charger les statistiques.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement du dashboard.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>
