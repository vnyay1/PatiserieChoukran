<!-- ===================================
PAGE 404
File: src/views/NotFound.vue
=================================== -->
<!--
  Illustration faite des icônes du projet (pas d'image à charger), puis une issue utile :
  chercher un produit, revenir à l'accueil ou au catalogue.
-->
<template>
  <div class="container mx-auto flex min-h-[60vh] max-w-xl flex-col items-center justify-center py-12 text-center">
    <div class="relative mb-7 h-32 w-32" aria-hidden="true">
      <span class="absolute inset-0 rounded-full bg-gold-100"></span>
      <span class="absolute inset-3 rounded-full border-2 border-dashed border-gold-300"></span>
      <CakeSlice class="absolute inset-0 m-auto text-gold-700" :size="56" :stroke-width="1.5" />
      <span class="absolute -right-1 bottom-1 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-gray-700 shadow-card ring-4 ring-cream">
        <SearchX :size="22" />
      </span>
    </div>

    <h1 class="text-balance text-3xl md:text-4xl">Cette page n'existe pas</h1>
    <p class="mt-3 text-gray-700">
      Le lien est peut-être erroné, ou la page a été déplacée. Nos pâtisseries, elles, sont toujours là.
    </p>

    <form role="search" class="mt-7 flex w-full flex-col gap-3 sm:flex-row" @submit.prevent="chercher">
      <label for="recherche-404" class="sr-only">Rechercher un produit</label>
      <input
        id="recherche-404"
        v-model="recherche"
        type="search"
        enterkeyhint="search"
        placeholder="Gâteau, glace, croissant…"
        class="input flex-1 rounded-full"
      />
      <Button type="submit" variant="primary" :icon="Search">Rechercher</Button>
    </form>

    <div class="mt-6 flex flex-wrap justify-center gap-3">
      <Button to="/" variant="outline" :icon="Home">Retour à l'accueil</Button>
      <Button to="/produits" variant="ghost">Voir tous les produits</Button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from '@/components/common/Button.vue'
import { CakeSlice, Home, Search, SearchX } from 'lucide-vue-next'

const router = useRouter()
const recherche = ref('')

const chercher = () => {
  const q = recherche.value.trim()
  router.push(q ? { path: '/produits', query: { q } } : '/produits')
}
</script>
