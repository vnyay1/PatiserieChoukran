<!-- ===================================
COMPOSANT PRODUIT CARD
File: src/components/produits/ProduitCard.vue
=================================== -->

<template>
  <Card
    :hoverable="true"
    padding="none"
    clickable
    @click="goToDetail"
  >
    <!-- Image -->
    <div class="relative aspect-square overflow-hidden">
      <img
        loading="lazy"
        :src="resolveImageUrl(produit.image_principale)" :alt="produit.nom"
        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
        @error="onImageError"
      />

      <!-- Badge promo -->
      <div
        v-if="produit.prix_promo"
        class="absolute top-2 right-2 badge badge-danger font-bold"
      >
        -{{ reductionPercent }}%
      </div>

      <!-- Badge vedette : vendeur mis en avant par l'admin -->
      <div
        v-if="produit.createur?.est_vendeur_vedette"
        class="absolute top-2 left-2 bg-gold-500 text-on-gold px-2 py-1 rounded-full text-xs font-bold flex items-center"
        title="Vendeur en vedette"
      >
        <Star :size="12" class="mr-1" fill="white" />
        Vedette
      </div>

      <!-- Badge rupture de stock -->
      <div
        v-if="!produit.est_disponible || produit.stock_disponible === 0"
        class="absolute inset-0 bg-black/50 flex items-center justify-center"
      >
        <span class="bg-danger text-white px-4 py-2 rounded-full font-semibold">
          Rupture de stock
        </span>
      </div>
    </div>

    <!-- Contenu -->
    <div class="p-4">
      <!-- Nom -->
      <h3 class="font-display font-semibold text-lg text-gray-800 mb-1 line-clamp-2">
        {{ produit.nom }}
      </h3>

      <!-- Vendeur : le panier crée une commande par vendeur -->
      <p v-if="produit.createur?.nom_complet" class="text-xs text-gray-500 mb-2 truncate">
        Vendu par
        <router-link
          :to="{ name: 'vendeur-profil', params: { id: produit.createur.id } }"
          class="text-gold-700 hover:text-gold-800 hover:underline"
          @click.stop
        >
          {{ produit.createur.nom_complet }}
        </router-link>
      </p>

      <!-- Prix -->
      <div class="flex items-baseline space-x-2 mb-3">
        <span class="price text-2xl">
          {{ formatPrice(produit.prix_promo || produit.prix_unitaire) }} FCFA
        </span>
        <span v-if="produit.prix_promo" class="price-old">
          {{ formatPrice(produit.prix_unitaire) }} FCFA
        </span>
      </div>

      <!-- Bouton ajouter au panier -->
      <Button
        variant="primary"
        size="sm"
        full-width
        :disabled="isRestrictedRole || !produit.est_disponible || produit.stock_disponible === 0"
        :loading="addingToCart"
        @click.stop="addToCart"
      >
        <ShoppingCart :size="16" />
        <span class="ml-2">Ajouter</span>
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

const reductionPercent = computed(() => {
  if (!props.produit.prix_promo) return 0
  const reduction = ((props.produit.prix_unitaire - props.produit.prix_promo) / props.produit.prix_unitaire) * 100
  return Math.round(reduction)
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price)
}

const goToDetail = () => {
  router.push(`/produits/${props.produit.slug}`)
}

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
