<!-- ===================================
PAGE PRODUITS - Liste complète avec filtres
File: src/views/Produits.vue
=================================== -->

<template>
  <div class="produits-page bg-cream min-h-screen pb-4">
    <!-- Header avec recherche -->
    <div class="bg-white sticky top-16 md:top-20 z-30 shadow-sm">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center gap-2">
          <div class="flex-1 relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" :size="20" />
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Rechercher un produit..."
              aria-label="Rechercher un produit"
              class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
              @input="handleSearch"
            />
          </div>
          <button
            class="md:hidden h-12 min-w-12 px-3 rounded-xl bg-gold-500 text-white flex items-center justify-center gap-2 shadow-sm"
            aria-label="Afficher les filtres"
            @click="showFilters = true"
          >
            <SlidersHorizontal :size="18" />
            <span v-if="activeFiltersCount > 0" class="text-xs font-bold">{{ activeFiltersCount }}</span>
          </button>
        </div>
      </div>
    </div>

    <div class="container mx-auto px-4 py-6">
      <div class="flex gap-6">
        <!-- Filtres Sidebar (Desktop) : appliqués dès qu'ils changent -->
        <aside class="hidden md:block w-64 flex-shrink-0">
          <div class="sticky top-32">
            <FiltersSidebar
              v-model:selectedCategory="selectedCategory"
              v-model:priceRange="priceRange"
              v-model:showPromo="showPromo"
              v-model:showVedette="showVedette"
              :categories="categories"
            />
          </div>
        </aside>

        <!-- Liste des produits -->
        <div class="flex-1">
          <!-- Header avec tri -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
                Nos Produits
              </h1>
              <p class="text-gray-600 text-sm mt-1">
                {{ totalProduits }} produit{{ totalProduits > 1 ? 's' : '' }} trouvé{{ totalProduits > 1 ? 's' : '' }}
              </p>
            </div>

            <!-- Tri -->
            <select
              v-model="sortBy"
              aria-label="Trier les produits"
              class="hidden md:block px-4 py-2 rounded-lg border border-gray-200 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
            >
              <option value="recent">Plus récents</option>
              <option value="price_asc">Prix croissant</option>
              <option value="price_desc">Prix décroissant</option>
              <option value="popular">Populaires</option>
            </select>
          </div>

          <!-- Filtres actifs (chips) -->
          <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 mb-4">
            <button
              v-if="searchQuery"
              class="badge badge-primary flex items-center gap-2"
              @click="searchQuery = ''; handleSearch()"
            >
              « {{ searchQuery }} »
              <X :size="14" />
            </button>
            <button
              v-if="selectedCategory"
              class="badge badge-primary flex items-center gap-2"
              @click="selectedCategory = null"
            >
              {{ getCategoryName(selectedCategory) }}
              <X :size="14" />
            </button>
            <button
              v-if="hasPriceFilter"
              class="badge badge-primary flex items-center gap-2"
              @click="priceRange = [null, null]"
            >
              {{ libellePrix }}
              <X :size="14" />
            </button>
            <button
              v-if="showPromo"
              class="badge badge-danger flex items-center gap-2"
              @click="showPromo = false"
            >
              En promotion
              <X :size="14" />
            </button>
            <button
              v-if="showVedette"
              class="badge badge-success flex items-center gap-2"
              @click="showVedette = false"
            >
              Produits vedettes
              <X :size="14" />
            </button>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-for="n in 8" :key="n" class="skeleton h-80 rounded-elegant"></div>
          </div>

          <!-- Produits -->
          <div v-else-if="produits.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <ProduitCard
              v-for="produit in produits"
              :key="produit.id"
              :produit="produit"
            />
          </div>

          <!-- Empty state -->
          <div v-else class="text-center py-16">
            <div class="text-6xl mb-4">🔍</div>
            <h3 class="font-display text-xl font-semibold text-gray-800 mb-2">
              Aucun produit trouvé
            </h3>
            <p class="text-gray-600 mb-6">
              Essayez de modifier vos filtres ou votre recherche
            </p>
            <Button variant="outline" @click="resetFilters">
              Réinitialiser les filtres
            </Button>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="mt-8 flex justify-center">
            <div class="flex items-center gap-2">
              <button
                :disabled="currentPage === 1"
                aria-label="Page précédente"
                class="px-4 py-2 rounded-lg border border-gray-200 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                @click="changePage(currentPage - 1)"
              >
                <ChevronLeft :size="20" />
              </button>

              <div class="flex gap-2">
                <button
                  v-for="page in displayedPages"
                  :key="page"
                  :class="[
                    'px-4 py-2 rounded-lg',
                    page === currentPage
                      ? 'bg-gold-500 text-white'
                      : 'border border-gray-200 hover:bg-gray-50'
                  ]"
                  :aria-current="page === currentPage ? 'page' : undefined"
                  @click="changePage(page)"
                >
                  {{ page }}
                </button>
              </div>

              <button
                :disabled="currentPage === totalPages"
                aria-label="Page suivante"
                class="px-4 py-2 rounded-lg border border-gray-200 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                @click="changePage(currentPage + 1)"
              >
                <ChevronRight :size="20" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Filtres (Mobile) -->
    <Teleport to="body">
      <div
        v-if="showFilters"
        class="fixed inset-0 bg-black/50 z-50 md:hidden"
        @click="showFilters = false"
      >
        <div
          class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl p-6 max-h-[80vh] overflow-y-auto animate-slideUp"
          @click.stop
        >
          <div class="mx-auto mb-4 h-1 w-12 rounded-full bg-gray-300"></div>
          <div class="flex items-center justify-between mb-6">
            <h2 class="font-display text-xl font-bold text-gray-800">Filtres</h2>
            <button aria-label="Fermer les filtres" @click="showFilters = false">
              <X :size="24" />
            </button>
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tri</label>
            <select
              v-model="sortBy"
              class="input"
            >
              <option value="recent">Plus récents</option>
              <option value="price_asc">Prix croissant</option>
              <option value="price_desc">Prix décroissant</option>
              <option value="popular">Populaires</option>
            </select>
          </div>

          <FiltersSidebar
            v-model:selectedCategory="selectedCategory"
            v-model:priceRange="priceRange"
            v-model:showPromo="showPromo"
            v-model:showVedette="showVedette"
            :categories="categories"
          />

          <div class="flex gap-3 mt-6">
            <Button variant="outline" full-width @click="resetFilters(); showFilters = false">
              Réinitialiser
            </Button>
            <Button variant="primary" full-width @click="showFilters = false">
              Voir {{ totalProduits }} résultat{{ totalProduits > 1 ? 's' : '' }}
            </Button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import ProduitCard from '@/components/produits/ProduitCard.vue'
import FiltersSidebar from '@/components/produits/FiltersSidebar.vue'
import Button from '@/components/common/Button.vue'
import { Search, SlidersHorizontal, X, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const toastStore = useToastStore()

const TRIS = {
  recent: { sort_by: 'created_at', sort_order: 'desc' },
  price_asc: { sort_by: 'prix', sort_order: 'asc' },
  price_desc: { sort_by: 'prix', sort_order: 'desc' },
  popular: { sort_by: 'nombre_commandes', sort_order: 'desc' },
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

const formatPrice = (price) => new Intl.NumberFormat('fr-FR').format(price)

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

onBeforeUnmount(() => clearTimeout(searchTimeout))
</script>
