<!-- ===================================
ADMIN - QUARTIERS (liste proposée dans les adresses)
File: src/views/admin/AdminQuartiers.vue
=================================== -->

<template>
  <div class="admin-quartiers-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-5xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            Quartiers
          </h1>
          <p class="text-gray-600 text-sm">
            Quartiers proposés aux clients dans leurs adresses, par ville. Un quartier désactivé
            n'est plus proposé mais les adresses existantes le conservent.
          </p>
        </div>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="ouvrirCreation">
          Nouveau quartier
        </Button>
      </div>

      <Card v-if="formulaireOuvert" padding="lg" class="mb-6">
        <form class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end" @submit.prevent="enregistrer">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
            <input v-model="form.nom" type="text" maxlength="150" class="input" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
            <select v-model="form.ville" class="input" required>
              <option v-for="ville in VILLES" :key="ville.valeur" :value="ville.valeur">{{ ville.libelle }}</option>
            </select>
          </div>
          <label class="inline-flex items-center gap-2 md:col-span-3">
            <input v-model="form.actif" type="checkbox" class="rounded border-gray-300 text-gold-600 focus:ring-gold-500" />
            <span class="text-sm text-gray-700">Proposé aux clients</span>
          </label>
          <p v-if="erreurFormulaire" class="text-sm text-red-600 md:col-span-3">{{ erreurFormulaire }}</p>
          <div class="md:col-span-3 flex gap-3">
            <Button type="submit" variant="primary" :loading="saving">
              {{ form.id ? 'Enregistrer' : 'Ajouter le quartier' }}
            </Button>
            <Button type="button" variant="outline" @click="formulaireOuvert = false">Annuler</Button>
          </div>
        </form>
      </Card>

      <Card padding="none">
        <div class="p-4 border-b border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="relative md:col-span-2">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" :size="18" />
            <input
              v-model="filtres.search"
              type="search"
              placeholder="Rechercher un quartier..."
              class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 focus:border-gold-500 focus:ring-2 focus:ring-gold-200 outline-none"
              @input="rechercherPlusTard"
            />
          </div>
          <select v-model="filtres.ville" class="input" @change="charger(1)">
            <option value="">Toutes les villes</option>
            <option v-for="ville in VILLES" :key="ville.valeur" :value="ville.valeur">{{ ville.libelle }}</option>
          </select>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="text-left font-semibold px-4 py-3">Quartier</th>
                <th class="text-left font-semibold px-4 py-3">Ville</th>
                <th class="text-left font-semibold px-4 py-3">Adresses</th>
                <th class="text-left font-semibold px-4 py-3">Statut</th>
                <th class="text-right font-semibold px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="5" class="p-6 text-center text-gray-500">Chargement...</td></tr>
            </tbody>
            <tbody v-else-if="quartiers.length === 0">
              <tr><td colspan="5" class="p-6 text-center text-gray-500">Aucun quartier.</td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="quartier in quartiers" :key="quartier.id" class="border-t border-gray-100">
                <td class="px-4 py-3 font-semibold text-gray-800">{{ quartier.nom }}</td>
                <td class="px-4 py-3">{{ formatVille(quartier.ville) }}</td>
                <td class="px-4 py-3">{{ quartier.adresses_count }}</td>
                <td class="px-4 py-3">
                  <button
                    type="button"
                    class="badge"
                    :class="quartier.actif ? 'badge-success' : 'badge-danger'"
                    :title="quartier.actif ? 'Cliquer pour désactiver' : 'Cliquer pour réactiver'"
                    @click="basculerActif(quartier)"
                  >
                    {{ quartier.actif ? 'Actif' : 'Désactivé' }}
                  </button>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="ouvrirModification(quartier)">
                      Modifier
                    </Button>
                    <Button
                      variant="danger"
                      size="sm"
                      :icon="Trash2"
                      :icon-size="16"
                      :disabled="quartier.adresses_count > 0"
                      :title="quartier.adresses_count > 0 ? 'Des adresses utilisent ce quartier : désactivez-le plutôt' : undefined"
                      @click="supprimer(quartier)"
                    >
                      Supprimer
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t border-gray-100 flex items-center justify-between">
          <div class="text-xs text-gray-500">{{ total }} quartier{{ total > 1 ? 's' : '' }} · page {{ page }} / {{ dernierePage }}</div>
          <div class="flex gap-2">
            <Button variant="outline" size="sm" :disabled="page <= 1" @click="charger(page - 1)">Précédent</Button>
            <Button variant="outline" size="sm" :disabled="page >= dernierePage" @click="charger(page + 1)">Suivant</Button>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { VILLES, formatVille } from '@/utils/villes'
