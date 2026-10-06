<!-- ===================================
ADMIN - PARAMÈTRES DU SITE
File: src/views/admin/AdminParametres.vue
=================================== -->
<!--
  Réglages courants (ReglagesBoutique) en tête, puis la liste brute des paramètres : filtres et
  page dans l'URL, TableauDonnees (cartes sur mobile), formulaire dans un tiroir (BaseModal).
-->
<template>
  <div class="container mx-auto max-w-6xl pb-6 pt-6 md:pt-8">
    <EnTetePage titre="Paramètres du site" sous-titre="Gérer les réglages globaux : textes, options et configurations.">
      <template #actions>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="openCreate">
          Nouveau paramètre
        </Button>
      </template>
    </EnTetePage>

    <ReglagesBoutique />

    <section aria-labelledby="titre-tous-parametres">
      <h2 id="titre-tous-parametres" class="mb-3 text-xl">Tous les paramètres</h2>

      <Card padding="md" class="mb-6">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-4">
          <ChampRecherche
            id="filtre-parametres-recherche"
            v-model="filtres.search"
            libelle="Recherche"
            libelle-visible
            placeholder="Clé, description, groupe…"
            class="md:col-span-2"
            @rechercher="mettreAJour({ page: 1 })"
          />
          <div>
            <label for="filtre-parametres-type" class="label">Type</label>
            <select id="filtre-parametres-type" v-model="filtres.type" class="input" @change="mettreAJour({ page: 1 })">
              <option value="">Tous</option>
              <option v-for="(libelle, valeur) in TYPES" :key="valeur" :value="valeur">{{ libelle }}</option>
            </select>
          </div>
          <div class="flex md:justify-end">
            <Button variant="outline" size="sm" :disabled="!filtresActifs" @click="reinitialiserFiltres">
              Réinitialiser les filtres
            </Button>
          </div>
        </div>
      </Card>

      <TableauDonnees
        libelle="Paramètres"
        :chargement="loading"
        :erreur="erreurListe"
        :vide="parametres.length === 0"
        :icone="SlidersHorizontal"
        :titre-vide="filtresActifs ? 'Aucun paramètre trouvé' : 'Aucun paramètre'"
        :texte-vide="filtresActifs ? 'Aucun paramètre ne correspond à ces filtres.' : ''"
        @reessayer="fetchParametres"
      >
        <template #barre>
          <p class="text-sm text-gray-600">{{ totalParametres }} paramètre{{ totalParametres > 1 ? 's' : '' }}</p>
          <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" :loading="loading" @click="fetchParametres">
            Actualiser
          </Button>
        </template>

        <!-- Mobile : une carte par paramètre -->
        <template #cartes>
          <li v-for="param in parametres" :key="param.id" class="space-y-2 p-4">
            <div>
              <p class="break-all font-semibold text-gray-900">{{ param.cle }}</p>
              <p v-if="param.description" class="text-sm text-gray-600">{{ param.description }}</p>
            </div>
            <p class="text-sm text-gray-700">
              <span class="font-semibold">{{ TYPES[param.type] || param.type }}</span><template v-if="param.groupe">, groupe {{ param.groupe }}</template>
            </p>
            <p class="break-words text-sm text-gray-800">{{ formatValeur(param.valeur, param.type) }}</p>
            <div class="flex flex-wrap gap-2 pt-1">
              <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(param)">
                Modifier<span class="sr-only"> {{ param.cle }}</span>
              </Button>
              <Button variant="ghost" size="sm" :icon="Trash2" :icon-size="16" class="text-red-700 hover:bg-red-50" @click="deleteParametre(param)">
                Supprimer<span class="sr-only"> {{ param.cle }}</span>
              </Button>
            </div>
          </li>
        </template>

        <!-- Desktop : tableau -->
        <template #entete>
          <th scope="col" class="px-4 py-3 font-semibold">Clé</th>
          <th scope="col" class="px-4 py-3 font-semibold">Groupe</th>
          <th scope="col" class="px-4 py-3 font-semibold">Type</th>
          <th scope="col" class="px-4 py-3 font-semibold">Valeur</th>
          <th scope="col" class="px-4 py-3 text-right font-semibold">Actions</th>
        </template>
        <template #lignes>
          <tr v-for="param in parametres" :key="param.id">
            <td class="px-4 py-3">
              <p class="font-semibold text-gray-900">{{ param.cle }}</p>
              <p v-if="param.description" class="text-xs text-gray-600">{{ param.description }}</p>
            </td>
            <td class="px-4 py-3 text-gray-700">{{ param.groupe || '—' }}</td>
            <td class="px-4 py-3 text-gray-700">{{ TYPES[param.type] || param.type }}</td>
            <td class="max-w-xs px-4 py-3 text-gray-700">
              <span class="break-words">{{ formatValeur(param.valeur, param.type) }}</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(param)">
                  Modifier<span class="sr-only"> {{ param.cle }}</span>
                </Button>
                <Button variant="ghost" size="sm" :icon="Trash2" :icon-size="16" class="text-red-700 hover:bg-red-50" @click="deleteParametre(param)">
                  Supprimer<span class="sr-only"> {{ param.cle }}</span>
                </Button>
              </div>
            </td>
          </tr>
        </template>

        <template v-if="totalPages > 1" #pied>
          <Pagination
            :page="filtres.page"
            :derniere="totalPages"
            :desactive="loading"
            libelle="Pages de paramètres"
            @update:page="(page) => mettreAJour({ page })"
          />
        </template>
      </TableauDonnees>
    </section>

    <!-- Création / modification : tiroir, focus sur la clé -->
    <BaseModal
      :ouvert="showForm"
      :titre="isEditing ? 'Modifier le paramètre' : 'Nouveau paramètre'"
      variante="tiroir"
      taille="lg"
      @fermer="closeForm"
    >
      <form id="formulaire-parametre" class="grid grid-cols-1 gap-5 sm:grid-cols-2" novalidate @submit.prevent="submitForm">
        <AlertMessage v-if="formError" ref="alerteFormulaire" type="error" tabindex="-1" class="sm:col-span-2">{{ formError }}</AlertMessage>
        <FormField v-slot="{ attrs }" label="Clé" requis>
          <input v-model="form.cle" v-bind="attrs" type="text" class="input" autocomplete="off" data-autofocus />
        </FormField>

        <FormField v-slot="{ attrs }" label="Groupe" facultatif>
          <input v-model="form.groupe" v-bind="attrs" type="text" class="input" placeholder="general, contact…" />
        </FormField>

        <FormField v-slot="{ attrs }" label="Type" requis>
          <select v-model="form.type" v-bind="attrs" class="input">
            <option v-for="(libelle, valeur) in TYPES" :key="valeur" :value="valeur">{{ libelle }}</option>
          </select>
        </FormField>

        <!-- Valeur : champ adapté au type ; une case à cocher porte son propre libellé -->
        <label v-if="form.type === 'boolean'" class="inline-flex min-h-11 cursor-pointer items-center gap-2 self-end">
          <input id="parametre-valeur" v-model="form.valeur" type="checkbox" class="h-5 w-5 flex-shrink-0 rounded" />
          <span class="text-sm text-gray-800">Valeur : activé</span>
        </label>
        <FormField
          v-else
          v-slot="{ attrs }"
          label="Valeur"
          :aide="form.type === 'json' ? 'JSON valide, par exemple { &quot;actif&quot;: true }.' : ''"
          :class="{ 'sm:col-span-2': form.type !== 'integer' }"
        >
          <input
            v-if="form.type === 'integer'"
            v-model="form.valeur"
            v-bind="attrs"
            type="number"
            inputmode="numeric"
            class="input"
          />
          <!-- Textes longs (conditions_vendeur) : zone qui s'agrandit avec le contenu -->
          <textarea
            v-else
            v-model="form.valeur"
            v-bind="attrs"
            :rows="form.type === 'string' && (String(form.valeur || '').length > 120 || String(form.valeur || '').includes('\n')) ? 10 : 3"
            class="input resize-y"
            :class="{ 'font-mono text-sm': form.type === 'json' }"
          ></textarea>
        </FormField>

        <FormField v-slot="{ attrs }" label="Description" facultatif class="sm:col-span-2">
          <textarea v-model="form.description" v-bind="attrs" rows="3" class="input resize-y"></textarea>
        </FormField>
      </form>

      <template #actions>
        <Button type="button" variant="outline" @click="closeForm">Annuler</Button>
        <Button type="submit" form="formulaire-parametre" variant="primary" :loading="saving">
          {{ isEditing ? 'Enregistrer' : 'Créer le paramètre' }}
        </Button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import { useFiltresUrl } from '@/composables/useFiltresUrl'
