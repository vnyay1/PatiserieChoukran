<!-- ===================================
1. PAGE CHECKOUT - Processus de commande
File: src/views/Checkout.vue
=================================== -->
<!--
  Trois étapes (Livraison, Paiement, Confirmation) annoncées par une liste ordonnée
  avec aria-current="step". À chaque étape, le focus va au titre de l'étape.
  Adresses et moyens de paiement en cartes radio (fieldset/legend) plutôt qu'en liste
  déroulante : tout est visible, un seul geste pour choisir. Récapitulatif collant sur desktop.
-->
<template>
  <div class="container mx-auto max-w-6xl pb-6 pt-6 md:pt-8">
    <h1>Finaliser la commande</h1>

    <!-- Étapes -->
    <ol class="mb-8 mt-6 grid grid-cols-3 gap-2" aria-label="Étapes de la commande">
      <li
        v-for="(etape, index) in ETAPES"
        :key="etape"
        class="flex flex-col gap-2"
        :aria-current="currentStep === index + 1 ? 'step' : undefined"
      >
        <span
          class="h-1.5 rounded-full transition-colors duration-300"
          :class="currentStep > index ? 'bg-gold-500' : 'bg-gray-200'"
          aria-hidden="true"
        ></span>
        <span class="flex items-center gap-2 text-sm font-semibold" :class="currentStep >= index + 1 ? 'text-gray-900' : 'text-gray-500'">
          <span
            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full text-xs"
            :class="currentStep > index + 1 ? 'bg-green-100 text-green-800' : currentStep === index + 1 ? 'bg-gold-500 text-on-gold' : 'bg-gray-200 text-gray-600'"
            aria-hidden="true"
          >
            <Check v-if="currentStep > index + 1" :size="14" />
            <template v-else>{{ index + 1 }}</template>
          </span>
          {{ etape }}
          <span v-if="currentStep > index + 1" class="sr-only">(terminée)</span>
        </span>
      </li>
    </ol>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-8">
      <div class="min-w-0">
        <!-- Étape 1 : livraison -->
        <section v-if="currentStep === 1" class="card p-5 sm:p-7" aria-labelledby="titre-etape">
          <h2 id="titre-etape" ref="titreEtape" tabindex="-1" class="text-xl sm:text-2xl">Livraison</h2>

          <form class="mt-6 space-y-7" novalidate @submit.prevent="goToStep2">
            <!-- Mode de réception -->
            <fieldset>
              <legend class="label mb-3">Mode de réception</legend>
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label v-for="mode in MODES" :key="mode.valeur" class="carte-radio">
                  <input v-model="formData.type_livraison" type="radio" name="type_livraison" :value="mode.valeur" class="sr-only" />
                  <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-gold-100 text-gold-700">
                    <component :is="mode.icone" :size="22" aria-hidden="true" />
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="block font-semibold text-gray-900">{{ mode.libelle }}</span>
                    <span class="block text-sm text-gray-600">{{ mode.description }}</span>
                  </span>
                  <span class="coche" aria-hidden="true"><Check :size="14" /></span>
                </label>
              </div>
            </fieldset>

            <!-- Adresse -->
            <fieldset v-if="estLivraison">
              <legend class="label mb-3">Adresse de livraison</legend>

              <AlertMessage v-if="adresses.length === 0 && !chargementAdresses" type="info" class="mb-3">
                Ajoutez une adresse de livraison pour continuer.
              </AlertMessage>

              <div class="space-y-3">
                <div v-for="adresse in adresses" :key="adresse.id" class="relative">
                  <label class="carte-radio items-start pr-16">
                    <input
                      v-model="formData.adresse_livraison_id"
                      type="radio"
                      name="adresse_livraison_id"
                      :value="adresse.id"
                      class="sr-only"
                    />
                    <MapPin :size="20" class="mt-0.5 flex-shrink-0 text-gold-600" aria-hidden="true" />
                    <span class="min-w-0 flex-1">
                      <span class="flex flex-wrap items-center gap-2 font-semibold text-gray-900">
                        {{ adresse.libelle || adresse.quartier || 'Adresse' }}
                        <span v-if="adresse.est_principale" class="badge badge-neutral">Principale</span>
                      </span>
                      <span class="block text-sm text-gray-600">{{ lieuAdresse(adresse) }}</span>
                      <span v-if="adresse.telephone_contact" class="block text-sm text-gray-600">{{ adresse.telephone_contact }}</span>
                      <span v-if="!adresse.quartier_id" class="mt-1 flex items-center gap-1.5 text-sm font-medium text-orange-700">
                        <AlertTriangle :size="14" aria-hidden="true" />
                        Quartier à préciser pour être livré
                      </span>
                    </span>
                  </label>
                  <button
                    type="button"
                    class="btn-ghost btn-sm absolute right-2 top-2 px-3"
                    :aria-label="`Modifier l'adresse ${adresse.libelle || lieuAdresse(adresse)}`"
                    @click="openEditAddress(adresse)"
                  >
                    <Pencil :size="15" aria-hidden="true" />
                    <span class="hidden sm:inline">Modifier</span>
                  </button>
                </div>

                <button
                  type="button"
                  class="flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-gray-300 px-4 font-semibold text-gray-700 transition-colors hover:border-gold-500 hover:bg-gold-50 hover:text-gray-900"
                  @click="openAddAddress"
                >
                  <Plus :size="18" aria-hidden="true" />
                  Ajouter une adresse
                </button>
              </div>
            </fieldset>

            <FormField
              v-if="estLivraison"
              v-slot="{ attrs }"
              label="Téléphone pour la livraison"
              requis
              aide="Le vendeur vous appelle à ce numéro pour convenir du passage."
              :erreur="erreurs.telephone_livraison"
            >
              <input
                v-model="formData.telephone_livraison"
                v-bind="attrs"
                type="tel"
                inputmode="tel"
                autocomplete="tel"
                placeholder="+237 6XX XX XX XX"
                class="input"
              />
            </FormField>

            <FormField v-slot="{ attrs }" label="Instructions pour le vendeur" facultatif>
              <textarea
                v-model="formData.instructions_speciales"
                v-bind="attrs"
                rows="3"
                placeholder="Ex. Sonner à l'interphone, appeler en arrivant, message à écrire sur le gâteau…"
                class="input resize-y"
              ></textarea>
            </FormField>

            <AlertMessage v-if="blocageEtape1" type="warning">{{ blocageEtape1 }}</AlertMessage>

            <Button type="submit" variant="primary" size="lg" full-width :disabled="isStep1Blocked">
              Continuer vers le paiement
              <ArrowRight :size="18" aria-hidden="true" />
            </Button>
          </form>
        </section>

        <!-- Étape 2 : paiement -->
        <section v-if="currentStep === 2" class="card p-5 sm:p-7" aria-labelledby="titre-etape">
          <h2 id="titre-etape" ref="titreEtape" tabindex="-1" class="text-xl sm:text-2xl">Paiement</h2>

          <form class="mt-6 space-y-7" novalidate @submit.prevent="submitOrder">
            <fieldset>
              <legend class="label mb-3">Moyen de paiement</legend>
              <div class="space-y-3">
                <label v-for="method in paymentMethods" :key="method.value" class="carte-radio">
                  <input v-model="formData.moyen_paiement" type="radio" name="moyen_paiement" :value="method.value" class="sr-only" />
                  <span class="flex h-11 w-14 flex-shrink-0 items-center justify-center rounded-lg bg-white p-1 ring-1 ring-gray-200">
                    <img :src="method.logo" alt="" class="max-h-full w-auto object-contain" />
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="block font-semibold text-gray-900">{{ method.label }}</span>
                    <span class="block text-sm text-gray-600">{{ method.description }}</span>
                  </span>
                  <span class="coche" aria-hidden="true"><Check :size="14" /></span>
                </label>
              </div>
            </fieldset>

            <FormField
              v-if="formData.moyen_paiement !== 'especes'"
              v-slot="{ attrs }"
              label="Numéro Mobile Money"
              requis
              aide="Vous serez redirigé vers la page de paiement sécurisée NotchPay pour valider sur votre téléphone."
              :erreur="erreurs.telephone_paiement"
            >
              <input
                v-model="formData.telephone_paiement"
                v-bind="attrs"
                type="tel"
                inputmode="tel"
                autocomplete="tel"
                placeholder="+237 6XX XX XX XX"
                class="input"
              />
            </FormField>

            <AlertMessage v-if="orderError" type="error">{{ orderError }}</AlertMessage>

            <div class="flex flex-col-reverse gap-3 sm:flex-row">
              <Button type="button" variant="outline" :icon="ArrowLeft" @click="allerEtape(1)">
                Retour
              </Button>
              <Button type="submit" variant="primary" size="lg" :loading="submitting" class="flex-1">
                <Lock :size="17" aria-hidden="true" />
                {{ formData.moyen_paiement === 'especes' ? 'Confirmer la commande' : `Payer ${formatPrice(totalGeneral)} FCFA` }}
              </Button>
            </div>
          </form>
        </section>

        <!-- Étape 3 : confirmation (une commande par vendeur) -->
        <section v-if="currentStep === 3" class="card p-5 text-center sm:p-8" aria-labelledby="titre-etape">
          <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-700">
            <CheckCircle2 :size="34" aria-hidden="true" />
          </span>
          <h2 id="titre-etape" ref="titreEtape" tabindex="-1" class="text-2xl">
            {{ commandesCreees.length > 1 ? `${commandesCreees.length} commandes confirmées` : 'Commande confirmée' }}
          </h2>
          <p class="mx-auto mt-2 max-w-md text-gray-600">
            <template v-if="commandesCreees.length > 1">Votre panier a été réparti en une commande par vendeur. </template>
            Suivez leur avancement dans « Mes commandes ».
          </p>

          <AlertMessage v-if="erreurPaiement" type="warning" class="mt-6 text-left">{{ erreurPaiement }}</AlertMessage>

          <ul class="mt-6 space-y-3 text-left">
            <li
              v-for="commande in commandesCreees"
              :key="commande.id"
              class="flex items-center justify-between gap-3 rounded-2xl border border-gray-200 p-4"
            >
              <div class="min-w-0">
                <p class="font-semibold text-gray-900">{{ commande.numero_commande }}</p>
                <p class="text-sm text-gray-600">{{ commande.vendeur?.nom_complet || 'Vendeur' }}</p>
              </div>
              <div class="text-right">
                <p class="price">{{ formatPrice(commande.montant_total) }} FCFA</p>
                <router-link :to="`/mes-commandes/${commande.id}`" class="lien text-sm">
                  Voir le détail<span class="sr-only"> de la commande {{ commande.numero_commande }}</span>
                </router-link>
              </div>
            </li>
          </ul>

          <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <Button to="/mes-commandes" variant="primary" class="flex-1">Voir mes commandes</Button>
            <Button to="/produits" variant="outline" class="flex-1">Continuer mes achats</Button>
          </div>
        </section>
      </div>

      <!-- Récapitulatif -->
      <aside v-if="currentStep < 3" class="lg:sticky lg:top-24" aria-labelledby="titre-recap">
        <div class="card p-5 sm:p-6">
          <h2 id="titre-recap" class="text-lg">Récapitulatif</h2>

          <p v-if="groupes.length > 1" class="mt-2 text-sm text-gray-600">
            {{ groupes.length }} commandes, une par vendeur.
          </p>

          <ul class="mt-4 space-y-3">
            <li
              v-for="groupe in livraisonParGroupe"
              :key="groupe.vendeurId ?? 'sans-vendeur'"
              class="rounded-xl bg-gray-50 p-3 text-sm"
            >
              <p class="flex justify-between gap-3 font-semibold text-gray-900">
                <span class="truncate">{{ groupe.vendeurNom }}</span>
                <span class="flex-shrink-0 font-normal text-gray-600">
                  {{ groupe.items.length }} article{{ groupe.items.length > 1 ? 's' : '' }}
                </span>
              </p>
              <dl class="mt-1.5 space-y-1">
                <div class="flex justify-between gap-3">
                  <dt class="text-gray-600">Produits</dt>
                  <dd class="tabular-nums">{{ formatPrice(groupe.sousTotal) }} FCFA</dd>
                </div>
                <div v-if="estLivraison && groupe.vendeurId" class="flex justify-between gap-3">
                  <dt class="text-gray-600">Livraison</dt>
                  <dd v-if="groupe.statut === 'ok'" class="tabular-nums">{{ formatPrice(groupe.frais) }} FCFA</dd>
                  <dd v-else-if="groupe.statut === 'non_couvert'" class="text-right font-medium text-red-700">
                    Non livré à {{ formatVille(villeLivraison) }}
                  </dd>
                  <dd v-else-if="groupe.statut === 'minimum_non_atteint'" class="text-right font-medium text-red-700">
                    Il manque {{ formatPrice(groupe.manque) }} FCFA
                  </dd>
                  <dd v-else-if="groupe.statut === 'chargement'" class="text-gray-600">Calcul…</dd>
                  <dd v-else class="text-gray-600">À calculer</dd>
                </div>
              </dl>
              <p v-if="groupe.statut === 'sans_vendeur'" class="mt-1 text-xs font-medium text-red-700">
                Ces produits ne sont rattachés à aucun vendeur et ne peuvent pas être commandés.
              </p>
            </li>
          </ul>

          <dl class="mt-4 space-y-2 border-t border-gray-200 pt-4 text-[0.9375rem]">
            <div class="flex justify-between gap-3">
              <dt class="text-gray-700">Produits</dt>
              <dd class="tabular-nums">{{ formatPrice(panierStore.total) }} FCFA</dd>
            </div>
            <div v-if="estLivraison" class="flex justify-between gap-3">
              <dt class="text-gray-700">Livraison</dt>
              <dd v-if="livraisonCalculee" class="tabular-nums">{{ formatPrice(fraisLivraison) }} FCFA</dd>
              <dd v-else class="text-sm text-gray-600">À calculer</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 pt-2">
              <dt class="font-semibold text-gray-900">Total</dt>
              <dd class="price text-2xl">{{ formatPrice(totalGeneral) }} FCFA</dd>
            </div>
          </dl>
        </div>
      </aside>
    </div>

    <!-- Ajout / modification d'adresse -->
    <AdresseFormModal
      v-if="showAddAddress"
      :adresse="adresseEnEdition"
      :telephone-par-defaut="authStore.user?.telephone || ''"
      @close="closeAddAddress"
      @saved="onAdresseSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { usePanierStore } from '@/stores/panier'
