<!-- ===================================
2. PAGE MES COMMANDES
File: src/views/MesCommandes.vue
=================================== -->
<!--
  Filtres en boutons bascule (aria-pressed) : ce ne sont pas des onglets (pas de panneaux).
  Le nombre de commandes est annoncé quand le filtre change.
-->
<template>
  <div class="container mx-auto pb-6 pt-6 md:pt-8">
    <h1>Mes commandes</h1>

    <div class="-mx-4 mb-6 mt-5 flex gap-2 overflow-x-auto px-4 pb-1 scrollbar-hide" role="group" aria-label="Filtrer les commandes">
      <button
        v-for="filtre in filtres"
        :key="filtre.value"
        type="button"
        class="puce flex-shrink-0"
        :aria-pressed="filtreActif === filtre.value"
        @click="changerFiltre(filtre.value)"
      >
        {{ filtre.label }}
      </button>
    </div>

    <p class="sr-only" aria-live="polite">
      <template v-if="!loading">{{ total }} commande{{ total > 1 ? 's' : '' }}</template>
    </p>

    <!-- Chargement -->
    <div v-if="loading" class="space-y-4" aria-hidden="true">
      <div v-for="n in 3" :key="n" class="skeleton h-44 rounded-elegant"></div>
    </div>

    <!-- Commandes -->
    <div v-else-if="commandes.length > 0">
      <p class="mb-3 text-sm text-gray-600" aria-hidden="true">
        {{ total }} commande{{ total > 1 ? 's' : '' }}
      </p>
      <ul class="space-y-4">
        <li v-for="commande in commandes" :key="commande.id">
          <CommandeCard :commande="commande" />
        </li>
      </ul>

      <div v-if="page < lastPage" class="pt-6 text-center">
        <Button variant="outline" :loading="loadingMore" @click="chargerPlus">
          Voir plus de commandes
        </Button>
      </div>
    </div>

    <!-- Aucune commande -->
    <EmptyState
      v-else
      :icone="Package"
      :titre="filtreActif === 'tous' ? 'Aucune commande pour le moment' : 'Aucune commande dans cette catégorie'"
      :texte="filtreActif === 'tous' ? 'Vos commandes apparaîtront ici, avec leur suivi de livraison.' : 'Essayez un autre filtre.'"
    >
      <Button v-if="filtreActif === 'tous'" to="/produits" variant="primary">Découvrir nos produits</Button>
      <Button v-else variant="outline" @click="changerFiltre('tous')">Voir toutes mes commandes</Button>
    </EmptyState>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import Button from '@/components/common/Button.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import CommandeCard from '@/components/commande/CommandeCard.vue'
import { Package } from 'lucide-vue-next'

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

onMounted(() => {
  fetchCommandes()
})
</script>
