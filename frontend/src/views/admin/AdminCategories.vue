<!-- ===================================
ADMIN / VENDEUR - GESTION DES CATÉGORIES
File: src/views/admin/AdminCategories.vue
=================================== -->
<!--
  Filtres et page dans l'URL. Liste dans TableauDonnees : cartes sur mobile, tableau dès md.
  Création et modification dans un tiroir (BaseModal). Un vendeur ne modifie que ses catégories.
-->
<template>
  <div class="container mx-auto max-w-6xl pb-6 pt-6 md:pt-8">
    <EnTetePage titre="Catégories" sous-titre="Voir toutes les catégories et en ajouter de nouvelles.">
      <template #actions>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="openCreate">
          Nouvelle catégorie
        </Button>
      </template>
    </EnTetePage>

    <Card padding="md" class="mb-6">
      <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-4">
        <ChampRecherche
          id="filtre-categories-recherche"
          v-model="filtres.search"
          libelle="Recherche"
          libelle-visible
          placeholder="Nom de catégorie…"
          class="md:col-span-2"
          @rechercher="mettreAJour({ page: 1 })"
        />
        <div>
          <label for="filtre-categories-statut" class="label">Statut</label>
          <select id="filtre-categories-statut" v-model="filtres.est_actif" class="input" @change="mettreAJour({ page: 1 })">
            <option value="">Toutes</option>
            <option value="1">Actives</option>
            <option value="0">Inactives</option>
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
      libelle="Catégories"
      :chargement="loading"
      :erreur="erreurListe"
      :vide="categories.length === 0"
      :icone="Tags"
      :titre-vide="filtresActifs ? 'Aucune catégorie trouvée' : 'Aucune catégorie pour le moment'"
      :texte-vide="filtresActifs ? 'Aucune catégorie ne correspond à ces filtres.' : ''"
      @reessayer="fetchCategories"
    >
      <template #barre>
        <p class="text-sm text-gray-600">{{ totalCategories }} catégorie{{ totalCategories > 1 ? 's' : '' }}</p>
        <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" :loading="loading" @click="fetchCategories">
          Actualiser
        </Button>
      </template>

      <!-- Mobile : une carte par catégorie -->
      <template #cartes>
        <li v-for="categorie in categories" :key="categorie.id" class="flex gap-3 p-4">
          <img
            loading="lazy"
            :src="resolveImageUrl(categorie.image)"
            alt=""
            class="h-16 w-16 flex-shrink-0 rounded-xl bg-gray-100 object-cover"
            @error="onImageError"
          />
          <div class="min-w-0 flex-1 space-y-2">
            <div>
              <p class="font-semibold text-gray-900">{{ categorie.nom }}</p>
              <p class="text-sm text-gray-600">
                Ordre {{ categorie.ordre_affichage || 0 }}<template v-if="categorie.createur?.nom_complet">, ajoutée par {{ categorie.createur.nom_complet }}</template>
              </p>
            </div>
            <span class="badge" :class="categorie.est_actif ? 'badge-success' : 'badge-neutral'">
              {{ categorie.est_actif ? 'Active' : 'Inactive' }}
            </span>
            <div v-if="canManageCategorie(categorie)" class="flex flex-wrap gap-2 pt-1">
              <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(categorie)">
                Modifier<span class="sr-only"> {{ categorie.nom }}</span>
              </Button>
              <Button variant="ghost" size="sm" :icon="Trash2" :icon-size="16" class="text-red-700 hover:bg-red-50" @click="deleteCategorie(categorie)">
                Supprimer<span class="sr-only"> {{ categorie.nom }}</span>
              </Button>
            </div>
            <p v-else class="text-sm text-gray-600">Lecture seule</p>
          </div>
        </li>
      </template>

      <!-- Desktop : tableau -->
      <template #entete>
        <th scope="col" class="px-4 py-3 font-semibold">Catégorie</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Ordre</th>
        <th scope="col" class="px-4 py-3 font-semibold">Statut</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Actions</th>
      </template>
      <template #lignes>
        <tr v-for="categorie in categories" :key="categorie.id">
          <td class="px-4 py-3">
            <div class="flex items-center gap-3">
              <img
                loading="lazy"
                :src="resolveImageUrl(categorie.image)"
                alt=""
                class="h-12 w-12 flex-shrink-0 rounded-lg bg-gray-100 object-cover"
                @error="onImageError"
              />
              <div class="min-w-0">
                <p class="font-semibold text-gray-900">{{ categorie.nom }}</p>
                <p class="text-xs text-gray-600">{{ categorie.slug }}</p>
                <p v-if="categorie.createur?.nom_complet" class="text-xs text-gray-600">
                  Ajoutée par {{ categorie.createur.nom_complet }}
                </p>
              </div>
            </div>
          </td>
          <td class="px-4 py-3 text-right tabular-nums">{{ categorie.ordre_affichage || 0 }}</td>
          <td class="px-4 py-3">
            <span class="badge" :class="categorie.est_actif ? 'badge-success' : 'badge-neutral'">
              {{ categorie.est_actif ? 'Active' : 'Inactive' }}
            </span>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-2">
              <template v-if="canManageCategorie(categorie)">
                <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(categorie)">
                  Modifier<span class="sr-only"> {{ categorie.nom }}</span>
                </Button>
                <Button variant="ghost" size="sm" :icon="Trash2" :icon-size="16" class="text-red-700 hover:bg-red-50" @click="deleteCategorie(categorie)">
                  Supprimer<span class="sr-only"> {{ categorie.nom }}</span>
                </Button>
              </template>
              <span v-else class="text-xs text-gray-600">Lecture seule</span>
            </div>
          </td>
        </tr>
      </template>

      <template v-if="totalPages > 1" #pied>
        <Pagination
          :page="filtres.page"
          :derniere="totalPages"
          :desactive="loading"
          libelle="Pages de catégories"
          @update:page="(page) => mettreAJour({ page })"
        />
      </template>
    </TableauDonnees>

    <!-- Création / modification : tiroir, focus sur le nom -->
    <BaseModal
      :ouvert="showForm"
      :titre="isEditing ? 'Modifier la catégorie' : 'Nouvelle catégorie'"
      variante="tiroir"
      taille="lg"
      @fermer="closeForm"
    >
      <form id="formulaire-categorie" class="grid grid-cols-1 gap-5 sm:grid-cols-2" novalidate @submit.prevent="submitForm">
        <AlertMessage v-if="formError" ref="alerteFormulaire" type="error" tabindex="-1" class="sm:col-span-2">{{ formError }}</AlertMessage>
        <FormField v-slot="{ attrs }" label="Nom" requis>
          <input v-model="form.nom" v-bind="attrs" type="text" class="input" data-autofocus />
        </FormField>

        <FormField v-slot="{ attrs }" label="Ordre d'affichage" aide="Les petits nombres passent en premier.">
          <input v-model.number="form.ordre_affichage" v-bind="attrs" type="number" min="0" inputmode="numeric" class="input" />
        </FormField>

        <FormField v-slot="{ attrs }" label="Description" facultatif class="sm:col-span-2">
          <textarea v-model="form.description" v-bind="attrs" rows="3" class="input resize-y"></textarea>
        </FormField>

        <FormField v-slot="{ attrs }" label="Image" facultatif class="sm:col-span-2">
          <TeleversementImage
            v-model="imageFile"
            v-bind="attrs"
            :image-actuelle="imageActuelle"
            aide="Affichée sur la page d'accueil."
            @erreur="formError = $event"
          />
        </FormField>

        <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 sm:col-span-2">
          <input v-model="form.est_actif" type="checkbox" class="h-5 w-5 flex-shrink-0 rounded" />
          <span class="text-sm text-gray-800">Catégorie active (visible dans le catalogue)</span>
        </label>
      </form>

      <template #actions>
        <Button type="button" variant="outline" @click="closeForm">Annuler</Button>
        <Button type="submit" form="formulaire-categorie" variant="primary" :loading="saving">
          {{ isEditing ? 'Enregistrer' : 'Créer la catégorie' }}
        </Button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import { useFiltresUrl } from '@/composables/useFiltresUrl'
