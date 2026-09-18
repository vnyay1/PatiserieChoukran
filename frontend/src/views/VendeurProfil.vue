<!-- ===================================
PAGE PUBLIQUE D'UN VENDEUR
File: src/views/VendeurProfil.vue
=================================== -->

<template>
  <div class="container mx-auto max-w-6xl pb-6 pt-6 md:pt-8">
    <div v-if="loading" class="space-y-6" aria-busy="true">
      <div class="skeleton h-48 rounded-elegant"></div>
      <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
        <div v-for="n in 4" :key="n" class="skeleton aspect-square rounded-elegant"></div>
      </div>
      <span class="sr-only">Chargement de la boutique…</span>
    </div>

    <EmptyState
      v-else-if="!vendeur"
      :icone="Store"
      niveau="h1"
      titre="Boutique introuvable"
      texte="Ce vendeur n'existe pas ou ne vend plus sur Choukrane pour le moment."
    >
      <Button to="/produits" variant="primary">Voir tous les produits</Button>
    </EmptyState>

    <template v-else>
      <!-- En-tête de la boutique -->
      <section class="card mb-10 overflow-hidden" aria-labelledby="nom-boutique">
        <div class="h-20 bg-gradient-peach sm:h-24" aria-hidden="true"></div>
        <div class="px-5 pb-6 sm:px-8">
          <div class="-mt-12 flex flex-col items-center gap-5 sm:flex-row sm:items-end">
            <div class="flex h-24 w-24 flex-shrink-0 items-center justify-center overflow-hidden rounded-2xl border-4 border-surface bg-white shadow-card sm:h-28 sm:w-28">
              <img
                v-if="vendeur.logo_boutique"
                :src="resolveImageUrl(vendeur.logo_boutique, { placeholder: false })"
                :alt="`Logo de ${vendeur.nom_complet}`"
                class="h-full w-full object-contain"
              />
              <Store v-else :size="40" class="text-gray-400" aria-hidden="true" />
            </div>
            <div class="min-w-0 text-center sm:pb-1 sm:text-left">
              <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                <h1 id="nom-boutique" class="text-2xl md:text-3xl">{{ vendeur.nom_complet }}</h1>
                <span v-if="vendeur.est_vendeur_vedette" class="badge bg-gold-500 text-on-gold">
                  <Star :size="12" fill="currentColor" aria-hidden="true" />
                  Vendeur vedette
                </span>
              </div>
              <p class="mt-1 text-sm text-gray-600">
                Vendeur depuis {{ formatDate(vendeur.membre_depuis, { month: 'long', year: 'numeric' }) }}
              </p>
            </div>
          </div>

          <p class="mx-auto mt-5 max-w-3xl whitespace-pre-line text-center text-gray-700 sm:mx-0 sm:text-left">{{ vendeur.description_boutique }}</p>

          <ul class="mt-5 flex flex-wrap justify-center gap-2 sm:justify-start">
            <li class="badge badge-neutral px-3 py-1.5 text-sm font-medium">
              <MapPin :size="15" aria-hidden="true" />
              {{ vendeur.villes_livraison?.length ? `Livre à ${vendeur.villes_livraison.map(formatVille).join(' et ')}` : 'Retrait en boutique uniquement' }}
            </li>
            <li class="badge badge-neutral px-3 py-1.5 text-sm font-medium">
              <Truck :size="15" aria-hidden="true" />
              {{ vendeur.montant_minimum_livraison > 0
                ? `Livraison dès ${formatPrice(vendeur.montant_minimum_livraison)} FCFA d'achat`
                : 'Livraison sans minimum d\'achat' }}
            </li>
            <li v-if="vendeur.email">
              <a :href="`mailto:${vendeur.email}`" class="badge badge-primary px-3 py-1.5 text-sm font-medium hover:bg-gold-200">
                <Mail :size="15" aria-hidden="true" />
                {{ vendeur.email }}
              </a>
            </li>
          </ul>
        </div>
      </section>

      <!-- Produits de la boutique -->
      <section aria-labelledby="titre-produits-vendeur">
        <h2 id="titre-produits-vendeur" class="mb-5">
          Ses créations
          <span class="font-body text-base font-normal text-gray-600">({{ totalProduits }})</span>
        </h2>

        <ul v-if="loadingProduits && produits.length === 0" class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4" aria-hidden="true">
          <li v-for="n in 4" :key="n" class="skeleton aspect-square rounded-elegant"></li>
        </ul>

        <EmptyState
          v-else-if="produits.length === 0"
          :icone="PackageOpen"
          niveau="h3"
          titre="Aucun produit pour le moment"
          texte="Ce vendeur n'a pas encore de produit disponible."
        />

        <template v-else>
          <ul class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
            <li v-for="produit in produits" :key="produit.id">
              <ProduitCard :produit="produit" />
            </li>
          </ul>
          <div v-if="page < dernierePage" class="mt-8 text-center">
            <Button variant="outline" :loading="loadingProduits" @click="chargerProduits(page + 1)">
              Voir plus de produits
            </Button>
          </div>
        </template>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import Button from '@/components/common/Button.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ProduitCard from '@/components/produits/ProduitCard.vue'
import { resolveImageUrl } from '@/utils/images'
import { formatPrice, formatDate } from '@/utils/format'
import { Store, Star, Mail, Truck, MapPin, PackageOpen } from 'lucide-vue-next'
import { formatVille } from '@/utils/villes'

const route = useRoute()

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
