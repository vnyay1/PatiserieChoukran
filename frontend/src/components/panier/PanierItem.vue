<!-- ===================================
COMPOSANT PANIER ITEM
File: src/components/panier/PanierItem.vue
=================================== -->

<template>
  <Card padding="md" class="panier-item">
    <div class="flex gap-4">
      <!-- Image -->
      <router-link
        :to="`/produits/${item.produit.slug}`"
        class="flex-shrink-0 w-24 h-24 md:w-32 md:h-32 rounded-lg overflow-hidden bg-gray-100"
      >
        <img
          :src="resolveImageUrl(item.produit.image_principale)" :alt="item.produit.nom"
          class="w-full h-full object-cover hover:scale-110 transition-transform"
          @error="onImageError"
        />
      </router-link>

      <!-- Infos -->
      <div class="flex-1 min-w-0">
        <!-- Nom et prix -->
        <div class="flex items-start justify-between gap-2 mb-2">
          <div class="flex-1 min-w-0">
            <router-link
              :to="`/produits/${item.produit.slug}`"
              class="font-display font-semibold text-lg text-gray-800 hover:text-gold-600 line-clamp-2"
            >
              {{ item.produit.nom }}
            </router-link>

            <!-- Prix unitaire -->
            <p class="text-sm text-gray-600 mt-1">
              {{ formatPrice(item.prix_unitaire_actuel) }} FCFA / unité
            </p>
          </div>

          <!-- Bouton supprimer (mobile) -->
          <button
            class="md:hidden flex-shrink-0 text-red-500 hover:text-red-600"
            aria-label="Retirer du panier"
            @click="$emit('remove', item.id)"
          >
            <Trash2 :size="20" />
          </button>
        </div>

        <!-- Quantité et actions (desktop) -->
        <div class="hidden md:flex items-center justify-between mt-4">
          <!-- Quantité -->
          <div class="flex items-center gap-2">
            <button
              :disabled="item.quantite <= 1 || loading"
              class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gold-500 disabled:opacity-50 disabled:cursor-not-allowed"
              aria-label="Diminuer la quantité"
              @click="updateQuantity(item.quantite - 1)"
            >
              <Minus :size="16" />
            </button>

            <input
              :value="item.quantite"
              type="number"
              min="1"
              :max="item.produit.stock_disponible"
              aria-label="Quantité"
              class="w-16 text-center border border-gray-200 rounded-lg py-1 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
              @change="updateQuantity($event.target.value)"
            />

            <button
              :disabled="item.quantite >= item.produit.stock_disponible || loading"
              class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gold-500 disabled:opacity-50 disabled:cursor-not-allowed"
              aria-label="Augmenter la quantité"
              @click="updateQuantity(item.quantite + 1)"
            >
              <Plus :size="16" />
            </button>
          </div>

          <!-- Sous-total et supprimer -->
          <div class="flex items-center gap-4">
            <span class="price text-xl">
              {{ formatPrice(item.sous_total) }} FCFA
            </span>

            <button
              class="text-red-500 hover:text-red-600"
              aria-label="Retirer du panier"
              @click="$emit('remove', item.id)"
            >
              <Trash2 :size="20" />
            </button>
          </div>
        </div>

        <!-- Quantité et prix (mobile) -->
        <div class="md:hidden mt-4 space-y-3">
          <!-- Quantité -->
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600 w-20">Quantité:</span>
            <button
              :disabled="item.quantite <= 1 || loading"
              class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-200 active:bg-gray-50 disabled:opacity-50"
              aria-label="Diminuer la quantité"
              @click="updateQuantity(item.quantite - 1)"
            >
              <Minus :size="18" />
            </button>

            <input
              :value="item.quantite"
              type="number"
              min="1"
              :max="item.produit.stock_disponible"
              aria-label="Quantité"
              class="w-16 text-center border border-gray-200 rounded-lg py-2 text-lg font-semibold"
              @change="updateQuantity($event.target.value)"
            />

            <button
              :disabled="item.quantite >= item.produit.stock_disponible || loading"
              class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-200 active:bg-gray-50 disabled:opacity-50"
              aria-label="Augmenter la quantité"
              @click="updateQuantity(item.quantite + 1)"
            >
              <Plus :size="18" />
            </button>
          </div>

          <!-- Sous-total -->
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Sous-total:</span>
            <span class="price text-xl">
              {{ formatPrice(item.sous_total) }} FCFA
            </span>
          </div>
        </div>

        <!-- Stock warning -->
        <div v-if="item.produit.stock_disponible < 5" class="mt-2 text-xs text-orange-600">
          ⚠️ Plus que {{ item.produit.stock_disponible }} en stock
        </div>
      </div>
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="absolute inset-0 bg-white/80 flex items-center justify-center rounded-elegant">
      <div class="spinner"></div>
    </div>
  </Card>
</template>

<script setup>
import Card from '@/components/common/Card.vue'
import { Minus, Plus, Trash2 } from 'lucide-vue-next'
import { resolveImageUrl, onImageError } from '@/utils/images'
import { formatPrice } from '@/utils/format'

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  // Fourni par le parent pendant la requête (un emit ne peut pas être attendu)
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update-quantity', 'remove'])

const updateQuantity = (newQuantite) => {
  const quantite = parseInt(newQuantite, 10)

  if (!Number.isFinite(quantite) || quantite < 1 || quantite > props.item.produit.stock_disponible) {
    return
  }

  emit('update-quantity', props.item.id, quantite)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.panier-item {
  position: relative;
}
</style>
