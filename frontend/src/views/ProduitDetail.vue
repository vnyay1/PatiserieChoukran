<!-- ===================================
PAGE DÉTAIL PRODUIT
File: src/views/ProduitDetail.vue
=================================== -->
<!--
  Fil d'Ariane (orientation), galerie (image principale chargée en priorité : c'est l'image LCP),
  informations dans l'ordre de décision : nom, vendeur, prix, livraison, stock, quantité, ajout.
  Mobile : barre d'achat fixée au-dessus de la navigation, le contenu garde une marge pour elle.
-->
<template>
  <div :class="afficherBarreMobile ? 'pb-28 md:pb-6' : 'pb-6'">
    <!-- Chargement -->
    <div v-if="loading" class="container mx-auto py-6" aria-busy="true">
      <div class="skeleton mb-6 h-4 w-64"></div>
      <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="skeleton aspect-square rounded-elegant"></div>
        <div class="space-y-4">
          <div class="skeleton h-6 w-24 rounded-full"></div>
          <div class="skeleton h-10 w-3/4"></div>
          <div class="skeleton h-5 w-1/3"></div>
          <div class="skeleton h-10 w-1/2"></div>
          <div class="skeleton h-28 w-full"></div>
        </div>
      </div>
      <span class="sr-only">Chargement du produit…</span>
    </div>

    <!-- Contenu -->
    <article v-else-if="produit" class="container mx-auto py-5 md:py-8">
      <!-- Fil d'Ariane -->
      <nav aria-label="Fil d'Ariane" class="mb-5 text-sm">
        <ol class="flex flex-wrap items-center gap-1 text-gray-600">
          <li><router-link to="/" class="hover:text-gray-900 hover:underline">Accueil</router-link></li>
          <li aria-hidden="true"><ChevronRight :size="14" /></li>
          <li><router-link to="/produits" class="hover:text-gray-900 hover:underline">Nos produits</router-link></li>
          <template v-if="produit.categorie">
            <li aria-hidden="true"><ChevronRight :size="14" /></li>
            <li>
              <router-link :to="`/produits?categorie=${produit.categorie_id}`" class="hover:text-gray-900 hover:underline">
                {{ produit.categorie.nom }}
              </router-link>
            </li>
          </template>
          <li aria-hidden="true" class="hidden sm:block"><ChevronRight :size="14" /></li>
          <li class="hidden max-w-[16rem] truncate font-semibold text-gray-900 sm:block" aria-current="page">{{ produit.nom }}</li>
        </ol>
      </nav>

      <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
        <!-- Galerie -->
        <div>
          <div class="relative aspect-square overflow-hidden rounded-elegant border border-gray-200/70 bg-gray-100 shadow-card sm:aspect-[4/5] lg:aspect-square">
            <img
              :src="currentImage"
              :alt="produit.nom"
              class="h-full w-full object-cover"
              fetchpriority="high"
              @error="onImageError"
            />

            <div class="absolute inset-x-4 top-4 flex items-start justify-between gap-2">
              <span v-if="produit.createur?.est_vendeur_vedette" class="badge bg-gold-500 px-3 py-1 text-sm text-on-gold shadow-sm">
                <Star :size="14" fill="currentColor" aria-hidden="true" />
                Vendeur vedette
              </span>
              <span v-else></span>
              <span v-if="produit.prix_promo" class="badge bg-danger px-3 py-1 text-sm text-white shadow-sm">
                <span aria-hidden="true">−{{ reductionPercent }} %</span>
                <span class="sr-only">Promotion : moins {{ reductionPercent }} %</span>
              </span>
            </div>

            <div v-if="indisponible" class="absolute inset-0 flex items-center justify-center bg-gray-900/55">
              <span class="badge bg-surface px-4 py-2 text-base text-gray-900">Rupture de stock</span>
            </div>
          </div>

          <!-- Miniatures -->
          <ul v-if="allImages.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1 scrollbar-hide" aria-label="Autres photos">
            <li v-for="(image, index) in allImages" :key="image" class="flex-shrink-0">
              <button
                type="button"
                class="block h-20 w-20 overflow-hidden rounded-xl border-2 transition-colors"
                :class="currentImage === image ? 'border-gold-600' : 'border-transparent hover:border-gray-300'"
                :aria-label="`Photo ${index + 1} sur ${allImages.length}`"
                :aria-pressed="currentImage === image"
                @click="currentImage = image"
              >
                <img loading="lazy" :src="image" alt="" class="h-full w-full object-cover" @error="onImageError" />
              </button>
            </li>
          </ul>
        </div>

        <!-- Informations -->
        <div class="min-w-0">
          <router-link
            v-if="produit.categorie"
            :to="`/produits?categorie=${produit.categorie_id}`"
            class="badge badge-primary mb-3 hover:bg-gold-200"
          >
            {{ produit.categorie.nom }}
          </router-link>

          <h1 class="text-3xl md:text-4xl">{{ produit.nom }}</h1>

          <!-- Vendeur -->
          <router-link
            v-if="produit.createur?.nom_complet"
            :to="{ name: 'vendeur-profil', params: { id: produit.createur.id } }"
            class="group mt-3 inline-flex items-center gap-2.5 rounded-full py-1 pr-2 text-sm text-gray-600"
          >
            <img
              v-if="produit.createur.logo_boutique"
              loading="lazy"
              :src="resolveImageUrl(produit.createur.logo_boutique, { placeholder: false })"
              alt=""
              class="h-9 w-9 rounded-full border border-gray-200 bg-surface object-cover"
            />
            <span v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-gold-100 text-gold-700" aria-hidden="true">
              <Store :size="18" />
            </span>
            <span>
              Vendu par
              <span class="font-semibold text-gray-900 underline-offset-2 group-hover:underline">{{ produit.createur.nom_complet }}</span>
            </span>
          </router-link>

          <!-- Prix -->
          <p class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span class="sr-only">Prix :</span>
            <span class="price text-4xl">{{ formatPrice(prixUnitaire) }} FCFA</span>
            <span v-if="produit.prix_promo" class="price-old text-lg">
              <span class="sr-only">au lieu de</span>
              {{ formatPrice(produit.prix_unitaire) }} FCFA
            </span>
          </p>

          <!-- Livraison et stock -->
          <ul class="mt-5 space-y-2.5 text-sm">
            <li v-if="livraisonVendeur" class="flex items-start gap-2.5" :class="livrableIci ? 'text-gray-700' : 'text-orange-700'">
              <component :is="livrableIci ? Truck : AlertTriangle" :size="18" class="mt-px flex-shrink-0" aria-hidden="true" />
              <span>
                <template v-if="!livraisonVendeur.villes.length">Retrait en boutique uniquement</template>
                <template v-else-if="villeStore.ville && !livrableIci">
                  Pas de livraison à {{ villeStore.libelle }} (livré à {{ villesVendeur }}) : retrait en boutique possible
                </template>
                <template v-else>
                  Livré à {{ villesVendeur }}<template v-if="livraisonVendeur.minimum > 0">, dès {{ formatPrice(livraisonVendeur.minimum) }} FCFA d'achat chez ce vendeur</template>
                </template>
              </span>
            </li>
            <li class="flex items-center gap-2.5" :class="classeStock">
              <component :is="indisponible ? XCircle : CheckCircle2" :size="18" class="flex-shrink-0" aria-hidden="true" />
              <span class="font-medium">{{ libelleStock }}</span>
            </li>
          </ul>

          <!-- Achat (desktop et tablette) -->
          <div v-if="!isRestrictedRole" class="mt-7 rounded-2xl border border-gray-200 bg-surface p-4 shadow-card sm:p-5">
            <div class="flex flex-wrap items-center gap-4">
              <QuantiteStepper
                v-model="quantite"
                :max="Math.max(1, produit.stock_disponible || 1)"
                :disabled="indisponible"
                :nom-produit="produit.nom"
              />
              <p class="ml-auto text-right">
                <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Total</span>
                <span class="price text-2xl" aria-live="polite">{{ formatPrice(sousTotal) }} FCFA</span>
              </p>
            </div>
            <Button
              variant="primary"
              size="lg"
              full-width
              class="mt-4"
              :icon="indisponible ? null : ShoppingCart"
              :disabled="indisponible"
              :loading="addingToCart"
              @click="addToCart"
            >
              {{ indisponible ? 'Indisponible' : 'Ajouter au panier' }}
            </Button>
          </div>

          <!-- Description -->
          <section class="mt-8" aria-labelledby="titre-description">
            <h2 id="titre-description" class="text-xl">Description</h2>
            <p class="mt-2 whitespace-pre-line leading-relaxed text-gray-700">{{ produit.description }}</p>
          </section>

          <section v-if="produit.ingredients" class="mt-6" aria-labelledby="titre-ingredients">
            <h2 id="titre-ingredients" class="text-xl">Ingrédients</h2>
            <p class="mt-2 leading-relaxed text-gray-700">{{ produit.ingredients }}</p>
          </section>

          <AlertMessage v-if="produit.allergenes" type="warning" titre="Allergènes" class="mt-6">
            {{ produit.allergenes }}
          </AlertMessage>
        </div>
      </div>

      <!-- Produits similaires -->
      <section v-if="produitsSimilaires.length > 0" class="mt-14" aria-labelledby="titre-similaires">
        <h2 id="titre-similaires" class="mb-5">Vous aimerez aussi</h2>
        <ul class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-4">
          <li v-for="similaire in produitsSimilaires" :key="similaire.id">
            <ProduitCard :produit="similaire" />
          </li>
        </ul>
      </section>

      <!-- Barre d'achat mobile -->
      <div
        v-if="afficherBarreMobile"
        class="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-30 border-t border-gray-200 bg-surface/95 px-4 py-3 backdrop-blur-md md:hidden"
      >
        <div class="flex items-center gap-3">
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-600">{{ quantite }} × {{ formatPrice(prixUnitaire) }} FCFA</p>
            <p class="price truncate text-lg">{{ formatPrice(sousTotal) }} FCFA</p>
          </div>
          <Button
            variant="primary"
            :icon="ShoppingCart"
            :loading="addingToCart"
            @click="addToCart"
          >
            Ajouter
          </Button>
        </div>
      </div>
    </article>

    <!-- Introuvable -->
    <EmptyState
      v-else
      :icone="PackageOpen"
      niveau="h1"
      titre="Produit introuvable"
      texte="Ce produit n'existe pas ou n'est plus disponible."
    >
      <Button to="/produits" variant="primary">Voir tous les produits</Button>
    </EmptyState>
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
import AlertMessage from '@/components/common/AlertMessage.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import QuantiteStepper from '@/components/common/QuantiteStepper.vue'
import { resolveImageUrl, onImageError } from '@/utils/images'
import { formatPrice } from '@/utils/format'
import {
  Star, ShoppingCart, AlertTriangle, Truck, ChevronRight, Store, CheckCircle2, XCircle, PackageOpen,
} from 'lucide-vue-next'
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
const indisponible = computed(() => !produit.value?.est_disponible || produit.value?.stock_disponible === 0)
const afficherBarreMobile = computed(() => Boolean(produit.value) && !indisponible.value && !isRestrictedRole.value)

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

const prixUnitaire = computed(() => produit.value?.prix_promo || produit.value?.prix_unitaire || 0)
const sousTotal = computed(() => prixUnitaire.value * quantite.value)

// Stock : texte + icône + couleur (jamais la couleur seule)
const libelleStock = computed(() => {
  const stock = produit.value?.stock_disponible || 0
  if (indisponible.value) return 'Rupture de stock'
  if (stock <= 5) return `Plus que ${stock} en stock`
  return 'En stock'
})

const classeStock = computed(() => {
  if (indisponible.value) return 'text-red-700'
  if ((produit.value?.stock_disponible || 0) <= 5) return 'text-orange-700'
  return 'text-green-700'
})

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
      document.title = `${produit.value.nom} - Pâtisserie`

      // Produits similaires et conditions de livraison du vendeur en parallèle
      fetchProduitsSimilaires()
      fetchLivraisonVendeur(produit.value.createur?.id)
    }
  } catch (error) {
    produit.value = null
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

  // Visiteur : le panier est réservé aux clients connectés
  if (!authStore.isAuthenticated) {
    toastStore.info('Connectez-vous pour ajouter des produits à votre panier.')
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }

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
