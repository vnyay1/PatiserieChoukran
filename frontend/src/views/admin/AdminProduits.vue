<!-- ===================================
ADMIN / VENDEUR - GESTION DES PRODUITS
File: src/views/admin/AdminProduits.vue
=================================== -->
<!--
  Filtres et page dans l'URL (appliqués dès qu'ils changent). Liste dans TableauDonnees : cartes
  sur mobile, tableau dès md. Création et modification dans un tiroir (BaseModal) : focus sur le
  premier champ, rendu au bouton d'origine à la fermeture.
-->
<template>
  <div class="container mx-auto max-w-6xl pb-6 pt-6 md:pt-8">
    <EnTetePage titre="Produits" sous-titre="Ajouter, modifier et retirer les produits du catalogue.">
      <template #actions>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="openCreate">
          Nouveau produit
        </Button>
      </template>
    </EnTetePage>

    <Card padding="md" class="mb-6">
      <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-4">
        <ChampRecherche
          id="filtre-produits-recherche"
          v-model="filtres.search"
          libelle="Recherche"
          libelle-visible
          placeholder="Nom ou description…"
          class="md:col-span-2"
          @rechercher="mettreAJour({ page: 1 })"
        />
        <div>
          <label for="filtre-produits-categorie" class="label">Catégorie</label>
          <select id="filtre-produits-categorie" v-model="filtres.categorie_id" class="input" @change="mettreAJour({ page: 1 })">
            <option value="">Toutes</option>
            <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
              {{ cat.nom }}
            </option>
          </select>
        </div>
        <div>
          <label for="filtre-produits-disponibilite" class="label">Disponibilité</label>
          <select id="filtre-produits-disponibilite" v-model="filtres.est_disponible" class="input" @change="mettreAJour({ page: 1 })">
            <option value="">Toutes</option>
            <option value="1">Disponibles</option>
            <option value="0">Indisponibles</option>
          </select>
        </div>
        <div class="flex md:col-span-4 md:justify-end">
          <Button variant="outline" size="sm" :disabled="!filtresActifs" @click="reinitialiserFiltres">
            Réinitialiser les filtres
          </Button>
        </div>
      </div>
    </Card>

    <TableauDonnees
      libelle="Produits"
      :chargement="loading"
      :erreur="erreurListe"
      :vide="produits.length === 0"
      :icone="PackageOpen"
      :titre-vide="filtresActifs ? 'Aucun produit trouvé' : 'Aucun produit pour le moment'"
      :texte-vide="filtresActifs ? 'Aucun produit ne correspond à ces filtres.' : 'Ajoutez votre premier produit pour l\'afficher dans le catalogue.'"
      @reessayer="fetchProduits"
    >
      <template #barre>
        <p class="text-sm text-gray-600">{{ totalProduits }} produit{{ totalProduits > 1 ? 's' : '' }}</p>
        <Button variant="outline" size="sm" :icon="RefreshCw" :icon-size="16" :loading="loading" @click="fetchProduits">
          Actualiser
        </Button>
      </template>

      <!-- Mobile : une carte par produit -->
      <template #cartes>
        <li v-for="produit in produits" :key="produit.id" class="flex gap-3 p-4">
          <img
            loading="lazy"
            :src="resolveImageUrl(produit.image_principale)"
            alt=""
            class="h-16 w-16 flex-shrink-0 rounded-xl bg-gray-100 object-cover"
            @error="onImageError"
          />
          <div class="min-w-0 flex-1 space-y-2">
            <div>
              <p class="font-semibold text-gray-900">{{ produit.nom }}</p>
              <p class="text-sm text-gray-600">
                {{ produit.categorie?.nom || 'Sans catégorie' }}<template v-if="isAdmin && produit.createur?.nom_complet">, {{ produit.createur.nom_complet }}</template>
              </p>
            </div>
            <p class="flex flex-wrap items-baseline gap-x-3">
              <span class="price text-base">{{ formatPrice(produit.prix_promo || produit.prix_unitaire) }} FCFA</span>
              <span v-if="produit.prix_promo" class="price-old text-xs">{{ formatPrice(produit.prix_unitaire) }} FCFA</span>
              <span class="text-sm text-gray-600">Stock : {{ produit.stock_disponible }}</span>
            </p>
            <div class="flex flex-wrap gap-2">
              <span class="badge" :class="produit.est_disponible ? 'badge-success' : 'badge-danger'">
                {{ produit.est_disponible ? 'Disponible' : 'Indisponible' }}
              </span>
              <span v-if="produit.createur?.est_vendeur_vedette" class="badge badge-primary">Vendeur vedette</span>
            </div>
            <div class="flex flex-wrap gap-2 pt-1">
              <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(produit)">
                Modifier<span class="sr-only"> {{ produit.nom }}</span>
              </Button>
              <Button variant="ghost" size="sm" :icon="Trash2" :icon-size="16" class="text-red-700 hover:bg-red-50" @click="deleteProduit(produit)">
                Supprimer<span class="sr-only"> {{ produit.nom }}</span>
              </Button>
            </div>
          </div>
        </li>
      </template>

      <!-- Desktop : tableau -->
      <template #entete>
        <th scope="col" class="px-4 py-3 font-semibold">Produit</th>
        <th scope="col" class="px-4 py-3 font-semibold">Prix</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Stock</th>
        <th scope="col" class="px-4 py-3 font-semibold">Statut</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Actions</th>
      </template>
      <template #lignes>
        <tr v-for="produit in produits" :key="produit.id">
          <td class="px-4 py-3">
            <div class="flex items-center gap-3">
              <img
                loading="lazy"
                :src="resolveImageUrl(produit.image_principale)"
                alt=""
                class="h-12 w-12 flex-shrink-0 rounded-lg bg-gray-100 object-cover"
                @error="onImageError"
              />
              <div class="min-w-0">
                <p class="font-semibold text-gray-900">{{ produit.nom }}</p>
                <p class="text-xs text-gray-600">
                  {{ produit.categorie?.nom || 'Sans catégorie' }}<template v-if="isAdmin && produit.createur?.nom_complet">, {{ produit.createur.nom_complet }}</template>
                </p>
              </div>
            </div>
          </td>
          <td class="whitespace-nowrap px-4 py-3">
            <p class="font-semibold text-gray-900">{{ formatPrice(produit.prix_promo || produit.prix_unitaire) }} FCFA</p>
            <p v-if="produit.prix_promo" class="price-old text-xs">{{ formatPrice(produit.prix_unitaire) }} FCFA</p>
          </td>
          <td class="px-4 py-3 text-right tabular-nums">{{ produit.stock_disponible }}</td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-2">
              <span class="badge" :class="produit.est_disponible ? 'badge-success' : 'badge-danger'">
                {{ produit.est_disponible ? 'Disponible' : 'Indisponible' }}
              </span>
              <span v-if="produit.createur?.est_vendeur_vedette" class="badge badge-primary">Vendeur vedette</span>
            </div>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-2">
              <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="openEdit(produit)">
                Modifier<span class="sr-only"> {{ produit.nom }}</span>
              </Button>
              <Button variant="ghost" size="sm" :icon="Trash2" :icon-size="16" class="text-red-700 hover:bg-red-50" @click="deleteProduit(produit)">
                Supprimer<span class="sr-only"> {{ produit.nom }}</span>
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
          libelle="Pages de produits"
          @update:page="(page) => mettreAJour({ page })"
        />
      </template>
    </TableauDonnees>

    <!-- Création / modification : tiroir, focus sur le nom -->
    <BaseModal
      :ouvert="showForm"
      :titre="isEditing ? 'Modifier le produit' : 'Nouveau produit'"
      variante="tiroir"
      taille="lg"
      @fermer="closeForm"
    >
      <form id="formulaire-produit" class="grid grid-cols-1 gap-5 sm:grid-cols-2" novalidate @submit.prevent="submitForm">
        <FormField v-slot="{ attrs }" label="Nom" requis class="sm:col-span-2">
          <input v-model="form.nom" v-bind="attrs" type="text" class="input" data-autofocus />
        </FormField>

        <FormField v-slot="{ attrs }" label="Catégorie" requis>
          <select v-model="form.categorie_id" v-bind="attrs" class="input">
            <option value="">Choisir…</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.nom }}
            </option>
          </select>
        </FormField>

        <FormField v-slot="{ attrs }" label="Stock" requis>
          <input v-model.number="form.stock_disponible" v-bind="attrs" type="number" min="0" inputmode="numeric" class="input" />
        </FormField>

        <FormField v-slot="{ attrs }" label="Prix (FCFA)" requis>
          <input v-model.number="form.prix_unitaire" v-bind="attrs" type="number" min="0" step="0.01" inputmode="decimal" class="input" />
        </FormField>

        <FormField v-slot="{ attrs }" label="Prix promotionnel (FCFA)" :aide="form.promo_active ? '' : 'Cochez « Activer le prix promotionnel » pour le saisir.'">
          <input
            v-model="form.prix_promo"
            v-bind="attrs"
            type="number"
            min="0"
            step="0.01"
            inputmode="decimal"
            class="input"
            :disabled="!form.promo_active"
          />
        </FormField>

        <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 sm:col-span-2">
          <input v-model="form.promo_active" type="checkbox" class="h-5 w-5 flex-shrink-0 rounded" />
          <span class="text-sm text-gray-800">Activer le prix promotionnel</span>
        </label>

        <FormField v-slot="{ attrs }" label="Description" facultatif class="sm:col-span-2">
          <textarea v-model="form.description" v-bind="attrs" rows="4" class="input resize-y"></textarea>
        </FormField>

        <FormField v-slot="{ attrs }" label="Image principale" class="sm:col-span-2">
          <TeleversementImage
            v-model="imagePrincipale"
            v-bind="attrs"
            :image-actuelle="imagePrincipaleActuelle"
            @erreur="formError = $event"
          />
        </FormField>

        <FormField v-slot="{ attrs }" label="Images secondaires (4 au plus)" facultatif class="sm:col-span-2" :aide="aideImagesSecondaires">
          <input
            :key="fileInputKey"
            v-bind="attrs"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            multiple
            class="input"
            @change="onImagesSecondaires"
          />
        </FormField>

        <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 sm:col-span-2">
          <input v-model="form.est_disponible" type="checkbox" class="h-5 w-5 flex-shrink-0 rounded" />
          <span class="text-sm text-gray-800">Produit disponible à la vente</span>
        </label>

        <AlertMessage v-if="formError" type="error" class="sm:col-span-2">{{ formError }}</AlertMessage>
      </form>

      <template #actions>
        <Button type="button" variant="outline" @click="closeForm">Annuler</Button>
        <Button type="submit" form="formulaire-produit" variant="primary" :loading="saving">
          {{ isEditing ? 'Enregistrer' : 'Créer le produit' }}
        </Button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api, { messageErreur } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import { useFiltresUrl } from '@/composables/useFiltresUrl'
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
import { Plus, Pencil, Trash2, RefreshCw, PackageOpen } from 'lucide-vue-next'
import { resolveImageUrl, onImageError, verifierImage, TAILLE_MAX_IMAGE_MO } from '@/utils/images'
import { formatPrice } from '@/utils/format'
import { chargerToutesLesPages } from '@/utils/pagination'

