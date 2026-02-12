<!-- ===================================
1. APP.VUE - Composant Principal
File: src/App.vue
=================================== -->

<template>
  <div id="app" class="min-h-screen bg-cream">
    <!-- Header (desktop) -->
    <Header v-if="!isMobile" />

    <!-- Contenu principal -->
    <main :class="mainClasses">
      <RouterView v-slot="{ Component }">
        <Transition name="fade" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>

    <!-- Bottom Navigation (mobile) -->
    <BottomNav v-if="isMobile && !hideBottomNav" />

    <!-- Footer (desktop) -->
    <Footer v-if="!isMobile" />
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import Header from '@/components/layout/Header.vue'
import Footer from '@/components/layout/Footer.vue'
import BottomNav from '@/components/layout/BottomNav.vue'

const route = useRoute()
const authStore = useAuthStore()
const panierStore = usePanierStore()

// Détecter si mobile
const isMobile = computed(() => window.innerWidth < 768)

// Cacher la bottom nav sur certaines pages
const hideBottomNav = computed(() => {
  return ['login', 'register'].includes(route.name)
})

// Classes pour le main
const mainClasses = computed(() => {
  const base = 'min-h-screen'
  const padding = isMobile.value ? 'pb-20' : 'pt-20'
  return `${base} ${padding}`
})

// Initialiser l'app
onMounted(async () => {
  await authStore.initialize()
})

watch(
  () => authStore.user?.id,
  async (userId) => {
    if (!userId) return
    if (!authStore.isClient) {
      panierStore.reset()
      return
    }
    if (panierStore.ownerUserId && panierStore.ownerUserId !== userId) {
      panierStore.reset()
    }
    await panierStore.fetch()
  }
)
</script>

<style>
/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
