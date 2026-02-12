<!-- ===================================
ADMIN - GESTION DES PRODUITS
File: src/views/admin/AdminProduits.vue
=================================== -->

<template>
  <div class="admin-produits-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            Administration Produits
          </h1>
          <p class="text-gray-600 text-sm">
            Ajouter, modifier et supprimer les produits du catalogue.
          </p>
        </div>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="openCreate">
          Nouveau produit
        </Button>
      </div>

      <Card padding="md" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
            <div class="relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" :size="18" />
              <input
                v-model="filters.search"
                type="search"
                placeholder="Nom ou description..."
                class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
                @input="handleSearch"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
            <select v-model="filters.categorie_id" class="input">
              <option value="">Toutes</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.nom }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Disponibilité</label>
            <select v-model="filters.est_disponible" class="input">
              <option value="">Toutes</option>
              <option value="1">Disponibles</option>
              <option value="0">Indisponibles</option>
            </select>
          </div>

          <div class="md:col-span-4 flex flex-wrap gap-3">
            <Button variant="outline" @click="resetFilters">
              Réinitialiser
            </Button>
            <Button variant="primary" @click="applyFilters">
              Appliquer
            </Button>
          </div>
        </div>
      </Card>

      <Card v-if="showForm" padding="lg" class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-display text-xl font-bold text-gray-800">
            {{ isEditing ? 'Modifier le produit' : 'Ajouter un produit' }}
          </h2>
          <button class="text-sm text-gray-500 hover:text-gray-700" @click="closeForm">
            Fermer
          </button>
        </div>

        <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
            <input v-model="form.nom" type="text" class="input" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie *</label>
            <select v-model="form.categorie_id" class="input" required>
              <option value="">Sélectionner...</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.nom }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Stock *</label>
            <input v-model.number="form.stock_disponible" type="number" min="0" class="input" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prix (FCFA) *</label>
            <input v-model.number="form.prix_unitaire" type="number" min="0" step="0.01" class="input" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prix promo (FCFA)</label>
            <input
              v-model="form.prix_promo"
              type="number"
              min="0"
              step="0.01"
              class="input"
              :disabled="!form.promo_active"
              :class="!form.promo_active ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : ''"
            />
            <p class="text-xs text-gray-500 mt-1">Activez pour appliquer le prix promo</p>
          </div>

          <div class="md:col-span-2">
            <label class="inline-flex items-center gap-2">
              <input
                v-model="form.promo_active"
                type="checkbox"
                class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
              />
              <span class="text-sm text-gray-700">Activer le prix promotionnel</span>
            </label>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea v-model="form.description" rows="4" class="input resize-none"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Image principale</label>
            <input :key="fileInputKey" type="file" accept="image/*" class="input" @change="onImagePrincipale" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Images secondaires</label>
            <input :key="fileInputKey + 1" type="file" accept="image/*" multiple class="input" @change="onImagesSecondaires" />
          </div>

          <div class="md:col-span-2 flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2">
              <input
                v-model="form.est_disponible"
                type="checkbox"
                class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
              />
              <span class="text-sm text-gray-700">Produit disponible</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input
                v-model="form.est_vedette"
                type="checkbox"
                class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
              />
              <span class="text-sm text-gray-700">Produit vedette</span>
            </label>
          </div>

          <div class="md:col-span-2 flex gap-3">
            <Button type="submit" variant="primary" :loading="saving">
              {{ isEditing ? 'Enregistrer' : 'Créer le produit' }}
            </Button>
            <Button type="button" variant="outline" @click="closeForm">
              Annuler
            </Button>
          </div>

          <p v-if="formError" class="text-sm text-red-600 md:col-span-2">
            {{ formError }}
          </p>
        </form>
      </Card>

      <Card padding="none">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <div class="text-sm text-gray-600">
            {{ totalProduits }} produit{{ totalProduits > 1 ? 's' : '' }}
          </div>
          <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" @click="fetchProduits">
            Actualiser
          </Button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="text-left font-semibold px-4 py-3">Produit</th>
                <th class="text-left font-semibold px-4 py-3">Prix</th>
                <th class="text-left font-semibold px-4 py-3">Stock</th>
                <th class="text-left font-semibold px-4 py-3">Statut</th>
                <th class="text-right font-semibold px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr>
                <td colspan="5" class="p-6 text-center text-gray-500">Chargement...</td>
              </tr>
            </tbody>
            <tbody v-else-if="produits.length === 0">
              <tr>
                <td colspan="5" class="p-6 text-center text-gray-500">Aucun produit trouvé.</td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="produit in produits" :key="produit.id" class="border-t border-gray-100">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <img
                      :src="resolveImageUrl(produit.image_principale)"
                      :alt="produit.nom"
                      class="h-12 w-12 rounded-lg object-cover border"
                    />
                    <div>
                      <div class="font-semibold text-gray-800">{{ produit.nom }}</div>
                      <div class="text-xs text-gray-500">{{ produit.categorie?.nom || 'Sans catégorie' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="font-semibold text-gray-800">
                    {{ formatPrice(produit.prix_promo || produit.prix_unitaire) }} FCFA
                  </div>
                  <div v-if="produit.prix_promo" class="text-xs text-gray-400 line-through">
                    {{ formatPrice(produit.prix_unitaire) }} FCFA
                  </div>
                </td>
                <td class="px-4 py-3">{{ produit.stock_disponible }}</td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <span class="badge" :class="produit.est_disponible ? 'badge-success' : 'badge-danger'">
                      {{ produit.est_disponible ? 'Disponible' : 'Indisponible' }}
                    </span>
                    <span v-if="produit.est_vedette" class="badge badge-primary">Vedette</span>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(produit)">
                      Modifier
                    </Button>
                    <Button variant="danger" size="sm" :icon="Trash2" :icon-size="16" @click="deleteProduit(produit)">
                      Supprimer
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t border-gray-100 flex items-center justify-between">
          <div class="text-xs text-gray-500">
            Page {{ currentPage }} / {{ totalPages }}
          </div>
          <div class="flex gap-2">
            <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="changePage(currentPage - 1)">
              Précédent
            </Button>
            <Button variant="outline" size="sm" :disabled="currentPage >= totalPages" @click="changePage(currentPage + 1)">
              Suivant
            </Button>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { Plus, Search, Pencil, Trash2, RefreshCw } from 'lucide-vue-next'

const produits = ref([])
const categories = ref([])
const loading = ref(false)
const saving = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const formError = ref('')
const currentPage = ref(1)
const perPage = ref(12)
const totalProduits = ref(0)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalProduits.value / perPage.value))
})

