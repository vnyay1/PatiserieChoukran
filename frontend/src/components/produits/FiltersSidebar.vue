<!-- ===================================
COMPOSANT FILTRES SIDEBAR
File: src/components/produits/FiltersSidebar.vue
=================================== -->
<!--
  Groupes sémantiques (fieldset/legend) : le lecteur d'écran annonce « Catégorie, groupe »
  avant chaque option. Cibles de 44 px, compteurs par catégorie, prix avec libellés visibles.
  Rendu deux fois (colonne desktop et tiroir mobile) : noms de groupes uniques via useId.
-->
<template>
  <div class="space-y-7">
    <!-- Catégories -->
    <fieldset>
      <legend class="titre-filtre">Catégorie</legend>
      <div class="space-y-0.5">
        <label class="option-filtre">
          <input
            type="radio"
            :name="nomCategories"
            :checked="selectedCategory === null"
            class="h-[1.125rem] w-[1.125rem] flex-shrink-0"
            @change="$emit('update:selectedCategory', null)"
          />
          <span class="flex-1">Toutes les catégories</span>
        </label>

        <label v-for="category in categories" :key="category.id" class="option-filtre">
          <input
            type="radio"
            :name="nomCategories"
            :value="category.id"
            :checked="selectedCategory === category.id"
            class="h-[1.125rem] w-[1.125rem] flex-shrink-0"
            @change="$emit('update:selectedCategory', category.id)"
          />
          <span class="flex-1">{{ category.nom }}</span>
          <span class="rounded-full bg-gray-100 px-2 text-xs font-semibold tabular-nums text-gray-600">
            {{ category.produits_disponibles_count || 0 }}
            <span class="sr-only">produits</span>
          </span>
        </label>
      </div>
    </fieldset>

    <!-- Prix -->
    <fieldset class="border-t border-gray-200 pt-6">
      <legend class="titre-filtre float-left w-full">Prix (FCFA)</legend>
      <div class="clear-left grid grid-cols-2 gap-3">
        <div>
          <label :for="`${nomPrix}-min`" class="mb-1 block text-xs font-semibold text-gray-600">Minimum</label>
          <input
            :id="`${nomPrix}-min`"
            type="number"
            min="0"
            step="500"
            inputmode="numeric"
            placeholder="0"
            class="input"
            :value="priceRange[0] ?? ''"
            @change="majPrix(0, $event.target.value)"
          />
        </div>
        <div>
          <label :for="`${nomPrix}-max`" class="mb-1 block text-xs font-semibold text-gray-600">Maximum</label>
          <input
            :id="`${nomPrix}-max`"
            type="number"
            min="0"
            step="500"
            inputmode="numeric"
            placeholder="—"
            class="input"
            :value="priceRange[1] ?? ''"
            @change="majPrix(1, $event.target.value)"
          />
        </div>
      </div>
    </fieldset>

    <!-- Options spéciales -->
    <fieldset class="border-t border-gray-200 pt-6">
      <legend class="titre-filtre float-left w-full">Options</legend>
      <div class="clear-left space-y-0.5">
        <label class="option-filtre">
          <input
            type="checkbox"
            :checked="showPromo"
            class="h-[1.125rem] w-[1.125rem] flex-shrink-0 rounded"
            @change="$emit('update:showPromo', !showPromo)"
          />
          <span class="flex-1">En promotion</span>
          <BadgePercent :size="18" class="text-red-600" aria-hidden="true" />
        </label>

        <label class="option-filtre">
          <input
            type="checkbox"
            :checked="showVedette"
            class="h-[1.125rem] w-[1.125rem] flex-shrink-0 rounded"
            @change="$emit('update:showVedette', !showVedette)"
          />
          <span class="flex-1">Vendeurs vedettes</span>
          <Star :size="18" class="text-gold-600" fill="currentColor" aria-hidden="true" />
        </label>
      </div>
    </fieldset>
  </div>
</template>

<script setup>
import { useId } from 'vue'
import { Star, BadgePercent } from 'lucide-vue-next'

const props = defineProps({
  categories: {
    type: Array,
    default: () => []
  },
  selectedCategory: {
    type: Number,
    default: null
  },
  // [min, max] ; null = pas de borne
  priceRange: {
    type: Array,
    default: () => [null, null]
  },
  showPromo: {
    type: Boolean,
    default: false
  },
  showVedette: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:selectedCategory', 'update:priceRange', 'update:showPromo', 'update:showVedette'])

const nomCategories = useId()
const nomPrix = useId()

const majPrix = (index, valeur) => {
  const prix = [...props.priceRange]
  const nombre = Number(valeur)
  prix[index] = valeur === '' || !Number.isFinite(nombre) || nombre < 0 ? null : nombre
  emit('update:priceRange', prix)
}
</script>

<style scoped>
.titre-filtre {
  @apply mb-3 font-body text-sm font-bold uppercase tracking-wider text-gray-900;
}

.option-filtre {
  @apply flex min-h-11 cursor-pointer items-center gap-3 rounded-xl px-2.5 text-[0.9375rem] text-gray-800 transition-colors hover:bg-gray-100;
}

.option-filtre:has(:checked) {
  @apply bg-gold-50 font-semibold text-gray-900;
}
</style>
