<!-- ===================================
COMPOSANT COMMANDE CARD
File: src/components/commande/CommandeCard.vue
=================================== -->

<template>
  <Card padding="md" hoverable clickable @click="$emit('click', commande)">
    <div class="flex flex-col md:flex-row md:items-center gap-4">
      <!-- Info principale -->
      <div class="flex-1">
        <!-- En-tête -->
        <div class="flex items-start justify-between mb-3">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <h3 class="font-display font-semibold text-lg">
                {{ commande.numero_commande }}
              </h3>
              <span :class="['badge', getBadgeClass(commande.statut)]">
                {{ getStatutLabel(commande.statut) }}
              </span>
            </div>
            <p class="text-sm text-gray-600">
              {{ formatDate(commande.created_at) }}
            </p>
          </div>
        </div>

        <!-- Produits -->
        <div class="flex items-center gap-2 mb-3 text-sm text-gray-600">
          <Package :size="16" />
          <span>{{ commande.ligne_commandes_count || commande.ligne_commandes?.length || 0 }} article(s)</span>
        </div>

        <!-- Vendeur (une commande par vendeur) -->
        <div v-if="commande.vendeur?.nom_complet" class="flex items-center gap-2 mb-3 text-sm text-gray-600">
          <Store :size="16" />
          <span>Vendeur : {{ commande.vendeur.nom_complet }}</span>
        </div>

        <!-- Livraison -->
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <component :is="getDeliveryIcon(commande.type_livraison)" :size="16" />
          <span>
            {{ commande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait en boutique' }}
          </span>
        </div>
      </div>

      <!-- Prix et action -->
      <div class="flex items-center justify-between md:flex-col md:items-end gap-3">
        <div class="text-right">
          <div class="text-sm text-gray-600 mb-1">Montant total</div>
          <div class="price text-xl">{{ formatPrice(commande.montant_total) }} FCFA</div>
        </div>

        <div class="flex gap-2">
          <!-- Badge paiement -->
          <div
            v-if="commande.statut !== 'annulee'"
            :class="['badge', getPaymentBadgeClass(commande.statut_paiement)]"
          >
            {{ getPaymentLabel(commande.statut_paiement) }}
          </div>

          <!-- Bouton voir détail -->
          <button
            class="text-gold-600 hover:text-gold-700"
            @click.stop="$emit('click', commande)"
          >
            <ChevronRight :size="20" />
          </button>
        </div>
      </div>
    </div>

    <!-- Barre de progression (si en cours) -->
    <div
      v-if="!['livree', 'annulee'].includes(commande.statut)"
      class="mt-4 pt-4 border-t border-gray-100"
    >
      <div class="flex items-center justify-between text-xs text-gray-600 mb-2">
        <span>{{ getProgressLabel(commande.statut) }}</span>
        <span>{{ getProgressPercent(commande.statut) }}%</span>
      </div>
      <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
        <div
          class="h-full bg-gradient-gold transition-all duration-500"
          :style="{ width: `${getProgressPercent(commande.statut)}%` }"
        ></div>
      </div>
    </div>
  </Card>
</template>

<script setup>
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

defineProps({
  commande: {
    type: Object,
    required: true
  }
})

defineEmits(['click'])

const getDeliveryIcon = (type) => {
  return type === 'livraison' ? Truck : Store
}

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