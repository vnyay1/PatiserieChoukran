<!-- ===================================
4. COMPOSANT BOUTON RÉUTILISABLE
File: src/components/common/Button.vue
=================================== -->
<!--
  Une navigation reste un lien : avec `to`, le bouton est rendu en <router-link>
  (clic milieu, « ouvrir dans un nouvel onglet », annonce « lien » au lecteur d'écran).
  Pendant `loading`, le bouton garde son libellé (lu par les lecteurs d'écran) et expose aria-busy.
-->
<template>
  <component
    :is="to ? RouterLink : 'button'"
    :to="to || undefined"
    :type="to ? undefined : type"
    :disabled="to ? undefined : (disabled || loading)"
    :aria-disabled="to && disabled ? 'true' : undefined"
    :aria-busy="loading ? 'true' : undefined"
    :class="buttonClasses"
    @click="handleClick"
  >
    <span v-if="loading" class="spinner" aria-hidden="true"></span>
    <component :is="icon" v-else-if="icon" :size="iconSize" aria-hidden="true" class="flex-shrink-0" />
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'outline', 'ghost', 'danger'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  type: {
    type: String,
    default: 'button'
  },
  // Destination vue-router : rend un lien stylé en bouton
  to: {
    type: [String, Object],
    default: null
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
    type: [Object, Function],
    default: null
  },
  iconSize: {
    type: Number,
    default: 18
  }
})

const emit = defineEmits(['click'])

const VARIANTES = {
  primary: 'btn-primary',
  secondary: 'btn-secondary',
  outline: 'btn-outline',
  ghost: 'btn-ghost',
  danger: 'btn-danger',
}

const TAILLES = {
  sm: 'btn-sm',
  md: '',
  lg: 'btn-lg',
}

const buttonClasses = computed(() => [
  VARIANTES[props.variant],
  TAILLES[props.size],
  props.fullWidth ? 'w-full' : '',
  props.to && props.disabled ? 'pointer-events-none' : '',
])

const handleClick = (event) => {
  if (props.disabled || props.loading) {
    event.preventDefault()
    return
  }
  emit('click', event)
}
</script>
