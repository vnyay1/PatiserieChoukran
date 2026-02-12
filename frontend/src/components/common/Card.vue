<!-- ===================================
5. COMPOSANT CARD RÉUTILISABLE
File: src/components/common/Card.vue
=================================== -->

<template>
  <div :class="cardClasses" @click="handleClick">
    <slot />
  </div>
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
    default: 'md', // none, sm, md, lg
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value)
  },
  clickable: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])

const cardClasses = computed(() => {
  const base = 'card'
  
  const paddings = {
    none: '',
    sm: 'p-4',
    md: 'p-6',
    lg: 'p-8'
  }

  const hover = props.hoverable ? 'hover:shadow-elegant-lg transform hover:-translate-y-1' : ''
  const group = props.hoverable ? 'group' : ''
  const cursor = props.clickable ? 'cursor-pointer' : ''

  return `${base} ${paddings[props.padding]} ${hover} ${group} ${cursor}`
})

const handleClick = (event) => {
  if (props.clickable) {
    emit('click', event)
  }
}
</script>
