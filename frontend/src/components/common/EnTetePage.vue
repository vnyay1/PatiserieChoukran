<!-- ===================================
COMPOSANT EN-TÊTE DE PAGE
File: src/components/common/EnTetePage.vue
=================================== -->
<!--
  Titre de la page (le seul h1), phrase d'explication facultative et actions de la page :
  à droite dès md, en dessous sur mobile. « annonce » rend le sous-titre vivant (aria-live)
  quand il porte un compteur qui change (« 3 non lues »).
-->
<template>
  <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div class="min-w-0">
      <h1><slot name="titre">{{ titre }}</slot></h1>
      <p
        v-if="sousTitre || $slots['sous-titre']"
        class="mt-1 max-w-2xl text-sm text-gray-600"
        :aria-live="annonce ? 'polite' : undefined"
      >
        <slot name="sous-titre">{{ sousTitre }}</slot>
      </p>
    </div>
    <div v-if="$slots.actions" class="flex flex-shrink-0 flex-wrap items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  titre: {
    type: String,
    default: '',
  },
  sousTitre: {
    type: String,
    default: '',
  },
  annonce: {
    type: Boolean,
    default: false,
  },
})
</script>
