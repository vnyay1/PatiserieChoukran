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

        <!-- Livraison -->
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <component :is="getDeliveryIcon(commande.type_livraison)" :size="16" />
          <span>
            {{ commande.type_livraison === 'livraison' ? 'Livraison' : 'Retrait en boutique' }}
            {{ commande.date_livraison_souhaitee ? ` - ${formatDate(commande.date_livraison_souhaitee)}` : '' }}
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
          <div :class="['badge', getPaymentBadgeClass(commande.statut_paiement)]">
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
import { Package, Truck, Store, ChevronRight } from 'lucide-vue-next'

defineProps({
  commande: {
    type: Object,
    required: true
  }
})

defineEmits(['click'])

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const getStatutLabel = (statut) => {
  const labels = {
    'en_attente': 'En attente',
    'confirmee': 'Confirmée',
    'en_preparation': 'En préparation',
    'prete': 'Prête',
    'en_livraison': 'En livraison',
    'livree': 'Livrée',
    'annulee': 'Annulée'
  }
  return labels[statut] || statut
}

const getBadgeClass = (statut) => {
  const classes = {
    'en_attente': 'bg-yellow-100 text-yellow-700',
    'confirmee': 'bg-blue-100 text-blue-700',
    'en_preparation': 'bg-purple-100 text-purple-700',
    'prete': 'bg-indigo-100 text-indigo-700',
    'en_livraison': 'bg-orange-100 text-orange-700',
    'livree': 'bg-green-100 text-green-700',
    'annulee': 'bg-red-100 text-red-700'
  }
  return classes[statut] || 'bg-gray-100 text-gray-700'
}

const getPaymentLabel = (statut) => {
  const labels = {
    'en_attente': 'À payer',
    'paye': 'Payé',
    'echec': 'Échec',
    'rembourse': 'Remboursé'
  }
  return labels[statut] || statut
}

const getPaymentBadgeClass = (statut) => {
  const classes = {
    'en_attente': 'bg-yellow-100 text-yellow-700',
    'paye': 'bg-green-100 text-green-700',
    'echec': 'bg-red-100 text-red-700',
    'rembourse': 'bg-gray-100 text-gray-700'
  }
  return classes[statut] || 'bg-gray-100 text-gray-700'
}

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