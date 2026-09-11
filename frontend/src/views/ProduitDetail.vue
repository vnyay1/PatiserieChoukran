<!-- ===================================
PAGE DÉTAIL PRODUIT
File: src/views/ProduitDetail.vue
=================================== -->

<template>
  <div class="produit-detail bg-cream min-h-screen pb-20">
    <!-- Loading -->
    <div v-if="loading" class="container mx-auto px-4 py-8">
      <div class="skeleton h-96 rounded-elegant mb-6"></div>
      <div class="skeleton h-8 w-3/4 mb-4"></div>
      <div class="skeleton h-6 w-1/2 mb-8"></div>
      <div class="skeleton h-32 mb-6"></div>
    </div>

    <!-- Contenu -->
    <div v-else-if="produit" class="container mx-auto px-4 py-6">
      <!-- Bouton retour -->
      <button
        class="flex items-center gap-2 text-gray-600 hover:text-gold-600 mb-4"
        @click="$router.back()"
      >
        <ArrowLeft :size="20" />
        <span>Retour</span>
      </button>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Galerie d'images -->
        <div>
          <!-- Image principale -->
          <div class="relative aspect-[4/5] rounded-elegant overflow-hidden bg-white shadow-card mb-4">
            <img
              :src="currentImage"
              :alt="produit.nom"
              class="w-full h-full object-cover"
              loading="lazy"
            />

            <!-- Badge vedette -->
            <div
              v-if="produit.est_vedette"
              class="absolute top-4 left-4 bg-gold-500 text-white px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1"
            >
              <Star :size="16" fill="white" />
              Vedette
            </div>

            <!-- Badge promo -->
            <div
              v-if="produit.prix_promo"
              class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold"
            >
              -{{ reductionPercent }}%
            </div>

            <!-- Badge rupture de stock -->
            <div
              v-if="!produit.est_disponible || produit.stock_disponible === 0"
              class="absolute inset-0 bg-black/50 flex items-center justify-center"
            >
              <span class="bg-red-500 text-white px-6 py-3 rounded-full font-semibold text-lg">
                Rupture de stock
              </span>
            </div>
          </div>

          <!-- Images secondaires -->
          <div v-if="allImages.length > 1" class="hidden sm:grid grid-cols-4 gap-2">
            <button
              v-for="(image, index) in allImages"
              :key="index"
              class="aspect-square rounded-lg overflow-hidden border-2 transition-all"
              :class="currentImage === image ? 'border-gold-500' : 'border-transparent'"
              @click="currentImage = image"
            >
              <img :src="image" :alt="`${produit.nom} - ${index + 1}`" class="w-full h-full object-cover" />
            </button>
          </div>
          <div v-if="allImages.length > 1" class="sm:hidden flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            <button
              v-for="(image, index) in allImages"
              :key="index"
              class="min-w-[80px] h-20 rounded-lg overflow-hidden border-2 transition-all flex-shrink-0"
              :class="currentImage === image ? 'border-gold-500' : 'border-transparent'"
              @click="currentImage = image"
            >
              <img :src="image" :alt="`${produit.nom} - ${index + 1}`" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>

        <!-- Informations -->
        <div>
          <!-- Catégorie -->
          <router-link
            :to="`/produits?categorie=${produit.categorie_id}`"
            class="inline-block badge badge-primary mb-3"
          >
            {{ produit.categorie?.nom }}
          </router-link>

          <!-- Nom -->
          <h1 class="font-display text-3xl md:text-4xl font-bold text-gray-800 mb-4">
            {{ produit.nom }}
          </h1>

          <!-- Prix -->
          <div class="flex items-baseline gap-3 mb-6">
            <span class="price text-4xl">
              {{ formatPrice(produit.prix_promo || produit.prix_unitaire) }} FCFA
            </span>
            <span v-if="produit.prix_promo" class="price-old text-xl">
              {{ formatPrice(produit.prix_unitaire) }} FCFA
            </span>
          </div>

          <!-- Stock -->
          <div class="flex items-center gap-2 mb-6">
            <div
              class="w-3 h-3 rounded-full"
              :class="produit.stock_disponible > 5 ? 'bg-green-500' : 'bg-orange-500'"
            ></div>
            <span class="text-sm text-gray-600">
              {{ produit.stock_disponible > 0 ? `${produit.stock_disponible} en stock` : 'Rupture de stock' }}
            </span>
          </div>

          <!-- Description -->
          <div class="mb-6">
            <h2 class="font-display text-xl font-semibold text-gray-800 mb-3">Description</h2>
            <p class="text-gray-600 leading-relaxed whitespace-pre-line">
              {{ produit.description }}
            </p>
          </div>

          <!-- Ingrédients (si disponibles) -->
          <div v-if="produit.ingredients" class="mb-6">
            <h2 class="font-display text-xl font-semibold text-gray-800 mb-3">Ingrédients</h2>
            <p class="text-gray-600">{{ produit.ingredients }}</p>
          </div>

          <!-- Allergènes (si disponibles) -->
          <div v-if="produit.allergenes" class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
            <div class="flex items-start gap-2">
              <AlertTriangle :size="20" class="text-orange-500 flex-shrink-0 mt-0.5" />
              <div>
                <h3 class="font-semibold text-orange-800 mb-1">Allergènes</h3>
                <p class="text-sm text-orange-700">{{ produit.allergenes }}</p>
              </div>
            </div>
          </div>

          <div class="divider-ornament"></div>

          <!-- Quantité et ajout au panier -->
          <div class="space-y-4">
            <!-- Quantité -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Quantité
              </label>
              <div class="flex items-center gap-3">
                <button
                  :disabled="quantite <= 1"
                  class="touch-target flex items-center justify-center w-12 h-12 rounded-lg border-2 border-gray-200 hover:border-gold-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="quantite--"
                >
                  <Minus :size="20" />
                </button>

                <input
                  v-model.number="quantite"
                  type="number"
                  min="1"
                  :max="produit.stock_disponible"
                  class="w-20 text-center text-lg font-semibold border-2 border-gray-200 rounded-lg py-2 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
                />

                <button
                  :disabled="quantite >= produit.stock_disponible"
                  class="touch-target flex items-center justify-center w-12 h-12 rounded-lg border-2 border-gray-200 hover:border-gold-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="quantite++"
                >
                  <Plus :size="20" />
                </button>
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex gap-3">
              <Button
                variant="primary"
                size="lg"
                full-width
                :disabled="isRestrictedRole || !produit.est_disponible || produit.stock_disponible === 0"
                :loading="addingToCart"
                @click="addToCart"
              >
                <ShoppingCart :size="20" />
                <span class="ml-2">Ajouter au panier</span>
              </Button>

              <button
                class="touch-target flex items-center justify-center w-14 h-14 rounded-full border-2 border-gold-500 text-gold-600 hover:bg-gold-50"
                :class="{ 'bg-gold-100': isFavorite }"
                @click="toggleFavorite"
              >
                <Heart :size="24" :fill="isFavorite ? 'currentColor' : 'none'" />
              </button>
            </div>

            <!-- Total -->
            <div class="bg-gold-50 border-2 border-gold-200 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <span class="text-gray-700 font-medium">Total</span>
                <span class="price text-2xl">
                  {{ formatPrice(sousTotal) }} FCFA
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Produits similaires -->
      <div v-if="produitsSimilaires.length > 0" class="mt-16">
        <h2 class="font-display text-2xl font-bold text-gold-600 mb-6">
          Produits similaires
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <ProduitCard
            v-for="produit in produitsSimilaires"
            :key="produit.id"
            :produit="produit"
          />
        </div>
      </div>

      <!-- CTA sticky mobile -->
      <div
        v-if="produit && produit.est_disponible"
        class="md:hidden fixed left-0 right-0 bottom-16 z-40 px-4 safe-bottom"
      >
        <div class="bg-white border border-gold-100 shadow-elegant rounded-2xl p-4 flex items-center gap-3">
          <div class="flex-1">
            <p class="text-xs text-gray-500">Total</p>
            <p class="font-display text-xl text-gold-700">{{ formatPrice(sousTotal) }} FCFA</p>
          </div>
          <Button
            variant="primary"
            size="md"
            :disabled="isRestrictedRole || !produit.est_disponible || produit.stock_disponible === 0"
            :loading="addingToCart"
            @click="addToCart"
          >
            Ajouter
          </Button>
        </div>
      </div>
    </div>

    <!-- Erreur -->
    <div v-else class="container mx-auto px-4 py-16 text-center">
      <div class="text-6xl mb-4">😕</div>
      <h2 class="font-display text-2xl font-bold text-gray-800 mb-2">
        Produit introuvable
      </h2>
      <p class="text-gray-600 mb-6">
        Ce produit n'existe pas ou n'est plus disponible
      </p>
      <Button variant="primary" @click="$router.push('/produits')">
        Retour aux produits
      </Button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import api from '@/services/api'
