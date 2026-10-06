<!-- ===================================
COMPOSANT MESSAGE (erreur, avertissement, succès, info)
File: src/components/common/AlertMessage.vue
=================================== -->
<!--
  Une erreur est annoncée tout de suite (role="alert"), les autres types poliment (role="status").
  L'icône double la couleur : l'information ne repose jamais sur la couleur seule (1.4.1).
-->
<template>
  <div
    :role="type === 'error' ? 'alert' : 'status'"
    class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm leading-relaxed"
    :class="STYLES[type]"
  >
    <component :is="ICONES[type]" :size="18" class="mt-0.5 flex-shrink-0" aria-hidden="true" />
    <div class="min-w-0 flex-1">
      <p v-if="titre" class="font-semibold">{{ titre }}</p>
      <slot />
    </div>
  </div>
</template>

<script setup>
import { AlertCircle, AlertTriangle, CheckCircle2, Info } from 'lucide-vue-next'

defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (valeur) => ['error', 'warning', 'success', 'info'].includes(valeur)
  },
  titre: {
    type: String,
    default: ''
  }
})

const STYLES = {
  error: 'border-red-200 bg-red-50 text-red-800',
  warning: 'border-yellow-200 bg-yellow-50 text-yellow-800',
  success: 'border-green-200 bg-green-50 text-green-800',
  info: 'border-gold-200 bg-gold-50 text-gold-900',
}

const ICONES = {
  error: AlertCircle,
  warning: AlertTriangle,
  success: CheckCircle2,
  info: Info,
}
</script>
