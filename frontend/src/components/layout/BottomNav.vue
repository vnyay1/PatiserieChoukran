<!-- ===================================
1. BOTTOM NAVIGATION (Mobile)
File: src/components/layout/BottomNav.vue
=================================== -->

<template>
  <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 safe-bottom z-50 md:hidden">
    <div class="flex justify-around items-center h-16">
      <router-link
        v-for="item in navItems"
        :key="item.name"
        :to="item.to"
        class="flex flex-col items-center justify-center flex-1 h-full touch-target relative"
        :class="isActive(item.name) ? 'text-gold-600' : 'text-gray-400'"
      >
        <!-- Badge pour le panier -->
        <span
          v-if="authStore.isAuthenticated && item.name === 'panier' && panierCount > 0"
          class="absolute top-1 right-1/4 bg-gold-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
        >
          {{ panierCount }}
        </span>
        <span
          v-if="showLivreurCommandesBadge(item.name)"
          class="absolute top-1 right-1/4 bg-gold-600 text-white text-xs rounded-full h-5 min-w-5 px-1 flex items-center justify-center font-bold"
        >
          {{ formatBadgeCount(livreurCommandesCount) }}
        </span>

        <component :is="item.icon" :size="24" :stroke-width="isActive(item.name) ? 2.5 : 2" />
        <span class="text-xs mt-1 font-medium">{{ item.label }}</span>
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { usePanierStore } from '@/stores/panier'
import { useAuthStore } from '@/stores/auth'
import { useLivreurCommandesBadge } from '@/composables/useLivreurCommandesBadge'
import { Home, ShoppingBag, ShoppingCart, Package, User, Shield, Settings } from 'lucide-vue-next'

const route = useRoute()
const panierStore = usePanierStore()
const authStore = useAuthStore()
const { livreurCommandesCount, formatBadgeCount, showLivreurCommandesBadge } = useLivreurCommandesBadge()

const panierCount = computed(() => panierStore.itemCount)

const navItems = computed(() => {
  if (authStore.isAdmin) {
    return [
      { name: 'home', label: 'Accueil', icon: Home, to: '/' },
      { name: 'produits', label: 'Produits', icon: ShoppingBag, to: '/produits' },
      { name: 'admin-dashboard', label: 'Admin', icon: Shield, to: '/admin/dashboard' },
      { name: 'admin-commandes', label: 'Commandes', icon: Package, to: '/admin/commandes' },
      { name: 'admin-parametres', label: 'Paramètres', icon: Settings, to: '/admin/parametres' },
    ]
  }

  if (authStore.isLivreur) {
    return [
      { name: 'home', label: 'Accueil', icon: Home, to: '/' },
      { name: 'produits', label: 'Produits', icon: ShoppingBag, to: '/produits' },
      { name: 'admin-commandes', label: 'Commandes', icon: Package, to: '/admin/commandes' },
      { name: 'admin-produits', label: 'Catalogue', icon: Shield, to: '/admin/produits' },
      { name: 'admin-zones', label: 'Zones', icon: Settings, to: '/admin/zones-livraison' },
    ]
  }

  return [
    { name: 'home', label: 'Accueil', icon: Home, to: '/' },
    { name: 'produits', label: 'Produits', icon: ShoppingBag, to: '/produits' },
    { name: 'panier', label: 'Panier', icon: ShoppingCart, to: '/panier' },
    { name: 'commandes', label: 'Commandes', icon: Package, to: '/mes-commandes' },
    { name: 'profil', label: 'Profil', icon: User, to: '/profil' },
  ]
})

const isActive = (name) => {
  if (name === 'home') return route.name === 'home'
  if (name === 'produits') return route.name === 'produits' || route.name === 'produit-detail'
  if (name === 'panier') return route.name === 'panier' || route.name === 'checkout'
  if (name === 'commandes') return route.name === 'mes-commandes' || route.name === 'commande-detail'
  if (name === 'profil') return route.name === 'profil'
  if (name === 'admin-dashboard') {
    return route.name === 'admin-dashboard' || route.name === 'admin-produits' || route.name === 'admin-users' || route.name === 'admin-zones' || route.name === 'admin-categories' || route.name === 'admin-parametres'
  }
  if (name === 'admin-produits') return route.name === 'admin-produits'
  if (name === 'admin-categories') return route.name === 'admin-categories'
  if (name === 'admin-zones') return route.name === 'admin-zones'
  if (name === 'admin-commandes') return route.name === 'admin-commandes'
  if (name === 'admin-parametres') return route.name === 'admin-parametres'
  return false
}

</script>
