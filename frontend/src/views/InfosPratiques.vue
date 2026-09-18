<!-- ===================================
PAGE INFOS PRATIQUES - Contact & Horaires
File: src/views/InfosPratiques.vue
=================================== -->

<template>
  <div class="pb-6">
    <section class="bg-gradient-peach">
      <div class="container mx-auto py-10 md:py-14">
        <p class="mb-2 text-sm font-bold uppercase tracking-wider text-gold-700">Infos pratiques</p>
        <h1>Nous contacter</h1>
        <p class="mt-3 max-w-2xl text-gray-700">
          Une question sur une commande, un événement ou une création personnalisée ? Notre équipe vous répond rapidement.
        </p>
      </div>
    </section>

    <div class="container mx-auto space-y-6 py-8 md:py-10">
      <ul class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <li v-for="contact in CONTACTS" :key="contact.titre" class="card flex items-start gap-4 p-5">
          <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-gold-100 text-gold-700">
            <component :is="contact.icone" :size="20" aria-hidden="true" />
          </span>
          <div class="min-w-0">
            <h2 class="font-body text-sm font-bold uppercase tracking-wider text-gray-600">{{ contact.titre }}</h2>
            <a
              v-if="contact.lien"
              :href="contact.lien"
              v-bind="contact.externe ? { target: '_blank', rel: 'noopener noreferrer' } : {}"
              class="lien mt-1 inline-block text-base"
            >
              {{ contact.valeur }}<span v-if="contact.externe" class="sr-only"> (nouvel onglet)</span>
            </a>
            <p v-else class="mt-1 font-semibold text-gray-900">{{ contact.valeur }}</p>
          </div>
        </li>
      </ul>

      <section class="card p-5 sm:p-7" aria-labelledby="titre-horaires">
        <h2 id="titre-horaires" class="flex items-center gap-2 text-xl">
          <Clock3 :size="22" class="text-gold-600" aria-hidden="true" />
          Horaires d'ouverture
        </h2>
        <dl class="mt-4 divide-y divide-gray-200">
          <div v-for="horaire in HORAIRES" :key="horaire.jours" class="flex items-center justify-between py-3">
            <dt class="font-medium text-gray-700">{{ horaire.jours }}</dt>
            <dd class="font-semibold" :class="horaire.ferme ? 'text-red-700' : 'text-gray-900'">{{ horaire.heures }}</dd>
          </div>
        </dl>
      </section>
    </div>
  </div>
</template>

<script setup>
import { Phone, MapPin, Instagram, Clock3 } from 'lucide-vue-next'

const CONTACTS = [
  { titre: 'Téléphone', valeur: '+237 658 55 56 00', lien: 'tel:+237658555600', icone: Phone },
  { titre: 'Adresse', valeur: 'Yaoundé, Olembé Échangeur', icone: MapPin },
  { titre: 'Instagram', valeur: '@Choukran.Patisserie', lien: 'https://instagram.com/Choukran.Patisserie', externe: true, icone: Instagram },
]

const HORAIRES = [
  { jours: 'Lundi – Vendredi', heures: '8 h – 18 h' },
  { jours: 'Samedi', heures: '9 h – 17 h' },
  { jours: 'Dimanche', heures: 'Fermé', ferme: true },
]
</script>
