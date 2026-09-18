<!-- ===================================
ADMIN - PARAMÈTRES DU SITE
File: src/views/admin/AdminParametres.vue
=================================== -->

<template>
  <div class="admin-parametres-page pb-6">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1>
            Paramètres du site
          </h1>
          <p class="text-gray-600 text-sm">
            Gérer les réglages globaux (textes, options et configurations).
          </p>
        </div>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="openCreate">
          Nouveau paramètre
        </Button>
      </div>

      <ReglagesBoutique />

      <h2 class="font-display text-lg font-bold text-gray-800 mb-3">Tous les paramètres</h2>
      <Card padding="md" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div class="md:col-span-2">
            <label for="admin-parametres-1" class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
            <div class="relative">
              <Search class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500" :size="18" aria-hidden="true" />
              <input
                id="admin-parametres-1"
                v-model="filters.search"
                type="search"
                placeholder="Clé, description, groupe..."
                class="input pl-10"
                @input="handleSearch"
              />
            </div>
          </div>

          <div>
            <label for="admin-parametres-2" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
            <select id="admin-parametres-2" v-model="filters.type" class="input">
              <option value="">Tous</option>
              <option value="string">Texte</option>
              <option value="integer">Nombre</option>
              <option value="boolean">Booléen</option>
              <option value="json">JSON</option>
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
            {{ isEditing ? 'Modifier le paramètre' : 'Ajouter un paramètre' }}
          </h2>
          <button class="text-sm text-gray-500 hover:text-gray-700" @click="closeForm">
            Fermer
          </button>
        </div>

        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submitForm">
          <div>
            <label for="admin-parametres-3" class="block text-sm font-medium text-gray-700 mb-2">Clé *</label>
            <input id="admin-parametres-3" v-model="form.cle" type="text" class="input" required />
          </div>

          <div>
            <label for="admin-parametres-4" class="block text-sm font-medium text-gray-700 mb-2">Groupe</label>
            <input id="admin-parametres-4" v-model="form.groupe" type="text" class="input" placeholder="general, contact..." />
          </div>

          <div>
            <label for="admin-parametres-5" class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
            <select id="admin-parametres-5" v-model="form.type" class="input" required>
              <option value="string">Texte</option>
              <option value="integer">Nombre</option>
              <option value="boolean">Booléen</option>
              <option value="json">JSON</option>
            </select>
          </div>

          <div :class="form.type === 'string' ? 'md:col-span-2' : ''">
            <label for="admin-parametres-6" class="block text-sm font-medium text-gray-700 mb-2">Valeur</label>
            <!-- Zone de texte : certains paramètres sont longs et sur plusieurs lignes (conditions_vendeur) -->
            <textarea
              v-if="form.type === 'string'"
              id="admin-parametres-6"
              v-model="form.valeur"
              :rows="String(form.valeur || '').length > 120 || String(form.valeur || '').includes('\n') ? 10 : 2"
              class="input resize-y"
            ></textarea>
            <input
              v-else-if="form.type === 'integer'"
              id="admin-parametres-6"
              v-model="form.valeur"
              type="number"
              class="input"
            />
            <label v-else-if="form.type === 'boolean'" class="inline-flex items-center gap-2 mt-2">
              <input
                v-model="form.valeur"
                type="checkbox"
                class="h-5 w-5 rounded"
              />
              <span class="text-sm text-gray-700">Activer</span>
            </label>
            <textarea
              v-else
              id="admin-parametres-6"
              v-model="form.valeur"
              rows="3"
              class="input resize-none"
              placeholder="{ &quot;exemple&quot;: true }"
            ></textarea>
          </div>

          <div class="md:col-span-2">
            <label for="admin-parametres-7" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea id="admin-parametres-7" v-model="form.description" rows="3" class="input resize-none"></textarea>
          </div>

          <div class="md:col-span-2 flex gap-3">
            <Button type="submit" variant="primary" :loading="saving">
              {{ isEditing ? 'Enregistrer' : 'Créer le paramètre' }}
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
            {{ totalParametres }} paramètre{{ totalParametres > 1 ? 's' : '' }}
          </div>
          <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" @click="fetchParametres">
            Actualiser
          </Button>
        </div>

        <div class="overflow-x-auto" role="region" aria-label="Liste des paramètres" tabindex="0">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th scope="col" class="text-left font-semibold px-4 py-3">Clé</th>
                <th scope="col" class="text-left font-semibold px-4 py-3">Groupe</th>
                <th scope="col" class="text-left font-semibold px-4 py-3">Type</th>
                <th scope="col" class="text-left font-semibold px-4 py-3">Valeur</th>
                <th scope="col" class="text-right font-semibold px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr>
                <td colspan="5" class="p-6 text-center text-gray-500">Chargement...</td>
              </tr>
            </tbody>
            <tbody v-else-if="parametres.length === 0">
              <tr>
                <td colspan="5" class="p-6 text-center text-gray-500">Aucun paramètre trouvé.</td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="param in parametres" :key="param.id" class="border-t border-gray-100">
                <td class="px-4 py-3">
                  <div class="font-semibold text-gray-800">{{ param.cle }}</div>
                  <div class="text-xs text-gray-500">{{ param.description }}</div>
                </td>
                <td class="px-4 py-3">{{ param.groupe || '-' }}</td>
                <td class="px-4 py-3">{{ param.type }}</td>
                <td class="px-4 py-3 text-gray-600">
                  {{ formatValeur(param.valeur, param.type) }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(param)">
                      Modifier
                    </Button>
                    <Button variant="danger" size="sm" :icon="Trash2" :icon-size="16" @click="deleteParametre(param)">
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
import ReglagesBoutique from '@/components/admin/ReglagesBoutique.vue'
import { Plus, Search, Pencil, Trash2, RefreshCw } from 'lucide-vue-next'

const toastStore = useToastStore()
const { confirmer } = useConfirm()

const parametres = ref([])
const loading = ref(false)
const saving = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const formError = ref('')
const currentPage = ref(1)
const perPage = ref(20)
const totalParametres = ref(0)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalParametres.value / perPage.value))
})

