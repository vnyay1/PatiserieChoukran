<!-- ===================================
COMPOSANT CARTE RADIO
File: src/components/common/CarteRadio.vue
=================================== -->
<!--
  Un choix d'un groupe radio présenté en carte (mode de réception, adresse, moyen de paiement).
  Le vrai <input type="radio"> reste dans le label : flèches du groupe et Espace au clavier.
  Choisie : bordure bronze, fond or et coche ; focus clavier : anneau autour de la carte.
  À placer dans un <fieldset> avec sa <legend>. Le contenu par défaut (titre + description)
  se remplace par le slot par défaut ; le slot « visuel » accueille une icône ou un logo.
-->
<template>
  <label class="carte-radio" :class="{ 'carte-radio--action': action }">
    <input v-model="choix" type="radio" :name="name" :value="value" :disabled="disabled" class="sr-only" />
    <slot name="visuel" />
    <span class="min-w-0 flex-1">
      <slot>
        <span class="block font-semibold text-gray-900">{{ titre }}</span>
        <span v-if="description" class="block text-sm text-gray-600">{{ description }}</span>
      </slot>
    </span>
    <span v-if="coche" class="coche" aria-hidden="true"><Check :size="14" /></span>
  </label>
</template>

<script setup>
import { Check } from 'lucide-vue-next'

const choix = defineModel({ type: [String, Number, Boolean], default: null })

defineProps({
  name: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number, Boolean],
    required: true,
  },
  titre: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  // Sans coche quand la carte porte déjà une action à droite (bouton « Modifier »)
  coche: {
    type: Boolean,
    default: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  // Bouton posé en haut à droite, hors du label (« Modifier ») : sa place est réservée et le
  // contenu s'aligne en haut. Une classe passée par la page n'y suffirait pas : le style scopé
  // de la carte l'emporte sur les utilitaires (spécificité 0,2,0).
  action: {
    type: Boolean,
    default: false,
  },
})
</script>

<style scoped>
.carte-radio {
  @apply relative flex min-h-[4.5rem] cursor-pointer items-center gap-3 rounded-2xl border-2 border-gray-200 bg-surface p-4 transition-colors hover:border-gray-300;
}

/* Icône seule (44 px) sous sm, « Modifier » en toutes lettres au-delà */
.carte-radio--action {
  @apply items-start pr-14 sm:pr-32;
}

.carte-radio:has(:checked) {
  @apply border-gold-600 bg-gold-50;
}

.carte-radio:has(:focus-visible) {
  @apply outline outline-2 outline-offset-2 outline-gold-600;
}

.carte-radio:has(:disabled) {
  @apply cursor-not-allowed opacity-60 hover:border-gray-200;
}

.coche {
  @apply flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-gray-300 text-transparent transition-colors;
}

.carte-radio:has(:checked) .coche {
  @apply border-gold-600 bg-gold-600 text-on-accent;
}
</style>
