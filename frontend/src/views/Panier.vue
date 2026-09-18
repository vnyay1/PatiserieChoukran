<!-- ===================================
PAGE PANIER
File: src/views/Panier.vue
=================================== -->
<!--
  Articles groupés par vendeur (une commande par vendeur), avec l'état de livraison de
  chaque groupe : icône + texte + couleur, et une jauge « plus que X FCFA » vers le minimum.
  Récapitulatif collant sur desktop, action « Commander » toujours visible.
-->
<template>
  <div class="container mx-auto pb-6 pt-6 md:pt-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1>Mon panier</h1>
        <p v-if="!panierStore.isEmpty" class="mt-1 text-sm text-gray-600">
          {{ panierStore.itemCount }} article{{ panierStore.itemCount > 1 ? 's' : '' }}
          <template v-if="groupes.length > 1"> chez {{ groupes.length }} vendeurs</template>
        </p>
      </div>
      <button
        v-if="!panierStore.isEmpty"
        type="button"
        class="btn-ghost btn-sm text-red-700 hover:bg-red-50"
        @click="confirmerVidage"
      >
        <Trash2 :size="16" aria-hidden="true" />
        Vider le panier
      </button>
    </div>

    <!-- Chargement -->
    <div v-if="panierStore.loading && panierStore.isEmpty" class="grid grid-cols-1 gap-6 lg:grid-cols-3" aria-busy="true">
      <div class="space-y-3 lg:col-span-2">
        <div v-for="n in 3" :key="n" class="skeleton h-28 rounded-elegant"></div>
      </div>
      <div class="skeleton h-72 rounded-elegant"></div>
    </div>

    <!-- Panier vide -->
    <EmptyState
      v-else-if="panierStore.isEmpty"
      :icone="ShoppingCart"
      titre="Votre panier est vide"
      texte="Parcourez nos gâteaux, pâtisseries et glaces : ils vous attendent."
    >
      <Button to="/produits" variant="primary" :icon="ShoppingBag">Découvrir nos produits</Button>
    </EmptyState>

    <!-- Contenu -->
    <div v-else class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3 lg:gap-8">
      <div class="space-y-6 lg:col-span-2">
        <AlertMessage v-if="groupes.length > 1" type="info">
          Votre panier contient les produits de {{ groupes.length }} vendeurs :
          une commande sera créée pour chacun, avec ses propres frais de livraison.
        </AlertMessage>

        <section
          v-for="groupe in livraisonParGroupe"
          :key="groupe.vendeurId ?? 'sans-vendeur'"
          class="card"
          :aria-labelledby="`vendeur-${groupe.vendeurId ?? 'aucun'}`"
        >
          <header class="border-b border-gray-200 px-4 py-4 sm:px-5">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <h2 :id="`vendeur-${groupe.vendeurId ?? 'aucun'}`" class="flex items-center gap-2 font-body text-base font-bold">
                <Store :size="18" class="text-gold-600" aria-hidden="true" />
                <router-link
                  v-if="groupe.vendeurId"
                  :to="{ name: 'vendeur-profil', params: { id: groupe.vendeurId } }"
                  class="hover:underline"
                >
                  {{ groupe.vendeurNom }}
                </router-link>
                <template v-else>{{ groupe.vendeurNom }}</template>
              </h2>
              <p class="text-sm text-gray-700">
                Sous-total : <span class="font-semibold tabular-nums">{{ formatPrice(groupe.sousTotal) }} FCFA</span>
              </p>
            </div>

            <p class="mt-2 flex items-start gap-2 text-sm" :class="etatLivraison(groupe).classe">
              <component :is="etatLivraison(groupe).icone" :size="16" class="mt-0.5 flex-shrink-0" aria-hidden="true" />
              <span>{{ etatLivraison(groupe).message }}</span>
            </p>

            <!-- Jauge vers le minimum de livraison du vendeur -->
            <div v-if="groupe.minimum > 0 && groupe.manque > 0" class="mt-3">
              <div
                class="h-2 overflow-hidden rounded-full bg-gray-200"
                role="progressbar"
                :aria-valuenow="Math.round(groupe.sousTotal)"
                aria-valuemin="0"
                :aria-valuemax="groupe.minimum"
                :aria-label="`Minimum de livraison chez ${groupe.vendeurNom}`"
                :aria-valuetext="`${formatPrice(groupe.sousTotal)} sur ${formatPrice(groupe.minimum)} FCFA`"
              >
                <div class="h-full rounded-full bg-gold-600 transition-[width] duration-500" :style="{ width: `${Math.min(100, (groupe.sousTotal / groupe.minimum) * 100)}%` }"></div>
              </div>
            </div>
          </header>

          <TransitionGroup name="liste" tag="ul" class="divide-y divide-gray-200">
            <PanierItem
              v-for="item in groupe.items"
              :key="item.id"
              :item="item"
              :loading="itemsEnCours.has(item.id)"
              @update-quantity="updateQuantity"
              @remove="removeItem"
            />
          </TransitionGroup>
        </section>
      </div>

      <!-- Récapitulatif -->
      <aside class="lg:sticky lg:top-24" aria-labelledby="titre-recapitulatif">
        <div class="card p-5 sm:p-6">
          <h2 id="titre-recapitulatif" class="text-xl">Récapitulatif</h2>

          <dl class="mt-5 space-y-3 text-[0.9375rem]">
            <div class="flex items-center justify-between gap-3 text-gray-700">
              <dt>Produits ({{ panierStore.itemCount }})</dt>
              <dd class="font-semibold tabular-nums text-gray-900">{{ formatPrice(panierStore.total) }} FCFA</dd>
            </div>
            <div class="flex items-center justify-between gap-3 text-gray-700">
              <dt>Livraison{{ livraisonEstimee ? ' estimée' : '' }}</dt>
              <dd v-if="livraisonEstimee" class="font-semibold tabular-nums text-gray-900">{{ formatPrice(fraisEstimes) }} FCFA</dd>
              <dd v-else class="text-sm text-gray-600">Calculée à l'étape suivante</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 border-t border-gray-200 pt-4">
              <dt class="font-semibold text-gray-900">Total{{ livraisonEstimee ? ' estimé' : '' }}</dt>
              <dd class="price text-2xl">{{ formatPrice(totalEstime) }} FCFA</dd>
            </div>
          </dl>

          <p v-if="livraisonEstimee" class="mt-2 text-xs text-gray-600">
            {{ adressePrincipale ? `Vers ${adressePrincipale.quartier}, ${formatVille(villeLivraison)} (adresse principale)` : `À ${formatVille(villeLivraison)} (ville choisie)` }},
            modifiable à l'étape suivante.
          </p>

          <AlertMessage v-if="aDesProduitsSansVendeur" type="error" class="mt-4">
            Retirez les produits sans vendeur pour pouvoir commander.
          </AlertMessage>

          <Button
            :to="aDesProduitsSansVendeur ? null : '/commander'"
            variant="primary"
            size="lg"
            full-width
            class="mt-5"
            :disabled="aDesProduitsSansVendeur"
          >
            Passer la commande
            <ArrowRight :size="18" aria-hidden="true" />
          </Button>

          <p class="mt-4 flex items-center justify-center gap-2 text-sm text-gray-600">
            <ShieldCheck :size="16" class="text-green-700" aria-hidden="true" />
            Paiement sécurisé
          </p>

          <!-- Durée réglée par l'admin : sans modification, le panier est vidé -->
          <p v-if="panierStore.expireLe" class="mt-4 flex items-start gap-2 rounded-xl bg-gray-50 p-3 text-xs text-gray-600">
            <Clock :size="14" class="mt-0.5 flex-shrink-0" aria-hidden="true" />
            <span>Sans modification, ce panier sera vidé le {{ formatDateHeure(panierStore.expireLe) }}.</span>
          </p>
        </div>

        <!-- Moyens de paiement acceptés -->
        <div class="mt-4 rounded-elegant border border-gray-200 bg-surface p-4">
          <p class="mb-3 text-center text-xs font-semibold text-gray-600">Moyens de paiement acceptés</p>
          <ul class="flex items-center justify-center gap-3">
            <li v-for="moyen in MOYENS" :key="moyen.nom" class="flex h-12 w-16 items-center justify-center rounded-lg bg-white p-1.5 ring-1 ring-gray-200">
              <img :src="moyen.logo" :alt="moyen.nom" class="max-h-full w-auto object-contain" loading="lazy" />
            </li>
          </ul>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePanierStore } from '@/stores/panier'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import api from '@/services/api'
