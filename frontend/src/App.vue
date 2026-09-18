<!-- ===================================
1. APP.VUE - Composant Principal
File: src/App.vue
=================================== -->
<!--
  - Lien d'évitement « Aller au contenu » (2.4.1), premier élément atteint au clavier.
  - Changement de page : le titre est annoncé aux lecteurs d'écran et le focus revient
    au début du contenu (sinon il resterait sur le lien cliqué, qui a disparu).
    Un simple changement de filtres (même chemin, autre query) ne déplace pas le focus.
-->
<template>
  <div class="flex min-h-screen flex-col bg-cream" :class="{ 'pb-[calc(4rem+env(safe-area-inset-bottom))] md:pb-0': afficherBottomNav }">
    <a
      href="#contenu"
      class="btn-primary sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-3 focus:z-[100]"
      @click.prevent="allerAuContenu"
    >
      Aller au contenu
    </a>

    <Header />

    <main id="contenu" ref="contenu" tabindex="-1" class="flex-1">
      <RouterView v-slot="{ Component }">
        <Transition name="page" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>

    <Footer />

    <BottomNav v-if="afficherBottomNav" />

    <!-- Annonce du changement de page pour les lecteurs d'écran -->
    <p class="sr-only" aria-live="polite" aria-atomic="true">{{ annoncePage }}</p>

    <!-- Retours à l'écran (remplacent alert/confirm/prompt) -->
    <ToastContainer />
    <ConfirmDialog />

    <!-- Première visite : le client choisit sa ville (catalogue filtré) -->
    <ChoixVilleModal />
  </div>
</template>

<script setup>
import { onMounted, watch, computed, ref, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import Header from '@/components/layout/Header.vue'
import Footer from '@/components/layout/Footer.vue'
import BottomNav from '@/components/layout/BottomNav.vue'
import ToastContainer from '@/components/common/ToastContainer.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import ChoixVilleModal from '@/components/common/ChoixVilleModal.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const panierStore = usePanierStore()

const contenu = ref(null)
const annoncePage = ref('')

// Pas de barre du bas sur les pages de connexion
const afficherBottomNav = computed(() => !['login', 'register'].includes(route.name))

const allerAuContenu = () => {
  contenu.value?.focus()
  contenu.value?.scrollIntoView({ block: 'start' })
}

router.afterEach(async (to, from) => {
  // Premier affichage ou simple changement de query (filtres, pagination) : rien à faire
  if (!from.name || to.path === from.path) return

  await nextTick()
  annoncePage.value = document.title
  contenu.value?.focus({ preventScroll: true })
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
/* Transition entre pages : fondu court (neutralisé par prefers-reduced-motion) */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.15s ease;
}

.page-enter-from,
.page-leave-to {
  opacity: 0;
}
</style>