import { useErreurFormulaire } from '@/composables/useErreurFormulaire'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import EnTetePage from '@/components/common/EnTetePage.vue'
import BaseModal from '@/components/common/BaseModal.vue'
import FormField from '@/components/common/FormField.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import TableauDonnees from '@/components/common/TableauDonnees.vue'
import Pagination from '@/components/common/Pagination.vue'
import ChampRecherche from '@/components/common/ChampRecherche.vue'
import ReglagesBoutique from '@/components/admin/ReglagesBoutique.vue'
import { Plus, Pencil, Trash2, RefreshCw, SlidersHorizontal } from 'lucide-vue-next'

const PAR_PAGE = 20
const FILTRES_VIDES = { search: '', type: '' }
const TYPES = { string: 'Texte', integer: 'Nombre', boolean: 'Oui / non', json: 'JSON' }

const toastStore = useToastStore()
const { confirmer } = useConfirm()

const parametres = ref([])
const loading = ref(false)
const erreurListe = ref('')
const saving = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const { erreur: formError, alerte: alerteFormulaire, signaler: signalerErreur } = useErreurFormulaire()
const totalParametres = ref(0)

// Filtres et page dans l'URL
const { filtres, mettreAJour, recharger } = useFiltresUrl({ ...FILTRES_VIDES, page: 1 }, (estActuel) => fetchParametres(estActuel))
const filtresActifs = computed(() => Object.keys(FILTRES_VIDES).some((cle) => filtres[cle] !== ''))
const reinitialiserFiltres = () => mettreAJour({ ...FILTRES_VIDES, page: 1 })
const totalPages = computed(() => Math.max(1, Math.ceil(totalParametres.value / PAR_PAGE)))

