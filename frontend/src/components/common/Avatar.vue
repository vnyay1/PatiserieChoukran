<!-- ===================================
COMPOSANT AVATAR À INITIALES
File: src/components/common/Avatar.vue
=================================== -->
<!--
  Décoratif (aria-hidden) : le nom est toujours écrit à côté de l'avatar.
  Initiales des deux premiers mots du nom, « ? » si le nom manque.
-->
<template>
  <span
    class="flex flex-shrink-0 select-none items-center justify-center rounded-full bg-gold-500 font-bold text-on-gold"
    :class="TAILLES[taille]"
    aria-hidden="true"
  >
    {{ initiales }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  nom: {
    type: String,
    default: '',
  },
  taille: {
    type: String,
    default: 'md',
    validator: (valeur) => ['sm', 'md', 'lg'].includes(valeur),
  },
})

const TAILLES = {
  sm: 'h-8 w-8 text-xs',
  md: 'h-11 w-11 text-base',
  lg: 'h-14 w-14 text-lg lg:h-20 lg:w-20 lg:text-2xl',
}

const initiales = computed(() => {
  const mots = (props.nom || '').trim().split(/\s+/).filter(Boolean)
  return (mots.slice(0, 2).map((mot) => mot[0]).join('') || '?').toUpperCase()
})
</script>
