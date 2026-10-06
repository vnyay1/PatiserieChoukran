<!-- ===================================
ADMIN - QUARTIERS (liste proposée dans les adresses)
File: src/views/admin/AdminQuartiers.vue
=================================== -->
<!--
  Filtres et page dans l'URL, TableauDonnees (cartes sur mobile), formulaire dans un tiroir.
  Un quartier utilisé par des adresses ne se supprime pas : il se désactive (bouton dédié,
  le statut n'est plus un badge cliquable).
-->
<template>
  <div class="container mx-auto max-w-5xl pb-6 pt-6 md:pt-8">
    <EnTetePage titre="Quartiers">
      <template #sous-titre>
        Quartiers proposés aux clients dans leurs adresses, par ville. Un quartier désactivé
        n'est plus proposé mais les adresses existantes le conservent.
      </template>
      <template #actions>
        <Button variant="primary" :icon="Plus" :icon-size="18" @click="ouvrirCreation">
          Nouveau quartier
        </Button>
      </template>
    </EnTetePage>

    <Card padding="md" class="mb-6">
      <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-3">
        <ChampRecherche
          id="filtre-quartiers-recherche"
          v-model="filtres.search"
          libelle="Recherche"
          libelle-visible
          placeholder="Nom du quartier…"
          class="md:col-span-2"
          @rechercher="mettreAJour({ page: 1 })"
        />
        <div>
          <label for="filtre-quartiers-ville" class="label">Ville</label>
          <select id="filtre-quartiers-ville" v-model="filtres.ville" class="input" @change="mettreAJour({ page: 1 })">
            <option value="">Toutes les villes</option>
            <option v-for="ville in VILLES" :key="ville.valeur" :value="ville.valeur">{{ ville.libelle }}</option>
          </select>
        </div>
      </div>
    </Card>

    <TableauDonnees
      libelle="Quartiers"
      :chargement="loading"
      :erreur="erreurListe"
      :vide="quartiers.length === 0"
      :icone="MapPinned"
      :titre-vide="filtresActifs ? 'Aucun quartier trouvé' : 'Aucun quartier'"
      :texte-vide="filtresActifs ? 'Aucun quartier ne correspond à ces filtres.' : ''"
      @reessayer="charger"
    >
      <template #barre>
        <p class="text-sm text-gray-600">{{ total }} quartier{{ total > 1 ? 's' : '' }}</p>
      </template>

      <!-- Mobile : une carte par quartier -->
      <template #cartes>
        <li v-for="quartier in quartiers" :key="quartier.id" class="space-y-2 p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-semibold text-gray-900">{{ quartier.nom }}</p>
              <p class="text-sm text-gray-600">
                {{ formatVille(quartier.ville) }}, {{ quartier.adresses_count }} adresse{{ quartier.adresses_count > 1 ? 's' : '' }}
              </p>
            </div>
            <span class="badge flex-shrink-0" :class="quartier.actif ? 'badge-success' : 'badge-neutral'">
              {{ quartier.actif ? 'Proposé' : 'Désactivé' }}
            </span>
          </div>
          <div class="flex flex-wrap gap-2 pt-1">
            <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="ouvrirModification(quartier)">
              Modifier<span class="sr-only"> {{ quartier.nom }}</span>
            </Button>
            <Button variant="ghost" size="sm" :loading="bascule === quartier.id" @click="basculerActif(quartier)">
              {{ quartier.actif ? 'Désactiver' : 'Réactiver' }}<span class="sr-only"> {{ quartier.nom }}</span>
            </Button>
            <Button
              v-if="!quartier.adresses_count"
              variant="ghost"
              size="sm"
              :icon="Trash2"
              :icon-size="16"
              class="text-red-700 hover:bg-red-50"
              @click="supprimer(quartier)"
            >
              Supprimer<span class="sr-only"> {{ quartier.nom }}</span>
            </Button>
          </div>
        </li>
      </template>

      <!-- Desktop : tableau -->
      <template #entete>
        <th scope="col" class="px-4 py-3 font-semibold">Quartier</th>
        <th scope="col" class="px-4 py-3 font-semibold">Ville</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Adresses</th>
        <th scope="col" class="px-4 py-3 font-semibold">Statut</th>
        <th scope="col" class="px-4 py-3 text-right font-semibold">Actions</th>
      </template>
      <template #lignes>
        <tr v-for="quartier in quartiers" :key="quartier.id">
          <td class="px-4 py-3 font-semibold text-gray-900">{{ quartier.nom }}</td>
          <td class="px-4 py-3 text-gray-700">{{ formatVille(quartier.ville) }}</td>
          <td class="px-4 py-3 text-right tabular-nums">{{ quartier.adresses_count }}</td>
          <td class="px-4 py-3">
            <span class="badge" :class="quartier.actif ? 'badge-success' : 'badge-neutral'">
              {{ quartier.actif ? 'Proposé' : 'Désactivé' }}
            </span>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-2">
              <Button variant="outline" size="sm" :icon="Pencil" :icon-size="16" @click="ouvrirModification(quartier)">
                Modifier<span class="sr-only"> {{ quartier.nom }}</span>
              </Button>
              <Button variant="ghost" size="sm" :loading="bascule === quartier.id" @click="basculerActif(quartier)">
                {{ quartier.actif ? 'Désactiver' : 'Réactiver' }}<span class="sr-only"> {{ quartier.nom }}</span>
              </Button>
              <!-- Utilisé par des adresses : on le désactive au lieu de le supprimer -->
              <Button
                v-if="!quartier.adresses_count"
                variant="ghost"
                size="sm"
                :icon="Trash2"
                :icon-size="16"
                class="text-red-700 hover:bg-red-50"
                @click="supprimer(quartier)"
              >
                Supprimer<span class="sr-only"> {{ quartier.nom }}</span>
              </Button>
            </div>
          </td>
        </tr>
      </template>

      <template v-if="dernierePage > 1" #pied>
        <Pagination
          :page="filtres.page"
          :derniere="dernierePage"
          :desactive="loading"
          libelle="Pages de quartiers"
          @update:page="(page) => mettreAJour({ page })"
        />
      </template>
    </TableauDonnees>

    <!-- Création / modification : tiroir, focus sur le nom -->
    <BaseModal
      :ouvert="formulaireOuvert"
      :titre="form.id ? 'Modifier le quartier' : 'Nouveau quartier'"
      variante="tiroir"
      @fermer="formulaireOuvert = false"
    >
      <form id="formulaire-quartier" class="space-y-5" novalidate @submit.prevent="enregistrer">
        <FormField v-slot="{ attrs }" label="Nom" requis>
          <input v-model="form.nom" v-bind="attrs" type="text" maxlength="150" class="input" data-autofocus />
        </FormField>
        <FormField v-slot="{ attrs }" label="Ville" requis>
          <select v-model="form.ville" v-bind="attrs" class="input">
            <option v-for="ville in VILLES" :key="ville.valeur" :value="ville.valeur">{{ ville.libelle }}</option>
          </select>
        </FormField>
        <label class="inline-flex min-h-11 cursor-pointer items-center gap-2">
          <input v-model="form.actif" type="checkbox" class="h-5 w-5 flex-shrink-0 rounded" />
          <span class="text-sm text-gray-800">Proposé aux clients</span>
        </label>
        <AlertMessage v-if="erreurFormulaire" type="error">{{ erreurFormulaire }}</AlertMessage>
      </form>

      <template #actions>
        <Button type="button" variant="outline" @click="formulaireOuvert = false">Annuler</Button>
        <Button type="submit" form="formulaire-quartier" variant="primary" :loading="saving">
          {{ form.id ? 'Enregistrer' : 'Ajouter le quartier' }}
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
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import EnTetePage from '@/components/common/EnTetePage.vue'
import BaseModal from '@/components/common/BaseModal.vue'
import FormField from '@/components/common/FormField.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import TableauDonnees from '@/components/common/TableauDonnees.vue'
import Pagination from '@/components/common/Pagination.vue'
import ChampRecherche from '@/components/common/ChampRecherche.vue'
import { VILLES, formatVille } from '@/utils/villes'
import { Plus, Pencil, Trash2, MapPinned } from 'lucide-vue-next'

const PAR_PAGE = 30

const toastStore = useToastStore()
const { confirmer } = useConfirm()

const quartiers = ref([])
const loading = ref(false)
const erreurListe = ref('')
const saving = ref(false)
const bascule = ref(null)
const dernierePage = ref(1)
const total = ref(0)
const formulaireOuvert = ref(false)
const erreurFormulaire = ref('')
const form = ref({ id: null, nom: '', ville: VILLES[0].valeur, actif: true })

// Filtres et page dans l'URL
const { filtres, mettreAJour, recharger } = useFiltresUrl({ search: '', ville: '', page: 1 }, (estActuel) => charger(estActuel))
const filtresActifs = computed(() => Boolean(filtres.search || filtres.ville))

const charger = async (estActuel = () => true) => {
  loading.value = true
  erreurListe.value = ''
  try {
    const params = { page: filtres.page, per_page: PAR_PAGE }
    if (filtres.search) params.search = filtres.search
    if (filtres.ville) params.ville = filtres.ville

    const response = await api.admin.quartiers.getAll(params)
    if (!estActuel()) return
    const pagination = response.data.data
    quartiers.value = pagination.data
    dernierePage.value = pagination.last_page
    total.value = pagination.total

    // Page vidée (dernier quartier supprimé, lien trop loin) : dernière page remplie
    if (quartiers.value.length === 0 && filtres.page > 1 && total.value > 0) {
      mettreAJour({ page: Math.max(1, Number(pagination.last_page) || filtres.page - 1) })
    }
  } catch (error) {
    if (!estActuel()) return
    erreurListe.value = messageErreur(error, 'Impossible de charger les quartiers.')
  } finally {
    // Un chargement dépassé laisse l'indicateur au plus récent
    if (estActuel()) loading.value = false
  }
}

const ouvrirCreation = () => {
  form.value = { id: null, nom: '', ville: filtres.ville || VILLES[0].valeur, actif: true }
  erreurFormulaire.value = ''
  formulaireOuvert.value = true
}

const ouvrirModification = (quartier) => {
  form.value = { id: quartier.id, nom: quartier.nom, ville: quartier.ville, actif: Boolean(quartier.actif) }
  erreurFormulaire.value = ''
  formulaireOuvert.value = true
}

const enregistrer = async () => {
  erreurFormulaire.value = ''
  if (!form.value.nom.trim()) {
    erreurFormulaire.value = 'Indiquez le nom du quartier.'
    return
  }

  saving.value = true
  const donnees = { nom: form.value.nom.trim(), ville: form.value.ville, actif: form.value.actif }

  try {
    if (form.value.id) {
      await api.admin.quartiers.update(form.value.id, donnees)
    } else {
      await api.admin.quartiers.create(donnees)
    }
    toastStore.succes(form.value.id ? 'Quartier mis à jour.' : 'Quartier ajouté.')
    formulaireOuvert.value = false
    await recharger()
  } catch (error) {
    erreurFormulaire.value = messageErreur(error, 'Impossible d\'enregistrer le quartier.')
  } finally {
    saving.value = false
  }
}

const basculerActif = async (quartier) => {
  bascule.value = quartier.id
  try {
    await api.admin.quartiers.update(quartier.id, { actif: !quartier.actif })
    quartier.actif = !quartier.actif
    toastStore.succes(quartier.actif ? `« ${quartier.nom} » est de nouveau proposé.` : `« ${quartier.nom} » n'est plus proposé.`)
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Impossible de modifier le quartier.'))
  } finally {
    bascule.value = null
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
    await recharger()
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Impossible de supprimer le quartier.'))
  }
}
</script>
