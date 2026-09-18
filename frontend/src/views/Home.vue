<!-- ===================================
1. PAGE D'ACCUEIL (Mobile-First)
File: src/views/Home.vue
=================================== -->
<!--
  Hiérarchie : promesse + action principale, réassurance (livraison, paiement, retrait),
  catégories (défilement horizontal sur mobile), vedettes, nouveautés.
  Grilles produits sur deux colonnes dès le mobile : deux fois moins de défilement.
-->
<template>
  <div>
    <!-- Hero -->
    <section class="bg-gradient-peach" aria-labelledby="titre-accueil">
      <div class="container mx-auto py-12 text-center md:py-20">
        <p class="badge badge-primary mb-5 px-3 py-1 text-sm">
          <MapPin :size="14" aria-hidden="true" />
          Livré à Yaoundé et Douala
        </p>
        <h1 id="titre-accueil" class="mx-auto max-w-3xl text-[2.25rem] leading-[1.1] sm:text-5xl md:text-6xl">
          L'art de sublimer vos <span class="text-gold-700 dark:text-gold-600">moments gourmands</span>
        </h1>
        <p class="mx-auto mt-5 max-w-xl text-lg text-gray-700">
          Gâteaux, pâtisseries et glaces de nos artisans, livrés chez vous ou à retirer en boutique.
        </p>

        <div class="mt-8 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
          <Button to="/produits" size="lg" :icon="ShoppingBag">
            Découvrir nos créations
          </Button>
          <Button v-if="!authStore.isAuthenticated" to="/inscription" variant="secondary" size="lg">
            Créer un compte
          </Button>
        </div>

        <ul class="mx-auto mt-10 grid max-w-3xl grid-cols-1 gap-3 text-left sm:grid-cols-3">
          <li v-for="atout in ATOUTS" :key="atout.titre" class="flex items-center gap-3 rounded-2xl bg-surface/70 px-4 py-3 backdrop-blur">
            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gold-100 text-gold-700">
              <component :is="atout.icone" :size="20" aria-hidden="true" />
            </span>
            <span class="text-sm leading-snug">
              <span class="block font-semibold text-gray-900">{{ atout.titre }}</span>
              <span class="text-gray-600">{{ atout.texte }}</span>
            </span>
          </li>
        </ul>
      </div>
    </section>

    <AlertMessage v-if="erreur" type="error" class="container mx-auto mt-8">
      Impossible de charger la boutique pour le moment.
      <button type="button" class="lien ml-1" @click="chargerAccueil">Réessayer</button>
    </AlertMessage>

    <!-- Catégories -->
    <section class="container mx-auto pt-12 md:pt-16" aria-labelledby="titre-categories">
      <div class="mb-5 flex items-end justify-between gap-4">
        <div>
          <h2 id="titre-categories">Nos catégories</h2>
          <p class="mt-1 text-gray-600">Des créations pour tous les goûts</p>
        </div>
        <router-link to="/produits" class="lien hidden flex-shrink-0 text-sm sm:inline">Tout voir</router-link>
      </div>

      <ul
        class="-mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-2 scrollbar-hide
               sm:mx-0 sm:grid sm:grid-cols-3 sm:gap-4 sm:overflow-visible sm:px-0 lg:grid-cols-5"
        :aria-busy="chargement"
      >
        <template v-if="chargement">
          <li v-for="n in 5" :key="n" class="w-36 flex-shrink-0 sm:w-auto">
            <div class="skeleton aspect-[4/3] rounded-2xl"></div>
            <div class="skeleton mt-3 h-4 w-2/3"></div>
          </li>
        </template>
        <li v-for="categorie in categories" v-else :key="categorie.id" class="w-36 flex-shrink-0 snap-start sm:w-auto">
          <router-link
            :to="`/produits?categorie=${categorie.id}`"
            class="group block rounded-2xl"
          >
            <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-gold-50">
              <img
                v-if="categorie.image"
                :src="resolveImageUrl(categorie.image)"
                alt=""
                class="h-full w-full object-cover transition-transform duration-500 ease-douce motion-safe:group-hover:scale-105"
                loading="lazy"
                @error="onImageError"
              />
              <div v-else class="flex h-full items-center justify-center text-gold-600">
                <CakeSlice :size="40" :stroke-width="1.5" aria-hidden="true" />
              </div>
            </div>
            <p class="mt-2.5 font-semibold text-gray-900 group-hover:text-gold-700">{{ categorie.nom }}</p>
            <p v-if="categorie.produits_disponibles_count" class="text-sm text-gray-600">
              {{ categorie.produits_disponibles_count }} produit{{ categorie.produits_disponibles_count > 1 ? 's' : '' }}
            </p>
          </router-link>
        </li>
      </ul>
    </section>

    <!-- Produits vedettes et nouveautés -->
    <section
      v-for="bloc in blocsProduits"
      :key="bloc.id"
      class="container mx-auto pt-12 md:pt-16"
      :aria-labelledby="`titre-${bloc.id}`"
    >
      <div class="mb-5 flex items-end justify-between gap-4">
        <div>
          <h2 :id="`titre-${bloc.id}`">{{ bloc.titre }}</h2>
          <p class="mt-1 text-gray-600">{{ bloc.sousTitre }}</p>
        </div>
        <router-link :to="bloc.lien" class="lien hidden flex-shrink-0 text-sm sm:inline">Tout voir</router-link>
      </div>

      <ul class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4" :aria-busy="chargement">
        <template v-if="chargement">
          <li v-for="n in 4" :key="n">
            <div class="skeleton aspect-square rounded-elegant"></div>
            <div class="skeleton mt-3 h-4 w-3/4"></div>
            <div class="skeleton mt-2 h-4 w-1/2"></div>
          </li>
        </template>
        <li v-for="produit in bloc.produits" v-else :key="produit.id">
          <ProduitCard :produit="produit" />
        </li>
      </ul>

      <EmptyState
        v-if="!chargement && !erreur && bloc.produits.length === 0"
        :icone="PackageOpen"
        niveau="h3"
        titre="Aucun produit pour le moment"
        :texte="villeStore.ville ? `Aucun vendeur ne propose encore ces produits à ${villeStore.libelle}.` : 'Revenez bientôt : nos vendeurs préparent leurs créations.'"
      />

      <div class="mt-6 text-center sm:hidden">
        <Button :to="bloc.lien" variant="outline" full-width>Voir tous les produits</Button>
      </div>
    </section>

    <!-- Pourquoi Choukrane -->
    <section class="container mx-auto pt-16" aria-labelledby="titre-pourquoi">
      <div class="rounded-[1.75rem] border border-gold-200 bg-gold-50 px-5 py-10 sm:px-10">
        <h2 id="titre-pourquoi" class="text-center">Pourquoi Choukrane ?</h2>
        <ul class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-3">
          <li v-for="raison in RAISONS" :key="raison.titre" class="text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-surface text-gold-700 shadow-card">
              <component :is="raison.icone" :size="26" :stroke-width="1.75" aria-hidden="true" />
            </span>
            <h3 class="text-lg">{{ raison.titre }}</h3>
            <p class="mt-1 text-gray-600">{{ raison.texte }}</p>
          </li>
        </ul>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useVilleStore } from '@/stores/ville'
