<!-- ===================================
PAGE PRODUITS - Liste complète avec filtres
File: src/views/Produits.vue
=================================== -->
<!--
  Recherche collante sous l'en-tête, filtres en colonne (desktop) ou en tiroir (mobile),
  nombre de résultats annoncé aux lecteurs d'écran, puces de filtres actifs retirables,
  pagination en <nav> avec cibles de 44 px. L'URL reflète tous les filtres.
-->
<template>
  <div class="pb-6">
    <!-- Recherche -->
    <div class="sticky top-14 z-30 border-b border-gray-200 bg-surface/95 backdrop-blur-md md:top-[4.5rem]">
      <div class="container mx-auto flex items-center gap-2 py-3">
        <form class="relative flex-1" role="search" @submit.prevent="mettreAJourUrl(1)">
          <label for="recherche-produits" class="sr-only">Rechercher un produit</label>
          <Search class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500" :size="20" aria-hidden="true" />
          <input
            id="recherche-produits"
            v-model="searchQuery"
            type="search"
            enterkeyhint="search"
            placeholder="Gâteau, glace, croissant…"
            class="input rounded-full pl-11"
            @input="handleSearch"
          />
        </form>
        <button
          type="button"
          class="btn-outline relative flex-shrink-0 px-4 md:hidden"
          :aria-label="libelleBoutonFiltres"
          @click="showFilters = true"
        >
          <SlidersHorizontal :size="18" aria-hidden="true" />
          <span aria-hidden="true">Filtres</span>
          <span v-if="activeFiltersCount > 0" class="badge-compteur ring-0" aria-hidden="true">{{ activeFiltersCount }}</span>
        </button>
      </div>
    </div>

    <div class="container mx-auto py-6">
      <div class="flex gap-8">
        <!-- Filtres (desktop) : appliqués dès qu'ils changent -->
        <aside class="hidden w-64 flex-shrink-0 md:block" aria-label="Filtres">
          <div class="sticky top-40 max-h-[calc(100dvh-11rem)] overflow-y-auto pb-4 pr-1">
            <FiltersSidebar
              v-model:selected-category="selectedCategory"
              v-model:price-range="priceRange"
              v-model:show-promo="showPromo"
              v-model:show-vedette="showVedette"
              :categories="categories"
            />
            <button v-if="hasActiveFilters" type="button" class="btn-ghost btn-sm mt-4 w-full" @click="resetFilters">
              Effacer tous les filtres
            </button>
          </div>
        </aside>

        <!-- Liste des produits -->
        <div class="min-w-0 flex-1">
          <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
              <h1>Nos produits</h1>
              <p class="mt-1 text-sm text-gray-600" aria-live="polite" aria-atomic="true">
                <template v-if="loading">Recherche en cours…</template>
                <template v-else>
                  {{ totalProduits }} produit{{ totalProduits > 1 ? 's' : '' }}
                  <template v-if="villeStore.ville"> livrable{{ totalProduits > 1 ? 's' : '' }} à {{ villeStore.libelle }}</template>
                </template>
              </p>
            </div>

            <!-- Tri (desktop) -->
            <div class="hidden items-center gap-2 md:flex">
              <label for="tri-produits" class="text-sm font-semibold text-gray-700">Trier par</label>
              <select id="tri-produits" v-model="sortBy" class="input w-auto min-w-[11rem] py-2">
                <option v-for="(libelle, valeur) in LIBELLES_TRI" :key="valeur" :value="valeur">{{ libelle }}</option>
              </select>
            </div>
          </div>

          <!-- Filtres actifs -->
          <ul v-if="hasActiveFilters" class="mb-5 flex flex-wrap items-center gap-2" aria-label="Filtres actifs">
            <li v-for="puce in pucesActives" :key="puce.cle">
              <button
                type="button"
                class="inline-flex min-h-9 items-center gap-1.5 rounded-full border border-gold-200 bg-gold-50 py-1 pl-3 pr-2 text-sm font-semibold text-gold-800 transition-colors hover:border-gold-400 hover:bg-gold-100"
                :aria-label="`Retirer le filtre : ${puce.libelle}`"
                @click="puce.retirer()"
              >
                {{ puce.libelle }}
                <X :size="15" aria-hidden="true" />
              </button>
            </li>
            <li>
              <button type="button" class="lien text-sm" @click="resetFilters">Tout effacer</button>
            </li>
          </ul>

          <!-- Chargement -->
          <ul v-if="loading" class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4" aria-hidden="true">
            <li v-for="n in 8" :key="n">
              <div class="skeleton aspect-square rounded-elegant"></div>
              <div class="skeleton mt-3 h-4 w-3/4"></div>
              <div class="skeleton mt-2 h-4 w-1/2"></div>
            </li>
          </ul>

          <!-- Produits -->
          <ul v-else-if="produits.length > 0" class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">
            <li v-for="produit in produits" :key="produit.id">
              <ProduitCard :produit="produit" />
            </li>
          </ul>

          <!-- Aucun résultat -->
          <EmptyState
            v-else
            :icone="SearchX"
            titre="Aucun produit trouvé"
            :texte="hasActiveFilters ? 'Essayez un autre mot-clé ou retirez un filtre.' : 'Aucun produit n’est disponible pour le moment.'"
          >
            <Button v-if="hasActiveFilters" variant="outline" @click="resetFilters">Effacer les filtres</Button>
          </EmptyState>

          <!-- Pagination -->
          <nav v-if="totalPages > 1" class="mt-10 flex justify-center" aria-label="Pagination">
            <ul class="flex flex-wrap items-center justify-center gap-1.5">
              <li>
                <button
                  type="button"
                  class="page-btn"
                  :disabled="currentPage === 1"
                  aria-label="Page précédente"
                  @click="changePage(currentPage - 1)"
                >
                  <ChevronLeft :size="20" aria-hidden="true" />
                </button>
              </li>
              <li v-for="page in displayedPages" :key="page">
                <button
                  type="button"
                  class="page-btn"
                  :aria-current="page === currentPage ? 'page' : undefined"
                  :aria-label="`Page ${page}`"
                  @click="changePage(page)"
                >
                  {{ page }}
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="page-btn"
                  :disabled="currentPage === totalPages"
                  aria-label="Page suivante"
                  @click="changePage(currentPage + 1)"
                >
                  <ChevronRight :size="20" aria-hidden="true" />
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Filtres (mobile) -->
    <BaseModal
      :ouvert="showFilters"
      titre="Filtres et tri"
      variante="feuille"
      @fermer="showFilters = false"
    >
      <div class="mb-7">
        <label for="tri-produits-mobile" class="titre-tri">Trier par</label>
        <select id="tri-produits-mobile" v-model="sortBy" class="input">
          <option v-for="(libelle, valeur) in LIBELLES_TRI" :key="valeur" :value="valeur">{{ libelle }}</option>
        </select>
      </div>

      <FiltersSidebar
        v-model:selected-category="selectedCategory"
        v-model:price-range="priceRange"
        v-model:show-promo="showPromo"
        v-model:show-vedette="showVedette"
        :categories="categories"
      />

      <template #actions>
        <Button variant="outline" :disabled="!hasActiveFilters" @click="resetFilters">
          Effacer
        </Button>
        <Button variant="primary" class="sm:min-w-[12rem]" :loading="loading" @click="showFilters = false">
          Voir {{ totalProduits }} résultat{{ totalProduits > 1 ? 's' : '' }}
        </Button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useVilleStore } from '@/stores/ville'
