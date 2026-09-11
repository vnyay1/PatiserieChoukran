<!-- ===================================
ADMIN - GESTION DES ZONES DE LIVRAISON
File: src/views/admin/AdminZones.vue
=================================== -->

<template>
  <div class="admin-zones-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            Administration Zones de livraison
          </h1>
          <p class="text-gray-600 text-sm">
            Créer, modifier et gérer les zones de livraison.
          </p>
        </div>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="openCreate">
          Nouvelle zone
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
                placeholder="Nom de zone ou ville..."
                class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
                @input="handleSearch"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
            <select v-model="filters.ville" class="input">
              <option value="">Toutes</option>
              <option v-for="v in villesDisponibles" :key="v" :value="v">{{ v }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
            <select v-model="filters.est_active" class="input">
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
            {{ isEditing ? 'Modifier la zone' : 'Ajouter une zone' }}
          </h2>
          <button class="text-sm text-gray-500 hover:text-gray-700" @click="closeForm">
            Fermer
          </button>
        </div>

        <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom de zone *</label>
            <input v-model="form.nom_zone" type="text" class="input" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
            <input v-model="form.ville" type="text" class="input" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tarif (FCFA) *</label>
            <input v-model.number="form.tarif_livraison" type="number" min="0" step="0.01" class="input" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Délai min (h) </label>
            <input v-model.number="form.delai_livraison_min" type="number" min="0" class="input" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Délai max (h) </label>
            <input v-model.number="form.delai_livraison_max" type="number" min="0" class="input" required />
          </div>

          <div class="md:col-span-2">
            <label class="inline-flex items-center gap-2">
              <input
                v-model="form.est_active"
                type="checkbox"
                class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
              />
              <span class="text-sm text-gray-700">Zone active</span>
            </label>
          </div>

          <div class="md:col-span-2 flex gap-3">
            <Button type="submit" variant="primary" :loading="saving">
              {{ isEditing ? 'Enregistrer' : 'Créer la zone' }}
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
            {{ totalZones }} zone{{ totalZones > 1 ? 's' : '' }}
          </div>
          <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" @click="fetchZones">
            Actualiser
          </Button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="text-left font-semibold px-4 py-3">Zone</th>
                <th class="text-left font-semibold px-4 py-3">Tarif</th>
                <th class="text-left font-semibold px-4 py-3">Délai</th>
                <th class="text-left font-semibold px-4 py-3">Statut</th>
                <th class="text-right font-semibold px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr>
                <td colspan="5" class="p-6 text-center text-gray-500">Chargement...</td>
              </tr>
            </tbody>
            <tbody v-else-if="zones.length === 0">
              <tr>
                <td colspan="5" class="p-6 text-center text-gray-500">Aucune zone trouvée.</td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="zone in zones" :key="zone.id" class="border-t border-gray-100">
                <td class="px-4 py-3">
                  <div class="font-semibold text-gray-800">{{ zone.nom_zone }}</div>
                  <div class="text-xs text-gray-500">{{ zone.ville }}</div>
                </td>
                <td class="px-4 py-3">{{ formatPrice(zone.tarif_livraison) }} FCFA</td>
                <td class="px-4 py-3">{{ zone.delai_livraison_min }} - {{ zone.delai_livraison_max }} h</td>
                <td class="px-4 py-3">
                  <span class="badge" :class="zone.est_active ? 'badge-success' : 'badge-danger'">
                    {{ zone.est_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(zone)">
                      Modifier
                    </Button>
                    <Button variant="danger" size="sm" :icon="Trash2" :icon-size="16" @click="deleteZone(zone)">
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
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { Plus, Search, Pencil, Trash2, RefreshCw } from 'lucide-vue-next'

const toastStore = useToastStore()
const { confirmer } = useConfirm()

const zones = ref([])
const villesDisponibles = ref([])
const loading = ref(false)
const saving = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const formError = ref('')
const currentPage = ref(1)
const perPage = ref(15)
const totalZones = ref(0)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalZones.value / perPage.value))
})

const filters = ref({
  search: '',
  ville: '',
  est_active: '',
})

const form = ref({
  id: null,
  nom_zone: '',
  ville: '',
  tarif_livraison: 0,
  delai_livraison_min: 0,
  delai_livraison_max: 0,
  est_active: true,
})

const formatPrice = (value) => {
  return new Intl.NumberFormat('fr-FR').format(value || 0)
}

const fetchVilles = async () => {
  try {
    const response = await api.zones.getAll()
    if (response.data.success) {
      const groupedByVille = response.data.data || {}
      villesDisponibles.value = Object.keys(groupedByVille)
        .map((name) => (name || '').trim())
        .filter((name) => !!name)
        .sort()
    }
  } catch (error) {
    console.error('Erreur chargement villes:', error)
  }
}

const fetchZones = async () => {
  loading.value = true

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.ville) params.ville = filters.value.ville
    if (filters.value.est_active !== '') params.est_active = filters.value.est_active

    const response = await api.admin.zones.getAll(params)
    if (response.data.success) {
      zones.value = response.data.data.data
      totalZones.value = response.data.data.total
    }
  } catch (error) {
    console.error('Erreur chargement zones:', error)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
  fetchZones()
}

const resetFilters = () => {
  filters.value.search = ''
  filters.value.ville = ''
  filters.value.est_active = ''
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
    nom_zone: '',
    ville: '',
    tarif_livraison: 0,
    delai_livraison_min: 0,
    delai_livraison_max: 0,
    est_active: true,
  }
  formError.value = ''
}

const openCreate = () => {
  resetForm()
  isEditing.value = false
  showForm.value = true
}

const openEdit = (zone) => {
  form.value = {
    id: zone.id,
    nom_zone: zone.nom_zone,
    ville: zone.ville,
    tarif_livraison: zone.tarif_livraison,
    delai_livraison_min: zone.delai_livraison_min,
    delai_livraison_max: zone.delai_livraison_max,
    est_active: !!zone.est_active,
  }
  isEditing.value = true
  showForm.value = true
  formError.value = ''
}

const closeForm = () => {
  showForm.value = false
  formError.value = ''
}

const submitForm = async () => {
  saving.value = true
  formError.value = ''

  try {
    let response
    if (isEditing.value && form.value.id) {
      response = await api.admin.zones.update(form.value.id, form.value)
    } else {
      response = await api.admin.zones.create(form.value)
    }

    if (response.data.success) {
      toastStore.succes(isEditing.value ? 'Zone mise à jour.' : 'Zone créée.')
      showForm.value = false
      fetchZones()
      resetForm()
    }
  } catch (error) {
    formError.value = messageErreur(error, 'Erreur lors de l\'enregistrement.')
  } finally {
    saving.value = false
  }
}

const deleteZone = async (zone) => {
  const confirmed = await confirmer({
    titre: 'Supprimer la zone',
    message: `La zone « ${zone.nom_zone} » sera supprimée.`,
    libelleConfirmer: 'Supprimer',
    danger: true,
  })
  if (!confirmed) return

  try {
    await api.admin.zones.remove(zone.id)
    toastStore.succes('Zone supprimée.')
    fetchZones()
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Erreur lors de la suppression.'))
  }
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchZones()
}

onMounted(() => {
  fetchVilles()
  fetchZones()
})
</script>
