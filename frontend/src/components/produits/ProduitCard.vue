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
        :src="resolveImageUrl(produit.image_principale)"
        :alt="produit.nom"
        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
      />

      <!-- Badge promo -->
      <div
        v-if="produit.prix_promo"
        class="absolute top-2 right-2 badge badge-danger font-bold"
      >
        -{{ reductionPercent }}%
      </div>

      <!-- Badge vedette -->
      <div
        v-if="produit.est_vedette"
        class="absolute top-2 left-2 bg-gold-500 text-white px-2 py-1 rounded-full text-xs font-bold flex items-center"
      >
        <Star :size="12" class="mr-1" fill="white" />
        Vedette
      </div>

      <!-- Badge rupture de stock -->
      <div
        v-if="!produit.est_disponible || produit.stock_disponible === 0"
        class="absolute inset-0 bg-black/50 flex items-center justify-center"
      >
        <span class="bg-red-500 text-white px-4 py-2 rounded-full font-semibold">
          Rupture de stock
        </span>
      </div>
    </div>

    <!-- Contenu -->
    <div class="p-4">
      <!-- Nom -->
      <h3 class="font-display font-semibold text-lg text-gray-800 mb-2 line-clamp-2">
        {{ produit.nom }}
      </h3>

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
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { ShoppingCart, Star } from 'lucide-vue-next'

const props = defineProps({
  produit: {
    type: Object,
    required: true
  }
})

const router = useRouter()
const authStore = useAuthStore()
const panierStore = usePanierStore()
const addingToCart = ref(false)
const isRestrictedRole = computed(() => authStore.isAdmin || authStore.isVendeur)

const reductionPercent = computed(() => {
  if (!props.produit.prix_promo) return 0
  const reduction = ((props.produit.prix_unitaire - props.produit.prix_promo) / props.produit.prix_unitaire) * 100
  return Math.round(reduction)
})

const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
const apiOrigin = (() => {
  try {
    return new URL(apiBase).origin
  } catch {
    return ''
  }
})()

const resolveImageUrl = (path) => {
  if (!path) return '/placeholder-product.jpg'
  if (path.startsWith('http') || path.startsWith('/')) return path
  return apiOrigin ? `${apiOrigin}/storage/${path}` : `/storage/${path}`
}

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
  addingToCart.value = true

  const result = await panierStore.addItem(props.produit.id, 1)

  if (result.success) {
    // Afficher un toast ou notification (à implémenter)
    console.log('Produit ajouté au panier')
  } else {
    console.error('Erreur:', result.message)
  }

  addingToCart.value = false
}
</script>
