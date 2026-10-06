<!-- ===================================
COMPOSANT TUILE DE STATISTIQUE
File: src/components/common/TuileStat.vue
=================================== -->
<!--
  Une tuile = un libellé (casse normale, sans deux-points), une valeur, une aide facultative.
  À placer dans un <dl> (la tuile porte dt et dd). Valeur en Manrope demi-gras à chiffres
  proportionnels : une grande valeur isolée n'a pas besoin de chiffres tabulaires.
  « alerte » signale un point d'attention par une icône, le libellé et un fond jaune,
  jamais par la couleur seule.
-->
<template>
  <div class="card flex flex-col p-4" :class="{ 'border-yellow-300 bg-yellow-50': alerte }">
    <dt class="flex items-center gap-1.5 text-sm text-gray-600">
      <AlertTriangle v-if="alerte" :size="15" class="flex-shrink-0 text-yellow-700" aria-hidden="true" />
      <span>{{ libelle }}<span v-if="alerte" class="sr-only"> (à surveiller)</span></span>
    </dt>
    <dd class="mt-1 text-2xl font-semibold leading-tight text-gray-900">{{ valeur }}</dd>
    <dd v-if="aide" class="mt-1 text-xs text-gray-600">{{ aide }}</dd>
  </div>
</template>

<script setup>
import { AlertTriangle } from 'lucide-vue-next'

defineProps({
  libelle: {
    type: String,
    required: true,
  },
  valeur: {
    type: [String, Number],
    required: true,
  },
  aide: {
    type: String,
    default: '',
  },
  alerte: {
    type: Boolean,
    default: false,
  },
})
</script>
