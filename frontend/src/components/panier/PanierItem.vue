<!-- ===================================
COMPOSANT PANIER ITEM
File: src/components/panier/PanierItem.vue
=================================== -->
<!--
  Un seul balisage pour mobile et desktop (l'ancien doublait chaque contrôle).
  Pendant la mise à jour, la ligne est marquée aria-busy et ses contrôles désactivés.
-->
<template>
  <li class="relative flex gap-3 p-4 sm:gap-4 sm:p-5" :aria-busy="loading">
    <router-link
      :to="`/produits/${item.produit.slug}`"
      class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-xl bg-gray-100 sm:h-24 sm:w-24"
      tabindex="-1"
      aria-hidden="true"
    >
      <img
        loading="lazy"
        :src="resolveImageUrl(item.produit.image_principale)"
        alt=""
        class="h-full w-full object-cover"
        @error="onImageError"
      />
    </router-link>

    <div class="flex min-w-0 flex-1 flex-col">
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
          <h3 class="font-body text-base font-semibold leading-snug text-gray-900">
            <router-link :to="`/produits/${item.produit.slug}`" class="line-clamp-2 hover:underline">
              {{ item.produit.nom }}
            </router-link>
          </h3>
          <p class="mt-0.5 text-sm text-gray-600">{{ formatPrice(item.prix_unitaire_actuel) }} FCFA l'unité</p>
        </div>

        <button
          type="button"
          class="btn-icone -mr-2 -mt-2 text-gray-600 hover:bg-red-50 hover:text-red-700"
          :disabled="loading"
          :aria-label="`Retirer ${item.produit.nom} du panier`"
          @click="$emit('remove', item.id)"
        >
          <Trash2 :size="19" aria-hidden="true" />
        </button>
      </div>

      <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <QuantiteStepper
          :model-value="item.quantite"
          :max="item.produit.stock_disponible"
          :disabled="loading"
          :nom-produit="item.produit.nom"
          taille="sm"
          @update:model-value="(quantite) => $emit('update-quantity', item.id, quantite)"
        />
        <p class="price text-lg">
          <span class="sr-only">Sous-total :</span>
          {{ formatPrice(item.sous_total) }} FCFA
        </p>
      </div>

      <p v-if="item.produit.stock_disponible < 5" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-orange-700">
        <AlertTriangle :size="14" aria-hidden="true" />
        Plus que {{ item.produit.stock_disponible }} en stock
      </p>
    </div>

    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-surface/70">
      <span class="spinner h-6 w-6 text-gold-600" aria-hidden="true"></span>
      <span class="sr-only">Mise à jour de la ligne…</span>
    </div>
  </li>
</template>

<script setup>
import QuantiteStepper from '@/components/common/QuantiteStepper.vue'
import { Trash2, AlertTriangle } from 'lucide-vue-next'
import { resolveImageUrl, onImageError } from '@/utils/images'
import { formatPrice } from '@/utils/format'

defineProps({
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

defineEmits(['update-quantity', 'remove'])
</script>
