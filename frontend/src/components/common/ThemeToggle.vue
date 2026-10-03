<!-- ===================================
COMPOSANT CHOIX DU THÈME (clair / sombre / système)
File: src/components/common/ThemeToggle.vue
=================================== -->
<!--
  variante « menu »     : bouton icône de l'en-tête desktop, ouvre une petite liste
  variante « segments » : trois boutons radio côte à côte (menu mobile, profil)
-->
<template>
  <fieldset v-if="variante === 'segments'">
    <legend class="label">Apparence</legend>
    <div class="grid grid-cols-3 gap-1 rounded-xl bg-gray-100 p-1">
      <label
        v-for="option in OPTIONS"
        :key="option.valeur"
        class="flex min-h-11 cursor-pointer flex-col items-center justify-center gap-0.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-gray-600 transition-colors
               hover:text-gray-900 has-[:checked]:bg-surface has-[:checked]:text-gray-900 has-[:checked]:shadow-sm
               has-[:focus-visible]:outline has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-gold-600"
      >
        <input
          type="radio"
          :name="nomGroupe"
          class="sr-only"
          :value="option.valeur"
          :checked="themeStore.preference === option.valeur"
          @change="themeStore.choisir(option.valeur)"
        />
        <component :is="option.icone" :size="18" aria-hidden="true" />
        {{ option.libelle }}
      </label>
    </div>
  </fieldset>

  <div v-else ref="racine" class="relative">
    <button
      ref="bouton"
      type="button"
      class="btn-icone"
      :aria-label="`Thème : ${optionActive.libelle}. Changer le thème`"
      :aria-expanded="ouvert"
      :aria-controls="idListe"
      @click="basculer"
    >
      <component :is="themeStore.estSombre ? Moon : Sun" :size="20" aria-hidden="true" />
    </button>

    <Transition name="deroulant">
      <ul
        v-if="ouvert"
        :id="idListe"
        class="absolute right-0 z-50 mt-2 w-44 rounded-xl border border-gray-200 bg-surface p-1 shadow-elegant-lg"
        @keydown.esc.stop="fermer(true)"
      >
        <li v-for="option in OPTIONS" :key="option.valeur">
          <button
            type="button"
            class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            :aria-pressed="themeStore.preference === option.valeur"
            @click="choisir(option.valeur)"
          >
            <component :is="option.icone" :size="18" aria-hidden="true" />
            <span class="flex-1">{{ option.libelle }}</span>
            <Check v-if="themeStore.preference === option.valeur" :size="16" class="text-gold-600" aria-hidden="true" />
          </button>
        </li>
      </ul>
    </Transition>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue'
import { Sun, Moon, Monitor, Check } from 'lucide-vue-next'
import { useThemeStore } from '@/stores/theme'
import { useMenuDeroulant } from '@/composables/useMenuDeroulant'

defineProps({
  variante: {
    type: String,
    default: 'menu',
    validator: (valeur) => ['menu', 'segments'].includes(valeur)
  }
})

const OPTIONS = [
  { valeur: 'clair', libelle: 'Clair', icone: Sun },
  { valeur: 'sombre', libelle: 'Sombre', icone: Moon },
  { valeur: 'systeme', libelle: 'Auto', icone: Monitor },
]

const themeStore = useThemeStore()
const { ouvert, racine, bouton, idMenu: idListe, fermer, basculer } = useMenuDeroulant()
const nomGroupe = useId()

const optionActive = computed(() => OPTIONS.find((option) => option.valeur === themeStore.preference) || OPTIONS[2])

const choisir = (valeur) => {
  themeStore.choisir(valeur)
  fermer(true)
}
</script>
