<!-- ===================================
COMPOSANT ÉTAT VIDE
File: src/components/common/EmptyState.vue
=================================== -->
<!--
  Liste vide, recherche sans résultat, page introuvable : une icône, un titre,
  une explication et l'action suivante. Remplace les emojis (rendus différemment
  selon le téléphone et lus « loupe inclinée à gauche » par les lecteurs d'écran).
-->
<template>
  <div class="flex flex-col items-center px-4 py-12 text-center sm:py-16">
    <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-gold-100 text-gold-700">
      <component :is="icone" :size="30" :stroke-width="1.75" aria-hidden="true" />
    </div>
    <component :is="niveau" class="font-display text-xl font-bold text-gray-900 sm:text-2xl">
      {{ titre }}
    </component>
    <p v-if="texte" class="mt-2 max-w-md text-gray-600">{{ texte }}</p>
    <div v-if="$slots.default" class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  icone: {
    type: [Object, Function],
    required: true
  },
  titre: {
    type: String,
    required: true
  },
  texte: {
    type: String,
    default: ''
  },
  niveau: {
    type: String,
    default: 'h2'
  }
})
</script>
