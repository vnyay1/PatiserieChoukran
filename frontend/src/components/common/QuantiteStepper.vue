<!-- ===================================
COMPOSANT SÉLECTEUR DE QUANTITÉ
File: src/components/common/QuantiteStepper.vue
=================================== -->
<!--
  − [ 3 ] + : boutons de 44 px (36 px en taille sm), saisie directe au clavier,
  valeur toujours entière et bornée. La nouvelle quantité est annoncée aux lecteurs d'écran.
  Émet update:modelValue à chaque changement validé.
-->
<template>
  <div
    role="group"
    :aria-label="libelle"
    class="inline-flex items-center rounded-full border border-gray-400 bg-surface p-0.5 transition-[border-color,box-shadow]
           focus-within:border-gold-600 focus-within:ring-4 focus-within:ring-gold-500/25"
    :class="{ 'opacity-60': disabled }"
  >
    <button
      type="button"
      class="btn-icone"
      :class="taille === 'sm' ? 'h-9 w-9' : ''"
      :disabled="disabled || modelValue <= min"
      :aria-label="`Diminuer la quantité${nomProduit ? ` de ${nomProduit}` : ''}`"
      @click="changer(modelValue - 1)"
    >
      <Minus :size="taille === 'sm' ? 16 : 18" aria-hidden="true" />
    </button>

    <input
      :value="modelValue"
      type="number"
      inputmode="numeric"
      :min="min"
      :max="max"
      :disabled="disabled"
      :aria-label="`Quantité${nomProduit ? ` de ${nomProduit}` : ''}`"
      class="w-11 bg-transparent text-center font-semibold tabular-nums text-gray-900 [appearance:textfield] focus:outline-none
             [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
      :class="taille === 'sm' ? 'text-base' : 'text-lg'"
      @change="changer($event.target.value, $event)"
    />

    <button
      type="button"
      class="btn-icone"
      :class="taille === 'sm' ? 'h-9 w-9' : ''"
      :disabled="disabled || (max !== null && modelValue >= max)"
      :aria-label="`Augmenter la quantité${nomProduit ? ` de ${nomProduit}` : ''}`"
      @click="changer(modelValue + 1)"
    >
      <Plus :size="taille === 'sm' ? 16 : 18" aria-hidden="true" />
    </button>

    <span class="sr-only" aria-live="polite">{{ annonce }}</span>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Minus, Plus } from 'lucide-vue-next'

const props = defineProps({
  modelValue: {
    type: Number,
    required: true
  },
  min: {
    type: Number,
    default: 1
  },
  // null = pas de limite connue
  max: {
    type: Number,
    default: null
  },
  disabled: {
    type: Boolean,
    default: false
  },
  taille: {
    type: String,
    default: 'md',
    validator: (valeur) => ['sm', 'md'].includes(valeur)
  },
  libelle: {
    type: String,
    default: 'Quantité'
  },
  nomProduit: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const annonce = ref('')

const changer = (valeur, event = null) => {
  const nombre = Math.floor(Number(valeur))
  const plafond = props.max !== null ? Math.max(props.min, props.max) : Infinity
  const quantite = Number.isFinite(nombre) ? Math.min(Math.max(nombre, props.min), plafond) : props.modelValue

  // Saisie invalide ou hors bornes : le champ reprend la valeur retenue
  if (event?.target) event.target.value = quantite

  if (quantite !== props.modelValue) {
    annonce.value = `Quantité : ${quantite}`
    emit('update:modelValue', quantite)
  }
}
</script>
