<!-- ===================================
COMPOSANT FILTRES SIDEBAR
File: src/components/produits/FiltersSidebar.vue
=================================== -->

<template>
  <div class="filters-sidebar">
    <!-- Catégories -->
    <div class="mb-6">
      <h3 class="font-display font-semibold text-lg text-gray-800 mb-3">
        Catégories
      </h3>
      <div class="space-y-2">
        <label
          class="flex items-center space-x-3 cursor-pointer hover:bg-gold-50 p-2 rounded-lg transition-colors"
        >
          <input
            type="radio"
            name="filtre-categorie"
            :checked="selectedCategory === null"
            class="w-4 h-4 text-gold-600 focus:ring-gold-500"
            @change="$emit('update:selectedCategory', null)"
          />
          <span class="text-gray-700 font-medium">Toutes les catégories</span>
        </label>

        <label
          v-for="category in categories"
          :key="category.id"
          class="flex items-center space-x-3 cursor-pointer hover:bg-gold-50 p-2 rounded-lg transition-colors"
        >
          <input
            type="radio"
            name="filtre-categorie"
            :value="category.id"
            :checked="selectedCategory === category.id"
            class="w-4 h-4 text-gold-600 focus:ring-gold-500"
            @change="$emit('update:selectedCategory', category.id)"
          />
          <span class="text-gray-700">{{ category.nom }}</span>
          <span class="text-xs text-gray-500 ml-auto">({{ category.produits_disponibles_count || 0 }})</span>
        </label>
      </div>
    </div>

    <div class="divider-ornament"></div>

    <!-- Prix -->
    <div class="mb-6">
      <h3 class="font-display font-semibold text-lg text-gray-800 mb-3">
        Prix (FCFA)
      </h3>
      <div class="grid grid-cols-2 gap-2">
        <input
          type="number"
          min="0"
          step="500"
          inputmode="numeric"
          placeholder="Min"
          aria-label="Prix minimum"
          class="input py-2"
          :value="priceRange[0] ?? ''"
          @change="majPrix(0, $event.target.value)"
        />
        <input
          type="number"
          min="0"
          step="500"
          inputmode="numeric"
          placeholder="Max"
          aria-label="Prix maximum"
          class="input py-2"
          :value="priceRange[1] ?? ''"
          @change="majPrix(1, $event.target.value)"
        />
      </div>
    </div>

    <div class="divider-ornament"></div>

    <!-- Options spéciales -->
    <div class="mb-6">
      <h3 class="font-display font-semibold text-lg text-gray-800 mb-3">
        Options
      </h3>
      <div class="space-y-3">
        <label class="flex items-center space-x-3 cursor-pointer hover:bg-gold-50 p-2 rounded-lg transition-colors">
          <input
            type="checkbox"
            :checked="showPromo"
            class="w-4 h-4 text-gold-600 rounded focus:ring-gold-500"
            @change="$emit('update:showPromo', !showPromo)"
          />
          <span class="text-gray-700">En promotion</span>
        </label>

        <label class="flex items-center space-x-3 cursor-pointer hover:bg-gold-50 p-2 rounded-lg transition-colors">
          <input
            type="checkbox"
            :checked="showVedette"
            class="w-4 h-4 text-gold-600 rounded focus:ring-gold-500"
            @change="$emit('update:showVedette', !showVedette)"
          />
          <span class="text-gray-700">Vendeurs vedettes</span>
          <Star :size="16" class="text-gold-500 ml-auto" fill="currentColor" />
        </label>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Star } from 'lucide-vue-next'

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

const majPrix = (index, valeur) => {
  const prix = [...props.priceRange]
  const nombre = Number(valeur)
  prix[index] = valeur === '' || !Number.isFinite(nombre) || nombre < 0 ? null : nombre
  emit('update:priceRange', prix)
}
</script>
