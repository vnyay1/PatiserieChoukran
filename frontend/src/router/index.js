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
const PaiementRetour = () => import('@/views/PaiementRetour.vue')
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
const AdminQuartiers = () => import('@/views/admin/AdminQuartiers.vue')
const MaLivraison = () => import('@/views/vendeur/MaLivraison.vue')
const AdminRapports = () => import('@/views/admin/AdminRapports.vue')
const ProfilBoutique = () => import('@/views/vendeur/ProfilBoutique.vue')
const VendeurProfil = () => import('@/views/VendeurProfil.vue')
const NotFound = () => import('@/views/NotFound.vue')

// Pages qu'un vendeur au profil boutique incomplet peut encore ouvrir
const ROUTES_PROFIL_INCOMPLET = ['vendeur-profil-boutique', 'not-found']

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
    path: '/vendeurs/:id',
    name: 'vendeur-profil',
    component: VendeurProfil,
    meta: { title: 'Boutique' }
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
    // Page de retour configurée comme callback NotchPay (NOTCHPAY_CALLBACK_URL)
    path: '/paiement/retour',
    name: 'paiement-retour',
    component: PaiementRetour,
    meta: { title: 'Paiement', requiresAuth: true }
  },
  {
    // Ancien chemin de callback, conservé si NOTCHPAY_CALLBACK_URL y pointe
    path: '/payments/callback',
    redirect: (to) => ({ name: 'paiement-retour', query: to.query }),
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
    path: '/admin/quartiers',
    name: 'admin-quartiers',
    component: AdminQuartiers,
    meta: { title: 'Quartiers', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/vendeur/livraison',
    name: 'vendeur-livraison',
    component: MaLivraison,
    meta: { title: 'Ma livraison', requiresAuth: true, requiresVendeur: true }
  },
  {
    path: '/admin/rapports',
    name: 'admin-rapports',
    component: AdminRapports,
    meta: { title: 'Rapports mensuels', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/vendeur/profil-boutique',
    name: 'vendeur-profil-boutique',
    component: ProfilBoutique,
    meta: { title: 'Ma boutique', requiresAuth: true, requiresVendeur: true }
  },
  {
    // Toute URL inconnue : page 404 plutôt qu'un écran vide
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFound,
    meta: { title: 'Page introuvable' }
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

  // Vendeur au profil boutique incomplet : il doit le compléter avant tout le reste
  if (isVendeur && authStore.user?.profil_vendeur_complet === false && !ROUTES_PROFIL_INCOMPLET.includes(to.name)) {
    next({ name: 'vendeur-profil-boutique' })
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
