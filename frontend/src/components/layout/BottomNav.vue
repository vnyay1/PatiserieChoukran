<!-- ===================================
1. BOTTOM NAVIGATION (Mobile)
File: src/components/layout/BottomNav.vue
=================================== -->

<template>
  <nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur border-t border-gray-100 safe-bottom z-50 md:hidden">
    <div class="flex justify-around items-center h-16 px-2">
      <router-link
        v-for="item in navItems"
        :key="item.name"
        :to="item.to"
        class="flex flex-col items-center justify-center flex-1 h-full touch-target relative"
        :class="isActive(item.name) ? 'text-gold-600' : 'text-gray-500'"
      >
        <!-- Badge pour le panier -->
        <span
          v-if="authStore.isAuthenticated && item.name === 'panier' && panierCount > 0"
          class="absolute top-1 right-1/4 bg-gold-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
        >
          {{ panierCount }}
        </span>
        <span
          v-if="showVendeurCommandesBadge(item.name)"
          class="absolute top-1 right-1/4 bg-gold-600 text-white text-xs rounded-full h-5 min-w-5 px-1 flex items-center justify-center font-bold"
        >
          {{ formatBadgeCount(vendeurCommandesCount) }}
        </span>

        <component :is="item.icon" :size="24" :stroke-width="isActive(item.name) ? 2.5 : 2" />
        <span class="text-[11px] mt-1 font-semibold">{{ item.label }}</span>
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { usePanierStore } from '@/stores/panier'
import { useAuthStore } from '@/stores/auth'
import { useVendeurCommandesBadge } from '@/composables/useVendeurCommandesBadge'
import { Home, ShoppingBag, ShoppingCart, Package, User, Shield, Settings, Truck } from 'lucide-vue-next'

const route = useRoute()
const panierStore = usePanierStore()
const authStore = useAuthStore()
const { vendeurCommandesCount, formatBadgeCount, showVendeurCommandesBadge } = useVendeurCommandesBadge()

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

  if (authStore.isVendeur) {
    return [
      { name: 'home', label: 'Accueil', icon: Home, to: '/' },
      { name: 'produits', label: 'Produits', icon: ShoppingBag, to: '/produits' },
      { name: 'admin-commandes', label: 'Commandes', icon: Package, to: '/admin/commandes' },
      { name: 'admin-produits', label: 'Catalogue', icon: Shield, to: '/admin/produits' },
      { name: 'vendeur-livraison', label: 'Livraison', icon: Truck, to: '/vendeur/livraison' },
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

// Entrées dont plusieurs pages sont « actives » ; les autres correspondent à une seule route
const ROUTES_ACTIVES = {
  produits: ['produits', 'produit-detail'],
  panier: ['panier', 'checkout'],
  commandes: ['mes-commandes', 'commande-detail'],
  // L'onglet Admin regroupe les pages d'administration sans onglet propre
  'admin-dashboard': ['admin-dashboard', 'admin-produits', 'admin-users', 'admin-quartiers', 'admin-categories', 'admin-rapports'],
}

const isActive = (name) => (ROUTES_ACTIVES[name] || [name]).includes(route.name)

</script>
