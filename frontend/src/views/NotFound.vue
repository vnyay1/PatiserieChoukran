<!-- ===================================
PAGE 404
File: src/views/NotFound.vue
=================================== -->

<template>
  <div class="min-h-[70vh] flex items-center justify-center px-4 py-16 bg-cream">
    <div class="text-center max-w-md">
      <div class="text-7xl mb-4">🧁</div>
      <p class="font-display text-5xl font-bold text-gold-600 mb-2">404</p>
      <h1 class="font-display text-2xl font-bold text-gray-800 mb-3">
        Cette page n'existe pas
      </h1>
      <p class="text-gray-600 mb-8">
        Le lien est peut-être erroné, ou la page a été déplacée.
      </p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <Button variant="primary" @click="$router.push('/')">
          Retour à l'accueil
        </Button>
        <Button variant="outline" @click="$router.push('/produits')">
          Voir nos produits
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import Button from '@/components/common/Button.vue'
</script>
