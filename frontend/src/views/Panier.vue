<!-- ===================================
PAGE PANIER
File: src/views/Panier.vue
=================================== -->

<template>
  <div class="panier-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
          Mon Panier
        </h1>
        <button
          v-if="!panierStore.isEmpty"
          class="text-red-500 hover:text-red-600 text-sm font-medium"
          @click="showClearConfirm = true"
        >
          Vider le panier
        </button>
      </div>

      <!-- Loading -->
      <div v-if="panierStore.loading" class="space-y-4">
        <div v-for="n in 3" :key="n" class="skeleton h-32 rounded-elegant"></div>
      </div>

      <!-- Panier vide -->
      <div v-else-if="panierStore.isEmpty" class="text-center py-16">
        <div class="text-6xl mb-4">🛒</div>
        <h2 class="font-display text-2xl font-bold text-gray-800 mb-2">
          Votre panier est vide
        </h2>
        <p class="text-gray-600 mb-6">
          Découvrez nos délicieuses créations
        </p>
        <Button variant="primary" @click="$router.push('/produits')">
          Découvrir nos produits
        </Button>
      </div>

      <!-- Contenu du panier -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Liste des articles, groupés par vendeur (une commande par vendeur) -->
        <div class="lg:col-span-2 space-y-6">
          <p v-if="groupes.length > 1" class="text-sm text-gray-600">
            Votre panier contient des produits de {{ groupes.length }} vendeurs :
            une commande sera créée par vendeur.
          </p>

          <section
            v-for="groupe in livraisonParGroupe"
            :key="groupe.vendeurId ?? 'sans-vendeur'"
            class="space-y-3"
          >
            <div class="flex flex-wrap items-end justify-between gap-2">
              <div>
                <h2 class="font-display text-lg font-bold text-gray-800">
                  {{ groupe.vendeurNom }}
                </h2>
                <p class="text-xs" :class="classeMessageLivraison(groupe)">
                  {{ messageLivraison(groupe) }}
                </p>
              </div>
              <div class="text-sm text-gray-700">
                Sous-total : <span class="font-semibold">{{ formatPrice(groupe.sousTotal) }} FCFA</span>
              </div>
            </div>

            <div class="space-y-4">
              <TransitionGroup name="list">
                <PanierItem
                  v-for="item in groupe.items"
                  :key="item.id"
                  :item="item"
                  @update-quantity="updateQuantity"
                  @remove="removeItem"
                />
              </TransitionGroup>
            </div>
          </section>
        </div>

        <!-- Résumé -->
        <div class="lg:col-span-1">
          <div class="sticky top-24">
            <Card padding="lg">
              <h2 class="font-display text-xl font-bold text-gray-800 mb-6">
                Résumé de la commande
              </h2>

              <!-- Détails -->
              <div class="space-y-3 mb-6">
                <div class="flex items-center justify-between text-gray-700">
                  <span>Sous-total ({{ panierStore.itemCount }} article{{ panierStore.itemCount > 1 ? 's' : '' }})</span>
                  <span class="font-semibold">{{ formatPrice(panierStore.total) }} FCFA</span>
                </div>

                <div class="flex items-center justify-between text-gray-700">
                  <span>Livraison{{ livraisonEstimee ? ' estimée' : '' }}</span>
                  <span v-if="livraisonEstimee" class="font-semibold">
                    {{ fraisEstimes > 0 ? `${formatPrice(fraisEstimes)} FCFA` : 'Gratuite' }}
                  </span>
                  <span v-else class="text-sm text-gray-500">À calculer</span>
                </div>
                <p v-if="livraisonEstimee" class="text-xs text-gray-500">
                  Vers {{ adressePrincipale?.quartier }} (adresse principale), modifiable à l'étape suivante.
                </p>

                <div class="divider-ornament"></div>

                <div class="flex items-center justify-between text-lg font-bold">
                  <span>Total{{ livraisonEstimee ? ' estimé' : '' }}</span>
                  <span class="price text-2xl">{{ formatPrice(totalEstime) }} FCFA</span>
                </div>

                <p v-if="groupes.length > 1" class="text-xs text-gray-500">
                  {{ groupes.length }} commandes seront créées, une par vendeur.
                </p>
              </div>

              <!-- Code promo -->
              <div class="mb-6">
                <button
                  class="flex items-center gap-2 text-gold-600 hover:text-gold-700 text-sm font-medium"
                  @click="showPromoInput = !showPromoInput"
                >
                  <Tag :size="16" />
                  <span>Ajouter un code promo</span>
                </button>

                <div v-if="showPromoInput" class="mt-3 flex gap-2">
                  <input
                    v-model="codePromo"
                    type="text"
                    placeholder="CODE PROMO"
                    class="input text-sm"
                  />
                  <Button variant="outline" size="sm" @click="applyPromo">
                    Appliquer
                  </Button>
                </div>
              </div>

              <!-- Bouton commander -->
              <p v-if="aDesProduitsSansVendeur" class="text-sm text-red-600 mb-3">
                Retirez les produits sans vendeur pour pouvoir commander.
              </p>
              <Button
                variant="primary"
                size="lg"
                full-width
                :disabled="aDesProduitsSansVendeur"
                @click="$router.push('/commander')"
              >
                Commander
              </Button>

              <!-- Paiement sécurisé -->
              <div class="mt-6 flex items-center justify-center gap-2 text-sm text-gray-500">
                <Shield :size="16" class="text-green-500" />
                <span>Paiement sécurisé</span>
              </div>
            </Card>

            <!-- Moyens de paiement acceptés -->
            <div class="mt-4 p-4 bg-white rounded-elegant">
              <p class="text-xs text-gray-600 text-center mb-2">Moyens de paiement acceptés</p>
              <div class="flex items-center justify-center gap-3">
                <div class="px-3 py-2 bg-orange-100 rounded text-xs font-semibold text-orange-700">
                  <img src="../../public/Orange-Money-logo.png" alt="LogoOrangeMoney" height="40px" width="60px">
                </div>
                <div class="px-3 py-2 bg-yellow-100 rounded text-xs font-semibold text-yellow-700">
                  <img src="../../public/Momo-logo.png" alt="LogoMobileMoney" height="42px" width="60px">
                </div>
                <div class="px-3 py-2 bg-green-100 rounded text-xs font-semibold text-green-700">
                  <img src="../../public/argent.png" alt="LogoArgent" height="40px" width="42px">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal confirmation vider le panier -->
    <Teleport to="body">
      <div
        v-if="showClearConfirm"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click="showClearConfirm = false"
      >
        <div
          class="bg-white rounded-elegant p-6 max-w-sm w-full animate-fadeIn"
          @click.stop
        >
          <div class="text-center mb-4">
            <div class="text-5xl mb-3">⚠️</div>
            <h3 class="font-display text-xl font-bold text-gray-800 mb-2">
              Vider le panier ?
            </h3>
            <p class="text-gray-600">
              Tous les articles seront supprimés
            </p>
          </div>

          <div class="flex gap-3">
            <Button variant="outline" full-width @click="showClearConfirm = false">
              Annuler
            </Button>
            <Button variant="danger" full-width @click="clearCart">
              Vider
            </Button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePanierStore } from '@/stores/panier'
