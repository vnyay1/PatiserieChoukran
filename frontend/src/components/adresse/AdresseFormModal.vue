<!-- ===================================
COMPOSANT FORMULAIRE ADRESSE (modale)
File: src/components/adresse/AdresseFormModal.vue
=================================== -->

<template>
  <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4 py-6">
    <div class="bg-white w-full max-w-2xl rounded-elegant shadow-card overflow-hidden flex flex-col max-h-[90vh] md:max-h-none">
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-display text-xl font-bold text-gray-800">
          {{ isEditing ? 'Modifier une adresse' : 'Ajouter une adresse' }}
        </h2>
        <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="$emit('close')">
          Fermer
        </button>
      </div>

      <form
        class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 overflow-y-auto md:overflow-visible max-h-[70vh] md:max-h-none scrollbar-hide"
        @submit.prevent="submit"
      >
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Libellé (optionnel)</label>
          <input v-model="form.libelle" type="text" class="input" placeholder="Maison, Bureau..." />
        </div>

        <!-- 1. Ville : réduit la liste des quartiers -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
          <select v-model="form.ville" class="input" required @change="form.quartier_id = ''">
            <option value="">Choisir la ville</option>
            <option v-for="ville in VILLES" :key="ville.valeur" :value="ville.valeur">
              {{ ville.libelle }}
            </option>
          </select>
        </div>

        <!-- 2. Quartier de cette ville (obligatoire) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Quartier *</label>
          <select
            v-model="form.quartier_id"
            class="input"
            required
            :disabled="!form.ville || chargementQuartiers"
          >
            <option value="">
              {{ !form.ville ? 'Choisissez d\'abord la ville' : (chargementQuartiers ? 'Chargement...' : 'Sélectionner un quartier') }}
            </option>
            <option v-for="quartier in quartiersFiltres" :key="quartier.id" :value="quartier.id">
              {{ quartier.nom }}
            </option>
          </select>
        </div>

        <div v-if="form.ville" class="md:col-span-2 -mt-2">
          <input
            v-model="recherche"
            type="search"
            class="input text-sm"
            :placeholder="`Rechercher un quartier de ${formatVille(form.ville)}...`"
          />
          <p v-if="recherche && quartiersFiltres.length === 0" class="text-xs text-gray-500 mt-1">
            Aucun quartier de {{ formatVille(form.ville) }} ne correspond à « {{ recherche }} ».
          </p>
        </div>

        <p v-if="adresseSansQuartier" class="md:col-span-2 text-xs text-orange-600 -mt-2">
          Cette adresse n'a pas encore de quartier reconnu ({{ adresse.quartier }}) :
          choisissez-en un pour pouvoir être livré.
        </p>

        <!-- 3. Zone libre (facultative) -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Zone / secteur (facultatif)</label>
          <input
            v-model="form.zone"
            type="text"
            maxlength="150"
            class="input"
            placeholder="Ex. Carrefour Obili, entrée du lycée..."
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
          <input
            v-model="form.telephone_contact"
            type="tel"
            class="input"
            placeholder="+237699123456"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Point de repère</label>
          <input v-model="form.point_repere" type="text" class="input" />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Complément d'adresse</label>
          <textarea v-model="form.complement_adresse" rows="2" class="input resize-none"></textarea>
        </div>

        <div class="md:col-span-2">
          <label class="inline-flex items-center gap-2">
            <input
              v-model="form.est_principale"
              type="checkbox"
              class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
            />
            <span class="text-sm text-gray-700">Définir comme adresse principale</span>
          </label>
        </div>

        <p v-if="error" class="text-sm text-red-600 md:col-span-2">
          {{ error }}
        </p>

        <div class="md:col-span-2 flex gap-3">
          <Button type="submit" variant="primary" :loading="saving">
            {{ isEditing ? 'Mettre à jour' : 'Enregistrer' }}
          </Button>
          <Button type="button" variant="outline" @click="$emit('close')">
            Annuler
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api, { messageErreur } from '@/services/api'
import Button from '@/components/common/Button.vue'
import { useVilleStore } from '@/stores/ville'
import { normaliserTexte } from '@/utils/format'
import { VILLES, formatVille, villeAdresse } from '@/utils/villes'

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

const quartiersFiltres = computed(() => {
  const terme = normaliserTexte(recherche.value.trim())
  const quartiers = quartiersParVille.value[form.value.ville] || []

  // Le quartier déjà sélectionné reste visible même s'il ne correspond pas à la recherche
  return quartiers.filter((quartier) => !terme
    || normaliserTexte(quartier.nom).includes(terme)
    || Number(quartier.id) === Number(form.value.quartier_id))
})

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

const submit = async () => {
  saving.value = true
  error.value = ''

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
