<!-- ===================================
PAGE INFOS PRATIQUES - Contact & Horaires
File: src/views/InfosPratiques.vue
=================================== -->

<template>
  <div class="infos-pratiques-page bg-cream min-h-screen pb-20">
    <section class="bg-white border-b border-gray-100">
      <div class="container mx-auto px-4 py-10 md:py-14">
        <p class="text-xs uppercase tracking-[0.2em] text-gold-600 font-semibold mb-3">Infos pratiques</p>
        <h1 class="font-display text-3xl md:text-4xl font-bold text-gray-900 mb-3">
          Nous Contacter
        </h1>
        <p class="text-sm md:text-base text-gray-600 max-w-2xl">
          Une question sur une commande, un événement ou une création personnalisée ? Notre équipe vous répond rapidement.
        </p>
      </div>
    </section>

    <div class="container mx-auto px-4 py-8 md:py-10 space-y-8">
      <section class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
        <Card padding="lg" class="border border-gold-100 bg-gold-50/40">
          <h2 class="font-display text-lg font-semibold text-gray-900 mb-2">Téléphone</h2>
          <a href="tel:658555600" class="text-gold-700 font-semibold hover:text-gold-800">658555600</a>
        </Card>

        <Card padding="lg" class="border border-gray-100">
          <h2 class="font-display text-lg font-semibold text-gray-900 mb-2">Adresse</h2>
          <p class="text-gray-700">Yaoundé - Olembé Échangeur</p>
        </Card>

        <Card padding="lg" class="border border-gray-100">
          <h2 class="font-display text-lg font-semibold text-gray-900 mb-2">Instagram</h2>
          <a
            href="https://instagram.com/Choukran.Patisserie"
            target="_blank"
            rel="noopener noreferrer"
            class="text-gold-700 font-semibold hover:text-gold-800"
          >
            @Choukran.Patisserie
          </a>
        </Card>
      </section>

      <section>
        <Card padding="lg" class="border border-gray-100">
          <div class="flex items-center justify-between gap-4 mb-4">
            <h2 class="font-display text-2xl font-bold text-gray-900">Horaires</h2>
            <span class="badge badge-primary">Mise à jour</span>
          </div>
          <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="font-medium text-gray-700">Lundi - Vendredi</span>
              <span class="text-gray-900 font-semibold">8h - 18h</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="font-medium text-gray-700">Samedi</span>
              <span class="text-gray-900 font-semibold">9h - 17h</span>
            </div>
            <div class="flex items-center justify-between py-2">
              <span class="font-medium text-gray-700">Dimanche</span>
              <span class="text-red-600 font-semibold">Fermé</span>
            </div>
          </div>
        </Card>
      </section>
    </div>
  </div>
</template>

<script setup>
import Card from '@/components/common/Card.vue'
</script>