const formulaireVide = () => ({
  id: null,
  cle: '',
  valeur: '',
  type: 'string',
  description: '',
  groupe: '',
})
const form = ref(formulaireVide())

const formatValeur = (valeur, type) => {
  if (valeur === null || valeur === undefined || valeur === '') return '—'
  if (type === 'boolean') return valeur === '1' || valeur === true ? 'Oui' : 'Non'
  // Textes longs (ex. conditions des vendeurs) : aperçu, la valeur complète est dans le formulaire
  if (type === 'json' || type === 'string') return valeur.length > 60 ? `${valeur.slice(0, 60)}…` : valeur
  return valeur
}

const fetchParametres = async (estActuel = () => true) => {
  loading.value = true
  erreurListe.value = ''

  try {
    const params = { page: filtres.page, per_page: PAR_PAGE }
    if (filtres.search) params.search = filtres.search
    if (filtres.type) params.type = filtres.type

    const response = await api.admin.parametres.getAll(params)
    if (!estActuel()) return
    if (response.data.success) {
      parametres.value = response.data.data.data
      totalParametres.value = response.data.data.total

      // Page vidée (dernier paramètre supprimé, lien trop loin) : dernière page remplie
      if (parametres.value.length === 0 && filtres.page > 1 && totalParametres.value > 0) {
        mettreAJour({ page: Math.max(1, Number(response.data.data.last_page) || filtres.page - 1) })
      }
    }
  } catch (error) {
    if (!estActuel()) return
    erreurListe.value = messageErreur(error, 'Impossible de charger les paramètres.')
  } finally {
    // Un chargement dépassé laisse l'indicateur au plus récent
    if (estActuel()) loading.value = false
  }
}

const openCreate = () => {
  form.value = formulaireVide()
  formError.value = ''
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
  formError.value = ''
  isEditing.value = true
  showForm.value = true
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
  formError.value = ''

  if (!form.value.cle.trim()) {
    signalerErreur('Indiquez la clé du paramètre.')
    return
  }
  if (form.value.type === 'json' && form.value.valeur) {
    try {
      JSON.parse(form.value.valeur)
    } catch {
      signalerErreur('La valeur n\'est pas un JSON valide.')
      return
    }
  }

  saving.value = true
  try {
    const payload = buildPayload()
    const response = isEditing.value && form.value.id
      ? await api.admin.parametres.update(form.value.id, payload)
      : await api.admin.parametres.create(payload)

    if (response.data.success) {
      toastStore.succes(isEditing.value ? 'Paramètre mis à jour.' : 'Paramètre créé.')
      showForm.value = false
      recharger()
    }
  } catch (error) {
    signalerErreur(messageErreur(error, 'Erreur lors de l\'enregistrement.'))
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
    recharger()
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Erreur lors de la suppression.'))
  }
}
</script>
