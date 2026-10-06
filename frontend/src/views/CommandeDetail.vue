<!-- ===================================
PAGE DÉTAIL COMMANDE
File: src/views/CommandeDetail.vue
=================================== -->
<!--
  En tête : numéro, statuts (texte + couleur), montant et l'action attendue (« Payer maintenant »).
  Puis articles et suivi (frise ordonnée), livraison et paiement en colonne latérale.
  Annulation confirmée par useConfirm (focus sur « Garder la commande »).
-->
<template>
  <div class="container mx-auto max-w-6xl pb-6 pt-5 md:pt-8">
    <router-link to="/mes-commandes" class="btn-ghost btn-sm -ml-3 mb-4">
      <ArrowLeft :size="16" aria-hidden="true" />
      Mes commandes
    </router-link>

    <!-- Chargement -->
    <div v-if="loading" class="space-y-4" aria-busy="true">
      <div v-for="n in 3" :key="n" class="skeleton h-40 rounded-elegant"></div>
      <span class="sr-only">Chargement de la commande…</span>
    </div>

    <!-- Introuvable -->
    <EmptyState
      v-else-if="!commande"
      :icone="PackageOpen"
      niveau="h1"
      titre="Commande introuvable"
      :texte="error || 'Impossible de charger la commande.'"
    >
      <Button to="/mes-commandes" variant="primary">Retour à mes commandes</Button>
    </EmptyState>

    <div v-else class="space-y-6">
      <!-- En-tête -->
      <section class="card p-5 sm:p-7" aria-labelledby="titre-commande">
        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <BadgeStatut :statut="commande.statut" />
              <BadgeStatut v-if="commande.statut !== 'annulee'" :statut="commande.statut_paiement" type="paiement" />
            </div>
            <h1 id="titre-commande" class="mt-3 text-2xl md:text-3xl">Commande {{ commande.numero_commande }}</h1>
            <p class="mt-1 text-sm text-gray-600">Passée le {{ formatDate(commande.created_at) }}</p>
            <p v-if="commande.vendeur?.nom_complet" class="mt-1 text-sm text-gray-600">
              Vendeur :
              <router-link :to="{ name: 'vendeur-profil', params: { id: commande.vendeur.id } }" class="lien">
                {{ commande.vendeur.nom_complet }}
              </router-link>
            </p>
          </div>

          <div class="flex flex-col gap-3 md:items-end">
            <p class="md:text-right">
              <span class="block text-sm text-gray-600">Montant total</span>
              <span class="price text-3xl">{{ formatPrice(commande.montant_total) }} FCFA</span>
            </p>
            <Button
              v-if="peutPayerEnLigne"
              variant="primary"
              :icon="Lock"
              :loading="ouverturePaiement"
              @click="payerMaintenant"
            >
              Payer maintenant
            </Button>
            <!-- Facture générée quand le vendeur confirme la commande -->
            <Button
              v-if="commande.facture"
              variant="outline"
              size="sm"
              :icon="FileDown"
              :icon-size="16"
              :loading="telechargementFacture"
              @click="telechargerFacture"
            >
              Facture {{ commande.facture.numero_facture }} (PDF)
            </Button>
          </div>
        </div>
      </section>

      <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
          <!-- Articles -->
          <section class="card p-5 sm:p-7" aria-labelledby="titre-articles">
            <h2 id="titre-articles" class="text-xl">Articles</h2>
            <LignesCommande :lignes="commande.ligne_commandes || []" class="mt-4" />
          </section>

          <!-- Suivi -->
          <section v-if="commande.historiques?.length" class="card p-5 sm:p-7" aria-labelledby="titre-suivi">
            <h2 id="titre-suivi" class="text-xl">Suivi de la commande</h2>
            <ChronologieCommande :historiques="commande.historiques" class="mt-5" />
          </section>
        </div>

        <div class="space-y-6">
          <!-- Livraison -->
          <section class="card p-5 sm:p-6" aria-labelledby="titre-livraison">
            <h2 id="titre-livraison" class="flex items-center gap-2 text-lg">
              <component :is="commande.type_livraison === 'livraison' ? Truck : Store" :size="20" class="text-gold-600" aria-hidden="true" />
              {{ commande.type_livraison === 'livraison' ? 'Livraison à domicile' : 'Retrait en boutique' }}
            </h2>
            <dl class="mt-4 space-y-3 text-sm">
              <div v-if="commande.type_livraison === 'livraison'">
                <dt class="font-semibold text-gray-900">Adresse</dt>
                <dd class="text-gray-700">{{ libelleAdresse(commande.adresse_livraison) || 'Adresse supprimée' }}</dd>
              </div>
              <div v-if="commande.type_livraison === 'livraison'">
                <dt class="font-semibold text-gray-900">Téléphone</dt>
                <dd class="text-gray-700">{{ commande.telephone_livraison || '—' }}</dd>
              </div>
              <div v-if="commande.instructions_speciales">
                <dt class="font-semibold text-gray-900">Instructions</dt>
                <dd class="text-gray-700">{{ commande.instructions_speciales }}</dd>
              </div>
            </dl>
            <!-- Contact du vendeur : utile en cas de question sur la livraison -->
            <a
              v-if="commande.vendeur?.telephone"
              :href="`tel:${commande.vendeur.telephone}`"
              class="btn-secondary btn-sm mt-5 w-full"
            >
              <Phone :size="16" aria-hidden="true" />
              Appeler le vendeur
              <span class="sr-only">au {{ commande.vendeur.telephone }}</span>
            </a>
          </section>

          <!-- Paiement -->
          <section class="card p-5 sm:p-6" aria-labelledby="titre-paiement">
            <h2 id="titre-paiement" class="text-lg">Paiement</h2>
            <RecapMontants
              :produits="commande.montant_produits"
              :livraison="commande.montant_livraison"
              :total="commande.montant_total"
              :afficher-livraison="commande.type_livraison === 'livraison'"
              class="mt-4"
            >
              <div class="flex justify-between gap-3 pt-2">
                <dt class="text-gray-700">Moyen</dt>
                <dd class="text-right text-gray-900">{{ getPaymentMethodLabel(commande.moyen_paiement) }}</dd>
              </div>
              <div v-if="commande.telephone_paiement" class="flex justify-between gap-3">
                <dt class="text-gray-700">Numéro</dt>
                <dd class="text-gray-900">{{ commande.telephone_paiement }}</dd>
              </div>
              <div v-if="commande.date_paiement" class="flex justify-between gap-3">
                <dt class="text-gray-700">Payée le</dt>
                <dd class="text-right text-gray-900">{{ formatDateHeure(commande.date_paiement) }}</dd>
              </div>
            </RecapMontants>
          </section>

          <!-- Actions (commande en attente) -->
          <section v-if="canEdit && !editing" class="card p-5 sm:p-6" aria-labelledby="titre-actions">
            <h2 id="titre-actions" class="text-lg">Modifier ou annuler</h2>
            <!-- Payée : seul le vendeur annule (il rembourse) ; les instructions restent modifiables -->
            <template v-if="estPayee">
              <p class="mt-1 text-sm text-gray-600">
                Votre commande est payée : pour l'annuler ou changer la livraison, contactez le vendeur, qui organisera le remboursement.
              </p>
              <div class="mt-4 flex flex-col gap-2">
                <a
                  v-if="commande.vendeur?.telephone"
                  :href="`tel:${commande.vendeur.telephone}`"
                  class="btn-secondary"
                >
                  <Phone :size="16" aria-hidden="true" />
                  Contacter le vendeur
                  <span class="sr-only">au {{ commande.vendeur.telephone }}</span>
                </a>
                <Button variant="outline" :icon="Pencil" @click="toggleEdit">Modifier les instructions</Button>
              </div>
            </template>
            <template v-else>
              <p class="mt-1 text-sm text-gray-600">Possible tant que le vendeur n'a pas confirmé la commande.</p>
              <p v-if="commande.paiement?.statut === 'en_attente'" class="mt-2 text-sm text-gray-600">
                Un paiement en ligne a été ouvert : tant qu'il est en cours, la livraison et le moyen de paiement ne peuvent plus changer.
              </p>
              <div class="mt-4 flex flex-col gap-2">
                <Button variant="outline" :icon="Pencil" @click="toggleEdit">Modifier la commande</Button>
                <Button variant="ghost" class="text-red-700 hover:bg-red-50" :icon="XCircle" :loading="canceling" @click="confirmerAnnulation">
                  Annuler la commande
                </Button>
              </div>
            </template>
          </section>
        </div>
      </div>

      <!-- Modification -->
      <section v-if="editing" ref="formulaireModif" class="card p-5 sm:p-7" aria-labelledby="titre-modification">
        <h2 id="titre-modification" ref="titreModif" tabindex="-1" class="text-xl">Modifier la commande</h2>

        <!-- Mêmes cartes qu'au checkout : le client retrouve les choix qu'il a faits -->
        <form class="mt-6 space-y-7" novalidate @submit.prevent="submitUpdate">
          <p v-if="estPayee" class="text-sm text-gray-600">
            Commande payée : le mode de réception, l'adresse et le paiement ne changent plus. Pour cela, contactez le vendeur.
          </p>

          <fieldset v-if="!estPayee">
            <legend class="label mb-3">Mode de réception</legend>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <CarteRadio
                v-for="mode in MODES_RECEPTION"
                :key="mode.valeur"
                v-model="form.type_livraison"
                name="modif_type_livraison"
                :value="mode.valeur"
                :titre="mode.libelle"
                :description="mode.description"
              >
                <template #visuel>
                  <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-gold-100 text-gold-700">
                    <component :is="mode.icone" :size="22" aria-hidden="true" />
                  </span>
                </template>
              </CarteRadio>
            </div>
          </fieldset>

          <fieldset v-if="!estPayee && form.type_livraison === 'livraison'">
            <legend class="label mb-3">Adresse de livraison</legend>
            <AlertMessage v-if="adresses.length === 0" type="info">
              Ajoutez d'abord une adresse depuis <router-link to="/profil" class="lien">votre compte</router-link>.
            </AlertMessage>
            <div v-else class="space-y-3">
              <CarteRadio
                v-for="adresse in adresses"
                :key="adresse.id"
                v-model="form.adresse_livraison_id"
                name="modif_adresse_livraison_id"
                :value="adresse.id"
                :titre="adresse.libelle || adresse.quartier || 'Adresse'"
                :description="libelleAdresse(adresse)"
              >
                <template #visuel>
                  <MapPin :size="20" class="flex-shrink-0 text-gold-600" aria-hidden="true" />
                </template>
              </CarteRadio>
            </div>
          </fieldset>

          <FormField
            v-if="form.type_livraison === 'livraison'"
            v-slot="{ attrs }"
            label="Téléphone pour la livraison"
            requis
            :erreur="erreursModif.telephone_livraison"
            class="max-w-sm"
          >
            <TelephoneInput v-model="form.telephone_livraison" v-bind="attrs" />
          </FormField>

          <fieldset v-if="!estPayee">
            <legend class="label mb-3">Moyen de paiement</legend>
            <div class="space-y-3">
              <CarteRadio
                v-for="moyen in MOYENS_PAIEMENT_CHOIX"
                :key="moyen.valeur"
                v-model="form.moyen_paiement"
                name="modif_moyen_paiement"
                :value="moyen.valeur"
                :titre="moyen.libelle"
                :description="moyen.description"
              >
                <template #visuel>
                  <span class="flex h-11 w-14 flex-shrink-0 items-center justify-center rounded-lg bg-plaque p-1 ring-1 ring-gray-200">
                    <img :src="moyen.logo" alt="" class="max-h-full w-auto object-contain" />
                  </span>
                </template>
              </CarteRadio>
            </div>
          </fieldset>

          <FormField
            v-if="!estPayee && form.moyen_paiement !== 'especes'"
            v-slot="{ attrs }"
            label="Numéro Mobile Money"
            requis
            :erreur="erreursModif.telephone_paiement"
            class="max-w-sm"
          >
            <TelephoneInput v-model="form.telephone_paiement" v-bind="attrs" />
          </FormField>

          <FormField v-slot="{ attrs }" label="Instructions pour le vendeur" facultatif>
            <textarea v-model="form.instructions_speciales" v-bind="attrs" rows="3" class="input resize-y"></textarea>
          </FormField>

          <p v-if="!estPayee && form.type_livraison === 'livraison' && livraisonPossible" class="flex items-center gap-2 text-sm text-gray-700">
            <Truck :size="16" aria-hidden="true" />
            Frais de livraison : {{ formatPrice(fraisLivraison) }} FCFA
          </p>
          <AlertMessage v-if="!estPayee && shippingError" type="warning">{{ shippingError }}</AlertMessage>

          <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <Button type="button" variant="outline" @click="toggleEdit">Abandonner</Button>
            <Button type="submit" variant="primary" :loading="updating">Enregistrer les modifications</Button>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import api, { messageErreur, lireErreurBlob } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { telechargerBlob } from '@/utils/telechargement'
