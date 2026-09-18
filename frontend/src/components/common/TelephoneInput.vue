<!-- ===================================
COMPOSANT SAISIE DE TÉLÉPHONE (+237)
File: src/components/common/TelephoneInput.vue
=================================== -->
<!--
  Préfixe +237 soudé au champ (un seul contour, un seul halo de focus).
  v-model : les 9 chiffres locaux, nettoyés à la saisie. Les attributs (id, aria-*,
  required, autocomplete) sont transmis au <input>, par exemple depuis FormField.
-->
<template>
  <div
    class="flex rounded-xl transition-[box-shadow] focus-within:ring-4 focus-within:ring-gold-500/25"
    :class="{ 'opacity-70': disabled }"
  >
    <span
      class="inline-flex select-none items-center rounded-l-xl border border-r-0 border-gray-400 bg-gray-100 px-3.5 font-semibold tabular-nums text-gray-700"
      aria-hidden="true"
    >
      +237
    </span>
    <input
      v-bind="$attrs"
      :value="modelValue"
      type="tel"
      inputmode="numeric"
      autocomplete="tel-national"
      maxlength="12"
      placeholder="6XX XX XX XX"
      :disabled="disabled"
      class="input rounded-l-none tabular-nums focus:ring-0"
      @input="saisir"
    />
  </div>
</template>

<script setup>
import { chiffresLocaux } from '@/utils/telephone'

defineOptions({ inheritAttrs: false })

defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const saisir = (event) => {
  const valeur = chiffresLocaux(event.target.value)
  // Le champ affiche toujours la valeur nettoyée (lettres et espaces retirés)
  event.target.value = valeur
  emit('update:modelValue', valeur)
}
</script>