const PAR_PAGE = 12
const FILTRES_VIDES = { search: '', categorie_id: '', est_disponible: '' }

const produits = ref([])
const categories = ref([])
const loading = ref(false)
const erreurListe = ref('')
const saving = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const formError = ref('')
const totalProduits = ref(0)

const authStore = useAuthStore()
const toastStore = useToastStore()
const { confirmer } = useConfirm()
const isAdmin = computed(() => authStore.isAdmin)

// Filtres et page dans l'URL
const { filtres, mettreAJour, recharger } = useFiltresUrl({ ...FILTRES_VIDES, page: 1 }, (estActuel) => fetchProduits(estActuel))
const filtresActifs = computed(() => Object.keys(FILTRES_VIDES).some((cle) => filtres[cle] !== ''))
const reinitialiserFiltres = () => mettreAJour({ ...FILTRES_VIDES, page: 1 })
const totalPages = computed(() => Math.max(1, Math.ceil(totalProduits.value / PAR_PAGE)))

const formulaireVide = () => ({
  id: null,
  categorie_id: '',
  nom: '',
  description: '',
  prix_unitaire: '',
  prix_promo: '',
  promo_active: false,
  stock_disponible: 0,
  est_disponible: true,
})
const form = ref(formulaireVide())

const imagePrincipale = ref(null)
const imagesSecondaires = ref([])
// Images déjà enregistrées (modification)
const imagePrincipaleActuelle = ref(null)
const nbImagesSecondairesActuelles = ref(0)
// Change pour vider le champ des images secondaires
const fileInputKey = ref(0)