import ProduitCard from '@/components/produits/ProduitCard.vue'
import FiltersSidebar from '@/components/produits/FiltersSidebar.vue'
import Button from '@/components/common/Button.vue'
import BaseModal from '@/components/common/BaseModal.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import { formatPrice } from '@/utils/format'
import { Search, SlidersHorizontal, X, ChevronLeft, ChevronRight, SearchX } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const toastStore = useToastStore()
const villeStore = useVilleStore()

const TRIS = {
  recent: { sort_by: 'created_at', sort_order: 'desc' },
  price_asc: { sort_by: 'prix', sort_order: 'asc' },
  price_desc: { sort_by: 'prix', sort_order: 'desc' },
  popular: { sort_by: 'nombre_commandes', sort_order: 'desc' },
}

const LIBELLES_TRI = {
  recent: 'Plus récents',
  price_asc: 'Prix croissant',
  price_desc: 'Prix décroissant',
  popular: 'Les plus commandés',
}

// State
const produits = ref([])
const categories = ref([])
const loading = ref(false)
const showFilters = ref(false)

// Filtres (reflétés dans l'URL : partage, rafraîchissement et bouton retour conservent la recherche)
const searchQuery = ref('')
const selectedCategory = ref(null)
const priceRange = ref([null, null])
const showPromo = ref(false)
const showVedette = ref(false)
const sortBy = ref('recent')

