// ===================================
// 3. API SERVICE
// File: src/services/api.js
// ===================================

import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import router from '@/router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  // Sans délai, un serveur figé laissait les boutons tourner indéfiniment
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})
let isHandlingUnauthorized = false

// Intercepteur de requête - Ajouter le token
api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Intercepteur de réponse - Gérer les erreurs
api.interceptors.response.use(
  (response) => {
    return response
  },
  async (error) => {
    const toastStore = useToastStore()

    // Pas de réponse : serveur injoignable, trop lent ou connexion coupée (hors annulation volontaire)
    if (!error.response) {
      if (error.code === 'ECONNABORTED' || error.code === 'ETIMEDOUT') {
        error.message = 'Le serveur met trop de temps à répondre. Réessayez dans quelques instants.'
        toastStore.erreur(error.message)
      } else if (!axios.isCancel(error)) {
        toastStore.erreur('Impossible de joindre le serveur. Vérifiez votre connexion internet.')
      }
      return Promise.reject(error)
    }

    // Erreur 401 - Session expirée, token révoqué ou compte suspendu
    if (error.response.status === 401) {
      const authStore = useAuthStore()
      const requestUrl = String(error.config?.url || '')
      const isAuthRequest =
        requestUrl.includes('/auth/login') ||
        requestUrl.includes('/auth/register') ||
        requestUrl.includes('/auth/logout')

      if (!isAuthRequest && authStore.token && !isHandlingUnauthorized) {
        isHandlingUnauthorized = true
        try {
          await authStore.logout({ callApi: false })
          toastStore.info(
            error.response.data?.message && error.response.data.message !== 'Unauthenticated.'
              ? error.response.data.message
              : 'Votre session a expiré. Veuillez vous reconnecter.'
          )

          const current = router.currentRoute.value
          if (current.name !== 'login') {
            await router.push({ name: 'login', query: { redirect: current.fullPath } })
          }
        } finally {
          isHandlingUnauthorized = false
        }
      }
    }

    // Erreur 403 - Vendeur dont le profil boutique est incomplet (ex. promu pendant sa session)
    if (error.response.status === 403 && error.response.data?.code === 'profil_vendeur_incomplet') {
      const authStore = useAuthStore()
      await authStore.fetchUser()
      if (router.currentRoute.value.name !== 'vendeur-profil-boutique') {
        await router.push({ name: 'vendeur-profil-boutique' })
      }
    }

    // Erreur 429 - Trop de requêtes
    if (error.response.status === 429) {
      toastStore.erreur(error.response.data?.message || 'Trop de requêtes, veuillez patienter quelques instants.')
    }

    return Promise.reject(error)
  }
)

const getCataloguePrefix = () => {
  const authStore = useAuthStore()
  return authStore.isVendeur ? '/vendeur/catalogue' : '/admin'
}

const getCommandesPrefix = () => {
  const authStore = useAuthStore()
  return authStore.isVendeur ? '/vendeur/commandes' : '/admin/commandes'
}

const fichierPdf = { responseType: 'blob', headers: { Accept: 'application/pdf, application/json' } }

// Téléchargement (responseType blob) : une erreur JSON arrive elle aussi en Blob,
// on la relit pour que messageErreur() retrouve le message du backend
export const lireErreurBlob = async (error) => {
  const data = error?.response?.data
  if (typeof Blob !== 'undefined' && data instanceof Blob) {
    try {
      error.response.data = JSON.parse(await data.text())
    } catch {
      error.response.data = {}
    }
  }
  return error
}

// Message lisible d'une erreur API : première erreur de validation, sinon message du backend
export const messageErreur = (error, fallback = 'Une erreur est survenue.') => {
  const erreursValidation = Object.values(error?.response?.data?.errors || {})
  const delaiDepasse = !error?.response && ['ECONNABORTED', 'ETIMEDOUT'].includes(error?.code)
  return erreursValidation[0]?.[0] || error?.response?.data?.message || (delaiDepasse ? error.message : null) || fallback
}

