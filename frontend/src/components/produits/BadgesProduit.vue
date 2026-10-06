<!-- ===================================
COMPOSANT BADGES D'UNE PHOTO PRODUIT
File: src/components/produits/BadgesProduit.vue
=================================== -->
<!--
  Posé dans le conteneur relatif de la photo (carte et fiche produit) :
  vendeur vedette à gauche, remise à droite, voile « Rupture de stock » sur toute la photo.
  La remise est lue « Promotion : moins 20 % » (le signe − seul n'est pas prononcé).
-->
<template>
  <div class="absolute flex items-start justify-between gap-2" :class="grand ? 'inset-x-4 top-4' : 'inset-x-2 top-2'">
    <!-- Vendeur mis en avant par l'admin -->
    <span v-if="produit.createur?.est_vendeur_vedette" class="badge bg-gold-500 text-on-gold shadow-sm" :class="{ 'px-3 py-1 text-sm': grand }">
      <Star :size="grand ? 14 : 12" fill="currentColor" aria-hidden="true" />
      {{ grand ? 'Vendeur vedette' : 'Vedette' }}
    </span>
    <span v-else></span>

    <span v-if="reduction > 0" class="badge bg-danger text-white shadow-sm" :class="{ 'px-3 py-1 text-sm': grand }">
      <span aria-hidden="true">−{{ reduction }} %</span>
      <span class="sr-only">Promotion : moins {{ reduction }} %</span>
    </span>
  </div>

  <div v-if="indisponible" class="absolute inset-0 flex items-center justify-center bg-voile/55">
    <span class="badge bg-surface text-gray-900" :class="grand ? 'px-4 py-2 text-base' : 'px-3 py-1 text-sm'">Rupture de stock</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Star } from 'lucide-vue-next'
import { estIndisponible, pourcentageReduction } from '@/utils/produit'

const props = defineProps({
  produit: {
    type: Object,
    required: true,
  },
  // Fiche produit : badges plus grands, libellé « Vendeur vedette »
  grand: {
    type: Boolean,
    default: false,
  },
})

const indisponible = computed(() => estIndisponible(props.produit))
const reduction = computed(() => pourcentageReduction(props.produit))
</script>
