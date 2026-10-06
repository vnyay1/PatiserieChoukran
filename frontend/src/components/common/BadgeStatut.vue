<!-- ===================================
COMPOSANT BADGE DE STATUT (commande, paiement, compte)
File: src/components/common/BadgeStatut.vue
=================================== -->
<!--
  Libellé et ton viennent de utils/format.js : un seul système de couleurs (.badge-*, fond 100 / texte 800).
  Le libellé porte l'information, la couleur ne fait que la doubler. Un paiement est précédé
  de « Paiement : » pour les lecteurs d'écran (« Payé » seul serait ambigu à côté de « Livrée »).
-->
<template>
  <span class="badge" :class="infos?.classe || 'badge-neutral'">
    <span v-if="type === 'paiement'" class="sr-only">Paiement :</span>
    {{ infos?.label || statut || '—' }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { STATUTS_COMMANDE, STATUTS_PAIEMENT, STATUTS_COMPTE } from '@/utils/format'

const TYPES = {
  commande: STATUTS_COMMANDE,
  paiement: STATUTS_PAIEMENT,
  compte: STATUTS_COMPTE,
}

const props = defineProps({
  statut: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'commande',
    validator: (valeur) => ['commande', 'paiement', 'compte'].includes(valeur),
  },
})

const infos = computed(() => TYPES[props.type][props.statut])
</script>
