<!-- ===================================
RETOUR DE PAIEMENT NOTCHPAY
File: src/views/PaiementRetour.vue
=================================== -->

<template>
  <div class="min-h-[70vh] flex items-center justify-center px-4 py-16 bg-cream">
    <div class="text-center max-w-md w-full">
      <template v-if="etat === 'verification'">
        <div class="mx-auto mb-4 h-12 w-12 rounded-full border-4 border-gold-200 border-t-gold-600 animate-spin"></div>
        <h1 class="font-display text-2xl font-bold text-gray-800 mb-2">Vérification du paiement…</h1>
        <p class="text-gray-600">
          Validez la demande sur votre téléphone si ce n'est pas encore fait. Cette page se met à jour automatiquement.
        </p>
      </template>

      <template v-else-if="etat === 'complete'">
        <div class="text-6xl mb-4">🎉</div>
        <h1 class="font-display text-2xl font-bold text-gray-800 mb-2">Paiement reçu</h1>
        <p class="text-gray-600 mb-6">
          {{ paiement.commandes.length > 1 ? 'Vos commandes sont payées' : 'Votre commande est payée' }} :
          {{ formatPrice(paiement.montant) }} FCFA.
        </p>
      </template>

      <template v-else>
        <div class="text-6xl mb-4">{{ etat === 'en_attente' ? '⏳' : '⚠️' }}</div>
        <h1 class="font-display text-2xl font-bold text-gray-800 mb-2">{{ titre }}</h1>
        <p class="text-gray-600 mb-6">{{ message }}</p>
      </template>

      <div v-if="paiement?.commandes?.length && etat !== 'verification'" class="space-y-2 mb-6 text-left">
        <router-link
          v-for="commande in paiement.commandes"
          :key="commande.id"
          :to="`/mes-commandes/${commande.id}`"
          class="flex items-center justify-between gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:border-gold-300"
        >
          <span class="font-medium text-gray-800">{{ commande.numero_commande }}</span>
          <span :class="['badge', classePaiement(commande.statut_paiement)]">
            {{ libellePaiement(commande.statut_paiement) }}
          </span>
        </router-link>
      </div>

      <div v-if="etat !== 'verification'" class="flex flex-col sm:flex-row gap-3 justify-center">
        <Button v-if="etat === 'en_attente'" variant="primary" @click="verifier">
          Vérifier à nouveau
        </Button>
        <Button :variant="etat === 'en_attente' ? 'outline' : 'primary'" @click="router.push('/mes-commandes')">
          Mes commandes
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { messageErreur } from '@/services/api'
import Button from '@/components/common/Button.vue'
import { classePaiement, formatPrice, libellePaiement } from '@/utils/format'
import { oublierReferencePaiement, referencePaiementMemorisee } from '@/utils/paiement'

// Le webhook confirme le paiement côté serveur ; ici on relit le statut quelques fois
const INTERVALLE_MS = 4000
const TENTATIVES_MAX = 15

const route = useRoute()
const router = useRouter()

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