import { memoriserReferencePaiement } from '@/utils/paiement'
import { estUrlPaiementSure } from '@/utils/url'
import {
  formatPrice,
  formatDateLongue as formatDate,
  formatDateHeure,
  libelleMoyenPaiement as getPaymentMethodLabel,
} from '@/utils/format'
import Button from '@/components/common/Button.vue'
import BadgeStatut from '@/components/common/BadgeStatut.vue'
import FormField from '@/components/common/FormField.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import CarteRadio from '@/components/common/CarteRadio.vue'
import TelephoneInput from '@/components/common/TelephoneInput.vue'
import LignesCommande from '@/components/commande/LignesCommande.vue'
import ChronologieCommande from '@/components/commande/ChronologieCommande.vue'
import RecapMontants from '@/components/commande/RecapMontants.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useLivraisonVendeurs } from '@/composables/useLivraisonVendeurs'
import { formatVille, libelleAdresse, villeAdresse } from '@/utils/villes'
import { ArrowLeft, Phone, Pencil, Truck, Store, FileDown, Lock, XCircle, PackageOpen, MapPin } from 'lucide-vue-next'
import { chiffresLocaux, estTelephoneComplet, telephoneComplet } from '@/utils/telephone'
import { MODES_RECEPTION, MOYENS_PAIEMENT_CHOIX } from '@/utils/choixCommande'

