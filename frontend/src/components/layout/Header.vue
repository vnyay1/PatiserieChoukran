<!-- ===================================
2. HEADER (Desktop & Mobile Top)
File: src/components/layout/Header.vue
=================================== -->

<template>
  <header class="bg-white shadow-sm sticky top-0 z-40 safe-top">
    <div class="container mx-auto px-4">
      <div class="flex items-center justify-between h-16 md:h-20">
        <!-- Logo -->
        <router-link to="/" class="flex items-center space-x-2">
          <img src="/logo.png" alt="Choukrane" class="h-10 md:h-12 w-auto" />
        </router-link>

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
              v-if="showLivreurCommandesBadge(item.name)"
              class="bg-gold-600 text-white text-xs rounded-full h-5 min-w-5 px-1 flex items-center justify-center font-bold"
            >
              {{ formatBadgeCount(livreurCommandesCount) }}
            </span>
          </router-link>
        </nav>

        <!-- Actions -->
        <div class="flex items-center space-x-4">
          <router-link
            to="/infos-pratiques"
            class="touch-target h-10 w-10 rounded-full border border-gold-200 bg-gold-50 text-gold-700 flex items-center justify-center md:hidden"
            aria-label="Infos pratiques"
            title="Infos pratiques"
          >
            <Info :size="20" :class="route.name === 'infos-pratiques' ? 'text-gold-600' : 'text-gold-700'" />
          </router-link>

          <!-- Authentification Desktop -->
          <div v-if="authStore.isAuthenticated" class="hidden md:flex items-center space-x-4">
            <router-link v-if="authStore.isClient" to="/panier" class="relative">
              <ShoppingCart :size="24" class="text-gray-700 hover:text-gold-600" />
              <span
                v-if="panierCount > 0"
                class="absolute -top-1 -right-1 bg-gold-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
              >
                {{ panierCount }}
              </span>
            </router-link>

            <router-link
              to="/profil"
              class="flex items-center space-x-2 hover:text-gold-600 transition-colors"
            >
              <User :size="24" />
              <span class="font-medium">{{ authStore.userName }}</span>
            </router-link>
          </div>

          <div v-else class="hidden md:flex items-center space-x-2">
            <router-link to="/connexion" class="btn-outline py-2 px-4 text-sm">
              Connexion
            </router-link>
            <router-link to="/inscription" class="btn-primary py-2 px-4 text-sm">
              Inscription
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import { useLivreurCommandesBadge } from '@/composables/useLivreurCommandesBadge'
import { ShoppingCart, User, Info } from 'lucide-vue-next'

const route = useRoute()
const authStore = useAuthStore()
const panierStore = usePanierStore()
const { livreurCommandesCount, formatBadgeCount, showLivreurCommandesBadge } = useLivreurCommandesBadge()

const panierCount = computed(() => panierStore.itemCount)

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
  }

  if (authStore.isLivreur) {
    items.push({ name: 'admin-commandes', label: 'Commandes', to: '/admin/commandes' })
  }

  if (authStore.canManageCatalogue) {
    items.push({ name: 'admin-categories', label: 'Catégories', to: '/admin/categories' })
    items.push({ name: 'admin-produits', label: 'Produits', to: '/admin/produits' })
    items.push({ name: 'admin-zones', label: 'Zones', to: '/admin/zones-livraison' })
  }

  return items
})

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
  return false
}

</script>
