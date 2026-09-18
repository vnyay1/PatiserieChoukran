<!-- ===================================
RETOUR DE PAIEMENT NOTCHPAY
File: src/views/PaiementRetour.vue
=================================== -->
<!--
  La zone d'état est une région live : « Vérification… » puis le résultat sont annoncés.
  Chaque issue a son icône, sa couleur et un texte explicite (jamais la couleur seule).
-->
<template>
  <div class="container mx-auto flex min-h-[60vh] max-w-lg items-center justify-center py-12">
    <div class="card w-full p-6 text-center sm:p-8">
      <div role="status" aria-live="polite">
        <span
          class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full"
          :class="presentation.fond"
          aria-hidden="true"
        >
          <span v-if="etat === 'verification'" class="spinner h-8 w-8 border-[3px] text-gold-600"></span>
          <component :is="presentation.icone" v-else :size="32" />
        </span>

        <h1 class="text-2xl">{{ etat === 'verification' ? 'Vérification du paiement…' : etat === 'complete' ? 'Paiement reçu' : titre }}</h1>
        <p class="mt-2 text-gray-600">
          <template v-if="etat === 'verification'">
            Validez la demande sur votre téléphone si ce n'est pas encore fait. Cette page se met à jour toute seule.
          </template>
          <template v-else-if="etat === 'complete'">
            {{ paiement.commandes.length > 1 ? 'Vos commandes sont payées' : 'Votre commande est payée' }} :
            <span class="font-semibold text-gray-900">{{ formatPrice(paiement.montant) }} FCFA</span>.
          </template>
          <template v-else>{{ message }}</template>
        </p>
      </div>

      <ul v-if="paiement?.commandes?.length && etat !== 'verification'" class="mt-6 space-y-2 text-left">
        <li v-for="commande in paiement.commandes" :key="commande.id">
          <router-link
            :to="`/mes-commandes/${commande.id}`"
            class="flex min-h-12 items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-2 transition-colors hover:border-gray-300 hover:bg-gray-50"
          >
            <span class="font-semibold text-gray-900">{{ commande.numero_commande }}</span>
            <span :class="['badge', classePaiement(commande.statut_paiement)]">
              {{ libellePaiement(commande.statut_paiement) }}
            </span>
          </router-link>
        </li>
      </ul>

      <div v-if="etat !== 'verification'" class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
        <Button v-if="etat === 'en_attente'" variant="primary" :icon="RefreshCw" @click="verifier">
          Vérifier à nouveau
        </Button>
        <Button to="/mes-commandes" :variant="etat === 'en_attente' ? 'outline' : 'primary'">
          Mes commandes
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api, { messageErreur } from '@/services/api'
import Button from '@/components/common/Button.vue'
import { CheckCircle2, Hourglass, AlertTriangle, RefreshCw } from 'lucide-vue-next'
import { classePaiement, formatPrice, libellePaiement } from '@/utils/format'
import { oublierReferencePaiement, referencePaiementMemorisee } from '@/utils/paiement'

// Le webhook confirme le paiement côté serveur ; ici on relit le statut quelques fois
const INTERVALLE_MS = 4000
const TENTATIVES_MAX = 15

const route = useRoute()

const etat = ref('verification')
const paiement = ref(null)
const erreur = ref('')
let minuterie = null
let tentatives = 0

// Le serveur accepte notre référence comme celle de NotchPay
const reference = route.query.reference
  || route.query.trxref
  || route.query.notchpay_trxref
  || referencePaiementMemorisee()

const titre = computed(() => ({
  en_attente: 'Paiement en cours de traitement',
  echec: 'Paiement refusé',
  annule: 'Paiement annulé',
  expire: 'Paiement expiré',
}[etat.value] || 'Paiement introuvable'))

// Icône et couleur de chaque issue
const presentation = computed(() => {
  if (etat.value === 'verification') return { fond: 'bg-gold-100', icone: null }
  if (etat.value === 'complete') return { fond: 'bg-green-100 text-green-700', icone: CheckCircle2 }
  if (etat.value === 'en_attente') return { fond: 'bg-yellow-100 text-yellow-700', icone: Hourglass }
  return { fond: 'bg-red-100 text-red-700', icone: AlertTriangle }
})

const message = computed(() => {
  if (erreur.value) {
    return erreur.value
  }
  if (etat.value === 'en_attente') {
    return 'NotchPay n\'a pas encore confirmé la transaction. Vous pouvez revenir plus tard : la commande sera marquée payée dès la confirmation.'
  }
  return 'Aucun montant n\'a été débité. Vous pouvez relancer le paiement depuis le détail de la commande.'
})

const arreter = () => {
  clearTimeout(minuterie)
  minuterie = null
}

const verifier = async () => {
  arreter()
  if (!reference) {
    etat.value = 'introuvable'
    erreur.value = 'Aucune référence de paiement dans le lien de retour.'
    return
  }

  etat.value = 'verification'
  erreur.value = ''
  tentatives = 0
  await interroger()
}

const interroger = async () => {
  try {
    const response = await api.paiements.verifier(reference)
    paiement.value = response.data.data
    tentatives += 1

    if (paiement.value.statut === 'en_attente') {
      if (tentatives < TENTATIVES_MAX) {
        minuterie = setTimeout(interroger, INTERVALLE_MS)
        return
      }
    } else {
      oublierReferencePaiement()
    }
    etat.value = paiement.value.statut
  } catch (err) {
    etat.value = 'introuvable'
    erreur.value = messageErreur(err, 'Impossible de vérifier le paiement pour le moment.')
  }
}

onMounted(verifier)
onBeforeUnmount(arreter)
</script>
