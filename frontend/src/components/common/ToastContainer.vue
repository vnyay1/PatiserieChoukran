<!-- ===================================
COMPOSANT CONTENEUR DE TOASTS
File: src/components/common/ToastContainer.vue
=================================== -->
<!--
  Hors de #app (Teleport) : les messages restent lisibles et annoncés quand une modale
  rend le reste de la page inerte. Deux régions live présentes dès le départ : les erreurs
  sont annoncées tout de suite (assertive), les autres poliment.
  Survol ou focus : le message ne disparaît pas (2.2.1).
-->
<template>
  <Teleport to="body">
    <div
      class="pointer-events-none fixed inset-x-4 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-[90] flex flex-col gap-2
             md:bottom-auto md:left-auto md:right-6 md:top-24 md:w-96"
    >
      <div
        v-for="region in REGIONS"
        :key="region.live"
        :aria-live="region.live"
        aria-atomic="false"
        class="flex flex-col gap-2"
      >
        <TransitionGroup name="toast">
          <div
            v-for="toast in toastStore.toasts.filter(region.filtre)"
            :key="toast.id"
            class="pointer-events-auto flex items-start gap-3 rounded-2xl border py-3 pl-4 pr-1.5 text-sm font-medium shadow-elegant-lg"
            :class="STYLES[toast.type] || STYLES.info"
            @mouseenter="toastStore.suspendre(toast.id)"
            @mouseleave="toastStore.reprendre(toast.id)"
            @focusin="toastStore.suspendre(toast.id)"
            @focusout="toastStore.reprendre(toast.id)"
          >
            <component :is="ICONES[toast.type] || Info" :size="18" class="mt-0.5 flex-shrink-0" aria-hidden="true" />
            <p class="flex-1 break-words py-px">{{ toast.message }}</p>
            <button
              type="button"
              class="-my-1.5 inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full opacity-75 transition hover:bg-gray-900/5 hover:opacity-100"
              aria-label="Fermer le message"
              @click="toastStore.retirer(toast.id)"
            >
              <X :size="16" aria-hidden="true" />
            </button>
          </div>
        </TransitionGroup>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { useToastStore } from '@/stores/toast'
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next'

const toastStore = useToastStore()

const REGIONS = [
  { live: 'assertive', filtre: (toast) => toast.type === 'error' },
  { live: 'polite', filtre: (toast) => toast.type !== 'error' },
]

const STYLES = {
  success: 'border-green-200 bg-green-50 text-green-800',
  error: 'border-red-200 bg-red-50 text-red-800',
  info: 'border-gray-200 bg-surface text-gray-800',
}

const ICONES = {
  success: CheckCircle2,
  error: AlertCircle,
  info: Info,
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.2, 0, 0, 1);
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

.toast-move {
  transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1);
}
</style>
