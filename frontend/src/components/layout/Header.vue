<!-- ===================================
2. HEADER (Desktop & Mobile Top)
File: src/components/layout/Header.vue
=================================== -->

<template>
  <header class="bg-white shadow-sm sticky top-0 z-40 safe-top">
    <div class="container mx-auto px-4">
      <div class="flex items-center justify-between h-14 md:h-20">
        <!-- Logo -->
        <router-link to="/" class="flex items-center space-x-2">
          <img src="/logo.png" alt="Choukrane" class="h-10 md:h-12 w-auto" loading="lazy" />
        </router-link>

        <!-- Mobile actions -->
        <div class="flex items-center gap-3 md:hidden">
          <router-link
            v-if="authStore.isAuthenticated"
            to="/notifications"
            class="relative touch-target h-11 w-11 rounded-full border border-gray-200 bg-white text-gray-700 flex items-center justify-center"
            aria-label="Notifications"
            title="Notifications"
          >
            <Bell :size="20" />
            <span
              v-if="unreadNotificationsCount > 0"
              class="absolute -top-1 -right-1 bg-gold-600 text-white text-[10px] rounded-full h-5 min-w-5 px-1 flex items-center justify-center font-bold"
            >
              {{ formatNotificationBadgeCount(unreadNotificationsCount) }}
            </span>
          </router-link>

          <router-link
            v-if="authStore.isAuthenticated && authStore.isClient"
            to="/panier"
            class="relative touch-target h-11 w-11 rounded-full border border-gold-200 bg-gold-50 text-gold-700 flex items-center justify-center"
            aria-label="Panier"
            title="Panier"
          >
            <ShoppingCart :size="22" />
            <span
              v-if="panierCount > 0"
              class="absolute -top-1 -right-1 bg-gold-600 text-white text-[10px] rounded-full h-5 min-w-5 px-1 flex items-center justify-center font-bold"
            >
              {{ panierCount }}
            </span>
          </router-link>

          <button
            type="button"
            class="touch-target h-11 w-11 rounded-full border border-gray-200 bg-white text-gray-800 flex items-center justify-center shadow-sm"
            aria-label="Menu"
            :aria-expanded="mobileOpen"
            @click="toggleMobileNav(!mobileOpen)"
          >
            <svg v-if="!mobileOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Navigation Desktop -->
        <nav class="hidden md:flex items-center space-x-8">
          <router-link
            v-for="item in desktopNavItems"
            :key="item.name"
            :to="item.to"
            class="relative inline-flex items-center gap-2 font-medium hover:text-gold-600 transition-colors"
            :class="isActiveRoute(item.name) ? 'text-gold-600' : 'text-gray-700'"
          >
            <span>{{ item.label }}</span>
            <span
              v-if="showVendeurCommandesBadge(item.name)"
              class="bg-gold-600 text-white text-xs rounded-full h-5 min-w-5 px-1 flex items-center justify-center font-bold"
            >
              {{ formatBadgeCount(vendeurCommandesCount) }}
            </span>
          </router-link>
        </nav>

        <!-- Actions desktop -->
        <div class="hidden md:flex items-center space-x-4">
          <router-link
            v-if="authStore.isAuthenticated && authStore.isClient"
            to="/panier"
            class="relative"
          >
            <ShoppingCart :size="24" class="text-gray-700 hover:text-gold-600" />
            <span
              v-if="panierCount > 0"
              class="absolute -top-1 -right-1 bg-gold-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
            >
              {{ panierCount }}
            </span>
          </router-link>

          <router-link
            v-if="authStore.isAuthenticated"
            to="/notifications"
            class="relative"
            aria-label="Notifications"
            title="Notifications"
          >
            <Bell
              :size="24"
              :class="route.name === 'notifications' ? 'text-gold-600' : 'text-gray-700 hover:text-gold-600'"
            />
            <span
              v-if="unreadNotificationsCount > 0"
              class="absolute -top-1 -right-1 bg-gold-600 text-white text-xs rounded-full h-5 min-w-5 px-1 flex items-center justify-center font-bold"
            >
              {{ formatNotificationBadgeCount(unreadNotificationsCount) }}
            </span>
          </router-link>

          <router-link
            v-if="authStore.isAuthenticated"
            to="/profil"
            class="flex items-center space-x-2 hover:text-gold-600 transition-colors"
          >
            <User :size="24" />
            <span class="font-medium">{{ authStore.userName }}</span>
          </router-link>

          <template v-else>
            <router-link to="/connexion" class="btn-outline py-2 px-4 text-sm">
              Connexion
            </router-link>
            <router-link to="/inscription" class="btn-primary py-2 px-4 text-sm">
              Inscription
            </router-link>
          </template>
        </div>

        <!-- Minimal actions already handled above for mobile -->
      </div>
    </div>

    <!-- Offcanvas mobile -->
    <Teleport to="body">
      <transition name="fade">
        <div
          v-if="mobileOpen"
          class="fixed inset-0 bg-black/40 z-40 md:hidden"
          @click="toggleMobileNav(false)"
        />
      </transition>
      <transition name="slide-right">
        <aside
          v-if="mobileOpen"
          class="fixed inset-y-0 right-0 w-[88%] max-w-xs bg-white z-50 shadow-elegant-lg md:hidden overflow-y-auto safe-top safe-bottom"
        >
          <div class="p-5 space-y-4">
            <div class="flex items-center justify-between">
              <router-link to="/" class="flex items-center space-x-2" @click="toggleMobileNav(false)">
                <img src="/logo.png" alt="Choukrane" class="h-10 w-auto" loading="lazy" />
              </router-link>
              <button
                type="button"
                class="touch-target h-10 w-10 rounded-full border border-gray-200 flex items-center justify-center"
                aria-label="Fermer le menu"
                @click="toggleMobileNav(false)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="flex flex-col space-y-2">
              <router-link
                v-for="item in mobileNavItems"
                :key="item.name"
                :to="item.to"
                class="flex items-center justify-between px-3 py-3 rounded-xl border border-gray-100 hover:border-gold-300 hover:bg-gold-50"
                :class="isActiveRoute(item.name) ? 'text-gold-600 font-semibold border-gold-200 bg-gold-50' : 'text-gray-800'"
                @click="toggleMobileNav(false)"
              >
                <span>{{ item.label }}</span>
                <span
                  v-if="showVendeurCommandesBadge(item.name)"
                  class="bg-gold-600 text-white text-[11px] rounded-full h-5 min-w-5 px-1 flex items-center justify-center font-bold"
                >
                  {{ formatBadgeCount(vendeurCommandesCount) }}
                </span>
              </router-link>
            </div>

            <div class="border-t border-gray-100 pt-4 space-y-3">
              <router-link
                v-if="authStore.isAuthenticated"
                to="/profil"
                class="flex items-center justify-between px-3 py-3 rounded-xl bg-gold-50 text-gold-800"
                @click="toggleMobileNav(false)"
              >
                <div>
                  <p class="text-sm text-gray-600">Connecté</p>
                  <p class="font-semibold">{{ authStore.userName }}</p>
                </div>
                <User :size="22" />
              </router-link>

              <div v-else class="grid grid-cols-2 gap-3">
                <router-link to="/connexion" class="btn-outline w-full text-center" @click="toggleMobileNav(false)">
                  Connexion
                </router-link>
                <router-link to="/inscription" class="btn-primary w-full text-center" @click="toggleMobileNav(false)">
                  Inscription
                </router-link>
              </div>

              <router-link
                to="/infos-pratiques"
                class="flex items-center gap-2 text-gray-800 hover:text-gold-600"
                @click="toggleMobileNav(false)"
              >
                <Info :size="18" />
                <span>Infos pratiques</span>
              </router-link>
            </div>
          </div>
        </aside>
      </transition>
    </Teleport>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import { useNotificationsStore } from '@/stores/notifications'
