<!-- ===================================
ADMIN - GESTION DES CATEGORIES
File: src/views/admin/AdminCategories.vue
=================================== -->

<template>
  <div class="admin-categories-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            Administration Catégories
          </h1>
          <p class="text-gray-600 text-sm">
            Créer, modifier et organiser les catégories de produits.
          </p>
        </div>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="openCreate">
          Nouvelle catégorie
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
                placeholder="Nom de catégorie..."
                class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
                @input="handleSearch"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
            <select v-model="filters.est_actif" class="input">
              <option value="">Toutes</option>
              <option value="1">Actives</option>
              <option value="0">Inactives</option>
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
            {{ isEditing ? 'Modifier la catégorie' : 'Ajouter une catégorie' }}
          </h2>
          <button class="text-sm text-gray-500 hover:text-gray-700" @click="closeForm">
            Fermer
          </button>
        </div>

        <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
            <input v-model="form.nom" type="text" class="input" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ordre d'affichage</label>
            <input v-model.number="form.ordre_affichage" type="number" min="0" class="input" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea v-model="form.description" rows="3" class="input resize-none"></textarea>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Image</label>
            <input :key="fileInputKey" type="file" accept="image/*" class="input" @change="onImageChange" />
          </div>

          <div class="md:col-span-2 flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2">
              <input
                v-model="form.est_actif"
                type="checkbox"
                class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
              />
              <span class="text-sm text-gray-700">Catégorie active</span>
            </label>
          </div>

          <div class="md:col-span-2 flex gap-3">
            <Button type="submit" variant="primary" :loading="saving">
              {{ isEditing ? 'Enregistrer' : 'Créer la catégorie' }}
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
            {{ totalCategories }} catégorie{{ totalCategories > 1 ? 's' : '' }}
          </div>
          <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" @click="fetchCategories">
            Actualiser
          </Button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="text-left font-semibold px-4 py-3">Catégorie</th>
                <th class="text-left font-semibold px-4 py-3">Ordre</th>
                <th class="text-left font-semibold px-4 py-3">Statut</th>
                <th class="text-right font-semibold px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr>
                <td colspan="4" class="p-6 text-center text-gray-500">Chargement...</td>
              </tr>
            </tbody>
            <tbody v-else-if="categories.length === 0">
              <tr>
                <td colspan="4" class="p-6 text-center text-gray-500">Aucune catégorie trouvée.</td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="categorie in categories" :key="categorie.id" class="border-t border-gray-100">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <img
                      :src="resolveImageUrl(categorie.image)"
                      :alt="categorie.nom"
                      class="h-12 w-12 rounded-lg object-cover border"
                    />
                    <div>
                      <div class="font-semibold text-gray-800">{{ categorie.nom }}</div>
                      <div class="text-xs text-gray-500">{{ categorie.slug }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3">{{ categorie.ordre_affichage || 0 }}</td>
                <td class="px-4 py-3">
                  <span class="badge" :class="categorie.est_actif ? 'badge-success' : 'badge-danger'">
                    {{ categorie.est_actif ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(categorie)">
                      Modifier
                    </Button>
                    <Button variant="danger" size="sm" :icon="Trash2" :icon-size="16" @click="deleteCategorie(categorie)">
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

const categories = ref([])
const loading = ref(false)
const saving = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const formError = ref('')
const currentPage = ref(1)
const perPage = ref(15)
const totalCategories = ref(0)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalCategories.value / perPage.value))
})

const filters = ref({
  search: '',
  est_actif: '',
})

const form = ref({
  id: null,
  nom: '',
  description: '',
  ordre_affichage: 0,
  est_actif: true,
})

const imageFile = ref(null)
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

const fetchCategories = async () => {
  loading.value = true

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.est_actif !== '') params.est_actif = filters.value.est_actif

    const response = await api.admin.categories.getAll(params)
    if (response.data.success) {
      categories.value = response.data.data.data
      totalCategories.value = response.data.data.total
    }
  } catch (error) {
    console.error('Erreur chargement catégories:', error)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
  fetchCategories()
}

const resetFilters = () => {
  filters.value.search = ''
  filters.value.est_actif = ''
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
    nom: '',
    description: '',
    ordre_affichage: 0,
    est_actif: true,
  }
  imageFile.value = null
  fileInputKey.value += 1
  formError.value = ''
}

const openCreate = () => {
  resetForm()
  isEditing.value = false
  showForm.value = true
}

const openEdit = (categorie) => {
  form.value = {
    id: categorie.id,
    nom: categorie.nom,
    description: categorie.description || '',
    ordre_affichage: categorie.ordre_affichage || 0,
    est_actif: !!categorie.est_actif,
  }
  imageFile.value = null
  fileInputKey.value += 1
  isEditing.value = true
  showForm.value = true
  formError.value = ''
}

const closeForm = () => {
  showForm.value = false
  formError.value = ''
}

const onImageChange = (event) => {
  imageFile.value = event.target.files?.[0] || null
}

const buildFormData = () => {
  const data = new FormData()
  data.append('nom', form.value.nom)
  data.append('ordre_affichage', form.value.ordre_affichage || 0)
  data.append('est_actif', form.value.est_actif ? 1 : 0)

  if (form.value.description) {
    data.append('description', form.value.description)
  }

  if (imageFile.value) {
    data.append('image', imageFile.value)
  }

  return data
}

const submitForm = async () => {
  saving.value = true
  formError.value = ''

  try {
    const data = buildFormData()
    let response

    if (isEditing.value && form.value.id) {
      data.append('_method', 'PUT')
      response = await api.admin.categories.update(form.value.id, data)
    } else {
      response = await api.admin.categories.create(data)
    }

    if (response.data.success) {
      showForm.value = false
      fetchCategories()
      resetForm()
    }
  } catch (error) {
    formError.value = error.response?.data?.message || 'Erreur lors de l\'enregistrement'
    console.error('Erreur sauvegarde catégorie:', error)
  } finally {
    saving.value = false
  }
}

const deleteCategorie = async (categorie) => {
  const confirmed = confirm(`Supprimer "${categorie.nom}" ?`)
  if (!confirmed) return

  try {
    await api.admin.categories.remove(categorie.id)
    fetchCategories()
  } catch (error) {
    console.error('Erreur suppression catégorie:', error)
    alert('Erreur lors de la suppression')
  }
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchCategories()
}

onMounted(() => {
  fetchCategories()
})
</script>