import PanierItem from '@/components/panier/PanierItem.vue'
import Button from '@/components/common/Button.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import { useLivraisonVendeurs, grouperParVendeur } from '@/composables/useLivraisonVendeurs'
import { useVilleStore } from '@/stores/ville'
import { formatVille, villeAdresse } from '@/utils/villes'
import {
  ShieldCheck, Clock, Trash2, ShoppingCart, ShoppingBag, Store, Truck, AlertTriangle, AlertCircle, Info, ArrowRight, Loader2,
} from 'lucide-vue-next'
import { formatDateHeure, formatPrice } from '@/utils/format'

const MOYENS = [
  { nom: 'Orange Money', logo: '/Orange-Money-logo.png' },
  { nom: 'MTN Mobile Money', logo: '/Momo-logo.png' },
  { nom: 'Espèces', logo: '/argent.png' },
]

const panierStore = usePanierStore()
const toastStore = useToastStore()
const villeStore = useVilleStore()
const { confirmer } = useConfirm()
const { chargerLivraisonVendeurs, livraisonDesGroupes } = useLivraisonVendeurs()

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

const messageMinimum = (groupe) => {
  return `Livraison dès ${formatPrice(groupe.minimum)} FCFA d'achat chez ce vendeur : ajoutez ${formatPrice(groupe.manque)} FCFA ou choisissez le retrait en boutique.`
}

