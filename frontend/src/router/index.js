// ===================================
// 2. ROUTER - Configuration
// File: src/router/index.js
// ===================================

import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Lazy loading des composants
const Home = () => import('@/views/Home.vue')
const Produits = () => import('@/views/Produits.vue')
const ProduitDetail = () => import('@/views/ProduitDetail.vue')
const Panier = () => import('@/views/Panier.vue')
const Checkout = () => import('@/views/Checkout.vue')
const MesCommandes = () => import('@/views/MesCommandes.vue')
const Profil = () => import('@/views/Profil.vue')
const Login = () => import('@/views/Login.vue')
const Register = () => import('@/views/Register.vue')

const routes = [
  {
    path: '/',
    name: 'home',
    component: Home,
    meta: { title: 'Accueil' }
  },
  {
    path: '/produits',
    name: 'produits',
    component: Produits,
    meta: { title: 'Nos Produits' }
  },
  {
    path: '/produits/:slug',
    name: 'produit-detail',
    component: ProduitDetail,
    meta: { title: 'Détail Produit' }
  },
  {
    path: '/panier',
    name: 'panier',
    component: Panier,
    meta: { title: 'Mon Panier', requiresAuth: true }
  },
  {
    path: '/commander',
    name: 'checkout',
    component: Checkout,
    meta: { title: 'Commander', requiresAuth: true }
  },
  {
    path: '/mes-commandes',
    name: 'mes-commandes',
    component: MesCommandes,
    meta: { title: 'Mes Commandes', requiresAuth: true }
  },
  {
    path: '/profil',
    name: 'profil',
    component: Profil,
    meta: { title: 'Mon Profil', requiresAuth: true }
  },
  {
    path: '/connexion',
    name: 'login',
    component: Login,
    meta: { title: 'Connexion', guest: true }
  },
  {
    path: '/inscription',
    name: 'register',
    component: Register,
    meta: { title: 'Inscription', guest: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0 }
  }
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const isAuthenticated = authStore.isAuthenticated

  // Mettre à jour le titre de la page
  document.title = `${to.meta.title || 'Choukrane'} - Pâtisserie`

  // Routes nécessitant l'authentification
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }

  // Routes réservées aux invités
  if (to.meta.guest && isAuthenticated) {
    next({ name: 'home' })
    return
  }

  next()
})

export default router