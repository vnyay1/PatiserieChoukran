<!-- ===================================
PAGE DÉTAIL COMMANDE
File: src/views/CommandeDetail.vue
=================================== -->

<template>
  <div class="commande-detail-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-5xl">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <button
          class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-800"
          @click="router.back()"
        >
          <ArrowLeft :size="16" />
          Retour
        </button>
        <span
          v-if="commande"
          :class="['badge', getBadgeClass(commande.statut)]"
        >
          {{ getStatutLabel(commande.statut) }}
        </span>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="space-y-4">
        <div v-for="n in 3" :key="n" class="skeleton h-40 rounded-elegant"></div>
      </div>

      <!-- Empty/Error -->
      <div v-else-if="!commande" class="text-center py-16">
        <div class="text-6xl mb-4">📦</div>
        <h2 class="font-display text-xl font-bold text-gray-800 mb-2">
          Commande introuvable
        </h2>
        <p class="text-gray-600 mb-6">{{ error || 'Impossible de charger la commande.' }}</p>
        <Button variant="primary" @click="$router.push('/mes-commandes')">
          Retour à mes commandes
        </Button>
      </div>

      <div v-else class="space-y-6">
        <!-- Résumé -->
        <Card padding="lg">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="font-display text-2xl font-bold text-gray-800">
                {{ commande.numero_commande }}
              </h1>
              <p class="text-sm text-gray-600">
                Créée le {{ formatDate(commande.created_at) }}
              </p>
              <p v-if="commande.vendeur?.nom_complet" class="text-sm text-gray-600">
                Vendeur : {{ commande.vendeur.nom_complet }}
              </p>
            </div>
            <div class="text-right">
              <div class="text-sm text-gray-600 mb-1">Montant total</div>
              <div class="price text-2xl">{{ formatPrice(commande.montant_total) }} FCFA</div>
              <div v-if="commande.statut !== 'annulee'" :class="['badge mt-2', getPaymentBadgeClass(commande.statut_paiement)]">
                {{ getPaymentLabel(commande.statut_paiement) }}
              </div>
            </div>
          </div>
        </Card>

        <!-- Articles -->
        <Card padding="lg">
          <h2 class="font-display text-xl font-bold text-gray-800 mb-4">
            Articles
          </h2>
          <div class="space-y-4">
            <div
              v-for="ligne in commande.ligne_commandes || []"
              :key="ligne.id"
              class="flex gap-4 items-center"
            >
              <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                <img
                  :src="resolveImageUrl(ligne.produit?.image_principale)" @error="onImageError"
                  :alt="ligne.nom_produit"
                  class="w-full h-full object-cover"
                />
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-800">
                  {{ ligne.nom_produit }}
                </div>
                <div class="text-sm text-gray-600">
                  {{ ligne.quantite }} × {{ formatPrice(ligne.prix_unitaire) }} FCFA
                </div>
              </div>
              <div class="price text-lg">
                {{ formatPrice(ligne.sous_total) }} FCFA
              </div>
            </div>
          </div>
        </Card>

        <!-- Livraison & Paiement -->
        <Card padding="lg">
          <h2 class="font-display text-xl font-bold text-gray-800 mb-4">
            Livraison & Paiement
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <div class="flex items-center gap-2 text-gray-700 mb-3">
                <component :is="getDeliveryIcon(commande.type_livraison)" :size="18" />
                <span class="font-medium">
                  {{ commande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait en boutique' }}
                </span>
              </div>

              <div v-if="commande.type_livraison === 'livraison'" class="space-y-2 text-sm text-gray-600">
                <div class="flex items-start gap-2">
                  <MapPin :size="16" class="mt-0.5" />
                  <span>
                    {{ commande.adresse_livraison?.libelle || 'Adresse' }} —
                    {{ commande.adresse_livraison?.quartier }}, {{ formatVille(commande.adresse_livraison?.ville) }}
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <Phone :size="16" />
                  <span>{{ commande.telephone_livraison || '-' }}</span>
                </div>
              </div>

              <div v-if="commande.date_livraison_souhaitee" class="flex items-center gap-2 text-sm text-gray-600 mt-3">
                <Clock :size="16" />
                <span>
                  {{ formatDate(commande.date_livraison_souhaitee) }}
                  <template v-if="commande.heure_livraison_souhaitee">
                    à {{ formatHeure(commande.heure_livraison_souhaitee) }}
                  </template>
                </span>
              </div>

              <div v-if="commande.instructions_speciales" class="text-sm text-gray-600 mt-2">
                <span class="font-medium text-gray-700">Instructions:</span>
                {{ commande.instructions_speciales }}
              </div>

              <!-- Contact du vendeur : utile en cas de question sur la livraison -->
              <div v-if="commande.vendeur?.telephone" class="text-sm text-gray-600 mt-3">
                <span class="font-medium text-gray-700">Contacter le vendeur :</span>
                <a :href="`tel:${commande.vendeur.telephone}`" class="text-gold-600 hover:text-gold-700">
                  {{ commande.vendeur.telephone }}
                </a>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex justify-between text-sm text-gray-600">
                <span>Produits</span>
                <span>{{ formatPrice(commande.montant_produits) }} FCFA</span>
              </div>
              <div class="flex justify-between text-sm text-gray-600">
                <span>Livraison</span>
                <span>{{ formatPrice(commande.montant_livraison) }} FCFA</span>
              </div>
              <div class="divider-ornament"></div>
              <div class="flex justify-between font-bold text-lg">
                <span>Total</span>
                <span class="price">{{ formatPrice(commande.montant_total) }} FCFA</span>
              </div>

              <div class="mt-4 text-sm text-gray-600">
                <div class="font-medium text-gray-700 mb-1">Paiement</div>
                <div>Moyen: {{ getPaymentMethodLabel(commande.moyen_paiement) }}</div>
                <div v-if="commande.telephone_paiement">Téléphone: {{ commande.telephone_paiement }}</div>
                <div v-if="commande.date_paiement">
                  Payée le {{ formatDateHeure(commande.date_paiement) }}
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Suivi -->
        <Card v-if="historiqueTrie.length" padding="lg">
          <h2 class="font-display text-xl font-bold text-gray-800 mb-4">
            Suivi de la commande
          </h2>
          <ol class="border-l-2 border-gold-200 pl-4 space-y-4">
            <li v-for="etape in historiqueTrie" :key="etape.id">
              <div class="font-medium text-gray-800">{{ getStatutLabel(etape.nouveau_statut) }}</div>
              <div class="text-xs text-gray-500">{{ formatDateHeure(etape.created_at) }}</div>
              <p v-if="etape.commentaire" class="text-sm text-gray-600 mt-1">{{ etape.commentaire }}</p>
            </li>
          </ol>
        </Card>

        <!-- Actions -->
        <Card v-if="canEdit" padding="lg">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h3 class="font-display text-lg font-bold text-gray-800">
                Actions
              </h3>
              <p class="text-sm text-gray-600">
                Vous pouvez modifier ou annuler cette commande tant qu'elle est en attente.
              </p>
            </div>
            <div class="flex gap-3">
              <Button variant="outline" @click="toggleEdit">
                <Pencil :size="16" class="mr-2" />
                Modifier
              </Button>
              <Button variant="danger" @click="showCancelConfirm = true">
                <Trash2 :size="16" class="mr-2" />
                Annuler
              </Button>
            </div>
          </div>
        </Card>

        <!-- Formulaire de modification -->
        <Card v-if="editing" padding="lg">
          <h3 class="font-display text-lg font-bold text-gray-800 mb-4">
            Modifier la commande
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Mode de livraison
              </label>
              <select v-model="form.type_livraison" class="input">
                <option value="livraison">Livraison</option>
                <option value="retrait_boutique">Retrait en boutique</option>
              </select>
            </div>

            <div v-if="form.type_livraison === 'livraison'">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Adresse de livraison
              </label>
              <select v-model="form.adresse_livraison_id" class="input">
                <option value="">Sélectionner une adresse</option>
                <option v-for="adresse in adresses" :key="adresse.id" :value="adresse.id">
                  {{ adresse.libelle || 'Adresse' }} - {{ adresse.quartier }}, {{ formatVille(adresse.ville) }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Date souhaitée
              </label>
              <input v-model="form.date_livraison_souhaitee" type="date" class="input" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Heure souhaitée
              </label>
              <input v-model="form.heure_livraison_souhaitee" type="time" class="input" />
            </div>

            <div v-if="form.type_livraison === 'livraison'">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Téléphone de livraison
              </label>
              <input v-model="form.telephone_livraison" type="tel" class="input" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Moyen de paiement
              </label>
              <select v-model="form.moyen_paiement" class="input">
                <option value="orange_money">Orange Money</option>
                <option value="mtn_momo">MTN Mobile Money</option>
                <option value="especes">Espèces</option>
              </select>
            </div>

            <div v-if="form.moyen_paiement !== 'especes'">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Téléphone paiement
              </label>
              <input v-model="form.telephone_paiement" type="tel" class="input" />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Instructions spéciales
              </label>
              <textarea v-model="form.instructions_speciales" rows="3" class="input resize-none"></textarea>
            </div>
          </div>

          <p v-if="form.type_livraison === 'livraison' && livraisonPossible" class="text-sm text-gray-700 mt-3">
            Frais de livraison : {{ fraisLivraison > 0 ? formatPrice(fraisLivraison) + ' FCFA' : 'Gratuit' }}
          </p>
          <p v-if="shippingError" class="text-sm text-red-600 mt-3">
            {{ shippingError }}
          </p>

          <div class="flex justify-end gap-3 mt-4">
            <Button variant="outline" @click="toggleEdit">
              Annuler
            </Button>
            <Button variant="primary" :loading="updating" @click="submitUpdate">
              Enregistrer
            </Button>
          </div>
        </Card>
      </div>
    </div>

    <!-- Modal confirmation annulation -->
    <Teleport to="body">
      <div
        v-if="showCancelConfirm"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click="showCancelConfirm = false"
      >
        <div
          class="bg-white rounded-elegant p-6 max-w-sm w-full animate-fadeIn"
          @click.stop
        >
          <div class="text-center mb-4">
            <div class="text-5xl mb-3">⚠️</div>
            <h3 class="font-display text-xl font-bold text-gray-800 mb-2">
              Annuler la commande ?
            </h3>
            <p class="text-gray-600">
              Cette action est définitive.
            </p>
          </div>

          <div class="flex gap-3">
            <Button variant="outline" full-width @click="showCancelConfirm = false">
              Retour
            </Button>
            <Button variant="danger" full-width :loading="canceling" @click="cancelOrder">
              Annuler
            </Button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import {
  formatPrice,
  formatHeure,
  formatDateLongue as formatDate,
  formatDateHeure,
  libelleStatut as getStatutLabel,
  classeStatut as getBadgeClass,
  libellePaiement as getPaymentLabel,
  classePaiement as getPaymentBadgeClass,
  libelleMoyenPaiement as getPaymentMethodLabel,
} from '@/utils/format'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { useLivraisonVendeurs, formatVille } from '@/composables/useLivraisonVendeurs'
import { ArrowLeft, MapPin, Clock, Phone, Pencil, Trash2, Truck, Store } from 'lucide-vue-next'
import { resolveImageUrl, onImageError } from '@/utils/images'

const route = useRoute()
const router = useRouter()
const toastStore = useToastStore()

const loading = ref(true)
const error = ref('')
const commande = ref(null)

const editing = ref(false)
const updating = ref(false)
const canceling = ref(false)
const showCancelConfirm = ref(false)

const adresses = ref([])
const fraisLivraison = ref(0)
const livraisonPossible = ref(false)
const shippingError = ref('')

const { erreur: erreurTarifs, estCharge, chargerQuartiersVendeurs, tarifPour } = useLivraisonVendeurs()

// Commandes antérieures au sprint : le vendeur n'était enregistré que dans livreur_id
const vendeurCommandeId = computed(() => commande.value?.vendeur_id ?? commande.value?.livreur_id ?? null)

const form = ref({
  type_livraison: 'livraison',
  adresse_livraison_id: '',
  date_livraison_souhaitee: '',
  heure_livraison_souhaitee: '',
  telephone_livraison: '',
  instructions_speciales: '',
  moyen_paiement: 'orange_money',
  telephone_paiement: '',
})

const canEdit = computed(() => commande.value?.statut === 'en_attente')

// Étapes de la commande, de la plus ancienne à la plus récente
const historiqueTrie = computed(() => {
  return [...(commande.value?.historiques || [])]
    .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

const getDeliveryIcon = (type) => {
  return type === 'livraison' ? Truck : Store
}

const initFormFromCommande = () => {
  if (!commande.value) return
  form.value = {
    type_livraison: commande.value.type_livraison || 'livraison',
    adresse_livraison_id: commande.value.adresse_livraison_id || '',
    date_livraison_souhaitee: commande.value.date_livraison_souhaitee || '',
    // "14:30:00" -> "14:30" : format attendu par <input type="time"> et par l'API (H:i)
    heure_livraison_souhaitee: formatHeure(commande.value.heure_livraison_souhaitee),
    telephone_livraison: commande.value.telephone_livraison || '',
    instructions_speciales: commande.value.instructions_speciales || '',
    moyen_paiement: commande.value.moyen_paiement || 'orange_money',
    telephone_paiement: commande.value.telephone_paiement || '',
  }
}

const fetchCommande = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.commandes.getOne(route.params.id)
    if (response.data.success) {
      commande.value = response.data.data
      initFormFromCommande()
      await fetchAdresses()
    } else {
      error.value = response.data?.message || 'Erreur lors du chargement'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement'
  } finally {
    loading.value = false
  }
}

const fetchAdresses = async () => {
  try {
    const response = await api.adresses.getAll()
    if (response.data.success) {
      adresses.value = response.data.data || []
    }
  } catch (err) {
    console.error('Erreur chargement adresses:', err)
  }
}

// Frais recalculés avec le tarif du vendeur de CETTE commande
// (et non celui du panier, vide une fois la commande passée)
const refreshShipping = async () => {
  shippingError.value = ''
  fraisLivraison.value = 0
  livraisonPossible.value = false

  if (form.value.type_livraison !== 'livraison' || !form.value.adresse_livraison_id) {
    return
  }

  const adresse = adresses.value.find((item) => String(item.id) === String(form.value.adresse_livraison_id))
  if (!adresse) {
    return
  }

  if (!adresse.quartier_id) {
    shippingError.value = 'Cette adresse n\'a pas de quartier reconnu : modifiez-la depuis votre profil.'
    return
  }

  if (!vendeurCommandeId.value) {
    shippingError.value = 'Vendeur de la commande introuvable.'
    return
  }

  await chargerQuartiersVendeurs([vendeurCommandeId.value])
  if (!estCharge(vendeurCommandeId.value)) {
    shippingError.value = erreurTarifs.value || 'Impossible de calculer les frais de livraison.'
    return
  }

  const tarif = tarifPour(vendeurCommandeId.value, adresse.quartier_id)
  if (!tarif) {
    shippingError.value = 'Le vendeur de cette commande ne livre pas dans ce quartier.'
    return
  }

  fraisLivraison.value = Number(tarif.tarif) || 0
  livraisonPossible.value = true
}

const toggleEdit = () => {
  editing.value = !editing.value
  if (editing.value) {
    initFormFromCommande()
    refreshShipping()
  }
}

const submitUpdate = async () => {
  if (form.value.type_livraison === 'livraison' && !livraisonPossible.value) {
    if (!shippingError.value) {
      shippingError.value = 'Veuillez sélectionner une adresse de livraison valide.'
    }
    return
  }

  updating.value = true
  try {
    const payload = { ...form.value }
    if (payload.type_livraison !== 'livraison') {
      payload.adresse_livraison_id = null
      payload.telephone_livraison = null
    }
    const response = await api.commandes.update(route.params.id, payload)
    if (response.data.success) {
      commande.value = response.data.data
      editing.value = false
      initFormFromCommande()
      toastStore.succes('Commande mise à jour.')
    }
  } catch (err) {
    toastStore.erreur(messageErreur(err, 'Erreur lors de la mise à jour.'))
  } finally {
    updating.value = false
  }
}

const cancelOrder = async () => {
  canceling.value = true
  try {
    const response = await api.commandes.cancel(route.params.id)
    if (response.data.success) {
      showCancelConfirm.value = false
      toastStore.succes('Commande annulée.')
      await fetchCommande()
    }
  } catch (err) {
    toastStore.erreur(messageErreur(err, 'Impossible d\'annuler la commande.'))
  } finally {
    canceling.value = false
  }
}

watch(
  () => [form.value.type_livraison, form.value.adresse_livraison_id],
  () => {
    if (!editing.value) return
    refreshShipping()
  }
)

onMounted(() => {
  fetchCommande()
})
</script>