const aideImagesSecondaires = computed(() => {
  const formats = `JPEG, PNG ou WebP, ${TAILLE_MAX_IMAGE_MO} Mo maximum par image.`
  if (imagesSecondaires.value.length) {
    return `${formats} ${imagesSecondaires.value.length} image(s) choisie(s).`
  }
  if (isEditing.value && nbImagesSecondairesActuelles.value) {
    return `${formats} ${nbImagesSecondairesActuelles.value} image(s) actuelle(s) : un nouvel envoi les remplace.`
  }
  return formats
})

const fetchCategories = async () => {
  try {
    categories.value = await chargerToutesLesPages(api.admin.categories.getAll)
  } catch (error) {
    console.error('Erreur chargement catégories:', error)
  }
}

const fetchProduits = async (estActuel = () => true) => {
  loading.value = true
  erreurListe.value = ''

  try {
    const params = { page: filtres.page, per_page: PAR_PAGE }
    if (filtres.search) params.search = filtres.search
    if (filtres.categorie_id) params.categorie_id = filtres.categorie_id
    if (filtres.est_disponible !== '') params.est_disponible = filtres.est_disponible

    const response = await api.admin.produits.getAll(params)
    if (!estActuel()) return
    if (response.data.success) {
      produits.value = response.data.data.data
      totalProduits.value = response.data.data.total

      // Page vidée (dernier produit supprimé, lien trop loin) : dernière page remplie
      if (produits.value.length === 0 && filtres.page > 1 && totalProduits.value > 0) {
        mettreAJour({ page: Math.max(1, Number(response.data.data.last_page) || filtres.page - 1) })
      }
    }
  } catch (error) {
    if (!estActuel()) return
    erreurListe.value = messageErreur(error, 'Impossible de charger les produits.')
  } finally {
    // Un chargement dépassé laisse l'indicateur au plus récent
    if (estActuel()) loading.value = false
  }
}

