<!-- ===================================
1. PAGE CHECKOUT - Processus de commande
File: src/views/Checkout.vue
=================================== -->

<template>
  <div class="checkout-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-4xl">
      <!-- Header -->
      <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600 mb-6">
        Finaliser la commande
      </h1>

      <!-- Étapes -->
      <div class="flex items-center justify-between mb-8">
        <div
          v-for="(etape, index) in etapes"
          :key="index"
          class="flex items-center flex-1"
        >
          <div class="flex flex-col items-center">
            <div
              class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-colors"
              :class="currentStep >= index + 1 ? 'bg-gold-500 text-white' : 'bg-gray-200 text-gray-500'"
            >
              {{ index + 1 }}
            </div>
            <span class="text-xs mt-2 text-center hidden md:block">{{ etape }}</span>
          </div>
          <div
            v-if="index < etapes.length - 1"
            class="flex-1 h-1 mx-2"
            :class="currentStep > index + 1 ? 'bg-gold-500' : 'bg-gray-200'"
          ></div>
        </div>
      </div>

      <!-- Étape 1: Livraison -->
      <Card v-if="currentStep === 1" padding="lg" class="mb-6">
        <h2 class="font-display text-xl font-bold text-gray-800 mb-6">
          Informations de livraison
        </h2>

        <form @submit.prevent="goToStep2">
          <!-- Type de livraison -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Mode de livraison
            </label>
            <div class="grid grid-cols-2 gap-3">
              <label
                class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors"
                :class="formData.type_livraison === 'livraison' ? 'border-gold-500 bg-gold-50' : 'border-gray-200'"
              >
                <input
                  v-model="formData.type_livraison"
                  type="radio"
                  value="livraison"
                  class="sr-only"
                />
                <Truck :size="24" class="mr-3 text-gold-600" />
                <div>
                  <div class="font-semibold">Livraison</div>
                  <div class="text-xs text-gray-600">À domicile</div>
                </div>
              </label>

              <label
                class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors"
                :class="formData.type_livraison === 'retrait_boutique' ? 'border-gold-500 bg-gold-50' : 'border-gray-200'"
              >
                <input
                  v-model="formData.type_livraison"
                  type="radio"
                  value="retrait_boutique"
                  class="sr-only"
                />
                <Store :size="24" class="mr-3 text-gold-600" />
                <div>
                  <div class="font-semibold">Retrait</div>
                  <div class="text-xs text-gray-600">En boutique</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Adresse (si livraison) -->
          <div v-if="formData.type_livraison === 'livraison'">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Adresse de livraison
              </label>
              <select
                v-model="formData.adresse_livraison_id"
                class="input"
                required
              >
                <option value="">Sélectionner une adresse</option>
                <option
                  v-for="adresse in adresses"
                  :key="adresse.id"
                  :value="adresse.id"
                >
                  {{ adresse.libelle }} - {{ adresse.quartier }}, {{ adresse.ville }}
                  {{ adresse.zone_livraison?.nom_zone ? ` (${adresse.zone_livraison?.nom_zone})` : '' }}
                </option>
              </select>
            </div>

            <div class="flex flex-wrap items-center gap-4 mb-6">
              <button
                type="button"
                class="text-gold-600 hover:text-gold-700 text-sm font-medium"
                @click="openAddAddress"
              >
                + Ajouter une nouvelle adresse
              </button>
              <button
                type="button"
                class="text-gray-600 hover:text-gray-800 text-sm font-medium disabled:text-gray-400 disabled:cursor-not-allowed"
                :disabled="!selectedAdresse"
                @click="openEditAddress(selectedAdresse)"
              >
                Modifier l'adresse sélectionnée
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Date de livraison souhaitée *
                </label>
                <input
                  v-model="formData.date_livraison_souhaitee"
                  type="date"
                  :min="minDate"
                  class="input"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Heure souhaitée *
                </label>
                <input
                  v-model="formData.heure_livraison_souhaitee"
                  type="time"
                  class="input"
                  required
                />
              </div>
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Téléphone de contact
              </label>
              <input
                v-model="formData.telephone_livraison"
                type="tel"
                placeholder="+237699123456"
                class="input"
                required
              />
            </div>
          </div>

          <!-- Instructions spéciales -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Instructions spéciales (optionnel)
            </label>
            <textarea
              v-model="formData.instructions_speciales"
              rows="3"
              placeholder="Ex: Sonner à l'interphone, Appeler en arrivant..."
              class="input resize-none"
            ></textarea>
          </div>

          <Button type="submit" variant="primary" size="lg" full-width>
            Continuer vers le paiement
          </Button>
        </form>
      </Card>

      <!-- Étape 2: Paiement -->
      <Card v-if="currentStep === 2" padding="lg" class="mb-6">
        <h2 class="font-display text-xl font-bold text-gray-800 mb-6">
          Mode de paiement
        </h2>

        <form @submit.prevent="submitOrder">
          <!-- Choix du paiement -->
          <div class="space-y-3 mb-6">
            <label
              v-for="method in paymentMethods"
              :key="method.value"
              class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors"
              :class="formData.moyen_paiement === method.value ? 'border-gold-500 bg-gold-50' : 'border-gray-200'"
            >
              <input
                v-model="formData.moyen_paiement"
                type="radio"
                :value="method.value"
                class="sr-only"
              />
              <component :is="method.icon" :size="24" class="mr-3" :class="method.color" />
              <div class="flex-1">
                <div class="font-semibold">{{ method.label }}</div>
                <div class="text-xs text-gray-600">{{ method.description }}</div>
              </div>
            </label>
          </div>

          <!-- Téléphone pour mobile money -->
          <div v-if="formData.moyen_paiement !== 'especes'" class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Numéro de téléphone Mobile Money *
            </label>
            <input
              v-model="formData.telephone_paiement"
              type="tel"
              placeholder="+237699123456"
              class="input"
              required
            />
            <p class="text-xs text-gray-500 mt-1">
              Vous recevrez une demande de paiement sur ce numéro
            </p>
          </div>

          <div class="flex gap-3">
            <Button type="button" variant="outline" @click="currentStep = 1">
              Retour
            </Button>
            <Button type="submit" variant="primary" :loading="submitting" class="flex-1">
              Confirmer la commande
            </Button>
          </div>
        </form>
      </Card>

      <!-- Résumé de la commande -->
      <Card padding="lg">
        <h3 class="font-display text-lg font-bold text-gray-800 mb-4">
          Récapitulatif
        </h3>

        <div class="space-y-3 mb-4">
          <div class="flex justify-between text-sm">
            <span>Sous-total</span>
            <span>{{ formatPrice(panierStore.total) }} FCFA</span>
          </div>
          <div v-if="formData.type_livraison === 'livraison'" class="flex justify-between text-sm">
            <span>Livraison</span>
            <span>{{ fraisLivraison > 0 ? formatPrice(fraisLivraison) + ' FCFA' : 'Gratuit' }}</span>
          </div>
          <p v-if="formData.type_livraison === 'livraison' && shippingError" class="text-xs text-red-600">
            {{ shippingError }}
          </p>
          <div class="divider-ornament"></div>
          <div class="flex justify-between font-bold text-lg">
            <span>Total</span>
            <span class="price">{{ formatPrice(totalGeneral) }} FCFA</span>
          </div>
        </div>
      </Card>

      <!-- Modal ajout adresse -->
      <div
        v-if="showAddAddress"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4 py-6"
      >
        <div class="bg-white w-full max-w-2xl rounded-elegant shadow-card overflow-hidden">
          <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-display text-xl font-bold text-gray-800">
              {{ isEditingAddress ? 'Modifier une adresse' : 'Ajouter une adresse' }}
            </h2>
            <button class="text-sm text-gray-500 hover:text-gray-700" @click="closeAddAddress">
              Fermer
            </button>
          </div>

          <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submitAddress">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Libellé (optionnel)</label>
              <input v-model="addressForm.libelle" type="text" class="input" placeholder="Maison, Bureau..." />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Quartier *</label>
              <input v-model="addressForm.quartier" type="text" class="input" required />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
              <input v-model="addressForm.ville" type="text" class="input" required />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Zone de livraison *</label>
              <select v-model="addressForm.zone_livraison_id" class="input" required>
                <option value="">Sélectionner une zone</option>
                <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                  {{ zone.ville }} - {{ zone.nom_zone }} ({{ formatPrice(zone.tarif_livraison) }} FCFA)
                </option>
              </select>
              <p v-if="addressForm.ville && zones.length === 0" class="text-xs text-red-600 mt-1">
                Aucune zone active pour cette ville.
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
              <input
                v-model="addressForm.telephone_contact"
                type="tel"
                class="input"
                placeholder="+237699123456"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Point de repère</label>
              <input v-model="addressForm.point_repere" type="text" class="input" />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Complément d'adresse</label>
              <textarea v-model="addressForm.complement_adresse" rows="2" class="input resize-none"></textarea>
            </div>

            <div class="md:col-span-2">
              <label class="inline-flex items-center gap-2">
                <input
                  v-model="addressForm.est_principale"
                  type="checkbox"
                  class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
                />
                <span class="text-sm text-gray-700">Définir comme adresse principale</span>
              </label>
            </div>

            <p v-if="addressError" class="text-sm text-red-600 md:col-span-2">
              {{ addressError }}
            </p>

            <div class="md:col-span-2 flex gap-3">
              <Button type="submit" variant="primary" :loading="savingAddress">
                {{ isEditingAddress ? 'Mettre à jour' : 'Enregistrer' }}
              </Button>
              <Button type="button" variant="outline" @click="closeAddAddress">
                Annuler
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePanierStore } from '@/stores/panier'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { Truck, Store, Smartphone, Banknote } from 'lucide-vue-next'

