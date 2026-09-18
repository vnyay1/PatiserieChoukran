<!-- ===================================
VENDEUR - MA LIVRAISON (villes livrées et minimum d'achat)
File: src/views/vendeur/MaLivraison.vue
=================================== -->

<template>
  <div class="ma-livraison-page pb-6">
    <div class="container mx-auto px-4 py-6 max-w-3xl">
      <div class="mb-6">
        <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
          Ma livraison
        </h1>
        <p class="text-gray-600 text-sm">
          Vos produits ne sont proposés qu'aux clients des villes que vous livrez. Le retrait en
          boutique reste toujours possible.
        </p>
      </div>

      <p v-if="erreur" class="text-sm text-red-600 mb-4">{{ erreur }}</p>

      <div v-if="loading" class="space-y-4">
        <div v-for="n in 2" :key="n" class="skeleton h-40 rounded-elegant"></div>
      </div>

      <form v-else class="space-y-6" @submit.prevent="enregistrer">
        <Card padding="lg">
          <h2 class="font-display text-lg font-bold text-gray-800 mb-1">Villes livrées</h2>
          <p class="text-sm text-gray-600 mb-4">Cochez les villes où vous livrez vous-même vos commandes.</p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label
              v-for="ville in VILLES"
              :key="ville.valeur"
              class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-colors"
              :class="form.villes.includes(ville.valeur) ? 'border-gold-600 bg-gold-50' : 'border-gray-200'"
            >
              <input
                v-model="form.villes"
                type="checkbox"
                :value="ville.valeur"
                class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
              />
              <MapPin :size="20" class="text-gold-600" />
              <span class="font-semibold text-gray-800">{{ ville.libelle }}</span>
            </label>
          </div>

          <p v-if="form.villes.length === 0" class="text-sm text-orange-600 mt-3">
            Sans ville, vos produits ne sont visibles que des clients qui n'ont pas choisi de ville
            et ne peuvent être que retirés en boutique.
          </p>
        </Card>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Card padding="lg">
            <h2 class="font-display text-lg font-bold text-gray-800 mb-1">Montant minimum</h2>
            <p class="text-sm text-gray-600 mb-4">
              En dessous de ce montant de produits, seul le retrait en boutique est proposé. 0 : aucun minimum.
            </p>
            <div class="relative">
              <input
                v-model.number="form.montant_minimum_livraison"
                type="number"
                min="0"
                step="500"
                class="input pr-16"
                required
              />
              <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">FCFA</span>
            </div>
          </Card>

          <Card padding="lg">
            <h2 class="font-display text-lg font-bold text-gray-800 mb-1">Frais de livraison</h2>
            <p class="price text-2xl my-2">{{ formatPrice(fraisStandard) }} FCFA</p>
            <p class="text-sm text-gray-600">
              Tarif unique fixé par la plateforme, payé par le client pour chaque commande livrée.
            </p>
          </Card>
        </div>

        <Button type="submit" variant="primary" :loading="saving">
          Enregistrer
        </Button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api, { messageErreur } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { formatPrice } from '@/utils/format'
import { VILLES, formatVille } from '@/utils/villes'
import { MapPin } from 'lucide-vue-next'

const toastStore = useToastStore()

const loading = ref(true)
const saving = ref(false)
const erreur = ref('')
const fraisStandard = ref(0)
const form = ref({ villes: [], montant_minimum_livraison: 0 })

const appliquer = (data) => {
  form.value = {
    villes: data.villes || [],
    montant_minimum_livraison: Number(data.montant_minimum_livraison) || 0,
  }
  fraisStandard.value = Number(data.frais_livraison_standard) || 0
}

const charger = async () => {
  loading.value = true
  try {
    const response = await api.vendeur.livraison.get()
    appliquer(response.data.data)
  } catch (error) {
    erreur.value = messageErreur(error, 'Impossible de charger vos réglages de livraison.')
  } finally {
    loading.value = false
  }
}

const enregistrer = async () => {
  saving.value = true
  erreur.value = ''
  try {
    const response = await api.vendeur.livraison.update({
      villes: form.value.villes,
      montant_minimum_livraison: form.value.montant_minimum_livraison || 0,
    })
    appliquer(response.data.data)

    const villes = form.value.villes.map(formatVille).join(' et ')
    toastStore.succes(villes ? `Livraison enregistrée : ${villes}.` : 'Réglages enregistrés (retrait en boutique uniquement).')
  } catch (error) {
    erreur.value = messageErreur(error, 'Impossible d\'enregistrer vos réglages de livraison.')
  } finally {
    saving.value = false
  }
}

onMounted(charger)
</script>
