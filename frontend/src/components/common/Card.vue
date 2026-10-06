<!-- ===================================
5. COMPOSANT CARD RÉUTILISABLE
File: src/components/common/Card.vue
=================================== -->
<!--
  Une carte n'est jamais cliquable elle-même (un <div> n'est pas atteignable au clavier) :
  pour une carte-lien, placer un <router-link class="after:absolute after:inset-0"> dans
  son titre (lien étiré) et garder `hoverable` pour l'effet de survol.
-->
<template>
  <component :is="tag" :class="cardClasses">
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  hoverable: {
    type: Boolean,
    default: false
  },
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value)
  },
  tag: {
    type: String,
    default: 'div'
  }
})

const PADDINGS = {
  none: '',
  sm: 'p-4',
  md: 'p-4 sm:p-6',
  lg: 'p-5 sm:p-8'
}

const cardClasses = computed(() => [
  'card',
  PADDINGS[props.padding],
  props.hoverable
    ? 'group relative transition-[box-shadow,transform] duration-200 ease-douce hover:shadow-elegant motion-safe:hover:-translate-y-0.5 focus-within:shadow-elegant'
    : '',
])
</script>