import ProduitCard from '@/components/produits/ProduitCard.vue'
import Button from '@/components/common/Button.vue'
import { ArrowLeft, Star, ShoppingCart, Heart, Minus, Plus, AlertTriangle } from 'lucide-vue-next'

const route = useRoute()
const authStore = useAuthStore()
const panierStore = usePanierStore()

const produit = ref(null)
const produitsSimilaires = ref([])
const loading = ref(true)
const addingToCart = ref(false)
const quantite = ref(1)
const currentImage = ref('')
const isFavorite = ref(false)
const isRestrictedRole = computed(() => authStore.isAdmin || authStore.isVendeur)

const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
const apiOrigin = (() => {
  try {
    return new URL(apiBase).origin
  } catch {
    return ''
  }
})()

const resolveImageUrl = (path, { placeholder = true } = {}) => {
  if (!path) return placeholder ? '/placeholder-product.jpg' : null
  if (path.startsWith('http') || path.startsWith('/')) return path
  return apiOrigin ? `${apiOrigin}/storage/${path}` : `/storage/${path}`
}

const allImages = computed(() => {
  if (!produit.value) return []
  const images = [produit.value.image_principale]
  if (produit.value.images_secondaires) {
    images.push(...produit.value.images_secondaires)
  }
  return images
    .filter(Boolean)
    .map((path) => resolveImageUrl(path, { placeholder: false }))
})