// Pagination
const currentPage = ref(1)
const perPage = ref(12)
const totalProduits = ref(0)
const totalPages = computed(() => Math.ceil(totalProduits.value / perPage.value))

const displayedPages = computed(() => {
  const pages = []
  const maxPages = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxPages / 2))
  let end = Math.min(totalPages.value, start + maxPages - 1)

  if (end - start < maxPages - 1) {
    start = Math.max(1, end - maxPages + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const hasPriceFilter = computed(() => priceRange.value[0] != null || priceRange.value[1] != null)
const hasActiveFilters = computed(() => {
  return Boolean(searchQuery.value || selectedCategory.value || showPromo.value || showVedette.value || hasPriceFilter.value)
})
const activeFiltersCount = computed(() => {
  return [selectedCategory.value, showPromo.value, showVedette.value, hasPriceFilter.value].filter(Boolean).length
})

const libellePrix = computed(() => {
  const [min, max] = priceRange.value
  if (min != null && max != null) return `${formatPrice(min)} - ${formatPrice(max)} FCFA`
  if (min != null) return `Dès ${formatPrice(min)} FCFA`
  return `Jusqu'à ${formatPrice(max)} FCFA`
})

// ---------- Synchronisation avec l'URL ----------

let lectureUrlEnCours = false

const lireQuery = async () => {
  lectureUrlEnCours = true
  const q = route.query
  searchQuery.value = typeof q.q === 'string' ? q.q : ''
  selectedCategory.value = Number(q.categorie) > 0 ? Number(q.categorie) : null
  showPromo.value = q.promo === '1'
  showVedette.value = q.vedette === '1'
  sortBy.value = TRIS[q.tri] ? q.tri : 'recent'
  priceRange.value = [
    q.prix_min !== undefined && Number(q.prix_min) >= 0 ? Number(q.prix_min) : null,
    q.prix_max !== undefined && Number(q.prix_max) >= 0 ? Number(q.prix_max) : null,
  ]
  currentPage.value = Number(q.page) > 0 ? Number(q.page) : 1
  // Les watchers déclenchés par ces affectations s'exécutent avant ce nextTick : ils sont ignorés
  await nextTick()
  lectureUrlEnCours = false
}

const construireQuery = () => {
  const query = {}
  if (searchQuery.value) query.q = searchQuery.value
  if (selectedCategory.value) query.categorie = String(selectedCategory.value)
  if (showPromo.value) query.promo = '1'
  if (showVedette.value) query.vedette = '1'
  if (sortBy.value !== 'recent') query.tri = sortBy.value
  if (priceRange.value[0] != null) query.prix_min = String(priceRange.value[0])
  if (priceRange.value[1] != null) query.prix_max = String(priceRange.value[1])
  if (currentPage.value > 1) query.page = String(currentPage.value)
  return query
}

const memeQuery = (a, b) => {
  const cles = new Set([...Object.keys(a), ...Object.keys(b)])
  return [...cles].every((cle) => String(a[cle] ?? '') === String(b[cle] ?? ''))
}

const mettreAJourUrl = (page = 1) => {
  currentPage.value = page
  const query = construireQuery()
  if (!memeQuery(query, route.query)) {
    router.push({ query })
  }
}

// ---------- Chargement ----------

const fetchProduits = async () => {
  loading.value = true

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      ...TRIS[sortBy.value],
    }

    if (searchQuery.value) params.search = searchQuery.value
    if (selectedCategory.value) params.categorie_id = selectedCategory.value
    if (showPromo.value) params.promotion = 1
    if (showVedette.value) params.vedette = 1
    if (priceRange.value[0] != null) params.prix_min = priceRange.value[0]
    if (priceRange.value[1] != null) params.prix_max = priceRange.value[1]

    const response = await api.produits.getAll(params)

    if (response.data.success) {
      produits.value = response.data.data.data
      totalProduits.value = response.data.data.total
    }
  } catch (error) {
    produits.value = []
    totalProduits.value = 0
    toastStore.erreur(messageErreur(error, 'Impossible de charger les produits.'))
  } finally {
    loading.value = false
  }
}

