<!-- ===================================
1. PAGE D'ACCUEIL (Mobile-First)
File: src/views/Home.vue
=================================== -->

<template>
  <div class="home">
    <!-- Hero Section -->
    <section class="relative bg-gradient-peach py-12 px-4 md:py-20">
      <div class="container mx-auto text-center">
        <div class="mb-6 ornament text-4xl">✦</div>
        <h1 class="font-display text-4xl md:text-5xl font-bold text-gold-700 mb-4">
          Choukrane Pâtisserie
        </h1>
        <p class="text-lg md:text-xl text-gray-700 mb-8 max-w-2xl mx-auto">
          L'art de sublimer vos moments gourmands
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Button variant="primary" size="lg" full-width @click="$router.push('/produits')">
            Découvrir nos créations
          </Button>
          <Button
            v-if="!authStore.isAuthenticated"
            variant="secondary"
            size="lg"
            full-width
            @click="$router.push('/inscription')"
          >
            Créer un compte
          </Button>
        </div>
      </div>
    </section>

    <!-- Catégories -->
    <section class="py-12 px-4">
      <div class="container mx-auto">
        <div class="text-center mb-8">
          <h2 class="font-display text-3xl font-bold text-gold-600 mb-2">Nos Catégories</h2>
          <p class="text-gray-600">Des créations pour tous les goûts</p>
        </div>

        <div v-if="loadingCategories" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="n" class="skeleton h-32 rounded-elegant"></div>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            v-for="categorie in categories"
            :key="categorie.id"
            :to="`/produits?categorie=${categorie.id}`"
            class="card text-center hover:shadow-elegant transform hover:-translate-y-1 transition-all"
          >
            <!-- Image de la catégorie (téléversée par l'admin), sinon un emoji -->
            <div v-if="categorie.image" class="aspect-[4/3] overflow-hidden bg-gold-50">
              <img
                :src="resolveImageUrl(categorie.image)"
                :alt="categorie.nom"
                class="w-full h-full object-cover"
                loading="lazy"
                @error="onImageError"
              />
            </div>
            <div v-else class="text-4xl pt-6">{{ getCategorieEmoji(categorie.nom) }}</div>
            <div class="p-4">
              <h3 class="font-display font-semibold text-lg text-gray-800">{{ categorie.nom }}</h3>
              <p v-if="categorie.produits_disponibles_count" class="text-xs text-gray-500 mt-1">
                {{ categorie.produits_disponibles_count }} produit{{ categorie.produits_disponibles_count > 1 ? 's' : '' }}
              </p>
            </div>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Produits Vedettes -->
    <section class="py-12 px-4 bg-white">
      <div class="container mx-auto">
        <div class="text-center mb-8">
          <h2 class="font-display text-3xl font-bold text-gold-600 mb-2">Nos Produits Vedettes</h2>
          <p class="text-gray-600">Les créations de nos vendeurs à la une</p>
        </div>

        <div v-if="loadingProduits" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="n" class="skeleton h-64 rounded-elegant"></div>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <ProduitCard
            v-for="produit in produitsFeatured"
            :key="produit.id"
            :produit="produit"
          />
        </div>

        <div class="text-center mt-8">
          <Button variant="outline" @click="$router.push('/produits')">
            Voir tous les produits
          </Button>
        </div>
      </div>
    </section>

    <!-- Nouveautés -->
    <section v-if="loadingNouveautes || produitsNouveautes.length > 0" class="py-12 px-4">
      <div class="container mx-auto">
        <div class="text-center mb-8">
          <h2 class="font-display text-3xl font-bold text-gold-600 mb-2">Nouveautés</h2>
          <p class="text-gray-600">Les dernières créations de nos vendeurs</p>
        </div>

        <div v-if="loadingNouveautes" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="n" class="skeleton h-64 rounded-elegant"></div>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <ProduitCard
            v-for="produit in produitsNouveautes"
            :key="produit.id"
            :produit="produit"
          />
        </div>
      </div>
    </section>

    <!-- Pourquoi Choukrane -->
    <section class="py-12 px-4">
      <div class="container mx-auto">
        <div class="text-center mb-8">
          <h2 class="font-display text-3xl font-bold text-gold-600 mb-2">Pourquoi Choukrane ?</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="text-center p-6">
            <div class="text-5xl mb-4">🎂</div>
            <h3 class="font-display font-semibold text-xl mb-2 text-gray-800">Fait Maison</h3>
            <p class="text-gray-600">Produits artisanaux préparés avec amour</p>
          </div>

          <div class="text-center p-6">
            <div class="text-5xl mb-4">🚚</div>
            <h3 class="font-display font-semibold text-xl mb-2 text-gray-800">Livraison Rapide</h3>
            <p class="text-gray-600">Livré chez vous dans les meilleurs délais</p>
          </div>

          <div class="text-center p-6">
            <div class="text-5xl mb-4">⭐</div>
            <h3 class="font-display font-semibold text-xl mb-2 text-gray-800">Qualité Premium</h3>
            <p class="text-gray-600">Ingrédients sélectionnés avec soin</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import Button from '@/components/common/Button.vue'
import ProduitCard from '@/components/produits/ProduitCard.vue'
import { resolveImageUrl, onImageError } from '@/utils/images'

const authStore = useAuthStore()

const categories = ref([])
const produitsFeatured = ref([])
const produitsNouveautes = ref([])
const loadingCategories = ref(true)
const loadingProduits = ref(true)
const loadingNouveautes = ref(true)

const getCategorieEmoji = (nom) => {
  const emojis = {
    'Gâteaux': '🎂',
    'Glaces': '🍦',
    'Viennoiseries': '🥐',
    'Pâtisseries': '🧁',
    'Anniversaires': '🎉'
  }
  return emojis[nom] || '🍰'
}

const charger = async (requete, cible, chargement) => {
  try {
    const response = await requete
    if (response.data.success) {
      cible.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur chargement page d\'accueil:', error)
  } finally {
    chargement.value = false
  }
}

onMounted(() => {
  // Les trois requêtes partent en même temps : la page s'affiche plus vite
  charger(api.categories.getAll(), categories, loadingCategories)
  charger(api.produits.getFeatured(), produitsFeatured, loadingProduits)
  charger(api.produits.getNouveautes(), produitsNouveautes, loadingNouveautes)
})
</script>
