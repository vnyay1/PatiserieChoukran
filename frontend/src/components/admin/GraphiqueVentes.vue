<!-- ===================================
GRAPHIQUE DES VENTES PAYÉES (7 derniers jours)
File: src/components/admin/GraphiqueVentes.vue
=================================== -->
<!--
  Règles du skill dataviz :
  - une série, donc une couleur (jeton « graphique », validé contre les surfaces claire et sombre)
    et pas de légende : le titre dit ce qui est tracé ;
  - un seul axe (montants). Le nombre de commandes n'a pas d'axe à lui : il est dans l'infobulle
    et le tableau ;
  - barres de 24 px au plus, bout arrondi de 4 px, posées sur une même ligne de base ;
    graduations arrondies, grille en filet plein discret ; seule la plus haute barre porte sa valeur ;
  - survol : la colonne entière est la cible et l'infobulle ne fait que compléter ;
  - les chiffres restent lisibles sans survol : tableau toujours lu par les lecteurs d'écran,
    affiché à l'écran par « Afficher les chiffres ».
  Apparition : les barres montent en scaleY (transform seulement), sauf avec prefers-reduced-motion.
-->
<template>
  <section aria-labelledby="titre-ventes">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-x-6 gap-y-2">
      <div>
        <h2 id="titre-ventes" class="text-xl">Ventes des 7 derniers jours</h2>
        <p class="mt-0.5 text-sm text-gray-600">Commandes payées, en FCFA</p>
      </div>
      <p class="text-right">
        <span class="block text-2xl font-semibold leading-tight text-gray-900">{{ formatPrice(total) }} FCFA</span>
        <span class="text-sm text-gray-600">{{ totalCommandes }} commande{{ totalCommandes > 1 ? 's' : '' }} payée{{ totalCommandes > 1 ? 's' : '' }}</span>
      </p>
    </div>

    <!-- Graphique : décoratif pour les lecteurs d'écran, qui lisent le tableau -->
    <div class="relative pl-12" aria-hidden="true">
      <!-- Graduations et grille -->
      <div class="absolute inset-x-0 top-0 h-48">
        <div
          v-for="(valeur, index) in echelle.graduations"
          :key="valeur"
          class="absolute inset-x-0 flex translate-y-1/2 items-center"
          :style="{ bottom: `${(index / (echelle.graduations.length - 1 || 1)) * 100}%` }"
        >
          <span class="w-10 pr-2 text-right text-xs tabular-nums text-gray-600">{{ compact(valeur) }}</span>
          <span class="h-px flex-1" :class="index === 0 ? 'bg-gray-300' : 'bg-gray-200'"></span>
        </div>
      </div>

      <!-- Barres -->
      <div class="relative grid h-48 grid-cols-7" :class="{ 'opacity-60': actualisation }">
        <div
          v-for="(jour, index) in jours"
          :key="jour.date"
          class="relative h-full"
          @pointerenter="survol = index"
          @pointerleave="survol = null"
        >
          <div
            v-if="jour.montant > 0"
            class="barre absolute bottom-0 left-1/2 w-[min(1.5rem,60%)] rounded-t bg-graphique"
            :class="{ 'brightness-110': survol === index }"
            :style="{
              height: `${hauteur(jour.montant)}%`,
              transform: `translateX(-50%) scaleY(${apparu ? 1 : 0})`,
              transitionDelay: `${index * 45}ms`,
            }"
          ></div>

          <!-- Valeur de la plus haute barre seulement -->
          <span
            v-if="index === indexMax && jour.montant > 0"
            class="absolute left-1/2 -translate-x-1/2 whitespace-nowrap text-xs font-semibold text-gray-900"
            :style="{ bottom: `calc(${hauteur(jour.montant)}% + 0.25rem)` }"
          >
            {{ compact(jour.montant) }}
          </span>

          <!-- Infobulle : la valeur d'abord, le jour ensuite -->
          <div
            v-if="survol === index"
            class="pointer-events-none absolute z-10 w-max rounded-lg border border-gray-200 bg-surface px-3 py-2 text-left shadow-elegant"
            :class="index === 0 ? 'left-0' : index === jours.length - 1 ? 'right-0' : 'left-1/2 -translate-x-1/2'"
            :style="{ bottom: `calc(${Math.max(hauteur(jour.montant), 0)}% + 1.75rem)` }"
          >
            <span class="block text-sm font-semibold text-gray-900">{{ formatPrice(jour.montant) }} FCFA</span>
            <span class="block text-xs text-gray-600">
              {{ jour.libelleLong }}, {{ jour.nombre }} commande{{ jour.nombre > 1 ? 's' : '' }}
            </span>
          </div>
        </div>

        <p v-if="total === 0" class="absolute inset-x-0 top-1/3 text-center text-sm text-gray-600">
          Aucune vente payée sur ces 7 jours.
        </p>
      </div>

      <!-- Jours -->
      <div class="mt-2 grid grid-cols-7 text-center text-xs leading-tight text-gray-600">
        <span v-for="jour in jours" :key="jour.date">
          <span class="block font-semibold text-gray-700">{{ jour.jour }}</span>
          <span class="block">{{ jour.mois }}</span>
        </span>
      </div>
    </div>

    <div class="mt-4">
      <button
        type="button"
        class="lien inline-flex min-h-11 items-center text-sm"
        :aria-expanded="tableauVisible"
        :aria-controls="idTableau"
        @click="tableauVisible = !tableauVisible"
      >
        {{ tableauVisible ? 'Masquer les chiffres' : 'Afficher les chiffres' }}
      </button>
    </div>

    <!-- Toujours présent pour les lecteurs d'écran ; affiché à l'écran sur demande -->
    <div :id="idTableau" :class="tableauVisible ? 'mt-2 overflow-x-auto' : 'sr-only'">
      <table class="min-w-full text-sm">
        <caption class="sr-only">Ventes payées des 7 derniers jours</caption>
        <thead class="text-left text-gray-600">
          <tr class="border-b border-gray-200">
            <th scope="col" class="py-2 pr-4 font-semibold">Jour</th>
            <th scope="col" class="py-2 pr-4 text-right font-semibold">Montant</th>
            <th scope="col" class="py-2 text-right font-semibold">Commandes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="jour in jours" :key="jour.date">
            <th scope="row" class="py-2 pr-4 text-left font-normal text-gray-800">{{ jour.libelleLong }}</th>
            <td class="py-2 pr-4 text-right tabular-nums text-gray-900">{{ formatPrice(jour.montant) }} FCFA</td>
            <td class="py-2 text-right tabular-nums text-gray-900">{{ jour.nombre }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, useId } from 'vue'
