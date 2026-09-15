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
                  <router-link
                    v-if="groupe.vendeurId"
                    :to="{ name: 'vendeur-profil', params: { id: groupe.vendeurId } }"
                    class="hover:text-gold-600"
                  >
                    {{ groupe.vendeurNom }}
                  </router-link>
                  <template v-else>{{ groupe.vendeurNom }}</template>
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
                  :loading="itemsEnCours.has(item.id)"
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
                    {{ formatPrice(fraisEstimes) }} FCFA
                  </span>
                  <span v-else class="text-sm text-gray-500">À calculer</span>
                </div>
                <p v-if="livraisonEstimee" class="text-xs text-gray-500">
                  {{ adressePrincipale ? `Vers ${adressePrincipale.quartier}, ${formatVille(villeLivraison)} (adresse principale)` : `À ${formatVille(villeLivraison)} (ville choisie)` }},
                  modifiable à l'étape suivante.
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
import { useToastStore } from '@/stores/toast'
import api from '@/services/api'
import PanierItem from '@/components/panier/PanierItem.vue'
import Button from '@/components/common/Button.vue'
import Card from '@/components/common/Card.vue'
import { useLivraisonVendeurs, grouperParVendeur } from '@/composables/useLivraisonVendeurs'
import { useVilleStore } from '@/stores/ville'
import { formatVille, villeAdresse } from '@/utils/villes'
import { Shield } from 'lucide-vue-next'

const panierStore = usePanierStore()
const toastStore = useToastStore()
const villeStore = useVilleStore()
const { chargerLivraisonVendeurs, livraisonDesGroupes } = useLivraisonVendeurs()

const showClearConfirm = ref(false)
// Sert à estimer les frais de livraison (l'adresse reste modifiable au checkout)
const adressePrincipale = ref(null)

const groupes = computed(() => grouperParVendeur(panierStore.items))
// Estimation vers la ville de l'adresse principale, sinon la ville choisie dans l'en-tête
const villeLivraison = computed(() => villeAdresse(adressePrincipale.value) || villeStore.ville)
const livraisonParGroupe = computed(() => livraisonDesGroupes(groupes.value, villeLivraison.value))

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
  const ville = formatVille(villeLivraison.value)

  switch (groupe.statut) {
    case 'ok':
      return `Livraison estimée à ${ville} : ${formatPrice(groupe.frais)} FCFA`
    case 'non_couvert':
      return `Ce vendeur ne livre pas à ${ville} (retrait en boutique possible)`
    case 'minimum_non_atteint':
      return messageMinimum(groupe)
    case 'chargement':
      return 'Calcul des frais de livraison...'
    case 'sans_vendeur':
      return 'Produits rattachés à aucun vendeur : ils ne peuvent pas être commandés.'
    default:
      // Sans adresse, le minimum du vendeur reste utile à connaître
      return groupe.manque > 0 ? messageMinimum(groupe) : 'Frais de livraison calculés à l\'étape suivante'
  }
}

const messageMinimum = (groupe) => {
  return `Livraison dès ${formatPrice(groupe.minimum)} FCFA d'achat chez ce vendeur : ajoutez ${formatPrice(groupe.manque)} FCFA ou choisissez le retrait en boutique.`
}

const classeMessageLivraison = (groupe) => {
  if (groupe.statut === 'sans_vendeur') return 'text-red-600'
  if (groupe.statut === 'non_couvert' || groupe.manque > 0) return 'text-orange-600'
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

// Charge les conditions de livraison (villes, minimum) des vendeurs du panier
watch(
  () => groupes.value.map((groupe) => groupe.vendeurId).filter(Boolean).join(','),
  () => chargerLivraisonVendeurs(groupes.value.map((groupe) => groupe.vendeurId)),
  { immediate: true }
)

// Lignes en cours de mise à jour (indicateur de chargement par article)
const itemsEnCours = ref(new Set())

const avecChargement = async (itemId, action) => {
  itemsEnCours.value.add(itemId)
  try {
    return await action()
  } finally {
    itemsEnCours.value.delete(itemId)
  }
}

const updateQuantity = async (itemId, quantite) => {
  const result = await avecChargement(itemId, () => panierStore.updateQuantity(itemId, quantite))
  if (!result?.success) {
    toastStore.erreur(result?.message || 'Impossible de modifier la quantité.')
  }
}

const removeItem = async (itemId) => {
  const result = await avecChargement(itemId, () => panierStore.removeItem(itemId))
  if (result?.success) {
    toastStore.succes('Article retiré du panier.')
  } else {
    toastStore.erreur(result?.message || 'Impossible de retirer cet article.')
  }
}

const clearCart = async () => {
  const result = await panierStore.clear()
  showClearConfirm.value = false
  if (result?.success) {
    toastStore.succes('Panier vidé.')
  } else {
    toastStore.erreur(result?.message || 'Impossible de vider le panier.')
  }
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