import { useVendeurCommandesBadge } from '@/composables/useVendeurCommandesBadge'
import { ShoppingCart, User, Info, Bell } from 'lucide-vue-next'

const route = useRoute()
const authStore = useAuthStore()
const panierStore = usePanierStore()
const notificationsStore = useNotificationsStore()
const { vendeurCommandesCount, formatBadgeCount, showVendeurCommandesBadge } = useVendeurCommandesBadge()
const mobileOpen = ref(false)

const panierCount = computed(() => panierStore.itemCount)
const unreadNotificationsCount = computed(() => notificationsStore.unreadCount)

const desktopNavItems = computed(() => {
  const items = [
    { name: 'home', label: 'Accueil', to: '/' },
    { name: 'produits', label: 'Nos Produits', to: '/produits' },
  ]

  if (authStore.isClient) {
    items.push({ name: 'commandes', label: 'Mes Commandes', to: '/mes-commandes' })
  }

  if (authStore.isAdmin) {
    items.push({ name: 'admin-dashboard', label: 'Dashboard', to: '/admin/dashboard' })
    items.push({ name: 'admin-commandes', label: 'Commandes', to: '/admin/commandes' })
    items.push({ name: 'admin-parametres', label: 'Paramètres', to: '/admin/parametres' })
    items.push({ name: 'admin-users', label: 'Utilisateurs', to: '/admin/users' })
    items.push({ name: 'admin-rapports', label: 'Rapports', to: '/admin/rapports' })
  }

  if (authStore.isVendeur) {
    items.push({ name: 'admin-commandes', label: 'Commandes', to: '/admin/commandes' })
  }

  if (authStore.canManageCatalogue) {
    items.push({ name: 'admin-categories', label: 'Catégories', to: '/admin/categories' })
    items.push({ name: 'admin-produits', label: 'Produits', to: '/admin/produits' })
    // Vendeur : quartiers desservis et minimum d'achat (utilisés au checkout) ; admin : zones (ancien système)
    items.push(authStore.isVendeur
      ? { name: 'admin-tarifs', label: 'Livraison', to: '/admin/tarifs-livraison' }
      : { name: 'admin-zones', label: 'Zones', to: '/admin/zones-livraison' })
  }

  if (authStore.isVendeur) {
    items.push({ name: 'vendeur-profil-boutique', label: 'Ma boutique', to: '/vendeur/profil-boutique' })
  }

  return items
})

