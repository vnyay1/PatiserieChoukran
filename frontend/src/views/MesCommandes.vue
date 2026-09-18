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
      <div class="flex gap-2 mb-6 overflow-x-auto scrollbar-hide" role="tablist">
        <button
          v-for="filtre in filtres"
          :key="filtre.value"
          class="px-4 py-2 rounded-full whitespace-nowrap transition-colors"
          :class="filtreActif === filtre.value ? 'bg-gold-500 text-on-gold' : 'bg-surface text-gray-700 hover:bg-gray-50'"
          role="tab"
          :aria-selected="filtreActif === filtre.value"
          @click="changerFiltre(filtre.value)"
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
        <p class="text-sm text-gray-600">
          {{ total }} commande{{ total > 1 ? 's' : '' }}
        </p>
        <CommandeCard
          v-for="commande in commandes"
          :key="commande.id"
          :commande="commande"
          @click="showCommandeDetail(commande)"
        />

        <div v-if="page < lastPage" class="text-center pt-2">
          <Button variant="outline" :loading="loadingMore" @click="chargerPlus">
            Voir plus de commandes
          </Button>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-16">
        <div class="text-6xl mb-4">📦</div>
        <h2 class="font-display text-xl font-bold text-gray-800 mb-2">
          {{ filtreActif === 'tous' ? 'Aucune commande' : 'Aucune commande dans cette catégorie' }}
        </h2>
        <p class="text-gray-600 mb-6">
          {{ filtreActif === 'tous' ? 'Vous n\'avez pas encore passé de commande' : 'Essayez un autre filtre.' }}
        </p>
        <Button v-if="filtreActif === 'tous'" variant="primary" @click="$router.push('/produits')">
          Découvrir nos produits
        </Button>
        <Button v-else variant="outline" @click="changerFiltre('tous')">
          Voir toutes mes commandes
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import Button from '@/components/common/Button.vue'
import CommandeCard from '@/components/commande/CommandeCard.vue'

const router = useRouter()
const toastStore = useToastStore()

const commandes = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const filtreActif = ref('tous')
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

const filtres = [
  { value: 'tous', label: 'Toutes' },
  { value: 'en_cours', label: 'En cours' },
  { value: 'livree', label: 'Livrées' },
  { value: 'annulee', label: 'Annulées' },
]

const fetchCommandes = async ({ ajouter = false } = {}) => {
  if (ajouter) {
    loadingMore.value = true
  } else {
    loading.value = true
    page.value = 1
  }

  try {
    const params = { page: page.value }
    if (filtreActif.value !== 'tous') {
      params.statut = filtreActif.value
    }

    const response = await api.commandes.getAll(params)
    if (response.data.success) {
      const pagination = response.data.data
      commandes.value = ajouter ? [...commandes.value, ...pagination.data] : pagination.data
      lastPage.value = pagination.last_page || 1
      total.value = pagination.total || 0
    }
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Impossible de charger vos commandes.'))
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const changerFiltre = (valeur) => {
  if (filtreActif.value === valeur) return
  filtreActif.value = valeur
  fetchCommandes()
}

const chargerPlus = () => {
  page.value += 1
  fetchCommandes({ ajouter: true })
}

const showCommandeDetail = (commande) => {
  router.push(`/mes-commandes/${commande.id}`)
}

onMounted(() => {
  fetchCommandes()
})
</script>
