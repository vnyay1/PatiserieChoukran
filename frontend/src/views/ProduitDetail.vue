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
          <div class="relative aspect-[4/5] rounded-elegant overflow-hidden bg-surface shadow-card mb-4">
            <img
              :src="currentImage"
              :alt="produit.nom"
              class="w-full h-full object-cover"
              loading="lazy"
              @error="onImageError"
            />

            <!-- Badge vedette -->
            <div
              v-if="produit.createur?.est_vendeur_vedette"
              class="absolute top-4 left-4 bg-gold-500 text-on-gold px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1"
              title="Vendeur en vedette"
            >
              <Star :size="16" fill="white" />
              Vedette
            </div>

            <!-- Badge promo -->
            <div
              v-if="produit.prix_promo"
              class="absolute top-4 right-4 bg-danger text-white px-3 py-1 rounded-full text-sm font-bold"
            >
              -{{ reductionPercent }}%
            </div>

            <!-- Badge rupture de stock -->
            <div
              v-if="!produit.est_disponible || produit.stock_disponible === 0"
              class="absolute inset-0 bg-black/50 flex items-center justify-center"
            >
              <span class="bg-danger text-white px-6 py-3 rounded-full font-semibold text-lg">
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
              :class="currentImage === image ? 'border-gold-600' : 'border-transparent'"
              @click="currentImage = image"
            >
              <img loading="lazy" :src="image" :alt="`${produit.nom} - ${index + 1}`" class="w-full h-full object-cover" @error="onImageError" />
            </button>
          </div>
          <div v-if="allImages.length > 1" class="sm:hidden flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            <button
              v-for="(image, index) in allImages"
              :key="index"
              class="min-w-[80px] h-20 rounded-lg overflow-hidden border-2 transition-all flex-shrink-0"
              :class="currentImage === image ? 'border-gold-600' : 'border-transparent'"
              @click="currentImage = image"
            >
              <img loading="lazy" :src="image" :alt="`${produit.nom} - ${index + 1}`" class="w-full h-full object-cover" @error="onImageError" />
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
          <h1 class="font-display text-3xl md:text-4xl font-bold text-gray-800 mb-2">
            {{ produit.nom }}
          </h1>

          <!-- Vendeur : il livre les villes qu'il a choisies, à partir de son minimum d'achat -->
          <router-link
            v-if="produit.createur?.nom_complet"
            :to="{ name: 'vendeur-profil', params: { id: produit.createur.id } }"
            class="inline-flex items-center gap-2 text-sm text-gray-600 mb-4 hover:text-gold-700"
          >
            <img
              v-if="produit.createur.logo_boutique"
              loading="lazy"
              :src="resolveImageUrl(produit.createur.logo_boutique, { placeholder: false })"
              :alt="produit.createur.nom_complet"
              class="h-8 w-8 rounded-full object-cover border bg-surface"
            />
            <span>Vendu par <span class="font-medium text-gray-800 underline-offset-2 hover:underline">{{ produit.createur.nom_complet }}</span></span>
          </router-link>

          <p v-if="livraisonVendeur" class="flex items-center gap-2 text-sm mb-4" :class="livrableIci ? 'text-gray-600' : 'text-orange-700'">
            <Truck :size="16" />
            <template v-if="!livraisonVendeur.villes.length">Retrait en boutique uniquement</template>
            <template v-else-if="villeStore.ville && !livrableIci">
              Pas de livraison à {{ villeStore.libelle }} (livré à {{ villesVendeur }}) : retrait en boutique possible
            </template>
            <template v-else>
              Livré à {{ villesVendeur }}{{ livraisonVendeur.minimum > 0 ? ` dès ${formatPrice(livraisonVendeur.minimum)} FCFA d'achat` : '' }}
            </template>
          </p>

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
                  class="touch-target flex items-center justify-center w-12 h-12 rounded-lg border-2 border-gray-200 hover:border-gold-600 disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="quantite--"
                >
                  <Minus :size="20" />
                </button>

                <input
                  v-model.number="quantite"
                  type="number"
                  min="1"
                  :max="produit.stock_disponible"
                  aria-label="Quantité"
                  class="w-20 text-center text-lg font-semibold border-2 border-gray-200 rounded-lg py-2 focus:border-gold-600 focus:ring-2 focus:ring-gold-200 outline-none"
                  @blur="normaliserQuantite"
                />

                <button
                  :disabled="quantite >= produit.stock_disponible"
                  class="touch-target flex items-center justify-center w-12 h-12 rounded-lg border-2 border-gray-200 hover:border-gold-600 disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="quantite++"
                >
                  <Plus :size="20" />
                </button>
              </div>
            </div>

            <!-- Bouton d'action -->
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
            v-for="similaire in produitsSimilaires"
            :key="similaire.id"
            :produit="similaire"
          />
        </div>
      </div>

      <!-- CTA sticky mobile -->
      <div
        v-if="produit && produit.est_disponible"
        class="md:hidden fixed left-0 right-0 bottom-16 z-40 px-4 safe-bottom"
      >
        <div class="bg-surface border border-gold-100 shadow-elegant rounded-2xl p-4 flex items-center gap-3">
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
import { useToastStore } from '@/stores/toast'
import { useVilleStore } from '@/stores/ville'
import api from '@/services/api'
import ProduitCard from '@/components/produits/ProduitCard.vue'
import Button from '@/components/common/Button.vue'
import { resolveImageUrl, onImageError } from '@/utils/images'
import { ArrowLeft, Star, ShoppingCart, Minus, Plus, AlertTriangle, Truck } from 'lucide-vue-next'
import { formatVille } from '@/utils/villes'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const panierStore = usePanierStore()
const toastStore = useToastStore()
const villeStore = useVilleStore()

