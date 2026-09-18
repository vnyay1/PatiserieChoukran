<!-- ===================================
ADMIN - RÉGLAGES DE LA BOUTIQUE
File: src/components/admin/ReglagesBoutique.vue
=================================== -->
<!-- Réglages courants sans passer par les clés techniques des paramètres -->

<template>
  <Card padding="lg" class="mb-6">
    <h2 class="font-display text-xl font-bold text-gray-800 mb-1">Réglages de la boutique</h2>
    <p class="text-sm text-gray-600 mb-6">Appliqués immédiatement à toute la boutique.</p>

    <div v-if="loading" class="space-y-3">
      <div v-for="n in 3" :key="n" class="skeleton h-12 rounded-lg"></div>
    </div>

    <form v-else class="grid grid-cols-1 md:grid-cols-2 gap-6" @submit.prevent="enregistrer">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2" for="reglage-panier">
          Vider les paniers inactifs après
        </label>
        <div class="flex gap-2">
          <input
            id="reglage-panier"
            v-model.number="duree.valeur"
            type="number"
            min="1"
            class="input"
            required
          />
          <select v-model="duree.unite" class="input w-32" aria-label="Unité">
            <option v-for="(minutes, unite) in UNITES" :key="unite" :value="unite">{{ unite }}</option>
          </select>
        </div>
        <p class="text-xs text-gray-500 mt-1">
          Délai sans ajout ni modification avant que le panier du client soit vidé (5 minutes à 30 jours).
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2" for="reglage-frais">Frais de livraison</label>
        <div class="relative">
          <input
            id="reglage-frais"
            v-model.number="form.frais_livraison_standard"
            type="number"
            min="0"
            step="100"
            class="input pr-16"
            required
          />
          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">FCFA</span>
        </div>
        <p class="text-xs text-gray-500 mt-1">Payés par le client pour chaque commande livrée.</p>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2" for="reglage-conditions">Conditions des vendeurs</label>
        <textarea id="reglage-conditions" v-model="form.conditions_vendeur" rows="6" class="input resize-y" required></textarea>
        <p class="text-xs text-gray-500 mt-1">Texte que chaque vendeur accepte en complétant son profil boutique.</p>
      </div>

      <p v-if="erreur" role="alert" class="text-sm font-medium text-red-700 md:col-span-2">{{ erreur }}</p>

      <div class="md:col-span-2">
        <Button type="submit" variant="primary" :loading="saving">Enregistrer les réglages</Button>
      </div>
    </form>
  </Card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'

const UNITES = { minutes: 1, heures: 60, jours: 1440 }

const toastStore = useToastStore()
const loading = ref(true)
const saving = ref(false)
const erreur = ref('')
const form = ref({ frais_livraison_standard: 0, conditions_vendeur: '' })
const duree = ref({ valeur: 24, unite: 'heures' })

// 1440 -> 1 jours, 90 -> 90 minutes : la plus grande unité qui tombe juste
const dureeDepuisMinutes = (minutes) => {
  const unite = ['jours', 'heures'].find((nom) => minutes % UNITES[nom] === 0) || 'minutes'
  return { valeur: minutes / UNITES[unite], unite }
}

const appliquer = (data) => {
  form.value = {
    frais_livraison_standard: data.frais_livraison_standard,
    conditions_vendeur: data.conditions_vendeur,
  }
  duree.value = dureeDepuisMinutes(data.panier_duree_minutes)
}

const charger = async () => {
  try {
    const response = await api.admin.reglages.get()
    appliquer(response.data.data)
  } catch (error) {
    erreur.value = messageErreur(error, 'Impossible de charger les réglages.')
  } finally {
    loading.value = false
  }
}

const enregistrer = async () => {
  saving.value = true
  erreur.value = ''
  try {
    const response = await api.admin.reglages.update({
      ...form.value,
      panier_duree_minutes: Math.round(duree.value.valeur * UNITES[duree.value.unite]),
    })
    appliquer(response.data.data)
    toastStore.succes('Réglages enregistrés.')
  } catch (error) {
    erreur.value = messageErreur(error, 'Impossible d\'enregistrer les réglages.')
  } finally {
    saving.value = false
  }
}

onMounted(charger)
</script>
