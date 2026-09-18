<!-- ===================================
SÉLECTEUR DE VILLE (en-tête)
File: src/components/layout/VilleSelecteur.vue
=================================== -->
<!--
  Bouton-disclosure : aria-expanded + aria-controls, choix actuel signalé par aria-pressed
  et une coche. Échap ferme la liste et rend le focus au bouton ; un focus qui sort la ferme.
-->
<template>
  <div ref="racine" class="relative">
    <button
      ref="bouton"
      type="button"
      class="inline-flex min-h-10 items-center gap-1.5 rounded-full border border-gold-200 bg-gold-50 px-3 text-sm font-semibold text-gold-800 transition-colors hover:border-gold-300 hover:bg-gold-100"
      :aria-expanded="ouvert"
      :aria-controls="idListe"
      @click="ouvert = !ouvert"
    >
      <MapPin :size="16" aria-hidden="true" />
      <span class="sr-only">Ville de livraison :</span>
      <span class="max-w-[6.5rem] truncate">{{ villeStore.ville ? villeStore.libelle : 'Ma ville' }}</span>
      <ChevronDown :size="14" class="transition-transform duration-200" :class="{ 'rotate-180': ouvert }" aria-hidden="true" />
    </button>

    <Transition name="deroulant">
      <div
        v-if="ouvert"
        :id="idListe"
        class="absolute z-50 mt-2 w-60 rounded-2xl border border-gray-200 bg-surface p-1.5 shadow-elegant-lg"
        :class="alignement === 'droite' ? 'right-0' : 'left-0'"
        @keydown.esc.stop="fermer(true)"
      >
        <p class="px-3 pb-1 pt-2 text-xs font-semibold text-gray-500">Produits livrables à…</p>
        <ul>
          <li v-for="ville in VILLES" :key="ville.valeur">
            <button
              type="button"
              class="option-ville"
              :aria-pressed="villeStore.ville === ville.valeur"
              @click="choisir(ville.valeur)"
            >
              {{ ville.libelle }}
              <Check v-if="villeStore.ville === ville.valeur" :size="16" class="text-gold-700" aria-hidden="true" />
            </button>
          </li>
          <li class="mt-1 border-t border-gray-200 pt-1">
            <button
              type="button"
              class="option-ville"
              :aria-pressed="!villeStore.ville"
              @click="choisir(null)"
            >
              Toutes les villes
              <Check v-if="!villeStore.ville" :size="16" class="text-gold-700" aria-hidden="true" />
            </button>
          </li>
        </ul>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, useId } from 'vue'
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
const bouton = ref(null)
const idListe = useId()

const fermer = (rendreFocus = false) => {
  ouvert.value = false
  if (rendreFocus) bouton.value?.focus()
}

const choisir = (ville) => {
  villeStore.choisir(ville)
  fermer(true)
  emit('choisie', ville)
}

const fermerSiExterieur = (event) => {
  if (ouvert.value && racine.value && !racine.value.contains(event.target)) fermer()
}

onMounted(() => {
  document.addEventListener('click', fermerSiExterieur)
  document.addEventListener('focusin', fermerSiExterieur)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', fermerSiExterieur)
  document.removeEventListener('focusin', fermerSiExterieur)
})
</script>

<style scoped>
.option-ville {
  @apply flex min-h-11 w-full items-center justify-between gap-3 rounded-xl px-3 text-left text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900;
}

.option-ville[aria-pressed='true'] {
  @apply bg-gold-50 font-semibold text-gold-800;
}

.deroulant-enter-active,
.deroulant-leave-active {
  transition: opacity 0.15s ease, transform 0.15s cubic-bezier(0.2, 0, 0, 1);
}

.deroulant-enter-from,
.deroulant-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
