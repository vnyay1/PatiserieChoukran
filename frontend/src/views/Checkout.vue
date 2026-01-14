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
                </option>
              </select>
            </div>

            <button
              type="button"
              class="text-gold-600 hover:text-gold-700 text-sm font-medium mb-6"
              @click="showAddAddress = true"
            >
              + Ajouter une nouvelle adresse
            </button>

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
          <div class="flex justify-between text-sm">
            <span>Livraison</span>
            <span>{{ fraisLivraison > 0 ? formatPrice(fraisLivraison) + ' FCFA' : 'Gratuit' }}</span>
          </div>
          <div class="divider-ornament"></div>
          <div class="flex justify-between font-bold text-lg">
            <span>Total</span>
            <span class="price">{{ formatPrice(totalGeneral) }} FCFA</span>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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

const adresses = ref([])
const fraisLivraison = ref(1000)

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
    }
  } catch (error) {
    console.error('Erreur chargement adresses:', error)
  }
}

const goToStep2 = () => {
  currentStep.value = 2
}

const submitOrder = async () => {
  submitting.value = true

  try {
    const response = await api.commandes.create(formData.value)
    
    if (response.data.success) {
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
</script>