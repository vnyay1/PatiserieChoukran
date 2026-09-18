// ===================================
// COMPOSABLE NAVIGATION (en-tête, menu mobile, barre du bas)
// File: src/composables/useNavigation.js
// ===================================
// Une seule définition des liens par rôle, partagée par Header et BottomNav :
// - principaux : toujours visibles dans l'en-tête desktop ;
// - gestion    : regroupés dans un menu (« Administration » / « Ma boutique »),
//                pour ne pas aligner dix liens dans l'en-tête ;
// - barreBas   : cinq onglets au plus sur mobile.

import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  Home, ShoppingBag, ShoppingCart, Package, User, LayoutDashboard, Settings,
  Truck, LayoutGrid, Users, BarChart3, MapPinned, Tags, Store,
} from 'lucide-vue-next'

const LIENS = {
  home: { name: 'home', label: 'Accueil', court: 'Accueil', to: '/', icone: Home },
  produits: { name: 'produits', label: 'Nos produits', court: 'Produits', to: '/produits', icone: ShoppingBag },
  panier: { name: 'panier', label: 'Panier', court: 'Panier', to: '/panier', icone: ShoppingCart },
  commandes: { name: 'commandes', label: 'Mes commandes', court: 'Commandes', to: '/mes-commandes', icone: Package },
  profil: { name: 'profil', label: 'Mon compte', court: 'Compte', to: '/profil', icone: User },
  'admin-dashboard': { name: 'admin-dashboard', label: 'Tableau de bord', court: 'Admin', to: '/admin/dashboard', icone: LayoutDashboard },
  'admin-commandes': { name: 'admin-commandes', label: 'Commandes', court: 'Commandes', to: '/admin/commandes', icone: Package },
  'admin-produits': { name: 'admin-produits', label: 'Produits', court: 'Catalogue', to: '/admin/produits', icone: LayoutGrid },
  'admin-categories': { name: 'admin-categories', label: 'Catégories', court: 'Catégories', to: '/admin/categories', icone: Tags },
  'admin-users': { name: 'admin-users', label: 'Utilisateurs', court: 'Utilisateurs', to: '/admin/users', icone: Users },
  'admin-quartiers': { name: 'admin-quartiers', label: 'Quartiers', court: 'Quartiers', to: '/admin/quartiers', icone: MapPinned },
  'admin-rapports': { name: 'admin-rapports', label: 'Rapports mensuels', court: 'Rapports', to: '/admin/rapports', icone: BarChart3 },
  'admin-parametres': { name: 'admin-parametres', label: 'Paramètres', court: 'Réglages', to: '/admin/parametres', icone: Settings },
  'vendeur-livraison': { name: 'vendeur-livraison', label: 'Ma livraison', court: 'Livraison', to: '/vendeur/livraison', icone: Truck },
  'vendeur-profil-boutique': { name: 'vendeur-profil-boutique', label: 'Ma boutique', court: 'Boutique', to: '/vendeur/profil-boutique', icone: Store },
}

// Entrées dont plusieurs pages sont « actives » ; les autres correspondent à une seule route
const ROUTES_ACTIVES = {
  produits: ['produits', 'produit-detail'],
  panier: ['panier', 'checkout'],
  commandes: ['mes-commandes', 'commande-detail'],
}

// Onglet « Admin » de la barre du bas : regroupe les pages sans onglet propre
const PAGES_ADMIN_SANS_ONGLET = ['admin-dashboard', 'admin-produits', 'admin-users', 'admin-quartiers', 'admin-categories', 'admin-rapports']
const PAGES_CATALOGUE_VENDEUR = ['admin-produits', 'admin-categories']

const liste = (...noms) => noms.map((nom) => LIENS[nom])

export const useNavigation = () => {
  const route = useRoute()
  const authStore = useAuthStore()

  const liensPrincipaux = computed(() => {
    if (authStore.isAdmin || authStore.isVendeur) return liste('home', 'produits', 'admin-commandes')
    if (authStore.isClient) return liste('home', 'produits', 'commandes')
    return liste('home', 'produits')
  })

  const menuGestion = computed(() => {
    if (authStore.isAdmin) {
      return {
        titre: 'Administration',
        liens: liste('admin-dashboard', 'admin-produits', 'admin-categories', 'admin-users', 'admin-quartiers', 'admin-rapports', 'admin-parametres'),
      }
    }
    if (authStore.isVendeur) {
      return {
        titre: 'Ma boutique',
        liens: [
          { ...LIENS['admin-produits'], label: 'Mes produits' },
          { ...LIENS['admin-categories'], label: 'Mes catégories' },
          LIENS['vendeur-livraison'],
          { ...LIENS['vendeur-profil-boutique'], label: 'Profil de la boutique' },
        ],
      }
    }
    return null
  })

  const liensBarreBas = computed(() => {
    if (authStore.isAdmin) {
      return [
        LIENS.home,
        LIENS.produits,
        { ...LIENS['admin-dashboard'], actifs: PAGES_ADMIN_SANS_ONGLET },
        LIENS['admin-commandes'],
        LIENS['admin-parametres'],
      ]
    }
    if (authStore.isVendeur) {
      return [
        LIENS.home,
        LIENS['admin-commandes'],
        { ...LIENS['admin-produits'], actifs: PAGES_CATALOGUE_VENDEUR },
        LIENS['vendeur-livraison'],
        LIENS['vendeur-profil-boutique'],
      ]
    }
    return liste('home', 'produits', 'panier', 'commandes', 'profil')
  })

  const estActif = (lien) => {
    const noms = lien.actifs || ROUTES_ACTIVES[lien.name] || [lien.name]
    return noms.includes(route.name)
  }

  const gestionActive = computed(() => Boolean(menuGestion.value?.liens.some(estActif)))

  return { liensPrincipaux, menuGestion, liensBarreBas, estActif, gestionActive }
}
