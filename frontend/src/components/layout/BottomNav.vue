<!-- ===================================
1. BOTTOM NAVIGATION (Mobile)
File: src/components/layout/BottomNav.vue
=================================== -->
<!--
  Cinq onglets de 64 px de haut. Onglet actif : pastille or derrière l'icône,
  trait plus épais et aria-current="page" (l'état ne repose pas que sur la couleur).
-->
<template>
  <nav
    class="safe-bottom fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-surface/95 backdrop-blur-md md:hidden"
    aria-label="Navigation mobile"
  >
    <ul class="grid h-16" :style="{ gridTemplateColumns: `repeat(${liensBarreBas.length}, minmax(0, 1fr))` }">
      <li v-for="lien in liensBarreBas" :key="lien.name">
        <router-link
          :to="lien.to"
          class="group flex h-full flex-col items-center justify-center gap-1 text-xs font-semibold text-gray-600 transition-colors
                 aria-[current=page]:text-gray-900"
          :aria-current="estActif(lien) ? 'page' : undefined"
        >
          <span
            class="relative flex h-8 w-14 items-center justify-center rounded-full transition-colors duration-200
                   group-hover:bg-gray-100 group-aria-[current=page]:bg-gold-100 group-aria-[current=page]:text-gold-800"
          >
            <component :is="lien.icone" :size="22" :stroke-width="estActif(lien) ? 2.4 : 1.9" aria-hidden="true" />
            <span v-if="compteur(lien) > 0" class="badge-compteur absolute -top-1 right-1.5">
              {{ compteur(lien) > 99 ? '99+' : compteur(lien) }}
              <span class="sr-only">{{ lien.name === 'panier' ? 'articles' : 'à traiter' }}</span>
            </span>
          </span>
          <span class="leading-none">{{ lien.court }}</span>
        </router-link>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { usePanierStore } from '@/stores/panier'
import { useAuthStore } from '@/stores/auth'
import { useVendeurCommandesBadge } from '@/composables/useVendeurCommandesBadge'
import { useNavigation } from '@/composables/useNavigation'

const panierStore = usePanierStore()
const authStore = useAuthStore()
const { vendeurCommandesCount, showVendeurCommandesBadge } = useVendeurCommandesBadge()
const { liensBarreBas, estActif } = useNavigation()

const compteur = (lien) => {
  if (lien.name === 'panier' && authStore.isAuthenticated) return panierStore.itemCount
  if (showVendeurCommandesBadge(lien.name)) return vendeurCommandesCount.value
  return 0
}
</script>
