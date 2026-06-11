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
                  :src="resolveImageUrl(ligne.produit?.image_principale)"
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
                    {{ commande.adresse_livraison?.quartier }}, {{ commande.adresse_livraison?.ville }}
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <Phone :size="16" />
                  <span>{{ commande.telephone_livraison || '-' }}</span>
                </div>
              </div>

              <div class="flex items-center gap-2 text-sm text-gray-600 mt-3">
                <Clock :size="16" />
                <span>
                  {{ formatDate(commande.date_livraison_souhaitee) }} — {{ commande.heure_livraison_souhaitee }}
                </span>
              </div>

              <div v-if="commande.instructions_speciales" class="text-sm text-gray-600 mt-2">
                <span class="font-medium text-gray-700">Instructions:</span>
                {{ commande.instructions_speciales }}
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
              </div>
            </div>
          </div>
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
                  {{ adresse.libelle }} - {{ adresse.quartier }}, {{ adresse.ville }}
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
import api from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { ArrowLeft, MapPin, Clock, Phone, Pencil, Trash2, Truck, Store } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref('')
const commande = ref(null)

const editing = ref(false)
const updating = ref(false)
const canceling = ref(false)
const showCancelConfirm = ref(false)

const adresses = ref([])
const fraisLivraison = ref(0)
const shippingError = ref('')

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

const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
const apiOrigin = (() => {
  try {
    return new URL(apiBase).origin
  } catch {
    return ''
  }
})()

const resolveImageUrl = (path) => {
  if (!path) return '/placeholder-product.jpg'
  if (path.startsWith('http') || path.startsWith('/')) return path
  return apiOrigin ? `${apiOrigin}/storage/${path}` : `/storage/${path}`
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const getStatutLabel = (statut) => {
  const labels = {
    'en_attente': 'En attente',
    'confirmee': 'Confirmée',
    'en_preparation': 'En préparation',
    'prete': 'Prête',
    'en_livraison': 'En livraison',
    'livree': 'Livrée',
    'annulee': 'Annulée'
  }
  return labels[statut] || statut
}

const getBadgeClass = (statut) => {
  const classes = {
    'en_attente': 'bg-yellow-100 text-yellow-700',
    'confirmee': 'bg-blue-100 text-blue-700',
    'en_preparation': 'bg-purple-100 text-purple-700',
    'prete': 'bg-indigo-100 text-indigo-700',
    'en_livraison': 'bg-orange-100 text-orange-700',
    'livree': 'bg-green-100 text-green-700',
    'annulee': 'bg-red-100 text-red-700'
  }
  return classes[statut] || 'bg-gray-100 text-gray-700'
}

const getPaymentLabel = (statut) => {
  const labels = {
    'en_attente': 'À payer',
    'paye': 'Payé',
    'echec': 'Échec',
    'rembourse': 'Remboursé'
  }
  return labels[statut] || statut
}

const getPaymentBadgeClass = (statut) => {
  const classes = {
    'en_attente': 'bg-yellow-100 text-yellow-700',
    'paye': 'bg-green-100 text-green-700',
    'echec': 'bg-red-100 text-red-700',
    'rembourse': 'bg-gray-100 text-gray-700'
  }
  return classes[statut] || 'bg-gray-100 text-gray-700'
}

const getPaymentMethodLabel = (method) => {
  const labels = {
    'orange_money': 'Orange Money',
    'mtn_momo': 'MTN Mobile Money',
    'especes': 'Espèces'
  }
  return labels[method] || method
}

const getDeliveryIcon = (type) => {
  return type === 'livraison' ? Truck : Store
}

const initFormFromCommande = () => {
  if (!commande.value) return
  form.value = {
    type_livraison: commande.value.type_livraison || 'livraison',
    adresse_livraison_id: commande.value.adresse_livraison_id || '',
    date_livraison_souhaitee: commande.value.date_livraison_souhaitee || '',
    heure_livraison_souhaitee: commande.value.heure_livraison_souhaitee || '',
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
      await refreshShipping()
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

const refreshShipping = async () => {
  shippingError.value = ''

  if (form.value.type_livraison !== 'livraison') {
    fraisLivraison.value = 0
    return
  }

  if (!form.value.adresse_livraison_id) {
    fraisLivraison.value = 0
    return
  }

  try {
    const response = await api.commandes.calculateShipping({
      adresse_id: form.value.adresse_livraison_id
    })
    if (response.data.success) {
      const data = response.data.data || {}
      fraisLivraison.value = data.frais_livraison || 0
    } else {
      fraisLivraison.value = 0
      shippingError.value = 'Impossible de calculer les frais de livraison.'
    }
  } catch (err) {
    fraisLivraison.value = 0
    shippingError.value = err.response?.data?.message || 'Erreur lors du calcul des frais de livraison.'
  }
}

const toggleEdit = () => {
  editing.value = !editing.value
  if (editing.value) {
    initFormFromCommande()
    refreshShipping()
  }
}

const submitUpdate = async () => {
  if (form.value.type_livraison === 'livraison' && (shippingError.value || fraisLivraison.value <= 0)) {
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
    }
  } catch (err) {
    console.error('Erreur mise à jour commande:', err)
    alert(err.response?.data?.message || 'Erreur lors de la mise à jour')
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
      await fetchCommande()
    }
  } catch (err) {
    console.error('Erreur annulation commande:', err)
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