const router = useRouter()
const panierStore = usePanierStore()
const authStore = useAuthStore()

const etapes = ['Livraison', 'Paiement', 'Confirmation']
const currentStep = ref(1)
const submitting = ref(false)
const showAddAddress = ref(false)
const editingAddressId = ref(null)
const preserveZoneSelectionOnCityChange = ref(false)

const adresses = ref([])
const zones = ref([])
const fraisLivraison = ref(0)
const shippingError = ref('')
const savingAddress = ref(false)
const addressError = ref('')

const addressForm = ref({
  libelle: '',
  quartier: '',
  ville: '',
  zone_livraison_id: '',
  telephone_contact: authStore.user?.telephone || '',
  point_repere: '',
  complement_adresse: '',
  est_principale: false,
})

const formData = ref({
  type_livraison: 'livraison',
  adresse_livraison_id: '',
  date_livraison_souhaitee: '',
  heure_livraison_souhaitee: '',
  telephone_livraison: authStore.user?.telephone || '',
  instructions_speciales: '',
  moyen_paiement: 'orange_money',
  telephone_paiement: authStore.user?.telephone || '',
})

const paymentMethods = [
  { value: 'orange_money', label: 'Orange Money', description: 'Paiement instantané', icon: Smartphone, color: 'text-orange-500' },
  { value: 'mtn_momo', label: 'MTN Mobile Money', description: 'Paiement instantané', icon: Smartphone, color: 'text-yellow-500' },
  { value: 'especes', label: 'Espèces à la livraison', description: 'Payer en liquide', icon: Banknote, color: 'text-green-500' },
]

