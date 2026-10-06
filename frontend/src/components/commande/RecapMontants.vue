<!-- ===================================
COMPOSANT RÉCAPITULATIF DES MONTANTS
File: src/components/commande/RecapMontants.vue
=================================== -->
<!--
  Produits, livraison et total (panier, commande en cours, détail d'une commande).
  livraison = null : frais pas encore connus, texteLivraison les remplace.
  Le slot par défaut ajoute des lignes après le total (moyen de paiement, date…) :
  chaque ligne est un <div class="flex justify-between gap-3"> avec <dt> et <dd>.
-->
<template>
  <dl class="space-y-2 text-[0.9375rem]">
    <div class="flex justify-between gap-3">
      <dt class="text-gray-700">{{ libelleProduits }}</dt>
      <dd class="tabular-nums text-gray-900">{{ formatPrice(produits) }} FCFA</dd>
    </div>
    <div v-if="afficherLivraison" class="flex justify-between gap-3">
      <dt class="text-gray-700">{{ libelleLivraison }}</dt>
      <dd v-if="livraison !== null" class="tabular-nums text-gray-900">{{ formatPrice(livraison) }} FCFA</dd>
      <dd v-else class="text-right text-sm text-gray-600">{{ texteLivraison }}</dd>
    </div>
    <div class="flex items-baseline justify-between gap-3 border-t border-gray-200 pt-3">
      <dt class="font-semibold text-gray-900">{{ libelleTotal }}</dt>
      <dd class="price" :class="grand ? 'text-2xl' : 'text-xl'">{{ formatPrice(total) }} FCFA</dd>
    </div>
    <slot />
  </dl>
</template>

<script setup>
import { formatPrice } from '@/utils/format'

defineProps({
  // Montants de l'API : nombres ou chaînes décimales (« 6500.00 »)
  produits: {
    type: [Number, String],
    default: 0,
  },
  livraison: {
    type: [Number, String],
    default: null,
  },
  total: {
    type: [Number, String],
    default: 0,
  },
  // Retrait en boutique : pas de ligne livraison
  afficherLivraison: {
    type: Boolean,
    default: true,
  },
  libelleProduits: {
    type: String,
    default: 'Produits',
  },
  libelleLivraison: {
    type: String,
    default: 'Livraison',
  },
  libelleTotal: {
    type: String,
    default: 'Total',
  },
  texteLivraison: {
    type: String,
    default: 'À calculer',
  },
  // Total en grand dans les récapitulatifs latéraux (panier, commande)
  grand: {
    type: Boolean,
    default: false,
  },
})
</script>