import { useAuthStore } from '@/stores/auth'
import api, { messageErreur } from '@/services/api'
import Button from '@/components/common/Button.vue'
import FormField from '@/components/common/FormField.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import AdresseFormModal from '@/components/adresse/AdresseFormModal.vue'
import { useLivraisonVendeurs, grouperParVendeur } from '@/composables/useLivraisonVendeurs'
import { useVilleStore } from '@/stores/ville'
import { formatVille, villeAdresse } from '@/utils/villes'
import { memoriserReferencePaiement } from '@/utils/paiement'
import { formatPrice } from '@/utils/format'
import {
  Truck, Store, Check, MapPin, Pencil, Plus, AlertTriangle, ArrowRight, ArrowLeft, Lock, CheckCircle2,
} from 'lucide-vue-next'

const router = useRouter()
const panierStore = usePanierStore()
const authStore = useAuthStore()
const villeStore = useVilleStore()
const {
  erreur: erreurTarifs,
  chargerLivraisonVendeurs,
  livraisonDesGroupes,
} = useLivraisonVendeurs()

const ETAPES = ['Livraison', 'Paiement', 'Confirmation']

const MODES = [
  { valeur: 'livraison', libelle: 'Livraison à domicile', description: 'Frais fixes par vendeur', icone: Truck },
  { valeur: 'retrait_boutique', libelle: 'Retrait en boutique', description: 'Gratuit, sans minimum d\'achat', icone: Store },
]