import { Plus, Search, Pencil, Trash2 } from 'lucide-vue-next'

const toastStore = useToastStore()
const { confirmer } = useConfirm()

const quartiers = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const dernierePage = ref(1)
const total = ref(0)
const filtres = ref({ search: '', ville: '' })
const formulaireOuvert = ref(false)
const erreurFormulaire = ref('')
const form = ref({ id: null, nom: '', ville: VILLES[0].valeur, actif: true })

const charger = async (numeroPage = page.value) => {
  loading.value = true
  try {
    const params = { page: numeroPage, per_page: 30 }
    if (filtres.value.search) params.search = filtres.value.search
    if (filtres.value.ville) params.ville = filtres.value.ville

    const response = await api.admin.quartiers.getAll(params)
    const pagination = response.data.data
    quartiers.value = pagination.data
    page.value = pagination.current_page
    dernierePage.value = pagination.last_page
    total.value = pagination.total
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Impossible de charger les quartiers.'))
  } finally {
    loading.value = false
  }
}

let minuterieRecherche = null
const rechercherPlusTard = () => {
  clearTimeout(minuterieRecherche)
  minuterieRecherche = setTimeout(() => charger(1), 400)
}

const ouvrirCreation = () => {
  form.value = { id: null, nom: '', ville: filtres.value.ville || VILLES[0].valeur, actif: true }
  erreurFormulaire.value = ''
  formulaireOuvert.value = true
}

const ouvrirModification = (quartier) => {
  form.value = { id: quartier.id, nom: quartier.nom, ville: quartier.ville, actif: Boolean(quartier.actif) }
  erreurFormulaire.value = ''
  formulaireOuvert.value = true
}

const enregistrer = async () => {
  saving.value = true
  erreurFormulaire.value = ''
  const donnees = { nom: form.value.nom.trim(), ville: form.value.ville, actif: form.value.actif }

  try {
    if (form.value.id) {
      await api.admin.quartiers.update(form.value.id, donnees)
    } else {
      await api.admin.quartiers.create(donnees)
    }
    toastStore.succes(form.value.id ? 'Quartier mis à jour.' : 'Quartier ajouté.')
    formulaireOuvert.value = false
    await charger()
  } catch (error) {
    erreurFormulaire.value = messageErreur(error, 'Impossible d\'enregistrer le quartier.')
  } finally {
    saving.value = false
  }
}

const basculerActif = async (quartier) => {
  try {
    await api.admin.quartiers.update(quartier.id, { actif: !quartier.actif })
    quartier.actif = !quartier.actif
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Impossible de modifier le quartier.'))
  }
}

const supprimer = async (quartier) => {
  const ok = await confirmer({
    titre: 'Supprimer le quartier',
    message: `« ${quartier.nom} » ne sera plus proposé aux clients.`,
    libelleConfirmer: 'Supprimer',
    danger: true,
  })
  if (!ok) return

  try {
    await api.admin.quartiers.remove(quartier.id)
    toastStore.succes('Quartier supprimé.')
    await charger()
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Impossible de supprimer le quartier.'))
  }
}

onMounted(() => charger(1))
</script>
