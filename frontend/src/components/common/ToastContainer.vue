<!-- ===================================
COMPOSANT CONTENEUR DE TOASTS
File: src/components/common/ToastContainer.vue
=================================== -->

<template>
  <div
    class="fixed z-[70] inset-x-4 bottom-24 md:bottom-auto md:top-24 md:left-auto md:right-6 md:w-96 flex flex-col gap-2 pointer-events-none"
    aria-live="polite"
    role="status"
  >
    <TransitionGroup name="toast">
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-elegant-lg"
        :class="styles[toast.type] || styles.info"
      >
        <component :is="icones[toast.type] || Info" :size="18" class="mt-0.5 flex-shrink-0" />
        <p class="flex-1 break-words">{{ toast.message }}</p>
        <button
          type="button"
          class="flex-shrink-0 opacity-60 hover:opacity-100"
          aria-label="Fermer la notification"
          @click="toastStore.retirer(toast.id)"
        >
          <X :size="16" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToastStore } from '@/stores/toast'
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next'

const toastStore = useToastStore()

const styles = {
  success: 'bg-green-50 border-green-200 text-green-800',
  error: 'bg-red-50 border-red-200 text-red-800',
  info: 'bg-white border-gold-200 text-gray-800',
}

const icones = {
  success: CheckCircle2,
  error: AlertCircle,
  info: Info,
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