const filters = ref({
  search: '',
  categorie_id: '',
  est_disponible: '',
})

  const form = ref({
    id: null,
    categorie_id: '',
    nom: '',
    description: '',
    prix_unitaire: '',
    prix_promo: '',
    promo_active: false,
    stock_disponible: 0,
    est_disponible: true,
    est_vedette: false,
  })

const imagePrincipale = ref(null)
const imagesSecondaires = ref([])
const fileInputKey = ref(0)

const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
const apiOrigin = (() => {
  try {
    return new URL(apiBase).origin
  } catch {
    return ''
  }
})()

const resolveImageUrl = (path) => {
  if (!path) return '/placeholder-product.jpg'
  if (path.startsWith('http') || path.startsWith('/')) return path
  return apiOrigin ? `${apiOrigin}/storage/${path}` : `/storage/${path}`
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price || 0)
}

const fetchCategories = async () => {
  try {
    const response = await api.categories.getAll()
    if (response.data.success) {
      categories.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur chargement catégories:', error)
  }
}

const fetchProduits = async () => {
  loading.value = true

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.categorie_id) params.categorie_id = filters.value.categorie_id
    if (filters.value.est_disponible !== '') params.est_disponible = filters.value.est_disponible

    const response = await api.admin.produits.getAll(params)

    if (response.data.success) {
      produits.value = response.data.data.data
      totalProduits.value = response.data.data.total
    }
  } catch (error) {
    console.error('Erreur chargement produits:', error)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
  fetchProduits()
}

