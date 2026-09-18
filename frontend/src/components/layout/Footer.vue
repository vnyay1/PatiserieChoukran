<!-- ===================================
3. FOOTER (Responsive)
File: src/components/layout/Footer.vue
=================================== -->

<template>
  <footer class="bg-surface border-t border-gray-100 mt-12">
    <!-- Mobile accordions -->
    <div class="md:hidden px-4 py-8 space-y-4">
      <div class="flex items-center gap-3">
        <img src="/logo.png" alt="Choukrane" class="h-10 w-auto" loading="lazy" />
        <p class="text-sm text-gray-600">Chaque création est une promesse de douceur.</p>
      </div>

      <div class="divide-y divide-gray-100 rounded-2xl border border-gray-100 overflow-hidden">
        <details v-for="section in mobileSections" :key="section.title" class="group">
          <summary class="flex items-center justify-between px-4 py-3 bg-surface cursor-pointer">
            <span class="font-semibold text-gray-800">{{ section.title }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-200 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 9l6 6 6-6" />
            </svg>
          </summary>
          <div class="px-4 pb-4 space-y-2">
            <template v-if="section.type === 'links'">
              <router-link
                v-for="item in footerNavItems"
                :key="item.to"
                :to="item.to"
                class="block text-sm text-gray-600 hover:text-gold-600"
              >
                {{ item.label }}
              </router-link>
            </template>
            <template v-else-if="section.type === 'contact'">
              <div class="flex items-center gap-2 text-sm text-gray-600">
                <Phone :size="16" class="text-gold-500" />
                <a href="tel:+237658555600" class="hover:text-gold-600">+237 658 55 56 00</a>
              </div>
              <div class="flex items-center gap-2 text-sm text-gray-600">
                <MapPin :size="16" class="text-gold-500" />
                <span>Yaoundé - Olembé Échangeur</span>
              </div>
              <div class="flex items-center gap-2 text-sm text-gray-600">
                <Instagram :size="16" class="text-gold-500" />
                <a href="https://instagram.com/Choukran.Patisserie" target="_blank" rel="noopener noreferrer" class="hover:text-gold-600">
                  @Choukran.Patisserie
                </a>
              </div>
            </template>
            <template v-else-if="section.type === 'hours'">
              <p class="text-sm text-gray-600">Lun - Ven: 8h - 18h</p>
              <p class="text-sm text-gray-600">Samedi: 9h - 17h</p>
              <p class="text-sm text-gray-600">Dimanche: Fermé</p>
            </template>
          </div>
        </details>
      </div>

      <div class="text-center text-xs text-gray-500 pt-2">
        <p>&copy; {{ currentYear }} Choukrane Pâtisserie. Tous droits réservés.</p>
        <p class="mt-1 ornament">✦ L'art de sublimer vos moments gourmands ✦</p>
      </div>
    </div>

    <!-- Desktop grid -->
    <div class="hidden md:block container mx-auto px-4 py-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- À propos -->
        <div>
          <img src="/logo.png" alt="Choukrane" class="h-12 w-auto mb-4" loading="lazy" />
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
              <a href="tel:+237658555600" class="hover:text-gold-600">+237 658 55 56 00</a>
            </li>
            <li class="flex items-center space-x-2">
              <MapPin :size="16" class="text-gold-500" />
              <span>Yaoundé - Olembé Échangeur</span>
            </li>
            <li class="flex items-center space-x-2">
              <Instagram :size="16" class="text-gold-500" />
              <a href="https://instagram.com/Choukran.Patisserie" target="_blank" rel="noopener noreferrer" class="hover:text-gold-600">
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

      <div class="divider-ornament"></div>

      <div class="text-center text-sm text-gray-500">
        <p>&copy; {{ currentYear }} Choukrane Pâtisserie. Tous droits réservés.</p>
        <p class="mt-1 ornament">✦ L'art de sublimer vos moments gourmands ✦</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { Phone, MapPin, Instagram } from 'lucide-vue-next'

const authStore = useAuthStore()
const currentYear = computed(() => new Date().getFullYear())

// Liens adaptés au profil : pas de lien vers des pages qui redirigeraient
const footerNavItems = computed(() => {
  const items = [
    { label: 'Accueil', to: '/' },
    { label: 'Nos Produits', to: '/produits' },
  ]

  if (!authStore.isAuthenticated) {
    items.push({ label: 'Connexion', to: '/connexion' })
    items.push({ label: 'Créer un compte', to: '/inscription' })
    return items
  }

  items.push({ label: 'Mon Compte', to: '/profil' })
  if (authStore.isClient) {
    items.push({ label: 'Mes Commandes', to: '/mes-commandes' })
  } else {
    items.push({ label: 'Gestion des commandes', to: '/admin/commandes' })
  }
  return items
})

const mobileSections = [
  { title: 'Navigation', type: 'links' },
  { title: 'Contact', type: 'contact' },
  { title: 'Horaires', type: 'hours' },
]
</script>
