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
              <span :class="['badge', getBadgeClass(commande.statut)]">{{ getStatutLabel(commande.statut) }}</span>
              <span v-if="commande.statut !== 'annulee'" :class="['badge', getPaymentBadgeClass(commande.statut_paiement)]">
                <span class="sr-only">Paiement :</span>
                {{ getPaymentLabel(commande.statut_paiement) }}
              </span>
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
              <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Montant total</span>
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
            <ul class="mt-4 divide-y divide-gray-200">
              <li v-for="ligne in commande.ligne_commandes || []" :key="ligne.id" class="flex items-center gap-4 py-3 first:pt-0 last:pb-0">
                <img
                  loading="lazy"
                  :src="resolveImageUrl(ligne.produit?.image_principale)"
                  alt=""
                  class="h-16 w-16 flex-shrink-0 rounded-xl bg-gray-100 object-cover sm:h-20 sm:w-20"
                  @error="onImageError"
                />
                <div class="min-w-0 flex-1">
                  <p class="font-semibold text-gray-900">{{ ligne.nom_produit }}</p>
                  <p class="text-sm text-gray-600">{{ ligne.quantite }} × {{ formatPrice(ligne.prix_unitaire) }} FCFA</p>
                </div>
                <p class="price text-lg">{{ formatPrice(ligne.sous_total) }} FCFA</p>
              </li>
            </ul>
          </section>

          <!-- Suivi -->
          <section v-if="historiqueTrie.length" class="card p-5 sm:p-7" aria-labelledby="titre-suivi">
            <h2 id="titre-suivi" class="text-xl">Suivi de la commande</h2>
            <ol class="mt-5">
              <li v-for="(etape, index) in historiqueTrie" :key="etape.id" class="relative flex gap-4 pb-6 last:pb-0">
                <span
                  v-if="index < historiqueTrie.length - 1"
                  class="absolute left-[0.6875rem] top-6 h-[calc(100%-1.5rem)] w-0.5 bg-gold-200"
                  aria-hidden="true"
                ></span>
                <span
                  class="relative mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full"
                  :class="index === historiqueTrie.length - 1 ? 'bg-gold-500 text-on-gold' : 'bg-gold-100 text-gold-700'"
                  aria-hidden="true"
                >
                  <Check :size="13" :stroke-width="3" />
                </span>
                <div class="min-w-0">
                  <p class="font-semibold text-gray-900">
                    {{ getStatutLabel(etape.nouveau_statut) }}
                    <span v-if="index === historiqueTrie.length - 1" class="sr-only">(étape actuelle)</span>
                  </p>
                  <p class="text-sm text-gray-600"><time :datetime="etape.created_at">{{ formatDateHeure(etape.created_at) }}</time></p>
                  <p v-if="etape.commentaire" class="mt-1 text-sm text-gray-700">{{ etape.commentaire }}</p>
                </div>
              </li>
            </ol>
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
            <dl class="mt-4 space-y-2 text-sm">
              <div class="flex justify-between gap-3">
                <dt class="text-gray-600">Produits</dt>
                <dd class="tabular-nums text-gray-900">{{ formatPrice(commande.montant_produits) }} FCFA</dd>
              </div>
              <div class="flex justify-between gap-3">
                <dt class="text-gray-600">Livraison</dt>
                <dd class="tabular-nums text-gray-900">{{ formatPrice(commande.montant_livraison) }} FCFA</dd>
              </div>
              <div class="flex items-baseline justify-between gap-3 border-t border-gray-200 pt-3">
                <dt class="font-semibold text-gray-900">Total</dt>
                <dd class="price text-xl">{{ formatPrice(commande.montant_total) }} FCFA</dd>
              </div>
              <div class="flex justify-between gap-3 pt-2">
                <dt class="text-gray-600">Moyen</dt>
                <dd class="text-gray-900">{{ getPaymentMethodLabel(commande.moyen_paiement) }}</dd>
              </div>
              <div v-if="commande.telephone_paiement" class="flex justify-between gap-3">
                <dt class="text-gray-600">Numéro</dt>
                <dd class="text-gray-900">{{ commande.telephone_paiement }}</dd>
              </div>
              <div v-if="commande.date_paiement" class="flex justify-between gap-3">
                <dt class="text-gray-600">Payée le</dt>
                <dd class="text-gray-900">{{ formatDateHeure(commande.date_paiement) }}</dd>
              </div>
            </dl>
          </section>

          <!-- Actions (commande en attente) -->
          <section v-if="canEdit && !editing" class="card p-5 sm:p-6" aria-labelledby="titre-actions">
            <h2 id="titre-actions" class="text-lg">Modifier ou annuler</h2>
            <p class="mt-1 text-sm text-gray-600">Possible tant que le vendeur n'a pas confirmé la commande.</p>
            <div class="mt-4 flex flex-col gap-2">
              <Button variant="outline" :icon="Pencil" @click="toggleEdit">Modifier la commande</Button>
              <Button variant="ghost" class="text-red-700 hover:bg-red-50" :icon="XCircle" :loading="canceling" @click="confirmerAnnulation">
                Annuler la commande
              </Button>
            </div>
          </section>
        </div>
      </div>

      <!-- Modification -->
      <section v-if="editing" ref="formulaireModif" class="card p-5 sm:p-7" aria-labelledby="titre-modification">
        <h2 id="titre-modification" ref="titreModif" tabindex="-1" class="text-xl">Modifier la commande</h2>

        <form class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2" novalidate @submit.prevent="submitUpdate">
          <FormField v-slot="{ attrs }" label="Mode de réception">
            <select v-model="form.type_livraison" v-bind="attrs" class="input">
              <option value="livraison">Livraison à domicile</option>
              <option value="retrait_boutique">Retrait en boutique</option>
            </select>
          </FormField>

          <FormField v-if="form.type_livraison === 'livraison'" v-slot="{ attrs }" label="Adresse de livraison" requis>
            <select v-model="form.adresse_livraison_id" v-bind="attrs" class="input">
              <option value="" disabled>Sélectionner une adresse</option>
              <option v-for="adresse in adresses" :key="adresse.id" :value="adresse.id">
                {{ libelleAdresse(adresse) }}
              </option>
            </select>
          </FormField>

          <FormField v-if="form.type_livraison === 'livraison'" v-slot="{ attrs }" label="Téléphone pour la livraison" requis>
            <input v-model="form.telephone_livraison" v-bind="attrs" type="tel" autocomplete="tel" class="input" />
          </FormField>

          <FormField v-slot="{ attrs }" label="Moyen de paiement">
            <select v-model="form.moyen_paiement" v-bind="attrs" class="input">
              <option value="orange_money">Orange Money</option>
              <option value="mtn_momo">MTN Mobile Money</option>
              <option value="especes">Espèces</option>
            </select>
          </FormField>

          <FormField v-if="form.moyen_paiement !== 'especes'" v-slot="{ attrs }" label="Numéro Mobile Money" requis>
            <input v-model="form.telephone_paiement" v-bind="attrs" type="tel" autocomplete="tel" class="input" />
          </FormField>

          <FormField v-slot="{ attrs }" label="Instructions pour le vendeur" facultatif class="md:col-span-2">
            <textarea v-model="form.instructions_speciales" v-bind="attrs" rows="3" class="input resize-y"></textarea>
          </FormField>

          <p v-if="form.type_livraison === 'livraison' && livraisonPossible" class="flex items-center gap-2 text-sm text-gray-700 md:col-span-2">
            <Truck :size="16" aria-hidden="true" />
            Frais de livraison : {{ formatPrice(fraisLivraison) }} FCFA
          </p>
          <AlertMessage v-if="shippingError" type="warning" class="md:col-span-2">{{ shippingError }}</AlertMessage>

          <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end md:col-span-2">
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
import {
  formatPrice,
  formatDateLongue as formatDate,
  formatDateHeure,
  libelleStatut as getStatutLabel,
  classeStatut as getBadgeClass,
  libellePaiement as getPaymentLabel,
  classePaiement as getPaymentBadgeClass,
  libelleMoyenPaiement as getPaymentMethodLabel,
} from '@/utils/format'
import Button from '@/components/common/Button.vue'
import FormField from '@/components/common/FormField.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useLivraisonVendeurs } from '@/composables/useLivraisonVendeurs'
import { formatVille, libelleAdresse, villeAdresse } from '@/utils/villes'
import { ArrowLeft, Phone, Pencil, Truck, Store, FileDown, Lock, Check, XCircle, PackageOpen } from 'lucide-vue-next'
import { resolveImageUrl, onImageError } from '@/utils/images'

const route = useRoute()
const toastStore = useToastStore()
const { confirmer } = useConfirm()

const loading = ref(true)
const error = ref('')
const commande = ref(null)

const editing = ref(false)
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
    memoriserReferencePaiement(response.data.data.reference)
    window.location.assign(response.data.data.url_paiement)
  } catch (err) {
    toastStore.erreur(messageErreur(err, 'Impossible d\'ouvrir le paiement pour le moment.'))
    ouverturePaiement.value = false
  }
}

// Étapes de la commande, de la plus ancienne à la plus récente
const historiqueTrie = computed(() => {
  return [...(commande.value?.historiques || [])]
    .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

const initFormFromCommande = () => {
  if (!commande.value) return
  form.value = {
    type_livraison: commande.value.type_livraison || 'livraison',
    adresse_livraison_id: commande.value.adresse_livraison_id || '',
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
