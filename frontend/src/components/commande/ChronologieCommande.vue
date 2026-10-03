<!-- ===================================
COMPOSANT CHRONOLOGIE D'UNE COMMANDE
File: src/components/commande/ChronologieCommande.vue
=================================== -->
<!--
  Étapes de la commande, de la plus ancienne à la plus récente (liste ordonnée).
  L'étape actuelle est pleine (or) et annoncée « étape actuelle » aux lecteurs d'écran.
  L'espace de gestion affiche aussi qui a changé le statut (afficherAuteur).
-->
<template>
  <ol>
    <li v-for="(etape, index) in etapes" :key="etape.id" class="relative flex gap-4 pb-6 last:pb-0">
      <span
        v-if="index < etapes.length - 1"
        class="absolute left-[0.6875rem] top-6 h-[calc(100%-1.5rem)] w-0.5 bg-gold-200"
        aria-hidden="true"
      ></span>
      <span
        class="relative mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full"
        :class="index === etapes.length - 1 ? 'bg-gold-500 text-on-gold' : 'bg-gold-100 text-gold-700'"
        aria-hidden="true"
      >
        <Check :size="13" :stroke-width="3" />
      </span>
      <div class="min-w-0">
        <p class="font-semibold text-gray-900">
          {{ libelleStatut(etape.nouveau_statut) }}
          <span v-if="index === etapes.length - 1" class="sr-only">(étape actuelle)</span>
        </p>
        <p class="text-sm text-gray-600">
          <time :datetime="etape.created_at">{{ formatDateHeure(etape.created_at) }}</time>
          <template v-if="afficherAuteur && etape.modifie_par?.nom_complet"> par {{ etape.modifie_par.nom_complet }}</template>
        </p>
        <p v-if="etape.commentaire" class="mt-1 break-words text-sm text-gray-700">{{ etape.commentaire }}</p>
      </div>
    </li>
  </ol>
</template>

<script setup>
import { computed } from 'vue'
import { Check } from 'lucide-vue-next'
import { formatDateHeure, libelleStatut } from '@/utils/format'

const props = defineProps({
  historiques: {
    type: Array,
    default: () => [],
  },
  afficherAuteur: {
    type: Boolean,
    default: false,
  },
})

const etapes = computed(() => [...props.historiques]
  .sort((a, b) => new Date(a.created_at) - new Date(b.created_at)))
</script>
