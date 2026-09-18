<!-- ===================================
1. PAGE CHECKOUT - Processus de commande
File: src/views/Checkout.vue
=================================== -->

<template>
  <div class="checkout-page pb-6">
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
              :class="currentStep >= index + 1 ? 'bg-gold-500 text-on-gold' : 'bg-gray-200 text-gray-500'"
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
                :class="formData.type_livraison === 'livraison' ? 'border-gold-600 bg-gold-50' : 'border-gray-200'"
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
                :class="formData.type_livraison === 'retrait_boutique' ? 'border-gold-600 bg-gold-50' : 'border-gray-200'"
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
          <div v-if="estLivraison">
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
                  {{ libelleOption(adresse) }}
                </option>
              </select>
              <p v-if="adresses.length === 0" class="text-xs text-gray-500 mt-1">
                Ajoutez une adresse de livraison pour continuer.
              </p>
              <p v-else-if="selectedAdresse && !selectedAdresse.quartier_id" class="text-xs text-orange-600 mt-1">
                Cette adresse n'a pas de quartier reconnu : modifiez-la pour calculer la livraison.
              </p>
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

          <p v-if="blocageEtape1" class="text-sm text-red-600 mb-4">
            {{ blocageEtape1 }}
          </p>

          <Button type="submit" variant="primary" size="lg" full-width :disabled="isStep1Blocked">
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
              :class="formData.moyen_paiement === method.value ? 'border-gold-600 bg-gold-50' : 'border-gray-200'"
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
              Après confirmation, vous serez redirigé vers la page de paiement sécurisée NotchPay
            </p>
          </div>

          <p v-if="orderError" class="text-sm text-red-600 mb-4">
            {{ orderError }}
          </p>

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

      <!-- Étape 3: Confirmation (une commande par vendeur) -->
      <Card v-if="currentStep === 3" padding="lg" class="mb-6">
        <div class="text-center mb-6">
          <div class="text-5xl mb-3">🎉</div>
          <h2 class="font-display text-xl font-bold text-gray-800 mb-2">
            {{ commandesCreees.length > 1 ? `${commandesCreees.length} commandes confirmées` : 'Commande confirmée' }}
          </h2>
          <p class="text-sm text-gray-600">
            <template v-if="commandesCreees.length > 1">
              Votre panier a été réparti en une commande par vendeur.
            </template>
            Vous pouvez suivre leur avancement dans « Mes commandes ».
          </p>
        </div>

        <p v-if="erreurPaiement" class="mb-6 rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">
          {{ erreurPaiement }}
        </p>

        <div class="space-y-3 mb-6">
          <div
            v-for="commande in commandesCreees"
            :key="commande.id"
            class="flex items-center justify-between gap-3 p-4 border border-gray-200 rounded-lg"
          >
            <div class="min-w-0">
              <div class="font-semibold text-gray-800">{{ commande.numero_commande }}</div>
              <div class="text-sm text-gray-600">{{ commande.vendeur?.nom_complet || 'Vendeur' }}</div>
            </div>
            <div class="text-right">
              <div class="price">{{ formatPrice(commande.montant_total) }} FCFA</div>
              <router-link
                :to="`/mes-commandes/${commande.id}`"
                class="text-sm text-gold-600 hover:text-gold-700"
              >
                Voir le détail
              </router-link>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <Button variant="primary" class="flex-1" @click="router.push('/mes-commandes')">
            Voir mes commandes
          </Button>
          <Button variant="outline" class="flex-1" @click="router.push('/produits')">
            Continuer mes achats
          </Button>
        </div>
      </Card>

      <!-- Résumé de la commande -->
      <Card v-if="currentStep < 3" padding="lg">
        <h3 class="font-display text-lg font-bold text-gray-800 mb-4">
          Récapitulatif
        </h3>

        <p v-if="groupes.length > 1" class="text-sm text-gray-600 mb-4">
          Votre panier sera réparti en {{ groupes.length }} commandes, une par vendeur.
        </p>

        <div class="space-y-3 mb-4">
          <div
            v-for="groupe in livraisonParGroupe"
            :key="groupe.vendeurId ?? 'sans-vendeur'"
            class="p-3 border border-gray-100 rounded-lg space-y-1"
          >
            <div class="flex justify-between gap-3 text-sm font-semibold text-gray-800">
              <span>{{ groupe.vendeurNom }}</span>
              <span class="text-gray-500 font-normal">
                {{ groupe.items.length }} article{{ groupe.items.length > 1 ? 's' : '' }}
              </span>
            </div>
            <div class="flex justify-between text-sm">
              <span>Sous-total</span>
              <span>{{ formatPrice(groupe.sousTotal) }} FCFA</span>
            </div>
            <div v-if="estLivraison && groupe.vendeurId" class="flex justify-between gap-3 text-sm">
              <span>Livraison</span>
              <span v-if="groupe.statut === 'ok'" class="text-right">
                {{ formatPrice(groupe.frais) }} FCFA
              </span>
              <span v-else-if="groupe.statut === 'non_couvert'" class="text-right text-red-600">
                Ce vendeur ne livre pas à {{ formatVille(villeLivraison) }}
              </span>
              <span v-else-if="groupe.statut === 'minimum_non_atteint'" class="text-right text-red-600">
                Dès {{ formatPrice(groupe.minimum) }} FCFA d'achat
                <span class="block text-xs">Il manque {{ formatPrice(groupe.manque) }} FCFA</span>
              </span>
              <span v-else-if="groupe.statut === 'chargement'" class="text-gray-500">Calcul...</span>
              <span v-else class="text-gray-500">À calculer</span>
            </div>
            <p v-if="groupe.statut === 'sans_vendeur'" class="text-xs text-red-600">
              Ces produits ne sont rattachés à aucun vendeur et ne peuvent pas être commandés.
            </p>
          </div>

          <div class="divider-ornament"></div>

          <div class="flex justify-between text-sm">
            <span>Sous-total</span>
            <span>{{ formatPrice(panierStore.total) }} FCFA</span>
          </div>
          <div v-if="estLivraison" class="flex justify-between text-sm">
            <span>Livraison</span>
            <span v-if="livraisonCalculee">
              {{ formatPrice(fraisLivraison) }} FCFA
            </span>
            <span v-else class="text-gray-500">À calculer</span>
          </div>
          <div class="divider-ornament"></div>
          <div class="flex justify-between font-bold text-lg">
            <span>Total</span>
            <span class="price">{{ formatPrice(totalGeneral) }} FCFA</span>
          </div>
        </div>
      </Card>

      <!-- Modal ajout / modification adresse -->
      <AdresseFormModal
        v-if="showAddAddress"
        :adresse="adresseEnEdition"
        :telephone-par-defaut="authStore.user?.telephone || ''"
        @close="closeAddAddress"
        @saved="onAdresseSaved"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePanierStore } from '@/stores/panier'