const currentStep = ref(1)
const titreEtape = ref(null)
const submitting = ref(false)
const showAddAddress = ref(false)
const adresseEnEdition = ref(null)
const adresses = ref([])
const chargementAdresses = ref(true)
const orderError = ref('')
const erreurs = ref({})
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
  { value: 'orange_money', label: 'Orange Money', description: 'Validation sur votre téléphone', logo: '/Orange-Money-logo.png' },
  { value: 'mtn_momo', label: 'MTN Mobile Money', description: 'Validation sur votre téléphone', logo: '/Momo-logo.png' },
  { value: 'especes', label: 'Espèces', description: 'À régler à la livraison ou au retrait', logo: '/argent.png' },
]
const estLivraison = computed(() => formData.value.type_livraison === 'livraison')

const selectedAdresse = computed(() => {
  return adresses.value.find((adresse) => String(adresse.id) === String(formData.value.adresse_livraison_id)) || null
})
const villeLivraison = computed(() => villeAdresse(selectedAdresse.value))

// « Carrefour Obili, Obili, Yaoundé »
const lieuAdresse = (adresse) => [adresse.zone, adresse.quartier, formatVille(villeAdresse(adresse))].filter(Boolean).join(', ')

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

  if (estLivraison.value && selectedAdresse.value && !selectedAdresse.value.quartier_id) {
    return 'Cette adresse n\'a pas de quartier reconnu : modifiez-la pour calculer la livraison.'
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

// Change d'étape, remonte en haut et place le focus sur le titre de l'étape
const allerEtape = async (etape) => {
  currentStep.value = etape
  await nextTick()
  window.scrollTo({ top: 0 })
  titreEtape.value?.focus({ preventScroll: true })
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
  } finally {
    chargementAdresses.value = false
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

const focusPremiereErreur = async () => {
  await nextTick()
  document.querySelector('[aria-invalid="true"]')?.focus()
}

const goToStep2 = () => {
  erreurs.value = {}
  if (estLivraison.value && !formData.value.telephone_livraison.trim()) {
    erreurs.value = { telephone_livraison: 'Indiquez un numéro pour que le vendeur puisse vous joindre.' }
    focusPremiereErreur()
    return
  }
  if (isStep1Blocked.value) {
    return
  }
  orderError.value = ''
  allerEtape(2)
}

const submitOrder = async () => {
  if (isStep1Blocked.value) {
    allerEtape(1)
    return
  }

  erreurs.value = {}
  if (formData.value.moyen_paiement !== 'especes' && !formData.value.telephone_paiement.trim()) {
    erreurs.value = { telephone_paiement: 'Indiquez le numéro Mobile Money qui va payer.' }
    focusPremiereErreur()
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
      allerEtape(3)
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

<style scoped>
/* Carte radio : bordure bronze + fond or + coche quand choisie, anneau au focus clavier */
.carte-radio {
  @apply relative flex min-h-[4.5rem] cursor-pointer items-center gap-3 rounded-2xl border-2 border-gray-200 bg-surface p-4 transition-colors hover:border-gray-300;
}

.carte-radio:has(:checked) {
  @apply border-gold-600 bg-gold-50;
}

.carte-radio:has(:focus-visible) {
  @apply outline outline-2 outline-offset-2 outline-gold-600;
}

.coche {
  @apply flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-gray-300 text-transparent transition-colors;
}

.carte-radio:has(:checked) .coche {
  @apply border-gold-600 bg-gold-600 text-on-accent;
}
</style>
