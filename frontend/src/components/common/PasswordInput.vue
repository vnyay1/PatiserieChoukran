<!-- ===================================
COMPOSANT CHAMP MOT DE PASSE (afficher / masquer)
File: src/components/common/PasswordInput.vue
=================================== -->
<!--
  Le bouton garde le même libellé et expose son état par aria-pressed
  (« Afficher le mot de passe, activé »). Les attributs vont au <input> (id, aria-*, autocomplete).
-->
<template>
  <div class="relative">
    <input
      v-bind="$attrs"
      :value="modelValue"
      :type="visible ? 'text' : 'password'"
      class="input pr-12"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <button
      type="button"
      class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-gray-600 transition-colors hover:text-gray-900"
      aria-label="Afficher le mot de passe"
      :aria-pressed="visible"
      @click="visible = !visible"
    >
      <EyeOff v-if="visible" :size="19" aria-hidden="true" />
      <Eye v-else :size="19" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Eye, EyeOff } from 'lucide-vue-next'

defineOptions({ inheritAttrs: false })

defineProps({
  modelValue: {
    type: String,
    default: ''
  }
})

defineEmits(['update:modelValue'])

const visible = ref(false)
</script>
