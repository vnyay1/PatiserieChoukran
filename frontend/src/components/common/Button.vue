<!-- ===================================
4. COMPOSANT BOUTON RÉUTILISABLE
File: src/components/common/Button.vue
=================================== -->

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="handleClick"
  >
    <span v-if="loading" class="spinner mr-2"></span>
    <component v-if="icon && !loading" :is="icon" :size="iconSize" class="mr-2" />
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary', // primary, secondary, outline, danger
    validator: (value) => ['primary', 'secondary', 'outline', 'danger'].includes(value)
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  type: {
    type: String,
    default: 'button'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  fullWidth: {
    type: Boolean,
    default: false
  },
  icon: {
    type: Object,
    default: null
  },
  iconSize: {
    type: Number,
    default: 20
  }
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => {
  const base = 'inline-flex items-center justify-center font-medium rounded-full transition-all duration-200 touch-target'
  
  // Variants
  const variants = {
    primary: 'btn-primary',
    secondary: 'btn-secondary',
    outline: 'btn-outline',
    danger: 'bg-red-500 text-white hover:bg-red-600 active:bg-red-700 shadow-md'
  }

  // Sizes
  const sizes = {
    sm: 'px-4 py-2 text-sm',
    md: 'px-6 py-3 text-base',
    lg: 'px-8 py-4 text-lg'
  }

  const width = props.fullWidth ? 'w-full' : ''

  return `${base} ${variants[props.variant]} ${sizes[props.size]} ${width}`
})

const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>