// Villes et minimum du vendeur : prévient avant l'ajout au panier si la ville n'est pas livrée
const livraisonVendeur = ref(null)
const villesVendeur = computed(() => (livraisonVendeur.value?.villes || []).map(formatVille).join(' et '))
const livrableIci = computed(() => !villeStore.ville || Boolean(livraisonVendeur.value?.villes.includes(villeStore.ville)))

const fetchLivraisonVendeur = async (vendeurId) => {
  livraisonVendeur.value = null
  if (!vendeurId) return
  try {
    const response = await api.livraison.vendeur(vendeurId)
    const data = response.data?.data || {}
    livraisonVendeur.value = { villes: data.villes || [], minimum: Number(data.montant_minimum_livraison) || 0 }
  } catch (error) {
    console.error('Erreur chargement livraison du vendeur:', error)
  }
}

const produit = ref(null)
const produitsSimilaires = ref([])
const loading = ref(true)
const addingToCart = ref(false)
const quantite = ref(1)
const currentImage = ref('')
const isRestrictedRole = computed(() => authStore.isAdmin || authStore.isVendeur)

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
      
      // Produits similaires et conditions de livraison du vendeur en parallèle
      fetchProduitsSimilaires()
      fetchLivraisonVendeur(produit.value.createur?.id)
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

// Quantité toujours entière et comprise entre 1 et le stock disponible
const normaliserQuantite = () => {
  const stock = Math.max(1, Number(produit.value?.stock_disponible) || 1)
  const valeur = Math.floor(Number(quantite.value))
  quantite.value = Number.isFinite(valeur) ? Math.min(Math.max(valeur, 1), stock) : 1
}

const addToCart = async () => {
  if (isRestrictedRole.value) {
    return
  }

  // Visiteur : le panier est réservé aux clients connectés
  if (!authStore.isAuthenticated) {
    toastStore.info('Connectez-vous pour ajouter des produits à votre panier.')
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }

  normaliserQuantite()
  addingToCart.value = true
  const result = await panierStore.addItem(produit.value.id, quantite.value)
  addingToCart.value = false

  if (result.success) {
    toastStore.succes(`${quantite.value} × « ${produit.value.nom} » ajouté au panier.`)
    quantite.value = 1
  } else {
    toastStore.erreur(result.message || 'Impossible d\'ajouter ce produit au panier.')
  }
}

onMounted(() => {
  fetchProduit()
})

watch(() => villeStore.ville, () => {
  if (produit.value) fetchProduitsSimilaires()
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