const resetForm = () => {
  form.value = formulaireVide()
  imagePrincipale.value = null
  imagesSecondaires.value = []
  imagePrincipaleActuelle.value = null
  nbImagesSecondairesActuelles.value = 0
  fileInputKey.value += 1
  formError.value = ''
}

const openCreate = () => {
  resetForm()
  isEditing.value = false
  showForm.value = true
}

const openEdit = (produit) => {
  resetForm()
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
  }
  imagePrincipaleActuelle.value = produit.image_principale || null
  nbImagesSecondairesActuelles.value = produit.images_secondaires?.length || 0
  isEditing.value = true
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  formError.value = ''
}

const onImagesSecondaires = (event) => {
  const fichiers = event.target.files ? Array.from(event.target.files) : []

  const erreur = fichiers.length > 4
    ? 'Vous pouvez envoyer 4 images secondaires au maximum.'
    : fichiers.map(verifierImage).find(Boolean)

  if (erreur) {
    formError.value = erreur
    imagesSecondaires.value = []
    event.target.value = ''
    return
  }

  formError.value = ''
  imagesSecondaires.value = fichiers
}

const buildFormData = () => {
  const data = new FormData()

  data.append('categorie_id', form.value.categorie_id)
  data.append('nom', form.value.nom)
  data.append('prix_unitaire', form.value.prix_unitaire)
  data.append('stock_disponible', form.value.stock_disponible)
  data.append('est_disponible', form.value.est_disponible ? 1 : 0)

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
  formError.value = ''

  if (!form.value.nom.trim() || !form.value.categorie_id || form.value.prix_unitaire === '') {
    formError.value = 'Renseignez au moins le nom, la catégorie et le prix.'
    return
  }
  if (form.value.promo_active && (form.value.prix_promo === '' || form.value.prix_promo === null)) {
    formError.value = 'Renseignez un prix promotionnel ou désactivez la promotion.'
    return
  }

  saving.value = true
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
      toastStore.succes(isEditing.value ? 'Produit mis à jour.' : 'Produit créé.')
      showForm.value = false
      recharger()
    }
  } catch (error) {
    formError.value = messageErreur(error, 'Erreur lors de l\'enregistrement.')
  } finally {
    saving.value = false
  }
}

const deleteProduit = async (produit) => {
  const confirmed = await confirmer({
    titre: 'Supprimer le produit',
    message: `Le produit « ${produit.nom} » sera supprimé définitivement.`,
    libelleConfirmer: 'Supprimer',
    danger: true,
  })
  if (!confirmed) return

  try {
    await api.admin.produits.remove(produit.id)
    toastStore.succes('Produit supprimé.')
    recharger()
  } catch (error) {
    // Produit présent dans des commandes : on propose de le retirer de la vente
    if (error.response?.data?.peut_desactiver) {
      const desactiver = await confirmer({
        titre: 'Suppression impossible',
        message: `${error.response.data.message}\n\nVoulez-vous le rendre indisponible à la vente ?`,
        libelleConfirmer: 'Rendre indisponible',
      })
      if (desactiver) await rendreIndisponible(produit)
      return
    }
    toastStore.erreur(messageErreur(error, 'Erreur lors de la suppression.'))
  }
}

const rendreIndisponible = async (produit) => {
  try {
    const data = new FormData()
    data.append('est_disponible', 0)
    data.append('_method', 'PUT')
    await api.admin.produits.update(produit.id, data)
    toastStore.succes(`« ${produit.nom} » n'est plus proposé à la vente.`)
    recharger()
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Impossible de modifier le produit.'))
  }
}

fetchCategories()
</script>