const route = useRoute()
const toastStore = useToastStore()
const { confirmer } = useConfirm()

const loading = ref(true)
const error = ref('')
const commande = ref(null)

const editing = ref(false)
const erreursModif = ref({})
const updating = ref(false)
const canceling = ref(false)
const titreModif = ref(null)

const adresses = ref([])
const fraisLivraison = ref(0)
const livraisonPossible = ref(false)
const shippingError = ref('')

const { erreur: erreurLivraison, estCharge, chargerLivraisonVendeurs, livreDans, livraisonDe, manquePourMinimum } = useLivraisonVendeurs()

const telechargementFacture = ref(false)

const telechargerFacture = async () => {
  telechargementFacture.value = true
  try {
    const response = await api.commandes.facture(commande.value.id)
    telechargerBlob(response, `${commande.value.facture.numero_facture}.pdf`)
  } catch (err) {
    await lireErreurBlob(err)
    toastStore.erreur(messageErreur(err, 'Impossible de télécharger la facture.'))
  } finally {
    telechargementFacture.value = false
  }
}

const vendeurCommandeId = computed(() => commande.value?.vendeur_id ?? null)

const form = ref({
  type_livraison: 'livraison',
  adresse_livraison_id: '',
  telephone_livraison: '',
  instructions_speciales: '',
  moyen_paiement: 'orange_money',
  telephone_paiement: '',
})

