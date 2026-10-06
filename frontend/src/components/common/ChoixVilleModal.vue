<!-- ===================================
CHOIX DE LA VILLE (première visite)
File: src/components/common/ChoixVilleModal.vue
=================================== -->
<template>
  <BaseModal
    :ouvert="visible"
    titre="Où souhaitez-vous être livré ?"
    description="Nous affichons les pâtisseries que nos vendeurs livrent dans votre ville. Vous pourrez la changer à tout moment en haut de la page."
    taille="sm"
    libelle-fermer="Voir toute la boutique"
    @fermer="villeStore.choisir(null)"
  >
    <div class="grid grid-cols-2 gap-3">
      <button
        v-for="(ville, index) in VILLES"
        :key="ville.valeur"
        type="button"
        class="flex min-h-24 flex-col items-center justify-center gap-2 rounded-2xl border-2 border-gray-200 bg-surface px-4 py-4 font-semibold text-gray-900
               transition-colors hover:border-gold-600 hover:bg-gold-50"
        :data-autofocus="index === 0 ? '' : undefined"
        @click="villeStore.choisir(ville.valeur)"
      >
        <MapPin :size="24" class="text-gold-600" aria-hidden="true" />
        {{ ville.libelle }}
      </button>
    </div>

    <div class="mt-4 text-center">
      <button type="button" class="btn-ghost btn-sm" @click="villeStore.choisir(null)">
        Voir toute la boutique
      </button>
    </div>
  </BaseModal>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useVilleStore } from '@/stores/ville'
import { VILLES } from '@/utils/villes'
import { MapPin } from 'lucide-vue-next'
import BaseModal from '@/components/common/BaseModal.vue'

const route = useRoute()
const authStore = useAuthStore()
const villeStore = useVilleStore()

// Seulement pour le parcours d'achat : ni sur les pages de connexion, ni pour l'équipe
const PAGES_CATALOGUE = ['home', 'produits', 'produit-detail', 'vendeur-profil']

const visible = computed(() => villeStore.doitDemander
  && PAGES_CATALOGUE.includes(route.name)
  && !authStore.isAdmin
  && !authStore.isVendeur)
</script>
