<!-- ===================================
COMPOSANT CHAMP MOT DE PASSE (afficher / masquer)
File: src/components/common/PasswordInput.vue
=================================== -->

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
      class="absolute inset-y-0 right-0 flex items-center px-4 text-gray-500 hover:text-gold-600"
      :aria-label="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
      @click="visible = !visible"
    >
      <EyeOff v-if="visible" :size="18" />
      <Eye v-else :size="18" />
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
