<!-- ===================================
COMPOSANT PRODUIT CARD
File: src/components/produits/ProduitCard.vue
=================================== -->
<!--
  Carte-lien accessible : le nom du produit est un vrai lien étiré sur toute la carte
  (after:inset-0) ; « Vendu par » et « Ajouter » restent au-dessus (z-10), sans
  éléments interactifs imbriqués. Compacte sur mobile (grille de deux colonnes).
-->
<template>
  <Card
    hoverable
    padding="none"
    tag="article"
    class="flex h-full flex-col has-[.lien-etire:focus-visible]:outline has-[.lien-etire:focus-visible]:outline-2 has-[.lien-etire:focus-visible]:outline-offset-2 has-[.lien-etire:focus-visible]:outline-gold-600"
  >
    <!-- Image -->
    <div class="relative aspect-square overflow-hidden bg-gray-100">
      <img
        loading="lazy"
        decoding="async"
        :src="resolveImageUrl(produit.image_principale)"
        alt=""
        class="h-full w-full object-cover transition-transform duration-500 ease-douce motion-safe:group-hover:scale-105"
        @error="onImageError"
      />

      <BadgesProduit :produit="produit" />
    </div>

    <!-- Contenu -->
    <div class="flex flex-1 flex-col p-3 sm:p-4">
      <component :is="niveauTitre" class="font-display text-base font-semibold leading-snug text-gray-900 sm:text-lg">
        <router-link
          :to="`/produits/${produit.slug}`"
          class="lien-etire line-clamp-2 after:absolute after:inset-0 after:content-[''] focus-visible:outline-none"
        >
          {{ produit.nom }}
        </router-link>
      </component>

      <!-- Vendeur : le panier crée une commande par vendeur -->
      <p v-if="produit.createur?.nom_complet" class="mt-1 truncate text-xs text-gray-600 sm:text-sm">
        Vendu par
        <router-link
          :to="{ name: 'vendeur-profil', params: { id: produit.createur.id } }"
          class="relative z-10 font-semibold text-gold-700 underline-offset-2 hover:underline"
        >
          {{ produit.createur.nom_complet }}
        </router-link>
      </p>

      <!-- Prix -->
      <p class="mb-3 mt-auto flex flex-wrap items-baseline gap-x-2 pt-2">
        <span class="sr-only">Prix :</span>
        <span class="price text-lg sm:text-xl">{{ formatPrice(produit.prix_promo || produit.prix_unitaire) }} FCFA</span>
        <span v-if="produit.prix_promo" class="price-old">
          <span class="sr-only">au lieu de</span>
          {{ formatPrice(produit.prix_unitaire) }} FCFA
        </span>
      </p>

      <!-- Ajout au panier : masqué pour l'équipe (admin, vendeur) -->
      <Button
        v-if="!estEquipe"
        variant="primary"
        size="sm"
        full-width
        class="relative z-10"
        :icon="indisponible ? null : ajoute ? Check : ShoppingCart"
        :icon-size="16"
        :disabled="indisponible"
        :loading="ajoutEnCours"
        :aria-label="indisponible ? `${produit.nom} : indisponible` : `Ajouter ${produit.nom} au panier`"
        @click="ajouter(produit)"
      >
        {{ indisponible ? 'Indisponible' : ajoute ? 'Ajouté' : 'Ajouter' }}
      </Button>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import BadgesProduit from '@/components/produits/BadgesProduit.vue'
import { ShoppingCart, Check } from 'lucide-vue-next'
import { useAjoutPanier } from '@/composables/useAjoutPanier'
import { resolveImageUrl, onImageError } from '@/utils/images'
import { formatPrice } from '@/utils/format'
import { estIndisponible } from '@/utils/produit'

const props = defineProps({
  produit: {
    type: Object,
    required: true
  },
  // Niveau du titre : h3 sous une section titrée (h2), h2 directement sous le h1 de la page
  niveauTitre: {
    type: String,
    default: 'h3',
    validator: (valeur) => ['h2', 'h3'].includes(valeur)
  }
})

const { ajouter, ajoutEnCours, ajoute, estEquipe } = useAjoutPanier()
const indisponible = computed(() => estIndisponible(props.produit))
</script>
