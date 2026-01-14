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
          <Button variant="primary" size="lg" @click="$router.push('/produits')">
            Découvrir nos créations
          </Button>
          <Button
            v-if="!authStore.isAuthenticated"
            variant="secondary"
            size="lg"
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

        <div v-if="loadingCategories" class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="n" class="skeleton h-32 rounded-elegant"></div>
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            v-for="categorie in categories"
            :key="categorie.id"
            :to="`/produits?categorie=${categorie.id}`"
            class="card p-6 text-center hover:shadow-elegant transform hover:-translate-y-1 transition-all"
          >
            <div class="text-4xl mb-3">{{ getCategorieEmoji(categorie.nom) }}</div>
            <h3 class="font-display font-semibold text-lg text-gray-800">{{ categorie.nom }}</h3>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Produits Vedettes -->
    <section class="py-12 px-4 bg-white">
      <div class="container mx-auto">
        <div class="text-center mb-8">
          <h2 class="font-display text-3xl font-bold text-gold-600 mb-2">Nos Produits Vedettes</h2>
          <p class="text-gray-600">Découvrez nos meilleures créations</p>
        </div>

        <div v-if="loadingProduits" class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="n" class="skeleton h-64 rounded-elegant"></div>
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
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

const authStore = useAuthStore()

const categories = ref([])
const produitsFeatured = ref([])
const loadingCategories = ref(true)
const loadingProduits = ref(true)

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

onMounted(async () => {
  try {
    // Charger les catégories
    const catResponse = await api.categories.getAll()
    if (catResponse.data.success) {
      categories.value = catResponse.data.data
    }
  } catch (error) {
    console.error('Erreur chargement catégories:', error)
  } finally {
    loadingCategories.value = false
  }

  try {
    // Charger les produits vedettes
    const prodResponse = await api.produits.getFeatured()
    if (prodResponse.data.success) {
      produitsFeatured.value = prodResponse.data.data
    }
  } catch (error) {
    console.error('Erreur chargement produits:', error)
  } finally {
    loadingProduits.value = false
  }
})
</script>