// État de livraison d'un groupe : message, icône et couleur
const etatLivraison = (groupe) => {
  const ville = formatVille(villeLivraison.value)

  switch (groupe.statut) {
    case 'ok':
      return { message: `Livraison à ${ville} : ${formatPrice(groupe.frais)} FCFA`, icone: Truck, classe: 'text-gray-700' }
    case 'non_couvert':
      return { message: `Ce vendeur ne livre pas à ${ville} : retrait en boutique possible.`, icone: AlertTriangle, classe: 'text-orange-700' }
    case 'minimum_non_atteint':
      return { message: messageMinimum(groupe), icone: AlertTriangle, classe: 'text-orange-700' }
    case 'chargement':
      return { message: 'Calcul des frais de livraison…', icone: Loader2, classe: 'text-gray-600' }
    case 'sans_vendeur':
      return { message: 'Produits rattachés à aucun vendeur : ils ne peuvent pas être commandés.', icone: AlertCircle, classe: 'text-red-700' }
    default:
      // Sans adresse, le minimum du vendeur reste utile à connaître
      return groupe.manque > 0
        ? { message: messageMinimum(groupe), icone: AlertTriangle, classe: 'text-orange-700' }
        : { message: 'Frais de livraison calculés à l\'étape suivante.', icone: Info, classe: 'text-gray-600' }
  }
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

const confirmerVidage = async () => {
  const ok = await confirmer({
    titre: 'Vider le panier ?',
    message: 'Tous les articles seront retirés. Cette action est définitive.',
    libelleConfirmer: 'Vider le panier',
    danger: true,
  })
  if (!ok) return

  const result = await panierStore.clear()
  if (result?.success) {
    toastStore.succes('Panier vidé.')
  } else {
    toastStore.erreur(result?.message || 'Impossible de vider le panier.')
  }
}
</script>

<style scoped>
.liste-enter-active,
.liste-leave-active {
  transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.2, 0, 0, 1);
}

.liste-enter-from,
.liste-leave-to {
  opacity: 0;
  transform: translateX(16px);
}

.liste-move {
  transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1);
}
</style>
