<!-- ===================================
COMPOSANT COMMANDE CARD
File: src/components/commande/CommandeCard.vue
=================================== -->
<!--
  Carte-lien : le numéro de commande est un lien étiré sur toute la carte (clavier, clic milieu).
  Suivi : barre de progression ARIA avec l'étape en texte.
-->
<template>
  <Card
    hoverable
    padding="none"
    tag="article"
    class="p-4 has-[.lien-etire:focus-visible]:outline has-[.lien-etire:focus-visible]:outline-2 has-[.lien-etire:focus-visible]:outline-offset-2 has-[.lien-etire:focus-visible]:outline-gold-600 sm:p-5"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="font-body text-base font-bold text-gray-900">
          <router-link
            :to="`/mes-commandes/${commande.id}`"
            class="lien-etire after:absolute after:inset-0 after:content-[''] focus-visible:outline-none"
          >
            {{ commande.numero_commande }}
          </router-link>
        </h3>
        <p class="text-sm text-gray-600">{{ formatDate(commande.created_at) }}</p>
      </div>
      <div class="flex flex-shrink-0 items-center gap-1">
        <span :class="['badge', getBadgeClass(commande.statut)]">{{ getStatutLabel(commande.statut) }}</span>
        <ChevronRight :size="20" class="text-gray-500" aria-hidden="true" />
      </div>
    </div>

    <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1.5 text-sm text-gray-700">
      <li class="flex items-center gap-1.5">
        <Package :size="16" class="text-gray-500" aria-hidden="true" />
        {{ nombreArticles }} article{{ nombreArticles > 1 ? 's' : '' }}
      </li>
      <li v-if="commande.vendeur?.nom_complet" class="flex items-center gap-1.5">
        <Store :size="16" class="text-gray-500" aria-hidden="true" />
        {{ commande.vendeur.nom_complet }}
      </li>
      <li class="flex items-center gap-1.5">
        <component :is="commande.type_livraison === 'livraison' ? Truck : Store" :size="16" class="text-gray-500" aria-hidden="true" />
        {{ commande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait en boutique' }}
      </li>
    </ul>

    <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
      <p>
        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Montant total</span>
        <span class="price text-xl">{{ formatPrice(commande.montant_total) }} FCFA</span>
      </p>
      <span v-if="commande.statut !== 'annulee'" :class="['badge', getPaymentBadgeClass(commande.statut_paiement)]">
        <span class="sr-only">Paiement :</span>
        {{ getPaymentLabel(commande.statut_paiement) }}
      </span>
    </div>

    <!-- Suivi (commande en cours) -->
    <div v-if="!['livree', 'annulee'].includes(commande.statut)" class="mt-4 border-t border-gray-200 pt-4">
      <p :id="`suivi-${commande.id}`" class="mb-2 text-sm font-medium text-gray-700">{{ getProgressLabel(commande.statut) }}</p>
      <div
        class="h-2 overflow-hidden rounded-full bg-gray-200"
        role="progressbar"
        :aria-labelledby="`suivi-${commande.id}`"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-valuenow="getProgressPercent(commande.statut)"
      >
        <div
          class="h-full rounded-full bg-gold-600 transition-[width] duration-500"
          :style="{ width: `${getProgressPercent(commande.statut)}%` }"
        ></div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/components/common/Card.vue'
import {
  formatPrice,
  formatDateLongue as formatDate,
  libelleStatut as getStatutLabel,
  classeStatut as getBadgeClass,
  libellePaiement as getPaymentLabel,
  classePaiement as getPaymentBadgeClass,
} from '@/utils/format'
import { Package, Truck, Store, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  commande: {
    type: Object,
    required: true
  }
})

const nombreArticles = computed(() => props.commande.ligne_commandes_count || props.commande.ligne_commandes?.length || 0)

const getProgressLabel = (statut) => {
  const labels = {
    'en_attente': 'Commande reçue',
    'confirmee': 'Commande confirmée',
    'en_preparation': 'En cours de préparation',
    'prete': 'Prête à être livrée',
    'en_livraison': 'En cours de livraison'
  }
  return labels[statut] || ''
}

const getProgressPercent = (statut) => {
  const percents = {
    'en_attente': 20,
    'confirmee': 40,
    'en_preparation': 60,
    'prete': 80,
    'en_livraison': 90,
    'livree': 100
  }
  return percents[statut] || 0
}
</script>