import { useErreurFormulaire } from '@/composables/useErreurFormulaire'
import api, { messageErreur } from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import EnTetePage from '@/components/common/EnTetePage.vue'
import BaseModal from '@/components/common/BaseModal.vue'
import FormField from '@/components/common/FormField.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import TableauDonnees from '@/components/common/TableauDonnees.vue'
import Pagination from '@/components/common/Pagination.vue'
import ChampRecherche from '@/components/common/ChampRecherche.vue'
import TeleversementImage from '@/components/common/TeleversementImage.vue'
import { Plus, Pencil, Trash2, RefreshCw, Tags } from 'lucide-vue-next'
import { resolveImageUrl, onImageError } from '@/utils/images'

const PAR_PAGE = 15
const FILTRES_VIDES = { search: '', est_actif: '' }

const authStore = useAuthStore()
const toastStore = useToastStore()
const { confirmer } = useConfirm()

const categories = ref([])
const loading = ref(false)
const erreurListe = ref('')
const saving = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const { erreur: formError, alerte: alerteFormulaire, signaler: signalerErreur } = useErreurFormulaire()
const totalCategories = ref(0)

// Filtres et page dans l'URL
const { filtres, mettreAJour, recharger } = useFiltresUrl({ ...FILTRES_VIDES, page: 1 }, (estActuel) => fetchCategories(estActuel))
const filtresActifs = computed(() => Object.keys(FILTRES_VIDES).some((cle) => filtres[cle] !== ''))
const reinitialiserFiltres = () => mettreAJour({ ...FILTRES_VIDES, page: 1 })
const totalPages = computed(() => Math.max(1, Math.ceil(totalCategories.value / PAR_PAGE)))