import { useAuthStore } from '@/stores/auth'
import api, { messageErreur } from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import AdresseFormModal from '@/components/adresse/AdresseFormModal.vue'
import { useLivraisonVendeurs, grouperParVendeur } from '@/composables/useLivraisonVendeurs'
import { useVilleStore } from '@/stores/ville'
import { formatVille, libelleAdresse, villeAdresse } from '@/utils/villes'
import { memoriserReferencePaiement } from '@/utils/paiement'
import { Truck, Store, Smartphone, Banknote } from 'lucide-vue-next'

const router = useRouter()
const panierStore = usePanierStore()
const authStore = useAuthStore()
const villeStore = useVilleStore()
const {
  erreur: erreurTarifs,
  chargerLivraisonVendeurs,
  livraisonDesGroupes,
} = useLivraisonVendeurs()

const etapes = ['Livraison', 'Paiement', 'Confirmation']
const currentStep = ref(1)
const submitting = ref(false)
const showAddAddress = ref(false)
const adresseEnEdition = ref(null)
const adresses = ref([])
const orderError = ref('')
const commandesCreees = ref([])
const erreurPaiement = ref('')

const formData = ref({
  type_livraison: 'livraison',
  adresse_livraison_id: '',
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
const estLivraison = computed(() => formData.value.type_livraison === 'livraison')

const selectedAdresse = computed(() => {
  return adresses.value.find((adresse) => String(adresse.id) === String(formData.value.adresse_livraison_id)) || null
})
const villeLivraison = computed(() => villeAdresse(selectedAdresse.value))

// Une commande sera créée par groupe (vendeur)
const groupes = computed(() => grouperParVendeur(panierStore.items))

// Livraison de chaque vendeur vers la ville de l'adresse choisie
const livraisonParGroupe = computed(() => {
  if (!estLivraison.value) {
    return groupes.value.map((groupe) => ({
      ...groupe,
      statut: groupe.vendeurId ? 'retrait' : 'sans_vendeur',
      frais: 0,
      tarif: null,
    }))
  }

  return livraisonDesGroupes(groupes.value, villeLivraison.value)
})

const livraisonCalculee = computed(() => livraisonParGroupe.value.every((groupe) => groupe.statut === 'ok'))
const fraisLivraison = computed(() => livraisonParGroupe.value.reduce((somme, groupe) => somme + groupe.frais, 0))
const totalGeneral = computed(() => panierStore.total + (estLivraison.value ? fraisLivraison.value : 0))

// Message expliquant pourquoi la commande ne peut pas être passée ('' si rien à signaler)
const blocageEtape1 = computed(() => {
  if (livraisonParGroupe.value.some((groupe) => groupe.statut === 'sans_vendeur')) {
    return 'Certains produits ne sont rattachés à aucun vendeur : retirez-les du panier pour continuer.'
  }

  const nonCouverts = livraisonParGroupe.value.filter((groupe) => groupe.statut === 'non_couvert')
  if (nonCouverts.length > 0) {
    const noms = nonCouverts.map((groupe) => groupe.vendeurNom).join(', ')
    return `${noms} : pas de livraison à ${formatVille(villeLivraison.value)}. Choisissez une autre adresse ou le retrait en boutique.`
  }

  // Minimum d'achat fixé par chaque vendeur pour accepter une livraison
  const sousMinimum = livraisonParGroupe.value.filter((groupe) => groupe.statut === 'minimum_non_atteint')
  if (sousMinimum.length > 0) {
    const details = sousMinimum
      .map((groupe) => `${groupe.vendeurNom} livre dès ${formatPrice(groupe.minimum)} FCFA d'achat (il manque ${formatPrice(groupe.manque)} FCFA)`)
      .join(' ; ')
    return `${details}. Complétez votre panier ou choisissez le retrait en boutique.`
  }

  if (livraisonParGroupe.value.some((groupe) => groupe.statut === 'erreur')) {
    return erreurTarifs.value || 'Impossible de calculer les frais de livraison.'
  }

  return ''
})

const isStep1Blocked = computed(() => {
  if (blocageEtape1.value) return true
  if (livraisonParGroupe.value.some((groupe) => groupe.statut === 'chargement')) return true

  return estLivraison.value && (
    !selectedAdresse.value
    || !selectedAdresse.value.quartier_id
  )
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price)
}

const libelleOption = (adresse) => {
  const libelle = libelleAdresse(adresse)
  return adresse.quartier_id ? libelle : `${libelle} (quartier à préciser)`
}

const fetchAdresses = async (adresseASelectionner = null) => {
  try {
    const response = await api.adresses.getAll()
    if (response.data.success) {
      adresses.value = response.data.data || []
      villeStore.proposerDepuisAdresse(villeAdresse(adresses.value.find((adresse) => adresse.est_principale)))

      const existe = (id) => adresses.value.some((adresse) => String(adresse.id) === String(id))
      if (adresseASelectionner && existe(adresseASelectionner)) {
        formData.value.adresse_livraison_id = adresseASelectionner
      } else if (!existe(formData.value.adresse_livraison_id)) {
        const principale = adresses.value.find((adresse) => adresse.est_principale)
        formData.value.adresse_livraison_id = principale?.id || adresses.value[0]?.id || ''
      }
    }
  } catch (error) {
    console.error('Erreur chargement adresses:', error)
  }
}

const openAddAddress = () => {
  adresseEnEdition.value = null
  showAddAddress.value = true
}

const openEditAddress = (adresse) => {
  if (!adresse) return
  adresseEnEdition.value = adresse
  showAddAddress.value = true
}

const closeAddAddress = () => {
  showAddAddress.value = false
  adresseEnEdition.value = null
}

const onAdresseSaved = async (adresse) => {
  closeAddAddress()
  await fetchAdresses(adresse?.id)
}

const goToStep2 = () => {
  if (isStep1Blocked.value) {
    return
  }
  orderError.value = ''
  currentStep.value = 2
}

const submitOrder = async () => {
  if (isStep1Blocked.value) {
    currentStep.value = 1
    return
  }

  submitting.value = true
  orderError.value = ''

  const payload = { ...formData.value }
  if (payload.moyen_paiement === 'especes') {
    delete payload.telephone_paiement
  }

  try {
    const response = await api.commandes.create(payload)

    if (response.data.success) {
      // Le backend renvoie une commande par vendeur
      const data = response.data.data
      commandesCreees.value = Array.isArray(data) ? data : [data]
      // Le panier a été vidé côté serveur : on resynchronise le store
      await panierStore.fetch()

      // Mobile money : un seul paiement NotchPay pour toutes les commandes du panier
      const urlPaiement = response.data.paiement?.url_paiement
      if (urlPaiement) {
        memoriserReferencePaiement(response.data.paiement.reference)
        window.location.assign(urlPaiement)
        return
      }
      erreurPaiement.value = response.data.erreur_paiement || ''
      currentStep.value = 3
    } else {
      orderError.value = response.data?.message || 'Erreur lors de la création de la commande.'
    }
  } catch (error) {
    console.error('Erreur création commande:', error)
    orderError.value = messageErreur(error, 'Erreur lors de la création de la commande.')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  // Accès direct à /commander : le panier n'est peut-être pas encore chargé
  if (panierStore.isEmpty) {
    await panierStore.fetch()
  }
  if (panierStore.isEmpty) {
    router.push('/panier')
    return
  }
  fetchAdresses()
})

// Charge les conditions de livraison (villes, minimum) des vendeurs du panier
watch(
  () => groupes.value.map((groupe) => groupe.vendeurId).filter(Boolean).join(','),
  () => chargerLivraisonVendeurs(groupes.value.map((groupe) => groupe.vendeurId)),
  { immediate: true }
)
</script>