const filters = ref({
  search: '',
  type: '',
})

const form = ref({
  id: null,
  cle: '',
  valeur: '',
  type: 'string',
  description: '',
  groupe: '',
})

const formatValeur = (valeur, type) => {
  if (valeur === null || valeur === undefined || valeur === '') return '-'
  if (type === 'boolean') return valeur === '1' || valeur === true ? 'Oui' : 'Non'
  // Textes longs (ex. conditions des vendeurs) : aperçu, la valeur complète est dans le formulaire
  if (type === 'json' || type === 'string') return valeur.length > 60 ? `${valeur.slice(0, 60)}...` : valeur
  return valeur
}

const fetchParametres = async () => {
  loading.value = true

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.type) params.type = filters.value.type

    const response = await api.admin.parametres.getAll(params)
    if (response.data.success) {
      parametres.value = response.data.data.data
      totalParametres.value = response.data.data.total
    }
  } catch (error) {
    console.error('Erreur chargement paramètres:', error)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
  fetchParametres()
}

const resetFilters = () => {
  filters.value.search = ''
  filters.value.type = ''
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
    cle: '',
    valeur: '',
    type: 'string',
    description: '',
    groupe: '',
  }
  formError.value = ''
}

const openCreate = () => {
  resetForm()
  isEditing.value = false
  showForm.value = true
}

const openEdit = (param) => {
  form.value = {
    id: param.id,
    cle: param.cle,
    valeur: param.type === 'boolean' ? param.valeur === '1' : (param.valeur ?? ''),
    type: param.type,
    description: param.description || '',
    groupe: param.groupe || '',
  }
  isEditing.value = true
  showForm.value = true
  formError.value = ''
}

const closeForm = () => {
  showForm.value = false
  formError.value = ''
}

const buildPayload = () => {
  let valeur = form.value.valeur
  if (form.value.type === 'boolean') {
    valeur = form.value.valeur ? 1 : 0
  }
  if (form.value.type === 'integer' && valeur !== '' && valeur !== null) {
    valeur = Number(valeur)
  }
  return {
    cle: form.value.cle,
    valeur,
    type: form.value.type,
    description: form.value.description,
    groupe: form.value.groupe,
  }
}

const submitForm = async () => {
  saving.value = true
  formError.value = ''

  try {
    if (form.value.type === 'json' && form.value.valeur) {
      JSON.parse(form.value.valeur)
    }
  } catch (e) {
    formError.value = 'Valeur JSON invalide'
    saving.value = false
    return
  }

  try {
    const payload = buildPayload()
    let response

    if (isEditing.value && form.value.id) {
      response = await api.admin.parametres.update(form.value.id, payload)
    } else {
      response = await api.admin.parametres.create(payload)
    }

    if (response.data.success) {
      toastStore.succes(isEditing.value ? 'Paramètre mis à jour.' : 'Paramètre créé.')
      showForm.value = false
      fetchParametres()
      resetForm()
    }
  } catch (error) {
    formError.value = messageErreur(error, 'Erreur lors de l\'enregistrement.')
  } finally {
    saving.value = false
  }
}

const deleteParametre = async (param) => {
  const confirmed = await confirmer({
    titre: 'Supprimer le paramètre',
    message: `Le paramètre « ${param.cle} » sera supprimé.`,
    libelleConfirmer: 'Supprimer',
    danger: true,
  })
  if (!confirmed) return

  try {
    await api.admin.parametres.remove(param.id)
    toastStore.succes('Paramètre supprimé.')
    fetchParametres()
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Erreur lors de la suppression.'))
  }
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchParametres()
}

onMounted(() => {
  fetchParametres()
})
</script>
