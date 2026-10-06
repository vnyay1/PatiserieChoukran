<!-- ===================================
COMPOSANT PAGINATION
File: src/components/common/Pagination.vue
=================================== -->
<!--
  <nav> nommée : précédent, numéros (première, dernière, voisines de la page courante,
  « … » entre elles) et suivant, tous en cibles de 44 px. Page courante : aria-current="page"
  et fond or. Sur mobile, les numéros laissent place à « Page 2 sur 8 ».
  Masquée s'il n'y a qu'une page. v-model:page ; « desactive » pendant un chargement.
  Boutons inactifs en aria-disabled, jamais disabled : un bouton désactivé perd le focus (Chrome
  le renvoie sur <body>), or c'est celui que l'on vient d'activer (page suivante, dernière page).
  L'espacement autour (mt-…) est donné par la page.
-->
<template>
  <nav v-if="derniere > 1" class="flex items-center justify-center gap-1.5" :aria-label="libelle">
    <button
      type="button"
      class="page-btn"
      :aria-disabled="page <= 1 || desactive ? 'true' : undefined"
      aria-label="Page précédente"
      @click="aller(page - 1)"
    >
      <ChevronLeft :size="20" aria-hidden="true" />
    </button>

    <p class="px-2 text-sm tabular-nums text-gray-700 sm:hidden">Page {{ page }} sur {{ derniere }}</p>

    <ul class="hidden items-center gap-1.5 sm:flex">
      <li v-for="element in elements" :key="element.cle">
        <span v-if="element.ellipse" class="px-1 text-gray-500" aria-hidden="true">…</span>
        <button
          v-else
          type="button"
          class="page-btn"
          :aria-current="element.numero === page ? 'page' : undefined"
          :aria-label="`Page ${element.numero}`"
          :aria-disabled="desactive ? 'true' : undefined"
          @click="aller(element.numero)"
        >
          {{ element.numero }}
        </button>
      </li>
    </ul>

    <button
      type="button"
      class="page-btn"
      :aria-disabled="page >= derniere || desactive ? 'true' : undefined"
      aria-label="Page suivante"
      @click="aller(page + 1)"
    >
      <ChevronRight :size="20" aria-hidden="true" />
    </button>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const page = defineModel('page', { type: Number, default: 1 })

const props = defineProps({
  derniere: {
    type: Number,
    default: 1,
  },
  desactive: {
    type: Boolean,
    default: false,
  },
  libelle: {
    type: String,
    default: 'Pagination',
  },
})

// 1 … 4 5 6 … 12 : première, dernière, la page courante et ses voisines
const elements = computed(() => {
  const numeros = [...new Set([1, page.value - 1, page.value, page.value + 1, props.derniere])]
    .filter((numero) => numero >= 1 && numero <= props.derniere)
    .sort((a, b) => a - b)

  return numeros.flatMap((numero, index) => {
    const precedent = numeros[index - 1]
    const element = { cle: `p${numero}`, numero }
    return precedent && numero - precedent > 1 ? [{ cle: `e${numero}`, ellipse: true }, element] : [element]
  })
})

const aller = (numero) => {
  if (props.desactive || numero < 1 || numero > props.derniere || numero === page.value) return
  page.value = numero
}
</script>

<style scoped>
.page-btn {
  @apply inline-flex h-11 min-w-11 items-center justify-center rounded-full border border-gray-300 bg-surface px-3 font-semibold tabular-nums text-gray-800
         transition-colors hover:border-gray-400 hover:bg-gray-50;
}

.page-btn[aria-disabled='true'] {
  @apply cursor-not-allowed opacity-45 hover:border-gray-300 hover:bg-surface;
}

.page-btn[aria-current='page'] {
  @apply border-gold-500 bg-gold-500 text-on-gold hover:bg-gold-500;
}
</style>