const formulaireVide = () => ({
  id: null,
  nom: '',
  description: '',
  ordre_affichage: 0,
  est_actif: true,
})
const form = ref(formulaireVide())

const imageFile = ref(null)
// Image déjà enregistrée (modification)
const imageActuelle = ref(null)

const canManageCategorie = (categorie) => {
  if (authStore.isAdmin) return true
  return Number(categorie?.created_by_user_id || 0) === Number(authStore.user?.id || 0)
}

const fetchCategories = async (estActuel = () => true) => {
  loading.value = true
  erreurListe.value = ''

  try {
    const params = { page: filtres.page, per_page: PAR_PAGE }
    if (filtres.search) params.search = filtres.search
    if (filtres.est_actif !== '') params.est_actif = filtres.est_actif

    const response = await api.admin.categories.getAll(params)
    if (!estActuel()) return
    if (response.data.success) {
      categories.value = response.data.data.data
      totalCategories.value = response.data.data.total

      // Page vidée (dernière catégorie supprimée, lien trop loin) : dernière page remplie
      if (categories.value.length === 0 && filtres.page > 1 && totalCategories.value > 0) {
        mettreAJour({ page: Math.max(1, Number(response.data.data.last_page) || filtres.page - 1) })
      }
    }
  } catch (error) {
    if (!estActuel()) return
    erreurListe.value = messageErreur(error, 'Impossible de charger les catégories.')
  } finally {
    // Un chargement dépassé laisse l'indicateur au plus récent
    if (estActuel()) loading.value = false
  }
}

const resetForm = () => {
  form.value = formulaireVide()
  imageFile.value = null
  imageActuelle.value = null
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

  resetForm()
  form.value = {
    id: categorie.id,
    nom: categorie.nom,
    description: categorie.description || '',
    ordre_affichage: categorie.ordre_affichage || 0,
    est_actif: !!categorie.est_actif,
  }
  imageActuelle.value = categorie.image || null
  isEditing.value = true
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  formError.value = ''
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
  formError.value = ''
  if (!form.value.nom.trim()) {
    signalerErreur('Donnez un nom à la catégorie.')
    return
  }

  saving.value = true
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
      recharger()
    }
  } catch (error) {
    signalerErreur(messageErreur(error, 'Erreur lors de l\'enregistrement.'))
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
    recharger()
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Erreur lors de la suppression.'))
  }
}
</script>
