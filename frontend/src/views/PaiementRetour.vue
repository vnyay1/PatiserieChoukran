<!-- ===================================
RETOUR DE PAIEMENT NOTCHPAY
File: src/views/PaiementRetour.vue
=================================== -->
<!--
  La zone d'état est une région live : « Vérification… » puis le résultat sont annoncés.
  Chaque issue a son icône, sa couleur, un texte explicite (jamais la couleur seule) et une action :
  réessayer la vérification, relancer le paiement depuis la commande, ou suivre ses commandes.
  Paiement confirmé par NotchPay mais commandes non payées : le montant est vérifié par l'équipe
  (contrôle du backend), la page le dit au lieu d'annoncer « payée ».
-->
<template>
  <div class="container mx-auto flex min-h-[60vh] max-w-lg items-center justify-center py-12">
    <div class="card w-full p-6 text-center sm:p-8">
      <div role="status" aria-live="polite">
        <span
          class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full ring-8 ring-gray-50"
          :class="presentation.fond"
          aria-hidden="true"
        >
          <span v-if="etatAffiche === 'verification'" class="spinner h-9 w-9 border-[3px] text-gold-600"></span>
          <component :is="presentation.icone" v-else :size="36" :stroke-width="1.75" />
        </span>

        <h1 class="text-balance text-2xl">{{ presentation.titre }}</h1>
        <p class="mt-2 text-gray-600">
          <template v-if="etatAffiche === 'complete'">
            {{ commandesPayables.length > 1 ? 'Vos commandes sont payées' : 'Votre commande est payée' }} :
            <span class="font-semibold text-gray-900">{{ formatPrice(paiement.montant) }} FCFA</span>.
          </template>
          <template v-else-if="etatAffiche === 'a_verifier'">
            NotchPay a confirmé votre paiement de
            <span class="font-semibold text-gray-900">{{ formatPrice(paiement.montant) }} FCFA</span>.
            Notre équipe vérifie le montant avant de valider la commande : vous serez prévenu dans vos notifications.
          </template>
          <template v-else>{{ message }}</template>
        </p>
      </div>

      <ul v-if="paiement?.commandes?.length && etatAffiche !== 'verification'" class="mt-6 space-y-2 text-left">
        <li v-for="commande in paiement.commandes" :key="commande.id">
          <router-link
            :to="`/mes-commandes/${commande.id}`"
            class="flex min-h-12 items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-2 transition-colors hover:border-gray-300 hover:bg-gray-50"
          >
            <span class="font-semibold text-gray-900">{{ commande.numero_commande }}</span>
            <BadgeStatut :statut="commande.statut_paiement" type="paiement" />
          </router-link>
        </li>
      </ul>

      <div v-if="etatAffiche !== 'verification'" class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
        <Button v-if="['en_attente', 'erreur'].includes(etatAffiche)" variant="primary" :icon="RefreshCw" @click="verifier">
          {{ etatAffiche === 'erreur' ? 'Réessayer' : 'Vérifier à nouveau' }}
        </Button>
        <!-- Refusé, annulé ou expiré : le paiement se relance depuis la commande -->
        <Button v-else-if="peutRelancer" :to="lienRelance" variant="primary" :icon="Lock">
          Relancer le paiement
        </Button>
        <Button to="/mes-commandes" :variant="actionPrincipale ? 'outline' : 'primary'">
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
import BadgeStatut from '@/components/common/BadgeStatut.vue'
import {
  CheckCircle2, Hourglass, XCircle, Ban, TimerOff, SearchX, WifiOff, ShieldCheck, RefreshCw, Lock,
} from 'lucide-vue-next'
import { formatPrice } from '@/utils/format'
import { oublierReferencePaiement, referencePaiementMemorisee } from '@/utils/paiement'

// Le webhook confirme le paiement côté serveur ; ici on relit le statut quelques fois
const INTERVALLE_MS = 4000
const TENTATIVES_MAX = 15

// Présentation de chaque issue (icône différente pour chacune : la couleur n'est qu'un renfort)
const ETATS = {
  verification: { titre: 'Vérification du paiement…', fond: 'bg-gold-100 text-gold-700' },
  complete: { titre: 'Paiement reçu', fond: 'bg-green-100 text-green-700', icone: CheckCircle2 },
  a_verifier: { titre: 'Paiement reçu, vérification en cours', fond: 'bg-yellow-100 text-yellow-800', icone: ShieldCheck },
  en_attente: { titre: 'Paiement en cours de traitement', fond: 'bg-yellow-100 text-yellow-800', icone: Hourglass },
  echec: { titre: 'Paiement refusé', fond: 'bg-red-100 text-red-700', icone: XCircle },
  annule: { titre: 'Paiement annulé', fond: 'bg-gray-100 text-gray-700', icone: Ban },
  expire: { titre: 'Paiement expiré', fond: 'bg-gray-100 text-gray-700', icone: TimerOff },
  introuvable: { titre: 'Paiement introuvable', fond: 'bg-gray-100 text-gray-700', icone: SearchX },
  erreur: { titre: 'Vérification impossible pour le moment', fond: 'bg-gray-100 text-gray-700', icone: WifiOff },
}

const route = useRoute()

const etat = ref('verification')
const paiement = ref(null)
const erreur = ref('')
let minuterie = null
let tentatives = 0

// Le serveur accepte notre référence comme celle de NotchPay (une seule valeur texte :
// ?reference=a&reference=b donnerait un tableau)
const premiereReference = (...valeurs) => valeurs.find((valeur) => typeof valeur === 'string' && valeur !== '')
const reference = premiereReference(route.query.reference, route.query.trxref, route.query.notchpay_trxref)
  || referencePaiementMemorisee()

// Commandes encore concernées par le paiement (une commande annulée entre-temps ne compte plus)
const commandesPayables = computed(() => (paiement.value?.commandes || []).filter((commande) => commande.statut !== 'annulee'))

const etatAffiche = computed(() => {
  if (etat.value === 'complete' && commandesPayables.value.some((commande) => commande.statut_paiement !== 'paye')) {
    return 'a_verifier'
  }
  return ETATS[etat.value] ? etat.value : 'introuvable'
})

const presentation = computed(() => ETATS[etatAffiche.value])

const peutRelancer = computed(() => ['echec', 'annule', 'expire'].includes(etatAffiche.value)
  && commandesPayables.value.some((commande) => commande.statut_paiement !== 'paye'))

// Une seule commande : sa fiche (bouton « Payer maintenant ») ; plusieurs : la liste
const lienRelance = computed(() => (commandesPayables.value.length === 1
  ? `/mes-commandes/${commandesPayables.value[0].id}`
  : '/mes-commandes'))

const actionPrincipale = computed(() => ['en_attente', 'erreur'].includes(etatAffiche.value) || peutRelancer.value)

const message = computed(() => {
  if (erreur.value) {
    return erreur.value
  }
  if (etatAffiche.value === 'en_attente') {
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
    // Référence inconnue : inutile de la garder ; panne réseau : elle reste pour réessayer
    if (err.response?.status === 404) {
      etat.value = 'introuvable'
      erreur.value = 'Ce paiement n\'existe pas ou n\'est pas lié à votre compte. Retrouvez vos commandes et leur paiement dans « Mes commandes ».'
      oublierReferencePaiement()
    } else {
      etat.value = 'erreur'
      erreur.value = messageErreur(err, 'Impossible de vérifier le paiement pour le moment.')
    }
  }
}

onMounted(verifier)
onBeforeUnmount(arreter)
</script>
