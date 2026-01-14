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
          v-if="item.name === 'panier' && panierCount > 0"
          class="absolute top-1 right-1/4 bg-gold-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
        >
          {{ panierCount }}
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
import { Home, ShoppingBag, ShoppingCart, Package, User } from 'lucide-vue-next'

const route = useRoute()
const panierStore = usePanierStore()

const panierCount = computed(() => panierStore.itemCount)

const navItems = [
  { name: 'home', label: 'Accueil', icon: Home, to: '/' },
  { name: 'produits', label: 'Produits', icon: ShoppingBag, to: '/produits' },
  { name: 'panier', label: 'Panier', icon: ShoppingCart, to: '/panier' },
  { name: 'commandes', label: 'Commandes', icon: Package, to: '/mes-commandes' },
  { name: 'profil', label: 'Profil', icon: User, to: '/profil' },
]

const isActive = (name) => {
  if (name === 'home') return route.name === 'home'
  if (name === 'produits') return route.name === 'produits' || route.name === 'produit-detail'
  if (name === 'panier') return route.name === 'panier' || route.name === 'checkout'
  if (name === 'commandes') return route.name === 'mes-commandes'
  if (name === 'profil') return route.name === 'profil'
  return false
}
</script>