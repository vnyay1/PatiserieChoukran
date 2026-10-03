<!-- ===================================
COMPOSANT CHAMP DE RECHERCHE
File: src/components/common/ChampRecherche.vue
=================================== -->
<!--
  Champ type="search" avec loupe. Le libellé est visible (filtres de l'espace de gestion)
  ou réservé aux lecteurs d'écran (barre de la vitrine). v-model suit chaque frappe ;
  « rechercher » est émis 400 ms après la dernière touche, ou tout de suite avec Entrée.
-->
<template>
  <div>
    <label :for="idChamp" :class="libelleVisible ? 'label' : 'sr-only'">{{ libelle }}</label>
    <div class="relative">
      <Search
        class="pointer-events-none absolute top-1/2 -translate-y-1/2 text-gray-500"
        :class="arrondi ? 'left-4' : 'left-3.5'"
        :size="18"
        aria-hidden="true"
      />
      <input
        :id="idChamp"
        v-model="valeur"
        type="search"
        enterkeyhint="search"
        autocomplete="off"
        :placeholder="placeholder"
        class="input"
        :class="arrondi ? 'rounded-full pl-11' : 'pl-10'"
        @input="declencher"
        @keydown.enter.prevent="lancerMaintenant"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue'
import { Search } from 'lucide-vue-next'
import { useRechercheDifferee } from '@/composables/useRechercheDifferee'

const valeur = defineModel({ type: String, default: '' })

const props = defineProps({
  libelle: {
    type: String,
    required: true,
  },
  libelleVisible: {
    type: Boolean,
    default: false,
  },
  placeholder: {
    type: String,
    default: '',
  },
  // Champ en pilule (barre de recherche de la vitrine)
  arrondi: {
    type: Boolean,
    default: false,
  },
  id: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['rechercher'])

const idAuto = useId()
const idChamp = computed(() => props.id || idAuto)

const { declencher, lancerMaintenant } = useRechercheDifferee(() => emit('rechercher', valeur.value))
</script>