const mobileNavItems = desktopNavItems

const isActiveRoute = (name) => {
  if (name === 'home') return route.name === 'home'
  if (name === 'produits') return route.name === 'produits' || route.name === 'produit-detail'
  if (name === 'commandes') return route.name === 'mes-commandes' || route.name === 'commande-detail'
  if (name === 'admin-dashboard') return route.name === 'admin-dashboard'
  if (name === 'admin-commandes') return route.name === 'admin-commandes'
  if (name === 'admin-categories') return route.name === 'admin-categories'
  if (name === 'admin-parametres') return route.name === 'admin-parametres'
  if (name === 'admin-produits') return route.name === 'admin-produits'
  if (name === 'admin-users') return route.name === 'admin-users'
  if (name === 'admin-zones') return route.name === 'admin-zones'
  if (name === 'admin-tarifs') return route.name === 'admin-tarifs'
  if (name === 'admin-rapports') return route.name === 'admin-rapports'
  if (name === 'vendeur-profil-boutique') return route.name === 'vendeur-profil-boutique'
  return false
}

const formatNotificationBadgeCount = (count) => {
  return count > 99 ? '99+' : count
}

const syncNotificationsState = async () => {
  if (!authStore.isAuthenticated) {
    notificationsStore.stopPolling()
    notificationsStore.reset()
    return
  }

  await notificationsStore.startPolling()
}

onMounted(() => {
  syncNotificationsState()
})

watch(
  () => mobileOpen.value,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
  }
)

watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false
    // Rafraîchissement opportuniste du badge (limité par le store)
    if (authStore.isAuthenticated) {
      notificationsStore.fetchUnreadCount()
    }
  }
)

watch(
  () => authStore.isAuthenticated,
  (isAuthenticated) => {
    if (isAuthenticated) {
      syncNotificationsState()
      return
    }

    notificationsStore.stopPolling()
    notificationsStore.reset()
  }
)

onBeforeUnmount(() => {
  notificationsStore.stopPolling()
  document.body.style.overflow = ''
})

const toggleMobileNav = (value) => {
  mobileOpen.value = value
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-right-enter-active,
.slide-right-leave-active {
  transition: transform 0.25s ease, opacity 0.25s ease;
}
.slide-right-enter-from,
.slide-right-leave-to {
  transform: translateX(16px);
  opacity: 0;
}
</style>