import { formatPrice } from '@/utils/format'

const props = defineProps({
  // [{ date: 'AAAA-MM-JJ', montant, nombre }], du plus ancien au plus récent
  ventes: {
    type: Array,
    default: () => [],
  },
  // Rechargement en cours : le graphique garde son tracé, atténué (pas de squelette)
  actualisation: {
    type: Boolean,
    default: false,
  },
})

const idTableau = useId()
const tableauVisible = ref(false)
const survol = ref(null)
const apparu = ref(false)

const formatJour = new Intl.DateTimeFormat('fr-FR', { day: 'numeric' })
const formatMois = new Intl.DateTimeFormat('fr-FR', { month: 'short' })
const formatLong = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })
const formatCompact = new Intl.NumberFormat('fr-FR', { notation: 'compact', maximumFractionDigits: 1 })
const compact = (valeur) => formatCompact.format(valeur)

const jours = computed(() => props.ventes.map((vente) => {
  const date = new Date(`${vente.date}T00:00:00`)
  return {
    ...vente,
    jour: formatJour.format(date),
    mois: formatMois.format(date),
    libelleLong: formatLong.format(date),
  }
}))

const total = computed(() => jours.value.reduce((somme, jour) => somme + jour.montant, 0))
const totalCommandes = computed(() => jours.value.reduce((somme, jour) => somme + jour.nombre, 0))

// Graduations rondes 0 / pas / 2 × pas, le haut couvrant la plus forte journée
const echelle = computed(() => {
  const max = Math.max(0, ...jours.value.map((jour) => jour.montant))
  if (max <= 0) return { haut: 1, graduations: [0] }
  const moitie = max / 2
  const puissance = 10 ** Math.floor(Math.log10(moitie))
  const pas = [1, 2, 2.5, 5, 10].map((multiple) => multiple * puissance).find((valeur) => valeur >= moitie)
  return { haut: pas * 2, graduations: [0, pas, pas * 2] }
})

const indexMax = computed(() => {
  let index = -1
  jours.value.forEach((jour, i) => {
    if (jour.montant > 0 && (index === -1 || jour.montant > jours.value[index].montant)) index = i
  })
  return index
})

// Hauteur en % du tracé ; une journée non nulle reste visible (2 % au moins)
const hauteur = (montant) => (montant > 0 ? Math.max(2, (montant / echelle.value.haut) * 100) : 0)

// Montée des barres après le premier rendu (transition sur transform seulement)
onMounted(() => {
  requestAnimationFrame(() => requestAnimationFrame(() => {
    apparu.value = true
  }))
})
</script>

<style scoped>
.barre {
  transform-origin: bottom;
  transition: transform 600ms cubic-bezier(0.2, 0, 0, 1), filter 150ms ease;
}

/* Sans animation demandée : barres posées directement */
@media (prefers-reduced-motion: reduce) {
  .barre {
    transition: none;
  }
}
</style>
