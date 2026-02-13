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
              class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
              @input="handleSearch"
            />
          </div>
          <button
            class="md:hidden h-12 min-w-12 px-3 rounded-xl bg-gold-500 text-white flex items-center justify-center gap-2 shadow-sm"
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
        <!-- Filtres Sidebar (Desktop) -->
        <aside class="hidden md:block w-64 flex-shrink-0">
          <div class="sticky top-32">
            <FiltersSidebar
              v-model:selectedCategory="selectedCategory"
              v-model:priceRange="priceRange"
              v-model:showPromo="showPromo"
              v-model:showVedette="showVedette"
              :categories="categories"
              @apply="applyFilters"
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
              class="hidden md:block px-4 py-2 rounded-lg border border-gray-200 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
              @change="applyFilters"
            >
              <option value="recent">Plus récents</option>
              <option value="price_asc">Prix croissant</option>
              <option value="price_desc">Prix décroissant</option>
              <option value="popular">Populaires</option>
            </select>
          </div>

          <!-- Filtres actifs (chips) -->
          <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 mb-4">
            <div
              v-if="selectedCategory"
              class="badge badge-primary flex items-center gap-2"
            >
              {{ getCategoryName(selectedCategory) }}
              <X :size="14" class="cursor-pointer" @click="selectedCategory = null; applyFilters()" />
            </div>
            <div
              v-if="showPromo"
              class="badge badge-danger flex items-center gap-2"
            >
              En promotion
              <X :size="14" class="cursor-pointer" @click="showPromo = false; applyFilters()" />
            </div>
            <div
              v-if="showVedette"
              class="badge badge-success flex items-center gap-2"
            >
              Produits vedettes
              <X :size="14" class="cursor-pointer" @click="showVedette = false; applyFilters()" />
            </div>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-for="n in 8" :key="n" class="skeleton h-80 rounded-elegant"></div>
          </div>

          <!-- Produits -->
          <div v-else-if="produits.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
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
                  @click="changePage(page)"
                >
                  {{ page }}
                </button>
              </div>

              <button
                :disabled="currentPage === totalPages"
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
          <div class="flex items-center justify-between mb-6">
            <h2 class="font-display text-xl font-bold text-gray-800">Filtres</h2>
            <button @click="showFilters = false">
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
            @apply="applyFilters(); showFilters = false"
          />

          <div class="flex gap-3 mt-6">
            <Button variant="outline" full-width @click="resetFilters(); showFilters = false">
              Réinitialiser
            </Button>
            <Button variant="primary" full-width @click="applyFilters(); showFilters = false">
              Appliquer
            </Button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import ProduitCard from '@/components/produits/ProduitCard.vue'
import FiltersSidebar from '@/components/produits/FiltersSidebar.vue'
import Button from '@/components/common/Button.vue'
import { Search, SlidersHorizontal, X, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

// State
const produits = ref([])
const categories = ref([])
const loading = ref(false)
const showFilters = ref(false)

// Filtres
const searchQuery = ref('')
const selectedCategory = ref(null)
const priceRange = ref([0, 100000])
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

const hasActiveFilters = computed(() => {
  return selectedCategory.value || showPromo.value || showVedette.value
})
const activeFiltersCount = computed(() => {
  return [selectedCategory.value, showPromo.value, showVedette.value].filter(Boolean).length
})

// Méthodes
const fetchProduits = async () => {
  loading.value = true

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (searchQuery.value) params.search = searchQuery.value
    if (selectedCategory.value) params.categorie_id = selectedCategory.value
    if (showPromo.value) params.promotion = true
    if (showVedette.value) params.vedette = true

    // Sort
    if (sortBy.value === 'price_asc') {
      params.sort_by = 'prix'
      params.sort_order = 'asc'
    } else if (sortBy.value === 'price_desc') {
      params.sort_by = 'prix'
      params.sort_order = 'desc'
    } else if (sortBy.value === 'popular') {
      params.sort_by = 'nombre_commandes'
      params.sort_order = 'desc'
    }

    const response = await api.produits.getAll(params)

    if (response.data.success) {
      produits.value = response.data.data.data
      totalProduits.value = response.data.data.total
    }
  } catch (error) {
    console.error('Erreur chargement produits:', error)
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
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    applyFilters()
  }, 500)
}

const applyFilters = () => {
  currentPage.value = 1
  fetchProduits()
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedCategory.value = null
  priceRange.value = [0, 100000]
  showPromo.value = false
  showVedette.value = false
  sortBy.value = 'recent'
  applyFilters()
}

const changePage = (page) => {
  currentPage.value = page
  fetchProduits()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Lifecycle
onMounted(() => {
  // Récupérer les paramètres de l'URL
  if (route.query.categorie) {
    selectedCategory.value = parseInt(route.query.categorie)
  }

  fetchCategories()
  fetchProduits()
})
</script>
