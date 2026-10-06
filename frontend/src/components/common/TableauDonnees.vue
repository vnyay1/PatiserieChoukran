<!-- ===================================
COMPOSANT TABLEAU DE DONNÉES (espace de gestion)
File: src/components/common/TableauDonnees.vue
=================================== -->
<!--
  Une seule coquille pour les listes de gestion :
  - barre d'outils (compteur, actions) ;
  - états distincts : squelette au premier chargement, erreur avec « Réessayer », liste vide ;
  - rechargement (page, filtre) : l'ancienne liste reste, atténuée (aria-busy), et sa pagination
    aussi, pour que le bouton activé garde le focus ;
  - tableau dès md (région défilable au clavier), cartes empilées dessous (slot « cartes ») ;
  - pied (pagination) affiché dès qu'il y a des lignes, même pendant un rechargement.
  Slots : barre, entete (<th>…), lignes (<tr>…), cartes (<li>…), vide, pied.
-->
<template>
  <div class="card overflow-hidden">
    <div v-if="$slots.barre" class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
      <slot name="barre" />
    </div>

    <div v-if="chargement && vide" class="space-y-3 p-4" aria-busy="true">
      <div v-for="n in lignesSquelette" :key="n" class="skeleton h-14 rounded-xl"></div>
      <span class="sr-only">Chargement…</span>
    </div>

    <div v-else-if="erreur" class="p-4">
      <AlertMessage type="error">
        {{ erreur }}
        <button type="button" class="lien ml-1" @click="$emit('reessayer')">Réessayer</button>
      </AlertMessage>
    </div>

    <div v-else-if="vide" class="px-4 py-2">
      <slot name="vide">
        <EmptyState :icone="icone" niveau="h2" :titre="titreVide" :texte="texteVide" />
      </slot>
    </div>

    <div v-else class="transition-opacity duration-base" :class="{ 'opacity-60': chargement }" :aria-busy="chargement ? 'true' : undefined">
      <ul v-if="$slots.cartes" class="divide-y divide-gray-200 md:hidden" :aria-label="libelle">
        <slot name="cartes" />
      </ul>
      <div
        class="overflow-x-auto"
        :class="{ 'hidden md:block': $slots.cartes }"
        role="region"
        :aria-label="libelle"
        tabindex="0"
      >
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-left text-gray-600">
            <tr><slot name="entete" /></tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <slot name="lignes" />
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="$slots.pied && !erreur && !vide" class="border-t border-gray-200 px-4 py-4">
      <slot name="pied" />
    </div>
  </div>
</template>

<script setup>
import AlertMessage from '@/components/common/AlertMessage.vue'
import EmptyState from '@/components/common/EmptyState.vue'

defineProps({
  // Nom de la liste pour les lecteurs d'écran (région défilable, liste de cartes)
  libelle: {
    type: String,
    required: true,
  },
  chargement: {
    type: Boolean,
    default: false,
  },
  erreur: {
    type: String,
    default: '',
  },
  vide: {
    type: Boolean,
    default: false,
  },
  titreVide: {
    type: String,
    default: 'Aucun résultat',
  },
  texteVide: {
    type: String,
    default: '',
  },
  icone: {
    type: [Object, Function],
    default: null,
  },
  lignesSquelette: {
    type: Number,
    default: 5,
  },
})

defineEmits(['reessayer'])
</script>
