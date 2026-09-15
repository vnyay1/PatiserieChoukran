<!-- ===================================
CHOIX DE LA VILLE (première visite)
File: src/components/common/ChoixVilleModal.vue
=================================== -->

<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed inset-0 z-[60] bg-black/50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="choix-ville-titre"
    >
      <div class="bg-white rounded-elegant p-6 max-w-sm w-full animate-fadeIn text-center">
        <MapPin :size="36" class="mx-auto text-gold-600 mb-3" />
        <h2 id="choix-ville-titre" class="font-display text-xl font-bold text-gray-800 mb-1">
          Où êtes-vous ?
        </h2>
        <p class="text-sm text-gray-600 mb-5">
          Nous vous montrons les pâtisseries que nos vendeurs peuvent vous livrer dans votre ville.
        </p>

        <div class="grid grid-cols-2 gap-3 mb-4">
          <button
            v-for="ville in VILLES"
            :key="ville.valeur"
            type="button"
            class="rounded-xl border-2 border-gold-200 bg-gold-50 px-4 py-3 font-semibold text-gold-800 hover:border-gold-500"
            @click="villeStore.choisir(ville.valeur)"
          >
            {{ ville.libelle }}
          </button>
        </div>

        <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="villeStore.choisir(null)">
          Voir toute la boutique
        </button>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useVilleStore } from '@/stores/ville'
import { VILLES } from '@/utils/villes'
import { MapPin } from 'lucide-vue-next'

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
