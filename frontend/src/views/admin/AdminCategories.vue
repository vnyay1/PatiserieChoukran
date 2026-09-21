<!-- ===================================
ADMIN - GESTION DES CATEGORIES
File: src/views/admin/AdminCategories.vue
=================================== -->

<template>
  <div class="admin-categories-page pb-6">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1>
            Catégories
          </h1>
          <p class="text-gray-600 text-sm">
            Voir toutes les catégories disponibles et en ajouter de nouvelles.
          </p>
        </div>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="openCreate">
          Nouvelle catégorie
        </Button>
      </div>

      <Card padding="md" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div class="md:col-span-2">
            <label for="admin-categories-1" class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
            <div class="relative">
              <Search class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500" :size="18" aria-hidden="true" />
              <input
                id="admin-categories-1"
                v-model="filters.search"
                type="search"
                placeholder="Nom de catégorie..."
                class="input pl-10"
                @input="handleSearch"
              />
            </div>
          </div>

          <div>
            <label for="admin-categories-2" class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
            <select id="admin-categories-2" v-model="filters.est_actif" class="input">
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

        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submitForm">
          <div>
            <label for="admin-categories-3" class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
            <input id="admin-categories-3" v-model="form.nom" type="text" class="input" required />
          </div>

          <div>
            <label for="admin-categories-4" class="block text-sm font-medium text-gray-700 mb-2">Ordre d'affichage</label>
            <input id="admin-categories-4" v-model.number="form.ordre_affichage" type="number" min="0" class="input" />
          </div>

          <div class="md:col-span-2">
            <label for="admin-categories-5" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea id="admin-categories-5" v-model="form.description" rows="3" class="input resize-none"></textarea>
          </div>

          <div class="md:col-span-2">
            <label for="admin-categories-6" class="block text-sm font-medium text-gray-700 mb-2">Image</label>
            <div class="flex items-center gap-4">
              <img
                v-if="apercuImage"
                loading="lazy"
                :src="apercuImage"
                alt="Aperçu de l'image de la catégorie"
                class="h-20 w-20 flex-shrink-0 rounded-lg object-cover border"
                @error="onImageError"
              />
              <input
                id="admin-categories-6"
                :key="fileInputKey"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="input"
                @change="onImageChange"
              />
            </div>
            <p class="text-xs text-gray-500 mt-1">
              JPEG, PNG ou WebP, {{ TAILLE_MAX_IMAGE_MO }} Mo maximum. Affichée sur la page d'accueil.
            </p>
          </div>

          <div class="md:col-span-2 flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2">
              <input
                v-model="form.est_actif"
                type="checkbox"
                class="h-5 w-5 flex-shrink-0 rounded"
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

          <p v-if="formError" role="alert" class="text-sm font-medium text-red-700 md:col-span-2">
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

        <div class="overflow-x-auto" role="region" aria-label="Liste des catégories" tabindex="0">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th scope="col" class="text-left font-semibold px-4 py-3">Catégorie</th>
                <th scope="col" class="text-left font-semibold px-4 py-3">Ordre</th>
                <th scope="col" class="text-left font-semibold px-4 py-3">Statut</th>
                <th scope="col" class="text-right font-semibold px-4 py-3">Actions</th>
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
                      loading="lazy"
                      :src="resolveImageUrl(categorie.image)" :alt="categorie.nom"
                      class="h-12 w-12 rounded-lg object-cover border"
                      @error="onImageError"
                    />
                    <div>
                      <div class="font-semibold text-gray-800">{{ categorie.nom }}</div>
                      <div class="text-xs text-gray-500">{{ categorie.slug }}</div>
                      <div v-if="categorie.createur?.nom_complet" class="text-xs text-gray-500">
                        Ajoutée par: {{ categorie.createur.nom_complet }}
                      </div>
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
                    <Button
                      v-if="canManageCategorie(categorie)"
                      variant="outline"
                      size="sm"
                      :icon="Pencil"
                      :icon-size="16"
                      @click="openEdit(categorie)"
                    >
                      Modifier
                    </Button>
                    <Button
                      v-if="canManageCategorie(categorie)"
                      variant="danger"
                      size="sm"
                      :icon="Trash2"
                      :icon-size="16"
                      @click="deleteCategorie(categorie)"
                    >
                      Supprimer
                    </Button>
                    <span v-else class="text-xs text-gray-500">Lecture seule</span>
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import api, { messageErreur } from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { Plus, Search, Pencil, Trash2, RefreshCw } from 'lucide-vue-next'
import { resolveImageUrl, onImageError, verifierImage, TAILLE_MAX_IMAGE_MO } from '@/utils/images'

const authStore = useAuthStore()
const toastStore = useToastStore()
const { confirmer } = useConfirm()
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
// Image déjà enregistrée (édition) et aperçu local du fichier choisi
const imageActuelle = ref(null)
const apercuLocal = ref(null)
const apercuImage = computed(() => apercuLocal.value || (imageActuelle.value ? resolveImageUrl(imageActuelle.value) : null))

const libererApercu = () => {
  if (apercuLocal.value) {
    URL.revokeObjectURL(apercuLocal.value)
    apercuLocal.value = null
  }
}

const canManageCategorie = (categorie) => {
  if (authStore.isAdmin) return true
  return Number(categorie?.created_by_user_id || 0) === Number(authStore.user?.id || 0)
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
  imageActuelle.value = null
  libererApercu()
  fileInputKey.value += 1
  formError.value = ''
}

const openCreate = () => {
  resetForm()
  isEditing.value = false
  showForm.value = true
}

const openEdit = (categorie) => {
  if (!canManageCategorie(categorie)) {
    return
  }

  form.value = {
    id: categorie.id,
    nom: categorie.nom,
    description: categorie.description || '',
    ordre_affichage: categorie.ordre_affichage || 0,
    est_actif: !!categorie.est_actif,
  }
  imageFile.value = null
  imageActuelle.value = categorie.image || null
  libererApercu()
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
  const fichier = event.target.files?.[0] || null
  libererApercu()

  // Refus immédiat avec un message clair plutôt qu'un échec à l'enregistrement
  const erreur = verifierImage(fichier)
  if (erreur) {
    formError.value = erreur
    imageFile.value = null
    fileInputKey.value += 1
    return
  }

  formError.value = ''
  imageFile.value = fichier
  apercuLocal.value = fichier ? URL.createObjectURL(fichier) : null
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
      toastStore.succes(isEditing.value ? 'Catégorie mise à jour.' : 'Catégorie créée.')
      showForm.value = false
      fetchCategories()
      resetForm()
    }
  } catch (error) {
    formError.value = messageErreur(error, 'Erreur lors de l\'enregistrement.')
  } finally {
    saving.value = false
  }
}

const deleteCategorie = async (categorie) => {
  if (!canManageCategorie(categorie)) {
    return
  }

  const confirmed = await confirmer({
    titre: 'Supprimer la catégorie',
    message: `La catégorie « ${categorie.nom} » sera supprimée.`,
    libelleConfirmer: 'Supprimer',
    danger: true,
  })
  if (!confirmed) return

  try {
    await api.admin.categories.remove(categorie.id)
    toastStore.succes('Catégorie supprimée.')
    fetchCategories()
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Erreur lors de la suppression.'))
  }
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchCategories()
}

onBeforeUnmount(libererApercu)

onMounted(() => {
  fetchCategories()
})
</script>
