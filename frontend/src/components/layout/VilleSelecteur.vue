<!-- ===================================
SÉLECTEUR DE VILLE (en-tête)
File: src/components/layout/VilleSelecteur.vue
=================================== -->

<template>
  <div ref="racine" class="relative">
    <button
      type="button"
      class="inline-flex items-center gap-1.5 rounded-full border border-gold-200 bg-gold-50 text-gold-800 px-3 py-1.5 text-sm font-medium hover:bg-gold-100"
      :aria-expanded="ouvert"
      aria-haspopup="listbox"
      :title="villeStore.ville ? `Produits livrables à ${villeStore.libelle}` : 'Choisir ma ville'"
      @click="ouvert = !ouvert"
    >
      <MapPin :size="16" />
      <span>{{ villeStore.ville ? villeStore.libelle : 'Ma ville' }}</span>
      <ChevronDown :size="14" />
    </button>

    <ul
      v-if="ouvert"
      class="absolute z-50 mt-2 w-56 rounded-xl border border-gray-100 bg-surface shadow-elegant-lg py-1"
      :class="alignement === 'droite' ? 'right-0' : 'left-0'"
      role="listbox"
    >
      <li v-for="ville in VILLES" :key="ville.valeur">
        <button
          type="button"
          class="w-full flex items-center justify-between px-4 py-2.5 text-sm hover:bg-gold-50"
          :class="villeStore.ville === ville.valeur ? 'text-gold-700 font-semibold' : 'text-gray-700'"
          role="option"
          :aria-selected="villeStore.ville === ville.valeur"
          @click="choisir(ville.valeur)"
        >
          {{ ville.libelle }}
          <Check v-if="villeStore.ville === ville.valeur" :size="16" />
        </button>
      </li>
      <li class="border-t border-gray-100 mt-1 pt-1">
        <button
          type="button"
          class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50"
          :class="villeStore.ville ? 'text-gray-600' : 'text-gold-700 font-semibold'"
          @click="choisir(null)"
        >
          Toutes les villes
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useVilleStore } from '@/stores/ville'
import { VILLES } from '@/utils/villes'
import { MapPin, ChevronDown, Check } from 'lucide-vue-next'

defineProps({
  // Côté d'ouverture du menu
  alignement: {
    type: String,
    default: 'gauche',
  },
})

const emit = defineEmits(['choisie'])

const villeStore = useVilleStore()
const ouvert = ref(false)
const racine = ref(null)

const choisir = (ville) => {
  villeStore.choisir(ville)
  ouvert.value = false
  emit('choisie', ville)
}

const fermerSiExterieur = (event) => {
  if (ouvert.value && racine.value && !racine.value.contains(event.target)) {
    ouvert.value = false
  }
}

onMounted(() => document.addEventListener('click', fermerSiExterieur))
onBeforeUnmount(() => document.removeEventListener('click', fermerSiExterieur))
</script>
