<!-- ===================================
2. PAGE MES COMMANDES
File: src/views/MesCommandes.vue
=================================== -->

<template>
  <div class="mes-commandes-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6">
      <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600 mb-6">
        Mes Commandes
      </h1>

      <!-- Filtres -->
      <div class="flex gap-2 mb-6 overflow-x-auto scrollbar-hide">
        <button
          v-for="filtre in filtres"
          :key="filtre.value"
          class="px-4 py-2 rounded-full whitespace-nowrap transition-colors"
          :class="filtreActif === filtre.value ? 'bg-gold-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
          @click="filtreActif = filtre.value; fetchCommandes()"
        >
          {{ filtre.label }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="space-y-4">
        <div v-for="n in 3" :key="n" class="skeleton h-40 rounded-elegant"></div>
      </div>

      <!-- Commandes -->
      <div v-else-if="commandes.length > 0" class="space-y-4">
        <CommandeCard
          v-for="commande in commandes"
          :key="commande.id"
          :commande="commande"
          @click="showCommandeDetail(commande)"
        />
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-16">
        <div class="text-6xl mb-4">📦</div>
        <h2 class="font-display text-xl font-bold text-gray-800 mb-2">
          Aucune commande
        </h2>
        <p class="text-gray-600 mb-6">
          Vous n'avez pas encore passé de commande
        </p>
        <Button variant="primary" @click="$router.push('/produits')">
          Découvrir nos produits
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import Button from '@/components/common/Button.vue'
import CommandeCard from '@/components/commande/CommandeCard.vue'

const router = useRouter()

const commandes = ref([])
const loading = ref(true)
const filtreActif = ref('tous')

const filtres = [
  { value: 'tous', label: 'Toutes' },
  { value: 'en_cours', label: 'En cours' },
  { value: 'livree', label: 'Livrées' },
  { value: 'annulee', label: 'Annulées' },
]

const fetchCommandes = async () => {
  loading.value = true

  try {
    const params = {}
    if (filtreActif.value !== 'tous') {
      params.statut = filtreActif.value
    }

    const response = await api.commandes.getAll(params)
    if (response.data.success) {
      commandes.value = response.data.data.data
    }
  } catch (error) {
    console.error('Erreur chargement commandes:', error)
  } finally {
    loading.value = false
  }
}

const showCommandeDetail = (commande) => {
  router.push(`/mes-commandes/${commande.id}`)
}

onMounted(() => {
  fetchCommandes()
})
</script>