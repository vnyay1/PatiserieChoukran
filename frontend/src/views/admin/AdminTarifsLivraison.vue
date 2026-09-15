<!-- ===================================
VENDEUR - MA LIVRAISON (minimum d'achat et quartiers desservis)
File: src/views/admin/AdminTarifsLivraison.vue
=================================== -->

<template>
  <div class="admin-tarifs-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            Ma livraison
          </h1>
          <p class="text-gray-600 text-sm">
            Les clients ne peuvent se faire livrer vos produits que dans les quartiers ci-dessous,
            dans le délai que vous fixez, et à partir de votre montant minimum d'achat.
          </p>
        </div>
        <Button
          variant="primary"
          :icon="Plus"
          :icon-size="18"
          :disabled="quartiersDisponibles.length === 0"
          @click="openCreate"
        >
          Ajouter un quartier
        </Button>
      </div>

      <p v-if="pageError" class="text-sm text-red-600 mb-4">{{ pageError }}</p>

      <!-- Conditions : frais fixés par la plateforme, minimum fixé par le vendeur -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <Card padding="lg">
          <h2 class="font-display text-lg font-bold text-gray-800 mb-1">Montant minimum de commande</h2>
          <p class="text-sm text-gray-600 mb-4">
            En dessous de ce montant de produits, vos clients ne peuvent choisir que le retrait en boutique.
            Laissez 0 pour livrer toutes les commandes.
          </p>
          <form class="flex flex-col sm:flex-row gap-3" @submit.prevent="enregistrerMinimum">
            <div class="relative flex-1">
              <input
                v-model.number="minimum"
                type="number"
                min="0"
                step="500"
                class="input pr-16"
                :disabled="loading"
                required
              />
              <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">FCFA</span>
            </div>
            <Button type="submit" variant="primary" :loading="savingMinimum" :disabled="loading">
              Enregistrer
            </Button>
          </form>
          <p v-if="minimumError" class="text-sm text-red-600 mt-2">{{ minimumError }}</p>
        </Card>

        <Card padding="lg">
          <h2 class="font-display text-lg font-bold text-gray-800 mb-1">Frais de livraison</h2>
          <p class="price text-2xl my-2">{{ formatPrice(fraisStandard) }} FCFA</p>
          <p class="text-sm text-gray-600">
            Tarif unique fixé par la plateforme, payé par le client pour chaque commande livrée.
          </p>
        </Card>
      </div>

      <Card v-if="showForm" padding="lg" class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-display text-xl font-bold text-gray-800">
            {{ isEditing ? `Modifier — ${quartierEnEdition?.nom || ''}` : 'Ajouter un quartier desservi' }}
          </h2>
          <button class="text-sm text-gray-500 hover:text-gray-700" @click="closeForm">
            Fermer
          </button>
        </div>

        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submitForm">
          <div v-if="!isEditing" class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Quartier *</label>
            <select v-model="form.quartier_id" class="input" required>
              <option value="">Sélectionner un quartier</option>
              <optgroup
                v-for="groupe in quartiersDisponibles"
                :key="groupe.ville"
                :label="groupe.label"
              >
                <option v-for="quartier in groupe.quartiers" :key="quartier.id" :value="quartier.id">
                  {{ quartier.nom }}
                </option>
              </optgroup>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3 md:col-span-2 md:max-w-md">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Délai min (min) *</label>
              <input v-model.number="form.delai_min" type="number" min="0" class="input" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Délai max (min) *</label>
              <input v-model.number="form.delai_max" type="number" min="0" class="input" required />
            </div>
          </div>

          <div class="md:col-span-2">
            <label class="inline-flex items-center gap-2">
              <input
                v-model="form.actif"
                type="checkbox"
                class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
              />
              <span class="text-sm text-gray-700">Livraison active dans ce quartier</span>
            </label>
          </div>

          <p v-if="formError" class="text-sm text-red-600 md:col-span-2">
            {{ formError }}
          </p>

          <div class="md:col-span-2 flex gap-3">
            <Button type="submit" variant="primary" :loading="saving">
              {{ isEditing ? 'Enregistrer' : 'Ajouter le quartier' }}
            </Button>
            <Button type="button" variant="outline" @click="closeForm">
              Annuler
            </Button>
          </div>
        </form>
      </Card>

      <Card padding="none">
        <div class="p-4 border-b border-gray-100 grid grid-cols-1 md:grid-cols-4 gap-3 items-center">
          <div class="text-sm text-gray-600">
            {{ nombreCouverts }} quartier{{ nombreCouverts > 1 ? 's' : '' }} couvert{{ nombreCouverts > 1 ? 's' : '' }}
            sur {{ quartiers.length }}
          </div>
          <div class="relative md:col-span-2">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" :size="18" />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Rechercher un quartier..."
              class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
            />
          </div>
          <select v-model="filters.ville" class="input">
            <option value="">Toutes les villes</option>
            <option value="yaoundé">Yaoundé</option>
            <option value="douala">Douala</option>
          </select>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="text-left font-semibold px-4 py-3">Quartier</th>
                <th class="text-left font-semibold px-4 py-3">Délai</th>
                <th class="text-left font-semibold px-4 py-3">Statut</th>
                <th class="text-right font-semibold px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr>
                <td colspan="4" class="p-6 text-center text-gray-500">Chargement...</td>
              </tr>
            </tbody>
            <tbody v-else-if="tarifsFiltres.length === 0">
              <tr>
                <td colspan="4" class="p-6 text-center text-gray-500">
                  {{ tarifs.length === 0
                    ? 'Aucun quartier desservi pour l\'instant : ajoutez-en un pour recevoir des commandes en livraison.'
                    : 'Aucun quartier ne correspond à la recherche.' }}
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="tarif in tarifsFiltres" :key="tarif.id" class="border-t border-gray-100">
                <td class="px-4 py-3">
                  <div class="font-semibold text-gray-800">{{ tarif.quartier?.nom }}</div>
                  <div class="text-xs text-gray-500">{{ formatVille(tarif.quartier?.ville) }}</div>
                </td>
                <td class="px-4 py-3">{{ formatDelai(tarif) }}</td>
                <td class="px-4 py-3">
                  <button
                    type="button"
                    class="badge"
                    :class="tarif.actif ? 'badge-success' : 'badge-danger'"
                    :title="tarif.actif ? 'Cliquer pour suspendre' : 'Cliquer pour réactiver'"
                    @click="toggleActif(tarif)"
                  >
                    {{ tarif.actif ? 'Actif' : 'Suspendu' }}
                  </button>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(tarif)">
                      Modifier
                    </Button>
                    <Button variant="danger" size="sm" :icon="Trash2" :icon-size="16" @click="deleteTarif(tarif)">
                      Retirer
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
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
import { formatVille, formatDelai, normaliserTexte } from '@/composables/useLivraisonVendeurs'
import { formatPrice } from '@/utils/format'
import { Plus, Search, Pencil, Trash2 } from 'lucide-vue-next'

const toastStore = useToastStore()
const { confirmer } = useConfirm()

// Quartiers desservis par le vendeur (table vendeur_tarifs_livraison : quartier + délais)
const tarifs = ref([])
// Tous les quartiers actifs, avec `couvert` = le vendeur dessert déjà ce quartier
const quartiers = ref([])
const loading = ref(false)
const saving = ref(false)
const showForm = ref(false)
const formError = ref('')
const pageError = ref('')

const minimum = ref(0)
const fraisStandard = ref(0)
const savingMinimum = ref(false)
const minimumError = ref('')

const filters = ref({
  search: '',
  ville: '',
})

const formVide = () => ({
  id: null,
  quartier_id: '',
  delai_min: 30,
  delai_max: 60,
  actif: true,
})

const form = ref(formVide())
const isEditing = computed(() => form.value.id !== null)

const quartierEnEdition = computed(() => {
  return tarifs.value.find((tarif) => tarif.id === form.value.id)?.quartier || null
})

const nombreCouverts = computed(() => quartiers.value.filter((quartier) => quartier.couvert).length)

const tarifsFiltres = computed(() => {
  const terme = normaliserTexte(filters.value.search.trim())

  return tarifs.value.filter((tarif) => {
    const quartier = tarif.quartier || {}
    if (filters.value.ville && quartier.ville !== filters.value.ville) return false
    return !terme || normaliserTexte(quartier.nom).includes(terme)
  })
})

// Quartiers proposés à la création : ceux que le vendeur ne dessert pas encore
const quartiersDisponibles = computed(() => {
  const parVille = {}
  quartiers.value
    .filter((quartier) => !quartier.couvert)
    .forEach((quartier) => {
      if (!parVille[quartier.ville]) parVille[quartier.ville] = []
      parVille[quartier.ville].push(quartier)
    })

  return Object.entries(parVille).map(([ville, liste]) => ({
    ville,
    label: formatVille(ville),
    quartiers: liste,
  }))
})

const fetchTarifs = async () => {
  loading.value = true
  pageError.value = ''

  try {
    const [reponseTarifs, reponseQuartiers] = await Promise.all([
      api.vendeur.tarifs.getAll(),
      api.vendeur.tarifs.quartiers(),
    ])
    tarifs.value = reponseTarifs.data.data || []
    minimum.value = Number(reponseTarifs.data.meta?.montant_minimum_livraison) || 0
    fraisStandard.value = Number(reponseTarifs.data.meta?.frais_livraison_standard) || 0
    quartiers.value = reponseQuartiers.data.data || []
  } catch (error) {
    pageError.value = messageErreur(error, 'Impossible de charger vos réglages de livraison.')
  } finally {
    loading.value = false
  }
}

const enregistrerMinimum = async () => {
  savingMinimum.value = true
  minimumError.value = ''

  try {
    const response = await api.vendeur.livraison.updateMinimum({ montant_minimum_livraison: minimum.value || 0 })
    minimum.value = Number(response.data.data?.montant_minimum_livraison) || 0
    toastStore.succes(minimum.value > 0
      ? `Livraison à partir de ${formatPrice(minimum.value)} FCFA d'achat.`
      : 'Toutes vos commandes peuvent être livrées.')
  } catch (error) {
    minimumError.value = messageErreur(error, 'Impossible d\'enregistrer le montant minimum.')
  } finally {
    savingMinimum.value = false
  }
}

const openCreate = () => {
  form.value = formVide()
  formError.value = ''
  showForm.value = true
}

const openEdit = (tarif) => {
  form.value = {
    id: tarif.id,
    quartier_id: tarif.quartier_id,
    delai_min: tarif.delai_min,
    delai_max: tarif.delai_max,
    actif: Boolean(tarif.actif),
  }
  formError.value = ''
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  formError.value = ''
}

const submitForm = async () => {
  if (Number(form.value.delai_max) < Number(form.value.delai_min)) {
    formError.value = 'Le délai max doit être supérieur ou égal au délai min.'
    return
  }

  saving.value = true
  formError.value = ''

  const payload = {
    delai_min: form.value.delai_min,
    delai_max: form.value.delai_max,
    actif: form.value.actif,
  }

  try {
    if (isEditing.value) {
      await api.vendeur.tarifs.update(form.value.id, payload)
    } else {
      await api.vendeur.tarifs.create({ ...payload, quartier_id: form.value.quartier_id })
    }
    toastStore.succes(isEditing.value ? 'Quartier mis à jour.' : 'Quartier ajouté à votre zone de livraison.')
    showForm.value = false
    await fetchTarifs()
  } catch (error) {
    formError.value = messageErreur(error, 'Erreur lors de l\'enregistrement du quartier.')
  } finally {
    saving.value = false
  }
}

const toggleActif = async (tarif) => {
  try {
    await api.vendeur.tarifs.update(tarif.id, { actif: !tarif.actif })
    tarif.actif = !tarif.actif
    toastStore.succes(tarif.actif
      ? `Livraison réactivée à ${tarif.quartier?.nom}.`
      : `Livraison suspendue à ${tarif.quartier?.nom}.`)
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Erreur lors de la mise à jour du quartier.'))
  }
}

const deleteTarif = async (tarif) => {
  const confirmed = await confirmer({
    titre: 'Retirer le quartier',
    message: `Vous ne livrerez plus le quartier « ${tarif.quartier?.nom} ».`,
    libelleConfirmer: 'Retirer',
    danger: true,
  })
  if (!confirmed) return

  try {
    await api.vendeur.tarifs.remove(tarif.id)
    toastStore.succes('Quartier retiré de votre zone de livraison.')
    await fetchTarifs()
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Erreur lors du retrait du quartier.'))
  }
}

onMounted(fetchTarifs)
</script>