const reductionPercent = computed(() => {
  if (!produit.value?.prix_promo) return 0
  return Math.round(((produit.value.prix_unitaire - produit.value.prix_promo) / produit.value.prix_unitaire) * 100)
})

const sousTotal = computed(() => {
  const prix = produit.value?.prix_promo || produit.value?.prix_unitaire || 0
  return prix * quantite.value
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price)
}

const fetchProduit = async () => {
  loading.value = true
  produitsSimilaires.value = []
  quantite.value = 1
  currentImage.value = ''

  try {
    const response = await api.produits.getOne(route.params.slug)
    if (response.data.success) {
      produit.value = response.data.data
      const images = allImages.value
      currentImage.value = images[0] || resolveImageUrl(null)
      
      // Charger les produits similaires
      fetchProduitsSimilaires()
    }
  } catch (error) {
    console.error('Erreur chargement produit:', error)
  } finally {
    loading.value = false
  }
}

const fetchProduitsSimilaires = async () => {
  try {
    const response = await api.produits.getSimilar(route.params.slug)
    if (response.data.success) {
      produitsSimilaires.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur chargement produits similaires:', error)
  }
}

const addToCart = async () => {
  if (isRestrictedRole.value) {
    return
  }
  addingToCart.value = true

  const result = await panierStore.addItem(produit.value.id, quantite.value)

  if (result.success) {
    // TODO: Afficher toast de succès
    console.log('Produit ajouté au panier')
    quantite.value = 1
  } else {
    // TODO: Afficher toast d'erreur
    console.error(result.message)
  }

  addingToCart.value = false
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  // TODO: Implémenter l'API des favoris
}

onMounted(() => {
  fetchProduit()
})

watch(
  () => route.params.slug,
  (nextSlug, prevSlug) => {
    if (nextSlug && nextSlug !== prevSlug) {
      fetchProduit()
    }
  }
)
</script>
