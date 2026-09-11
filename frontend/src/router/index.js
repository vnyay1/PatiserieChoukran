// ===================================
// 2. ROUTER - Configuration
// File: src/router/index.js
// ===================================

import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Lazy loading des composants
const Home = () => import('@/views/Home.vue')
const Produits = () => import('@/views/Produits.vue')
const InfosPratiques = () => import('@/views/InfosPratiques.vue')
const ProduitDetail = () => import('@/views/ProduitDetail.vue')
const Panier = () => import('@/views/Panier.vue')
const Checkout = () => import('@/views/Checkout.vue')
const MesCommandes = () => import('@/views/MesCommandes.vue')
const CommandeDetail = () => import('@/views/CommandeDetail.vue')
const Notifications = () => import('@/views/Notifications.vue')
const Profil = () => import('@/views/Profil.vue')
const Login = () => import('@/views/Login.vue')
const Register = () => import('@/views/Register.vue')
const AdminDashboard = () => import('@/views/admin/AdminDashboard.vue')
const AdminCategories = () => import('@/views/admin/AdminCategories.vue')
const AdminCommandes = () => import('@/views/admin/AdminCommandes.vue')
const AdminParametres = () => import('@/views/admin/AdminParametres.vue')
const AdminProduits = () => import('@/views/admin/AdminProduits.vue')
const AdminUsers = () => import('@/views/admin/AdminUsers.vue')
const AdminZones = () => import('@/views/admin/AdminZones.vue')
const AdminTarifsLivraison = () => import('@/views/admin/AdminTarifsLivraison.vue')

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
    path: '/infos-pratiques',
    name: 'infos-pratiques',
    component: InfosPratiques,
    meta: { title: 'Nous Contacter & Horaires', mobileOnly: true }
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
    path: '/mes-commandes/:id',
    name: 'commande-detail',
    component: CommandeDetail,
    meta: { title: 'Détail Commande', requiresAuth: true }
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: Notifications,
    meta: { title: 'Notifications', requiresAuth: true }
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
  },
  {
    path: '/admin',
    redirect: { name: 'admin-dashboard' },
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: AdminDashboard,
    meta: { title: 'Dashboard Admin', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/categories',
    name: 'admin-categories',
    component: AdminCategories,
    meta: { title: 'Administration Catégories', requiresAuth: true, requiresCatalogueManager: true }
  },
  {
    path: '/admin/parametres',
    name: 'admin-parametres',
    component: AdminParametres,
    meta: { title: 'Paramètres du site', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/commandes',
    name: 'admin-commandes',
    component: AdminCommandes,
    meta: { title: 'Gestion Commandes', requiresAuth: true, requiresCommandesManager: true }
  },
  {
    path: '/admin/produits',
    name: 'admin-produits',
    component: AdminProduits,
    meta: { title: 'Administration Produits', requiresAuth: true, requiresCatalogueManager: true }
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: AdminUsers,
    meta: { title: 'Administration Utilisateurs', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/zones-livraison',
    name: 'admin-zones',
    component: AdminZones,
    meta: { title: 'Administration Zones de livraison', requiresAuth: true, requiresCatalogueManager: true }
  },
  {
    path: '/admin/tarifs-livraison',
    name: 'admin-tarifs',
    component: AdminTarifsLivraison,
    meta: { title: 'Mes tarifs de livraison', requiresAuth: true, requiresVendeur: true }
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
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  const isAuthenticated = authStore.isAuthenticated
  const isAdmin = authStore.isAdmin
  const isVendeur = authStore.isVendeur
  const canManageCatalogue = authStore.canManageCatalogue
  const canManageCommandes = isAdmin || isVendeur

  // Mettre à jour le titre de la page
  document.title = `${to.meta.title || 'Choukrane'} - Pâtisserie`

  // Routes nécessitant l'authentification
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }

  // Routes nécessitant un rôle admin
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next({ name: 'home' })
    return
  }

  // Routes réservées aux vendeurs
  if (to.meta.requiresVendeur && !isVendeur) {
    next({ name: 'home' })
    return
  }

  // Routes de gestion commandes (admin + vendeur)
  if (to.meta.requiresCommandesManager && !canManageCommandes) {
    next({ name: 'home' })
    return
  }

  // Routes de gestion catalogue (admin + vendeur)
  if (to.meta.requiresCatalogueManager && !canManageCatalogue) {
    next({ name: 'home' })
    return
  }

  // Empêcher admin/vendeur d'accéder aux pages client de commande
  if ((isAdmin || isVendeur) && ['panier', 'checkout', 'mes-commandes', 'commande-detail'].includes(to.name)) {
    next({ name: isAdmin ? 'admin-dashboard' : 'admin-commandes' })
    return
  }

  // Routes réservées aux invités
  if (to.meta.guest && isAuthenticated) {
    next({ name: 'home' })
    return
  }

  // Page mobile uniquement
  if (to.meta.mobileOnly && typeof window !== 'undefined' && window.matchMedia('(min-width: 768px)').matches) {
    next({ name: 'home' })
    return
  }

  next()
})

export default router
