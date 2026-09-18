<!-- ===================================
PAGE PUBLIQUE D'UN VENDEUR
File: src/views/VendeurProfil.vue
=================================== -->

<template>
  <div class="vendeur-profil-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
      <div v-if="loading" class="space-y-4">
        <div class="skeleton h-48 rounded-elegant"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="n" class="skeleton h-64 rounded-elegant"></div>
        </div>
      </div>

      <div v-else-if="!vendeur" class="text-center py-16">
        <div class="text-6xl mb-4">🏪</div>
        <h1 class="font-display text-2xl font-bold text-gray-800 mb-2">Boutique introuvable</h1>
        <p class="text-gray-600 mb-6">Ce vendeur n'existe pas ou ne vend plus sur Choukrane pour le moment.</p>
        <Button variant="primary" @click="router.push('/produits')">Voir tous les produits</Button>
      </div>

      <template v-else>
        <!-- En-tête de la boutique -->
        <Card padding="lg" class="mb-8">
          <div class="flex flex-col sm:flex-row gap-6 items-center sm:items-start">
            <div class="h-28 w-28 flex-shrink-0 rounded-elegant border bg-surface overflow-hidden flex items-center justify-center">
              <img
                v-if="vendeur.logo_boutique"
                :src="resolveImageUrl(vendeur.logo_boutique, { placeholder: false })"
                :alt="`Logo de ${vendeur.nom_complet}`"
                class="h-full w-full object-contain"
              />
              <Store v-else :size="40" class="text-gray-300" />
            </div>

            <div class="flex-1 min-w-0 text-center sm:text-left">
              <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-2">
                <h1 class="font-display text-2xl md:text-3xl font-bold text-gray-800">
                  {{ vendeur.nom_complet }}
                </h1>
                <span
                  v-if="vendeur.est_vendeur_vedette"
                  class="bg-gold-500 text-on-gold px-2 py-1 rounded-full text-xs font-bold inline-flex items-center"
                >
                  <Star :size="12" class="mr-1" fill="white" />
                  Vedette
                </span>
              </div>

              <p class="text-gray-700 whitespace-pre-line mb-4">{{ vendeur.description_boutique }}</p>

              <div class="flex flex-wrap justify-center sm:justify-start gap-x-6 gap-y-2 text-sm text-gray-600">
                <a :href="`mailto:${vendeur.email}`" class="inline-flex items-center gap-2 text-gold-700 hover:text-gold-800">
                  <Mail :size="16" />
                  {{ vendeur.email }}
                </a>
                <span class="inline-flex items-center gap-2">
                  <MapPin :size="16" />
                  {{ vendeur.villes_livraison?.length ? `Livre à ${vendeur.villes_livraison.map(formatVille).join(' et ')}` : 'Retrait en boutique uniquement' }}
                </span>
                <span class="inline-flex items-center gap-2">
                  <Truck :size="16" />
                  {{ vendeur.montant_minimum_livraison > 0
                    ? `Livraison dès ${formatPrice(vendeur.montant_minimum_livraison)} FCFA d'achat`
                    : 'Livraison sans minimum d\'achat' }}
                </span>
                <span class="inline-flex items-center gap-2">
                  <Calendar :size="16" />
                  Vendeur depuis {{ formatDate(vendeur.membre_depuis, { month: 'long', year: 'numeric' }) }}
                </span>
              </div>
            </div>
          </div>
        </Card>

        <!-- Produits de la boutique -->
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-display text-xl font-bold text-gray-800">
            Ses produits
            <span class="text-gray-500 font-normal text-base">({{ totalProduits }})</span>
          </h2>
        </div>

        <div v-if="loadingProduits && produits.length === 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="n" class="skeleton h-64 rounded-elegant"></div>
        </div>

        <p v-else-if="produits.length === 0" class="text-gray-600 text-center py-10">
          Ce vendeur n'a pas encore de produit disponible.
        </p>

        <template v-else>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <ProduitCard v-for="produit in produits" :key="produit.id" :produit="produit" />
          </div>
          <div v-if="page < dernierePage" class="text-center mt-8">
            <Button variant="outline" :loading="loadingProduits" @click="chargerProduits(page + 1)">
              Voir plus de produits
            </Button>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import ProduitCard from '@/components/produits/ProduitCard.vue'
import { resolveImageUrl } from '@/utils/images'
import { formatPrice, formatDate } from '@/utils/format'
import { Store, Star, Mail, Truck, Calendar, MapPin } from 'lucide-vue-next'
import { formatVille } from '@/utils/villes'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const vendeur = ref(null)
const produits = ref([])
const loadingProduits = ref(false)
const page = ref(1)
const dernierePage = ref(1)
const totalProduits = ref(0)

const chargerProduits = async (numeroPage = 1) => {
  loadingProduits.value = true
  try {
    // ville: undefined -> la ville choisie ne filtre pas la vitrine du vendeur (ses villes sont affichées)
    const response = await api.produits.getAll({ vendeur_id: route.params.id, ville: undefined, page: numeroPage, per_page: 12 })
    const pagination = response.data.data
    produits.value = numeroPage === 1 ? pagination.data : [...produits.value, ...pagination.data]
    page.value = pagination.current_page
    dernierePage.value = pagination.last_page
    totalProduits.value = pagination.total
  } catch (error) {
    console.error('Erreur chargement produits du vendeur:', error)
  } finally {
    loadingProduits.value = false
  }
}

const charger = async () => {
  loading.value = true
  vendeur.value = null
  produits.value = []

  try {
    const response = await api.vendeurs.getOne(route.params.id)
    vendeur.value = response.data.data
    document.title = `${vendeur.value.nom_complet} - Pâtisserie`
    await chargerProduits(1)
  } catch {
    vendeur.value = null
  } finally {
    loading.value = false
  }
}

watch(() => route.params.id, (id) => {
  if (id) charger()
}, { immediate: true })
</script>