const canEdit = computed(() => commande.value?.statut === 'en_attente')
// Payée : seul le vendeur annule ou change la livraison (il rembourse)
const estPayee = computed(() => commande.value?.statut_paiement === 'paye')

// Paiement NotchPay proposé tant qu'une commande mobile money n'est ni payée ni annulée
const paiementEnLigne = ref(false)
const ouverturePaiement = ref(false)
const peutPayerEnLigne = computed(() => paiementEnLigne.value
  && ['orange_money', 'mtn_momo'].includes(commande.value?.moyen_paiement)
  && commande.value?.statut_paiement !== 'paye'
  && commande.value?.statut !== 'annulee')

const payerMaintenant = async () => {
  ouverturePaiement.value = true
  try {
    const response = await api.commandes.payer(commande.value.id)
    const { reference, url_paiement: urlPaiement } = response.data.data
    // Seule la page de paiement NotchPay est ouverte, jamais une autre adresse
    if (!estUrlPaiementSure(urlPaiement)) {
      throw new Error('Adresse de paiement inattendue')
    }
    memoriserReferencePaiement(reference)
    window.location.assign(urlPaiement)
  } catch (err) {
    toastStore.erreur(messageErreur(err, 'Impossible d\'ouvrir le paiement pour le moment.'))
    ouverturePaiement.value = false
  }
}

