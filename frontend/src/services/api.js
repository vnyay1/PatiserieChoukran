// ===================================
// 3. API SERVICE
// File: src/services/api.js
// ===================================

import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

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
  (error) => {
    if (error.response) {
      // Erreur 401 - Non authentifié
      if (error.response.status === 401) {
        const authStore = useAuthStore()
        authStore.logout()
        router.push({ name: 'login' })
      }

      // Erreur 403 - Non autorisé
      if (error.response.status === 403) {
        console.error('Accès non autorisé')
      }

      // Erreur 429 - Too Many Requests
      if (error.response.status === 429) {
        console.error('Trop de requêtes, veuillez patienter')
      }
    }

    return Promise.reject(error)
  }
)

// Méthodes API
export default {
  // Auth
  auth: {
    login: (data) => api.post('/auth/login', data),
    register: (data) => api.post('/auth/register', data),
    logout: () => api.post('/auth/logout'),
    getUser: () => api.get('/auth/user'),
    updateProfile: (data) => api.put('/auth/profile', data),
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
    cancel: (id) => api.post(`/commandes/${id}/cancel`),
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
  },

  // Zones de livraison
  zones: {
    getAll: () => api.get('/zones-livraison'),
    byCity: (ville) => api.get(`/zones-livraison/ville/${ville}`),
    search: (data) => api.post('/zones-livraison/search', data),
  }
}