import api from '@/services/api'
import PanierItem from '@/components/panier/PanierItem.vue'
import Button from '@/components/common/Button.vue'
import Card from '@/components/common/Card.vue'
import { useLivraisonVendeurs, grouperParVendeur, formatDelai } from '@/composables/useLivraisonVendeurs'
import { Tag, Shield } from 'lucide-vue-next'

const panierStore = usePanierStore()
const { chargerQuartiersVendeurs, livraisonDesGroupes } = useLivraisonVendeurs()

const showPromoInput = ref(false)
const codePromo = ref('')
const showClearConfirm = ref(false)
// Sert à estimer les frais de livraison (l'adresse reste modifiable au checkout)
const adressePrincipale = ref(null)

const groupes = computed(() => grouperParVendeur(panierStore.items))
const livraisonParGroupe = computed(() => {
  return livraisonDesGroupes(groupes.value, adressePrincipale.value?.quartier_id || null)
})

const livraisonEstimee = computed(() => {
  return livraisonParGroupe.value.length > 0 && livraisonParGroupe.value.every((groupe) => groupe.statut === 'ok')
})
const fraisEstimes = computed(() => livraisonParGroupe.value.reduce((somme, groupe) => somme + groupe.frais, 0))
const totalEstime = computed(() => panierStore.total + (livraisonEstimee.value ? fraisEstimes.value : 0))
const aDesProduitsSansVendeur = computed(() => groupes.value.some((groupe) => !groupe.vendeurId))

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR').format(price)
}

const messageLivraison = (groupe) => {
  const quartier = adressePrincipale.value?.quartier

  switch (groupe.statut) {
    case 'ok': {
      const frais = groupe.frais > 0 ? `${formatPrice(groupe.frais)} FCFA` : 'gratuite'
      const delai = formatDelai(groupe.tarif)
      return `Livraison estimée vers ${quartier} : ${frais}${delai ? ` (${delai})` : ''}`
    }
    case 'non_couvert':
      return `Ce vendeur ne livre pas à ${quartier} (retrait en boutique possible)`
    case 'chargement':
      return 'Calcul des frais de livraison...'
    case 'sans_vendeur':
      return 'Produits rattachés à aucun vendeur : ils ne peuvent pas être commandés.'
    default:
      return 'Frais de livraison calculés à l\'étape suivante'
  }
}

const classeMessageLivraison = (groupe) => {
  if (groupe.statut === 'sans_vendeur') return 'text-red-600'
  if (groupe.statut === 'non_couvert') return 'text-orange-600'
  if (groupe.statut === 'ok') return 'text-gray-600'
  return 'text-gray-500'
}

const fetchAdressePrincipale = async () => {
  try {
    const response = await api.adresses.getAll()
    const adresses = response.data?.data || []
    adressePrincipale.value = adresses.find((adresse) => adresse.est_principale) || adresses[0] || null
  } catch (error) {
    console.error('Erreur chargement adresse principale:', error)
  }
}

onMounted(fetchAdressePrincipale)

// Charge les tarifs des vendeurs présents dans le panier
watch(
  () => groupes.value.map((groupe) => groupe.vendeurId).filter(Boolean).join(','),
  () => chargerQuartiersVendeurs(groupes.value.map((groupe) => groupe.vendeurId)),
  { immediate: true }
)

const updateQuantity = async (itemId, quantite) => {
  await panierStore.updateQuantity(itemId, quantite)
}

const removeItem = async (itemId) => {
  await panierStore.removeItem(itemId)
}

const clearCart = async () => {
  await panierStore.clear()
  showClearConfirm.value = false
}

const applyPromo = () => {
  // TODO: Implémenter l'application du code promo
  console.log('Code promo:', codePromo.value)
}
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}

.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.list-move {
  transition: transform 0.3s ease;
}
</style>