const resetFilters = () => {
  filters.value.search = ''
  filters.value.categorie_id = ''
  filters.value.est_disponible = ''
  applyFilters()
}

let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 400)
}

const resetForm = () => {
  form.value = {
    id: null,
    categorie_id: '',
    nom: '',
    description: '',
    prix_unitaire: '',
    prix_promo: '',
    promo_active: false,
    stock_disponible: 0,
    est_disponible: true,
    est_vedette: false,
  }
  imagePrincipale.value = null
  imagesSecondaires.value = []
  fileInputKey.value += 2
  formError.value = ''
}

const openCreate = () => {
  resetForm()
  isEditing.value = false
  showForm.value = true
}

const openEdit = (produit) => {
  form.value = {
    id: produit.id,
    categorie_id: produit.categorie_id,
    nom: produit.nom,
    description: produit.description || '',
    prix_unitaire: produit.prix_unitaire,
    prix_promo: produit.prix_promo || '',
    promo_active: !!produit.prix_promo,
    stock_disponible: produit.stock_disponible,
    est_disponible: !!produit.est_disponible,
    est_vedette: !!produit.est_vedette,
  }
  imagePrincipale.value = null
  imagesSecondaires.value = []
  fileInputKey.value += 2
  isEditing.value = true
  showForm.value = true
  formError.value = ''
}

const closeForm = () => {
  showForm.value = false
  formError.value = ''
}

const onImagePrincipale = (event) => {
  imagePrincipale.value = event.target.files?.[0] || null
}

const onImagesSecondaires = (event) => {
  imagesSecondaires.value = event.target.files ? Array.from(event.target.files) : []
}

const buildFormData = () => {
  const data = new FormData()

  data.append('categorie_id', form.value.categorie_id)
  data.append('nom', form.value.nom)
  data.append('prix_unitaire', form.value.prix_unitaire)
  data.append('stock_disponible', form.value.stock_disponible)
  data.append('est_disponible', form.value.est_disponible ? 1 : 0)
  data.append('est_vedette', form.value.est_vedette ? 1 : 0)

  if (form.value.description) {
    data.append('description', form.value.description)
  }

  data.append('promo_active', form.value.promo_active ? 1 : 0)

  if (form.value.promo_active && form.value.prix_promo !== '' && form.value.prix_promo !== null) {
    data.append('prix_promo', form.value.prix_promo)
  }

  if (imagePrincipale.value) {
    data.append('image_principale', imagePrincipale.value)
  }

  imagesSecondaires.value.forEach((file) => {
    data.append('images_secondaires[]', file)
  })

  return data
}

const submitForm = async () => {
  saving.value = true
  formError.value = ''

  if (form.value.promo_active && (form.value.prix_promo === '' || form.value.prix_promo === null)) {
    formError.value = 'Veuillez renseigner un prix promo ou désactiver la promotion.'
    saving.value = false
    return
  }

  try {
    const data = buildFormData()
    let response

    if (isEditing.value && form.value.id) {
      data.append('_method', 'PUT')
      response = await api.admin.produits.update(form.value.id, data)
    } else {
      response = await api.admin.produits.create(data)
    }

    if (response.data.success) {
      showForm.value = false
      fetchProduits()
      resetForm()
    }
  } catch (error) {
    formError.value = error.response?.data?.message || 'Erreur lors de l\'enregistrement'
    console.error('Erreur sauvegarde produit:', error)
  } finally {
    saving.value = false
  }
}

const deleteProduit = async (produit) => {
  const confirmed = confirm(`Supprimer "${produit.nom}" ?`)
  if (!confirmed) return

  try {
    await api.admin.produits.remove(produit.id)
    fetchProduits()
  } catch (error) {
    console.error('Erreur suppression produit:', error)
    alert('Erreur lors de la suppression')
  }
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchProduits()
}

onMounted(() => {
  fetchCategories()
  fetchProduits()
})
</script>
