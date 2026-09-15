<!-- ===================================
1. APP.VUE - Composant Principal
File: src/App.vue
=================================== -->

<template>
  <div id="app" class="min-h-screen bg-cream">
    <!-- Header -->
    <Header />

    <!-- Contenu principal -->
    <main class="min-h-screen pt-16 md:pt-20 pb-20 md:pb-0">
      <RouterView v-slot="{ Component }">
        <Transition name="fade" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>

    <!-- Bottom Navigation (mobile) -->
    <BottomNav v-if="!hideBottomNav" class="md:hidden" />

    <!-- Footer -->
    <Footer />

    <!-- Retours à l'écran (remplacent alert/confirm/prompt) -->
    <ToastContainer />
    <ConfirmDialog />

    <!-- Première visite : le client choisit sa ville (catalogue filtré) -->
    <ChoixVilleModal />
  </div>
</template>

<script setup>
import { onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import Header from '@/components/layout/Header.vue'
import Footer from '@/components/layout/Footer.vue'
import BottomNav from '@/components/layout/BottomNav.vue'
import ToastContainer from '@/components/common/ToastContainer.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import ChoixVilleModal from '@/components/common/ChoixVilleModal.vue'

const route = useRoute()
const authStore = useAuthStore()
const panierStore = usePanierStore()

// Cacher la bottom nav sur certaines pages
const hideBottomNav = computed(() => {
  return ['login', 'register'].includes(route.name)
})

// Initialiser l'app
onMounted(async () => {
  await authStore.initialize()
})

watch(
  () => authStore.user?.id,
  async (userId) => {
    // Déconnexion : ne pas garder le panier (et son badge) du compte précédent
    if (!userId) {
      panierStore.reset()
      return
    }
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