// Méthodes API
export default {
  // Auth
  auth: {
    login: (data) => api.post('/auth/login', data),
    register: (data) => api.post('/auth/register', data),
    logout: () => api.post('/auth/logout'),
    getUser: () => api.get('/auth/user'),
    updateProfile: (data) => api.put('/auth/profile', data),
    changePassword: (data) => api.post('/auth/change-password', data),
  },

  // Catégories
  categories: {
    getAll: () => api.get('/categories'),
    getOne: (slug) => api.get(`/categories/${slug}`),
  },

  // Produits
  produits: {
    getAll: (params) => api.get('/produits', { params }),
    getOne: (slug) => api.get(`/produits/${slug}`),
    getFeatured: () => api.get('/produits/featured'),
    getNouveautes: () => api.get('/produits/nouveautes'),
    getPromotions: () => api.get('/produits/promotions'),
    getSimilar: (slug) => api.get(`/produits/${slug}/similar`),
  },

  // Panier
  panier: {
    get: () => api.get('/panier'),
    add: (data) => api.post('/panier', data),
    update: (id, data) => api.put(`/panier/${id}`, data),
    remove: (id) => api.delete(`/panier/${id}`),
    clear: () => api.delete('/panier'),
    count: () => api.get('/panier/count'),
  },

  // Commandes
  commandes: {
    getAll: (params) => api.get('/commandes', { params }),
    getOne: (id) => api.get(`/commandes/${id}`),
    create: (data) => api.post('/commandes', data),
    update: (id, data) => api.put(`/commandes/${id}`, data),
    cancel: (id) => api.post(`/commandes/${id}/cancel`),
    facture: (id) => api.get(`/commandes/${id}/facture`, fichierPdf),
    calculateShipping: (data) => api.post('/commandes/calculate-shipping', data),
    stats: () => api.get('/commandes/stats'),
  },

  // Adresses
  adresses: {
    getAll: () => api.get('/adresses'),
    getOne: (id) => api.get(`/adresses/${id}`),
    create: (data) => api.post('/adresses', data),
    update: (id, data) => api.put(`/adresses/${id}`, data),
    remove: (id) => api.delete(`/adresses/${id}`),
    setPrincipal: (id) => api.post(`/adresses/${id}/set-principal`),
  },

  // Notifications
  notifications: {
    getAll: (params) => api.get('/notifications', { params }),
    markAsRead: (id) => api.post(`/notifications/${id}/mark-read`),
    markAllAsRead: () => api.post('/notifications/mark-all-read'),
    unreadCount: () => api.get('/notifications/unread-count'),
    remove: (id) => api.delete(`/notifications/${id}`),
    clearRead: () => api.delete('/notifications/clear-read'),
  },

  // Zones de livraison
  zones: {
    getAll: () => api.get('/zones-livraison'),
    byCity: (ville) => api.get(`/zones-livraison/ville/${ville}`),
    byCityForCommande: (ville) => api.get(`/zones-livraison/ville/${ville}/commande`),
    search: (data) => api.post('/zones-livraison/search', data),
  },

  // Livraison par quartier (tarifs définis par chaque vendeur)
  livraison: {
    quartiers: (params) => api.get('/livraison/quartiers', { params }),
    quartiersByVendeur: (vendeurId) => api.get(`/livraison/quartiers/vendeur/${vendeurId}`),
  },

  // Pages publiques des vendeurs
  vendeurs: {
    getOne: (id) => api.get(`/vendeurs/${id}`),
    conditions: () => api.get('/conditions-vendeur'),
  },

  // Espace vendeur
  vendeur: {
    stats: () => api.get('/vendeur/stats'),
    // Profil boutique : multipart (logo), envoyé en POST + _method=PUT
    profil: {
      get: () => api.get('/vendeur/profil'),
      update: (data) => api.post('/vendeur/profil', data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }),
    },
    livraison: {
      updateMinimum: (data) => api.put('/vendeur/livraison/minimum', data),
    },
    tarifs: {
      getAll: () => api.get('/vendeur/tarifs-livraison'),
      quartiers: () => api.get('/vendeur/tarifs-livraison/quartiers'),
      create: (data) => api.post('/vendeur/tarifs-livraison', data),
      update: (id, data) => api.put(`/vendeur/tarifs-livraison/${id}`, data),
      remove: (id) => api.delete(`/vendeur/tarifs-livraison/${id}`),
    },
  },

  // Admin
  admin: {
    dashboard: {
      stats: (params) => api.get('/admin/dashboard/stats', { params }),
    },
    commandes: {
      getAll: (params) => api.get(getCommandesPrefix(), { params }),
      getOne: (id) => api.get(`${getCommandesPrefix()}/${id}`),
      updateStatus: (id, data) => api.patch(`${getCommandesPrefix()}/${id}/status`, data),
      confirmPayment: (id, data) => api.post(`${getCommandesPrefix()}/${id}/confirm-payment`, data),
      assignVendeur: (id, data) => api.post(`/admin/commandes/${id}/assign-vendeur`, data),
      facture: (id) => api.get(`${getCommandesPrefix()}/${id}/facture`, fichierPdf),
    },
    rapports: {
      list: () => api.get('/admin/rapports'),
      apercu: (mois) => api.get('/admin/rapports/mensuel', { params: { mois, format: 'json' } }),
      telecharger: (mois, format) => api.get('/admin/rapports/mensuel', {
        params: { mois, format },
        responseType: 'blob',
        headers: { Accept: format === 'pdf' ? 'application/pdf, application/json' : 'text/csv, application/json' },
      }),
    },
    categories: {
      getAll: (params) => api.get(`${getCataloguePrefix()}/categories`, { params }),
      getOne: (id) => api.get(`${getCataloguePrefix()}/categories/${id}`),
      create: (data) => api.post(`${getCataloguePrefix()}/categories`, data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }),
      update: (id, data) => api.post(`${getCataloguePrefix()}/categories/${id}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }),
      remove: (id) => api.delete(`${getCataloguePrefix()}/categories/${id}`),
    },
    parametres: {
      getAll: (params) => api.get('/admin/parametres', { params }),
      getOne: (id) => api.get(`/admin/parametres/${id}`),
      create: (data) => api.post('/admin/parametres', data),
      update: (id, data) => api.put(`/admin/parametres/${id}`, data),
      remove: (id) => api.delete(`/admin/parametres/${id}`),
    },
    produits: {
      getAll: (params) => api.get(`${getCataloguePrefix()}/produits`, { params }),
      getOne: (id) => api.get(`${getCataloguePrefix()}/produits/${id}`),
      create: (data) => api.post(`${getCataloguePrefix()}/produits`, data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }),
      update: (id, data) => api.post(`${getCataloguePrefix()}/produits/${id}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }),
      remove: (id) => api.delete(`${getCataloguePrefix()}/produits/${id}`),
    },
    users: {
      getAll: (params) => api.get('/admin/users', { params }),
      getOne: (id) => api.get(`/admin/users/${id}`),
      updateStatus: (id, data) => api.patch(`/admin/users/${id}/status`, data),
      updateRole: (id, data) => api.patch(`/admin/users/${id}/role`, data),
      updateVedette: (id, data) => api.patch(`/admin/users/${id}/vedette`, data),
    },
    zones: {
      getAll: (params) => api.get(`${getCataloguePrefix()}/zones-livraison`, { params }),
      getOne: (id) => api.get(`${getCataloguePrefix()}/zones-livraison/${id}`),
      create: (data) => api.post(`${getCataloguePrefix()}/zones-livraison`, data),
      update: (id, data) => api.put(`${getCataloguePrefix()}/zones-livraison/${id}`, data),
      remove: (id) => api.delete(`${getCataloguePrefix()}/zones-livraison/${id}`),
    }
  }
}