const minDate = computed(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
})

const totalGeneral = computed(() => {
  return panierStore.total + (formData.value.type_livraison === 'livraison' ? fraisLivraison.value : 0)
})
const isEditingAddress = computed(() => editingAddressId.value !== null)
const selectedAdresse = computed(() => {
  return adresses.value.find((adresse) => String(adresse.id) === String(formData.value.adresse_livraison_id)) || null
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price)
}

const fetchAdresses = async () => {
  try {
    const response = await api.adresses.getAll()
    if (response.data.success) {
      adresses.value = response.data.data
      if (adresses.value.length > 0) {
        const principale = adresses.value.find(a => a.est_principale)
        formData.value.adresse_livraison_id = principale?.id || adresses.value[0].id
      }
      await refreshShipping()
    }
  } catch (error) {
    console.error('Erreur chargement adresses:', error)
  }
}

const resetAddressForm = () => {
  editingAddressId.value = null
  preserveZoneSelectionOnCityChange.value = false
  addressForm.value = {
    libelle: '',
    quartier: '',
    ville: '',
    zone_livraison_id: '',
    telephone_contact: authStore.user?.telephone || '',
    point_repere: '',
    complement_adresse: '',
    est_principale: false,
  }
  addressError.value = ''
}

const openAddAddress = () => {
  resetAddressForm()
  showAddAddress.value = true
}

const openEditAddress = (adresse) => {
  if (!adresse) return

  const zoneId = adresse.zone_livraison_id || adresse.zone_livraison?.id || ''
  editingAddressId.value = adresse.id
  preserveZoneSelectionOnCityChange.value = true
  addressError.value = ''

  addressForm.value = {
    libelle: adresse.libelle || '',
    quartier: adresse.quartier || '',
    ville: adresse.ville || '',
    zone_livraison_id: zoneId,
    telephone_contact: adresse.telephone_contact || authStore.user?.telephone || '',
    point_repere: adresse.point_repere || '',
    complement_adresse: adresse.complement_adresse || '',
    est_principale: Boolean(adresse.est_principale),
  }

  showAddAddress.value = true
}