const initFormFromCommande = () => {
  if (!commande.value) return
  form.value = {
    type_livraison: commande.value.type_livraison || 'livraison',
    adresse_livraison_id: commande.value.adresse_livraison_id || '',
    // 9 chiffres locaux dans les champs, « +237… » à l'envoi
    telephone_livraison: chiffresLocaux(commande.value.telephone_livraison),
    instructions_speciales: commande.value.instructions_speciales || '',
    moyen_paiement: commande.value.moyen_paiement || 'orange_money',
    telephone_paiement: chiffresLocaux(commande.value.telephone_paiement),
  }
}

const fetchCommande = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.commandes.getOne(route.params.id)
    if (response.data.success) {
      commande.value = response.data.data
      paiementEnLigne.value = Boolean(response.data.paiement_en_ligne)
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

// Livraison vérifiée avec le vendeur de CETTE commande (et non ceux du panier, vide une
// fois la commande passée) : ville livrée et montant minimum ; frais standard
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

  await chargerLivraisonVendeurs([vendeurCommandeId.value])
  if (!estCharge(vendeurCommandeId.value)) {
    shippingError.value = erreurLivraison.value || 'Impossible de calculer les frais de livraison.'
    return
  }

  const ville = villeAdresse(adresse)
  if (!livreDans(vendeurCommandeId.value, ville)) {
    shippingError.value = `Le vendeur de cette commande ne livre pas à ${formatVille(ville)}.`
    return
  }

  const manque = manquePourMinimum(vendeurCommandeId.value, commande.value.montant_produits)
  if (manque > 0) {
    const { minimum } = livraisonDe(vendeurCommandeId.value)
    shippingError.value = `Ce vendeur livre à partir de ${formatPrice(minimum)} FCFA d'achat (il manque ${formatPrice(manque)} FCFA) : gardez le retrait en boutique.`
    return
  }

  fraisLivraison.value = livraisonDe(vendeurCommandeId.value).frais
  livraisonPossible.value = true
}

const toggleEdit = async () => {
  editing.value = !editing.value
  if (editing.value) {
    initFormFromCommande()
    refreshShipping()
    // Le formulaire apparaît sous le contenu : on y amène la vue et le focus
    await nextTick()
    titreModif.value?.scrollIntoView({ block: 'start', behavior: 'smooth' })
    titreModif.value?.focus({ preventScroll: true })
  }
}

const focusPremiereErreur = async () => {
  await nextTick()
  document.querySelector('[aria-invalid="true"]')?.focus()
}

const submitUpdate = async () => {
  erreursModif.value = {}
  if (form.value.type_livraison === 'livraison' && !estTelephoneComplet(form.value.telephone_livraison)) {
    erreursModif.value.telephone_livraison = 'Indiquez les 9 chiffres du numéro auquel le vendeur peut vous joindre.'
  }
  if (!estPayee.value && form.value.moyen_paiement !== 'especes' && !estTelephoneComplet(form.value.telephone_paiement)) {
    erreursModif.value.telephone_paiement = 'Indiquez les 9 chiffres du numéro Mobile Money qui va payer.'
  }
  if (Object.keys(erreursModif.value).length) {
    focusPremiereErreur()
    return
  }

  if (!estPayee.value && form.value.type_livraison === 'livraison' && !livraisonPossible.value) {
    if (!shippingError.value) {
      shippingError.value = 'Veuillez sélectionner une adresse de livraison valide.'
    }
    return
  }

  updating.value = true
  try {
    const payload = {
      ...form.value,
      telephone_livraison: telephoneComplet(form.value.telephone_livraison),
      telephone_paiement: telephoneComplet(form.value.telephone_paiement),
    }
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

const confirmerAnnulation = async () => {
  const ok = await confirmer({
    titre: 'Annuler la commande ?',
    message: `La commande ${commande.value.numero_commande} sera annulée. Cette action est définitive.`,
    libelleConfirmer: 'Annuler la commande',
    libelleAnnuler: 'Garder la commande',
    danger: true,
  })
  if (ok) cancelOrder()
}

const cancelOrder = async () => {
  canceling.value = true
  try {
    const response = await api.commandes.cancel(route.params.id)
    if (response.data.success) {
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
