<!-- ===================================
3. FOOTER (Desktop)
File: src/components/layout/Footer.vue
=================================== -->

<template>
  <footer class="bg-white border-t border-gray-100 mt-20 hidden md:block">
    <div class="container mx-auto px-4 py-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- À propos -->
        <div>
          <img src="/logo.png" alt="Choukrane" class="h-12 w-auto mb-4" />
          <p class="text-sm text-gray-600 leading-relaxed">
            Chez Choukrane, chaque création est une promesse de douceur.
          </p>
        </div>

        <!-- Navigation -->
        <div>
          <h3 class="font-display font-semibold text-lg mb-4 text-gold-600">Navigation</h3>
          <ul class="space-y-2">
            <li v-for="item in footerNavItems" :key="item.to">
              <router-link
                :to="item.to"
                class="text-sm text-gray-600 hover:text-gold-600 transition-colors"
              >
                {{ item.label }}
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Contact -->
        <div>
          <h3 class="font-display font-semibold text-lg mb-4 text-gold-600">Contact</h3>
          <ul class="space-y-2 text-sm text-gray-600">
            <li class="flex items-center space-x-2">
              <Phone :size="16" class="text-gold-500" />
              <span>658555600</span>
            </li>
            <li class="flex items-center space-x-2">
              <MapPin :size="16" class="text-gold-500" />
              <span>Yaoundé - Olembé Échangeur</span>
            </li>
            <li class="flex items-center space-x-2">
              <Instagram :size="16" class="text-gold-500" />
              <a href="https://instagram.com/Choukran.Patisserie" target="_blank" class="hover:text-gold-600">
                @Choukran.Patisserie
              </a>
            </li>
          </ul>
        </div>

        <!-- Horaires -->
        <div>
          <h3 class="font-display font-semibold text-lg mb-4 text-gold-600">Horaires</h3>
          <ul class="space-y-1 text-sm text-gray-600">
            <li>Lun - Ven: 8h - 18h</li>
            <li>Samedi: 9h - 17h</li>
            <li>Dimanche: Fermé</li>
          </ul>
        </div>
      </div>

      <!-- Divider ornement -->
      <div class="divider-ornament"></div>

      <!-- Copyright -->
      <div class="text-center text-sm text-gray-500">
        <p>&copy; {{ currentYear }} Choukrane Pâtisserie. Tous droits réservés.</p>
        <p class="mt-1 ornament">✦ L'art de sublimer vos moments gourmands ✦</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'
import { Phone, MapPin, Instagram } from 'lucide-vue-next'

const currentYear = computed(() => new Date().getFullYear())

const footerNavItems = [
  { label: 'Accueil', to: '/' },
  { label: 'Nos Produits', to: '/produits' },
  { label: 'Mon Compte', to: '/profil' },
  { label: 'Mes Commandes', to: '/mes-commandes' },
]
</script>