const closeAddAddress = () => {
  showAddAddress.value = false
  resetAddressForm()
}

const submitAddress = async () => {
  savingAddress.value = true
  addressError.value = ''

  try {
    const response = isEditingAddress.value
      ? await api.adresses.update(editingAddressId.value, addressForm.value)
      : await api.adresses.create(addressForm.value)

    if (response.data.success) {
      const updatedAddressId = response.data.data?.id || editingAddressId.value
      closeAddAddress()
      await fetchAdresses()
      if (updatedAddressId) {
        formData.value.adresse_livraison_id = updatedAddressId
      }
      await refreshShipping()
    } else {
      addressError.value = response.data?.message || 'Erreur lors de l\'enregistrement de l\'adresse.'
    }
  } catch (error) {
    addressError.value = error.response?.data?.message || 'Erreur lors de l\'enregistrement de l\'adresse.'
  } finally {
    savingAddress.value = false
  }
}

const fetchZonesByVille = async (ville) => {
  if (!ville) {
    zones.value = []
    addressError.value = ''
    return
  }

  try {
    const response = await api.zones.byCityForCommande(ville)
    if (response.data.success) {
      zones.value = response.data.data || []
      addressError.value = ''
    }
  } catch (error) {
    zones.value = []
    addressError.value = error.response?.data?.message || 'Impossible de charger les zones de livraison disponibles.'
    console.error('Erreur chargement zones:', error)
  }
}

const refreshShipping = async () => {
  shippingError.value = ''

  if (formData.value.type_livraison !== 'livraison') {
    fraisLivraison.value = 0
    return
  }

  if (!formData.value.adresse_livraison_id) {
    fraisLivraison.value = 0
    return
  }

  try {
    const response = await api.commandes.calculateShipping({
      adresse_id: formData.value.adresse_livraison_id
    })
    if (response.data.success) {
      const data = response.data.data || {}
      fraisLivraison.value = data.frais_livraison || 0
    } else {
      fraisLivraison.value = 0
      shippingError.value = 'Impossible de calculer les frais de livraison.'
    }
  } catch (error) {
    fraisLivraison.value = 0
    shippingError.value = error.response?.data?.message || 'Erreur lors du calcul des frais de livraison.'
  }
}

const goToStep2 = () => {
  if (formData.value.type_livraison === 'livraison' && (shippingError.value || fraisLivraison.value <= 0)) {
    alert('Merci d\'ajouter une adresse valide pour calculer les frais de livraison.')
    return
  }
  currentStep.value = 2
}

const submitOrder = async () => {
  submitting.value = true

  try {
    if (formData.value.type_livraison === 'livraison' && (shippingError.value || fraisLivraison.value <= 0)) {
      alert('Adresse de livraison invalide. Merci de vérifier votre adresse.')
      return
    }
    const response = await api.commandes.create(formData.value)
    
    if (response.data.success) {
      // Vider le panier après une commande valide
      await panierStore.clear()
      // Rediriger vers la page de confirmation
      router.push(`/mes-commandes`)
    }
  } catch (error) {
    console.error('Erreur création commande:', error)
    alert('Erreur lors de la création de la commande')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  if (panierStore.isEmpty) {
    router.push('/panier')
    return
  }
  fetchAdresses()
})

watch(
  () => [formData.value.type_livraison, formData.value.adresse_livraison_id],
  () => {
    refreshShipping()
  }
)

let zoneSearchTimeout = null
watch(
  () => addressForm.value.ville,
  (ville) => {
    clearTimeout(zoneSearchTimeout)
    const trimmed = (ville || '').trim()
    if (!trimmed) {
      zones.value = []
      if (!preserveZoneSelectionOnCityChange.value) {
        addressForm.value.zone_livraison_id = ''
      }
      preserveZoneSelectionOnCityChange.value = false
      return
    }
    if (!preserveZoneSelectionOnCityChange.value) {
      addressForm.value.zone_livraison_id = ''
    }
    zoneSearchTimeout = setTimeout(() => {
      fetchZonesByVille(trimmed)
      preserveZoneSelectionOnCityChange.value = false
    }, 300)
  }
)

onBeforeUnmount(() => {
  clearTimeout(zoneSearchTimeout)
})
</script>