const fetchCategories = async () => {
  try {
    const response = await api.categories.getAll()
    if (response.data.success) {
      categories.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur chargement catégories:', error)
  }
}

const libelleBoutonFiltres = computed(() => {
  const nombre = activeFiltersCount.value
  return nombre > 0 ? `Filtres et tri, ${nombre} actif${nombre > 1 ? 's' : ''}` : 'Filtres et tri'
})

// Puces des filtres actifs, chacune retirable d'un clic
const pucesActives = computed(() => {
  const puces = []
  if (searchQuery.value) {
    puces.push({ cle: 'q', libelle: `« ${searchQuery.value} »`, retirer: () => { searchQuery.value = ''; mettreAJourUrl(1) } })
  }
  if (selectedCategory.value) {
    puces.push({ cle: 'categorie', libelle: getCategoryName(selectedCategory.value) || 'Catégorie', retirer: () => { selectedCategory.value = null } })
  }
  if (hasPriceFilter.value) {
    puces.push({ cle: 'prix', libelle: libellePrix.value, retirer: () => { priceRange.value = [null, null] } })
  }
  if (showPromo.value) {
    puces.push({ cle: 'promo', libelle: 'En promotion', retirer: () => { showPromo.value = false } })
  }
  if (showVedette.value) {
    puces.push({ cle: 'vedette', libelle: 'Vendeurs vedettes', retirer: () => { showVedette.value = false } })
  }
  return puces
})

const getCategoryName = (id) => {
  const cat = categories.value.find(c => c.id === id)
  return cat ? cat.nom : ''
}

let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => mettreAJourUrl(1), 400)
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedCategory.value = null
  priceRange.value = [null, null]
  showPromo.value = false
  showVedette.value = false
  sortBy.value = 'recent'
  mettreAJourUrl(1)
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  mettreAJourUrl(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Tout changement de filtre repart de la page 1
watch(
  [selectedCategory, showPromo, showVedette, sortBy, () => priceRange.value.join('|')],
  () => {
    if (!lectureUrlEnCours) mettreAJourUrl(1)
  }
)

// L'URL fait foi : chaque changement (filtre, page, retour arrière, lien) recharge la liste
watch(
  () => route.query,
  async () => {
    // Pendant la transition vers une autre page, la query change aussi : on l'ignore
    if (route.name !== 'produits') return
    await lireQuery()
    fetchProduits()
  },
  { immediate: true }
)

fetchCategories()

// Nouvelle ville : liste et compteurs des catégories rechargés, retour à la page 1
watch(() => villeStore.ville, () => {
  fetchCategories()
  if (currentPage.value === 1) fetchProduits()
  else mettreAJourUrl(1)
})

onBeforeUnmount(() => clearTimeout(searchTimeout))
</script>

<style scoped>
.titre-tri {
  @apply mb-3 block font-body text-sm font-bold uppercase tracking-wider text-gray-900;
}

.page-btn {
  @apply inline-flex h-11 min-w-11 items-center justify-center rounded-full border border-gray-300 bg-surface px-3 font-semibold tabular-nums text-gray-800
         transition-colors hover:border-gray-400 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-45;
}

.page-btn[aria-current='page'] {
  @apply border-gold-500 bg-gold-500 text-on-gold hover:bg-gold-500;
}
</style>
