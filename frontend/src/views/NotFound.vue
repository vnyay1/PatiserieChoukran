<!-- ===================================
PAGE 404
File: src/views/NotFound.vue
=================================== -->

<template>
  <div class="container mx-auto flex min-h-[60vh] items-center justify-center">
    <EmptyState
      :icone="CakeSlice"
      niveau="h1"
      titre="Cette page n'existe pas"
      texte="Le lien est peut-être erroné, ou la page a été déplacée. Nos pâtisseries, elles, sont toujours là."
    >
      <Button to="/" variant="primary" :icon="Home">Retour à l'accueil</Button>
      <Button to="/produits" variant="outline">Voir nos produits</Button>
    </EmptyState>
  </div>
</template>

<script setup>
import Button from '@/components/common/Button.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import { CakeSlice, Home } from 'lucide-vue-next'
</script>