import api from '@/services/api'
import Button from '@/components/common/Button.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ProduitCard from '@/components/produits/ProduitCard.vue'
import { resolveImageUrl, onImageError } from '@/utils/images'
import {
  MapPin, ShoppingBag, Truck, ShieldCheck, Store, CakeSlice, PackageOpen, ChefHat, Clock3, Award,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const villeStore = useVilleStore()

const ATOUTS = [
  { titre: 'Livraison à domicile', texte: 'Frais fixes par vendeur', icone: Truck },
  { titre: 'Paiement mobile', texte: 'Orange Money, MTN MoMo', icone: ShieldCheck },
  { titre: 'Retrait en boutique', texte: 'Sans frais de livraison', icone: Store },
]

const RAISONS = [
  { titre: 'Fait maison', texte: 'Des créations artisanales préparées avec soin par nos vendeurs.', icone: ChefHat },
  { titre: 'Livraison rapide', texte: 'Livré chez vous à Yaoundé et Douala, ou prêt en boutique.', icone: Clock3 },
  { titre: 'Qualité premium', texte: 'Des ingrédients sélectionnés pour chaque gâteau et chaque glace.', icone: Award },
]

const categories = ref([])
const produitsFeatured = ref([])
const produitsNouveautes = ref([])
const chargement = ref(true)
const erreur = ref(false)

const blocsProduits = computed(() => [
  {
    id: 'vedettes',
    titre: 'Nos produits vedettes',
    sousTitre: 'Les créations de nos vendeurs à la une',
    lien: '/produits?vedette=1',
    produits: produitsFeatured.value,
  },
  // Nouveautés : section masquée quand il n'y en a pas
  ...(chargement.value || produitsNouveautes.value.length > 0
    ? [{
        id: 'nouveautes',
        titre: 'Nouveautés',
        sousTitre: 'Les dernières créations de nos vendeurs',
        lien: '/produits',
        produits: produitsNouveautes.value,
      }]
    : []),
])

// Une seule requête pour toute la page (catégories, vedettes, nouveautés)
const chargerAccueil = async () => {
  chargement.value = true
  erreur.value = false
  try {
    const response = await api.accueil()
    if (response.data.success) {
      categories.value = response.data.data.categories
      produitsFeatured.value = response.data.data.vedettes
      produitsNouveautes.value = response.data.data.nouveautes
    }
  } catch (error) {
    erreur.value = true
    console.error('Erreur chargement page d\'accueil:', error)
  } finally {
    chargement.value = false
  }
}

onMounted(chargerAccueil)

// Nouvelle ville : seuls les produits livrables y sont proposés
watch(() => villeStore.ville, chargerAccueil)
</script>
