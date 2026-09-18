<!-- ===================================
COMPOSANT FORMULAIRE ADRESSE (modale)
File: src/components/adresse/AdresseFormModal.vue
=================================== -->
<!--
  Ordre des champs : ville, puis quartier de cette ville (avec recherche), puis précisions facultatives.
  Sur mobile, la fenêtre s'ouvre en tiroir bas ; les boutons restent visibles en pied.
-->
<template>
  <BaseModal
    :titre="isEditing ? 'Modifier l\'adresse' : 'Nouvelle adresse de livraison'"
    variante="feuille"
    taille="lg"
    @fermer="$emit('close')"
  >
    <form :id="idFormulaire" class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-2" novalidate @submit.prevent="submit">
      <AlertMessage v-if="adresseSansQuartier" type="warning" class="md:col-span-2">
        Cette adresse n'a pas encore de quartier reconnu ({{ adresse.quartier }}) :
        choisissez-en un pour pouvoir être livré.
      </AlertMessage>

      <!-- 1. Ville : réduit la liste des quartiers -->
      <FormField v-slot="{ attrs }" label="Ville" requis :erreur="erreurs.ville">
        <select v-model="form.ville" v-bind="attrs" class="input" data-autofocus @change="changerVille">
          <option value="" disabled>Choisir la ville</option>
          <option v-for="ville in VILLES" :key="ville.valeur" :value="ville.valeur">
            {{ ville.libelle }}
          </option>
        </select>
      </FormField>

      <!-- 2. Quartier de cette ville (obligatoire) -->
      <FormField
        v-slot="{ attrs }"
        label="Quartier"
        requis
        :erreur="erreurs.quartier_id"
        :aide="!form.ville ? 'Choisissez d\'abord la ville.' : ''"
      >
        <select
          v-model="form.quartier_id"
          v-bind="attrs"
          class="input"
          :disabled="!form.ville || chargementQuartiers"
        >
          <option value="" disabled>
            {{ chargementQuartiers ? 'Chargement…' : 'Sélectionner un quartier' }}
          </option>
          <option v-for="quartier in quartiersFiltres" :key="quartier.id" :value="quartier.id">
            {{ quartier.nom }}
          </option>
        </select>
      </FormField>

      <div v-if="form.ville" class="md:col-span-2">
        <label class="sr-only" :for="idRecherche">Rechercher un quartier de {{ formatVille(form.ville) }}</label>
        <div class="relative">
          <Search :size="18" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500" aria-hidden="true" />
          <input
            :id="idRecherche"
            v-model="recherche"
            type="search"
            class="input pl-10 text-sm"
            :placeholder="`Filtrer les quartiers de ${formatVille(form.ville)}…`"
            :aria-describedby="idResultat"
          />
        </div>
        <p :id="idResultat" class="aide" aria-live="polite">
          <template v-if="recherche && quartiersFiltres.length === 0">
            Aucun quartier de {{ formatVille(form.ville) }} ne correspond à « {{ recherche }} ».
          </template>
          <template v-else-if="recherche">
            {{ quartiersFiltres.length }} quartier{{ quartiersFiltres.length > 1 ? 's' : '' }} dans la liste.
          </template>
        </p>
      </div>

      <!-- 3. Précisions facultatives -->
      <FormField v-slot="{ attrs }" label="Zone ou secteur" facultatif class="md:col-span-2">
        <input
          v-model="form.zone"
          v-bind="attrs"
          type="text"
          maxlength="150"
          class="input"
          placeholder="Ex. Carrefour Obili, entrée du lycée"
        />
      </FormField>

      <FormField
        v-slot="{ attrs }"
        label="Téléphone du destinataire"
        requis
        :erreur="erreurs.telephone_contact"
        aide="Le vendeur vous appelle à ce numéro pour la livraison."
      >
        <input
          v-model="form.telephone_contact"
          v-bind="attrs"
          type="tel"
          inputmode="tel"
          autocomplete="tel"
          class="input"
          placeholder="+237 6XX XX XX XX"
        />
      </FormField>

      <FormField v-slot="{ attrs }" label="Point de repère" facultatif>
        <input v-model="form.point_repere" v-bind="attrs" type="text" class="input" placeholder="Ex. Face à la pharmacie" />
      </FormField>

      <FormField v-slot="{ attrs }" label="Complément d'adresse" facultatif class="md:col-span-2">
        <textarea v-model="form.complement_adresse" v-bind="attrs" rows="2" class="input resize-none"></textarea>
      </FormField>

      <FormField v-slot="{ attrs }" label="Nom de l'adresse" facultatif aide="Pour la retrouver facilement : Maison, Bureau…" class="md:col-span-2">
        <input v-model="form.libelle" v-bind="attrs" type="text" class="input" placeholder="Maison" />
      </FormField>

      <label class="flex min-h-11 cursor-pointer items-center gap-3 md:col-span-2">
        <input v-model="form.est_principale" type="checkbox" class="h-5 w-5 flex-shrink-0 rounded" />
        <span class="text-sm font-medium text-gray-800">Utiliser comme adresse principale</span>
      </label>

      <AlertMessage v-if="error" type="error" class="md:col-span-2">{{ error }}</AlertMessage>
    </form>

    <template #actions>
      <Button type="button" variant="outline" @click="$emit('close')">
        Annuler
      </Button>
      <Button type="submit" :form="idFormulaire" variant="primary" :loading="saving">
        {{ isEditing ? 'Enregistrer les modifications' : 'Enregistrer l\'adresse' }}
      </Button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, useId } from 'vue'
