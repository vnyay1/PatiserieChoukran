<!-- ===================================
COMPOSANT LIGNES D'UNE COMMANDE
File: src/components/commande/LignesCommande.vue
=================================== -->
<!--
  Articles d'une commande passée (détail client et détail admin/vendeur) :
  photo décorative (le nom est écrit à côté), quantité × prix unitaire, sous-total.
-->
<template>
  <p v-if="!lignes.length" class="text-sm text-gray-600">Aucun article.</p>
  <ul v-else class="divide-y divide-gray-200">
    <li v-for="ligne in lignes" :key="ligne.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0 sm:gap-4">
      <img
        loading="lazy"
        :src="resolveImageUrl(ligne.produit?.image_principale)"
        alt=""
        class="flex-shrink-0 rounded-xl bg-gray-100 object-cover"
        :class="compact ? 'h-12 w-12' : 'h-16 w-16 sm:h-20 sm:w-20'"
        @error="onImageError"
      />
      <div class="min-w-0 flex-1">
        <p class="break-words font-semibold text-gray-900">{{ ligne.nom_produit }}</p>
        <p class="text-sm text-gray-600">{{ ligne.quantite }} × {{ formatPrice(ligne.prix_unitaire) }} FCFA</p>
      </div>
      <p class="price flex-shrink-0 whitespace-nowrap" :class="compact ? 'text-base' : 'text-lg'">
        {{ formatPrice(ligne.sous_total) }} FCFA
      </p>
    </li>
  </ul>
</template>

<script setup>
import { formatPrice } from '@/utils/format'
import { resolveImageUrl, onImageError } from '@/utils/images'

defineProps({
  lignes: {
    type: Array,
    default: () => [],
  },
  // Vignettes plus petites (fenêtre de détail de l'espace de gestion)
  compact: {
    type: Boolean,
    default: false,
  },
})
</script>
