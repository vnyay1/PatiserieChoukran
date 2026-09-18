<!-- ===================================
COMPOSANT PRODUIT CARD
File: src/components/produits/ProduitCard.vue
=================================== -->
<!--
  Carte-lien accessible : le nom du produit est un vrai lien étiré sur toute la carte
  (after:inset-0) ; « Vendu par » et « Ajouter » restent au-dessus (z-10), sans
  éléments interactifs imbriqués. Compacte sur mobile (grille de deux colonnes).
-->
<template>
  <Card
    hoverable
    padding="none"
    tag="article"
    class="flex h-full flex-col has-[.lien-etire:focus-visible]:outline has-[.lien-etire:focus-visible]:outline-2 has-[.lien-etire:focus-visible]:outline-offset-2 has-[.lien-etire:focus-visible]:outline-gold-600"
  >
    <!-- Image -->
    <div class="relative aspect-square overflow-hidden bg-gray-100">
      <img
        loading="lazy"
        decoding="async"
        :src="resolveImageUrl(produit.image_principale)"
        alt=""
        class="h-full w-full object-cover transition-transform duration-500 ease-douce motion-safe:group-hover:scale-105"
        @error="onImageError"
      />

      <div class="absolute inset-x-2 top-2 flex items-start justify-between gap-2">
        <!-- Vendeur mis en avant par l'admin -->
        <span v-if="produit.createur?.est_vendeur_vedette" class="badge bg-gold-500 text-on-gold shadow-sm">
          <Star :size="12" fill="currentColor" aria-hidden="true" />
          Vedette
        </span>
        <span v-else></span>

        <span v-if="produit.prix_promo" class="badge bg-danger text-white shadow-sm">
          <span aria-hidden="true">−{{ reductionPercent }} %</span>
          <span class="sr-only">Promotion : moins {{ reductionPercent }} %</span>
        </span>
      </div>

      <!-- Rupture de stock -->
      <div v-if="indisponible" class="absolute inset-0 flex items-center justify-center bg-gray-900/55">
        <span class="badge bg-surface px-3 py-1 text-sm text-gray-900">Rupture de stock</span>
      </div>
    </div>

    <!-- Contenu -->
    <div class="flex flex-1 flex-col p-3 sm:p-4">
      <h3 class="font-display text-base font-semibold leading-snug text-gray-900 sm:text-lg">
        <router-link
          :to="`/produits/${produit.slug}`"
          class="lien-etire line-clamp-2 after:absolute after:inset-0 after:content-[''] focus-visible:outline-none"
        >
          {{ produit.nom }}
        </router-link>
      </h3>

      <!-- Vendeur : le panier crée une commande par vendeur -->
      <p v-if="produit.createur?.nom_complet" class="mt-1 truncate text-xs text-gray-600 sm:text-sm">
        Vendu par
        <router-link
          :to="{ name: 'vendeur-profil', params: { id: produit.createur.id } }"
          class="relative z-10 font-semibold text-gold-700 underline-offset-2 hover:underline"
        >
          {{ produit.createur.nom_complet }}
        </router-link>
      </p>

      <!-- Prix -->
      <p class="mb-3 mt-auto flex flex-wrap items-baseline gap-x-2 pt-2">
        <span class="sr-only">Prix :</span>
        <span class="price text-lg sm:text-xl">{{ formatPrice(produit.prix_promo || produit.prix_unitaire) }} FCFA</span>
        <span v-if="produit.prix_promo" class="price-old">
          <span class="sr-only">au lieu de</span>
          {{ formatPrice(produit.prix_unitaire) }} FCFA
        </span>
      </p>

      <!-- Ajout au panier : masqué pour l'équipe (admin, vendeur) -->
      <Button
        v-if="!isRestrictedRole"
        variant="primary"
        size="sm"
        full-width
        class="relative z-10"
        :icon="indisponible ? null : ShoppingCart"
        :icon-size="16"
        :disabled="indisponible"
        :loading="addingToCart"
        :aria-label="indisponible ? `${produit.nom} : indisponible` : `Ajouter ${produit.nom} au panier`"
        @click="addToCart"
      >
        {{ indisponible ? 'Indisponible' : 'Ajouter' }}
      </Button>
    </div>
  </Card>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { ShoppingCart, Star } from 'lucide-vue-next'
import { resolveImageUrl, onImageError } from '@/utils/images'
import { formatPrice } from '@/utils/format'

const props = defineProps({
  produit: {
    type: Object,
    required: true
  }
})

const router = useRouter()
const authStore = useAuthStore()
const panierStore = usePanierStore()
const toastStore = useToastStore()
const addingToCart = ref(false)
const isRestrictedRole = computed(() => authStore.isAdmin || authStore.isVendeur)
const indisponible = computed(() => !props.produit.est_disponible || props.produit.stock_disponible === 0)

const reductionPercent = computed(() => {
  if (!props.produit.prix_promo) return 0
  const reduction = ((props.produit.prix_unitaire - props.produit.prix_promo) / props.produit.prix_unitaire) * 100
  return Math.round(reduction)
})

const addToCart = async () => {
  if (isRestrictedRole.value) {
    return
  }

  // Visiteur : le panier est réservé aux clients connectés
  if (!authStore.isAuthenticated) {
    toastStore.info('Connectez-vous pour ajouter des produits à votre panier.')
    router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
    return
  }

  addingToCart.value = true
  const result = await panierStore.addItem(props.produit.id, 1)
  addingToCart.value = false

  if (result.success) {
    toastStore.succes(`« ${props.produit.nom} » ajouté au panier.`)
  } else {
    toastStore.erreur(result.message || 'Impossible d\'ajouter ce produit au panier.')
  }
}
</script>