import api, { messageErreur } from '@/services/api'
import BaseModal from '@/components/common/BaseModal.vue'
import Button from '@/components/common/Button.vue'
import FormField from '@/components/common/FormField.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import { useVilleStore } from '@/stores/ville'
import { normaliserTexte } from '@/utils/format'
import { VILLES, formatVille, villeAdresse } from '@/utils/villes'
import { Search } from 'lucide-vue-next'

const props = defineProps({
  // null = création, sinon l'adresse à modifier
  adresse: {
    type: Object,
    default: null
  },
  telephoneParDefaut: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['close', 'saved'])

const idFormulaire = useId()
const idRecherche = useId()
const idResultat = useId()

const villeStore = useVilleStore()
const isEditing = computed(() => Boolean(props.adresse?.id))
const adresseSansQuartier = computed(() => isEditing.value && !props.adresse?.quartier_id)

const form = ref({
  libelle: props.adresse?.libelle || '',
  // Nouvelle adresse : la ville choisie dans l'en-tête est proposée
  ville: villeAdresse(props.adresse) || (isEditing.value ? '' : villeStore.ville || ''),
  quartier_id: props.adresse?.quartier_id || '',
  zone: props.adresse?.zone || '',
  telephone_contact: props.adresse?.telephone_contact || props.telephoneParDefaut || '',
  point_repere: props.adresse?.point_repere || '',
  complement_adresse: props.adresse?.complement_adresse || '',
  est_principale: Boolean(props.adresse?.est_principale),
})

// { 'yaoundé': [{ id, nom, ville }], 'douala': [...] }
const quartiersParVille = ref({})
const chargementQuartiers = ref(false)
const recherche = ref('')
const saving = ref(false)
const error = ref('')
const erreurs = ref({})

const quartiersFiltres = computed(() => {
  const terme = normaliserTexte(recherche.value.trim())
  const quartiers = quartiersParVille.value[form.value.ville] || []

  // Le quartier déjà sélectionné reste visible même s'il ne correspond pas à la recherche
  return quartiers.filter((quartier) => !terme
    || normaliserTexte(quartier.nom).includes(terme)
    || Number(quartier.id) === Number(form.value.quartier_id))
})

const changerVille = () => {
  form.value.quartier_id = ''
  recherche.value = ''
}

const fetchQuartiers = async () => {
  chargementQuartiers.value = true
  try {
    const response = await api.livraison.quartiers()
    if (response.data.success) {
      quartiersParVille.value = response.data.data || {}
    }
  } catch (err) {
    error.value = messageErreur(err, 'Impossible de charger la liste des quartiers.')
  } finally {
    chargementQuartiers.value = false
  }
}

// Contrôle côté navigateur : message sous le champ concerné plutôt qu'une bulle native
const valider = () => {
  const manquants = {}
  if (!form.value.ville) manquants.ville = 'Choisissez la ville de livraison.'
  if (!form.value.quartier_id) manquants.quartier_id = 'Choisissez le quartier.'
  if (!form.value.telephone_contact.trim()) manquants.telephone_contact = 'Indiquez un numéro pour joindre le destinataire.'
  erreurs.value = manquants
  return Object.keys(manquants).length === 0
}

const submit = async () => {
  error.value = ''
  if (!valider()) {
    // Le focus va au premier champ en erreur (3.3.1)
    await nextTick()
    document.querySelector(`#${CSS.escape(idFormulaire)} [aria-invalid="true"]`)?.focus()
    return
  }

  saving.value = true

  // Le backend reprend le nom du quartier et sa ville à partir de quartier_id
  const payload = {
    libelle: form.value.libelle.trim() || null,
    ville: form.value.ville,
    quartier_id: form.value.quartier_id,
    zone: form.value.zone.trim() || null,
    telephone_contact: form.value.telephone_contact.trim(),
    point_repere: form.value.point_repere.trim() || null,
    complement_adresse: form.value.complement_adresse.trim() || null,
    est_principale: form.value.est_principale,
  }

  try {
    const response = isEditing.value
      ? await api.adresses.update(props.adresse.id, payload)
      : await api.adresses.create(payload)

    if (response.data.success) {
      emit('saved', response.data.data)
    } else {
      error.value = response.data?.message || 'Erreur lors de l\'enregistrement de l\'adresse.'
    }
  } catch (err) {
    error.value = messageErreur(err, 'Erreur lors de l\'enregistrement de l\'adresse.')
  } finally {
    saving.value = false
  }
}

onMounted(fetchQuartiers)
</script>
