<!-- ===================================
3. FOOTER (Responsive)
File: src/components/layout/Footer.vue
=================================== -->
<template>
  <footer class="mt-16 border-t border-gray-200 bg-surface">
    <div class="container mx-auto py-10 md:py-14">
      <div class="grid grid-cols-1 gap-8 md:grid-cols-4 md:gap-10">
        <!-- À propos -->
        <div class="md:pr-4">
          <img src="/logo.png" alt="Choukrane Pâtisserie" class="mb-4 h-11 w-auto" loading="lazy" />
          <p class="text-sm leading-relaxed text-gray-600">
            Chaque création est une promesse de douceur. Pâtisseries, gâteaux et glaces
            livrés à Yaoundé et Douala.
          </p>
        </div>

        <!-- Sections : accordéons sur mobile, colonnes dès md -->
        <component
          :is="estMobile ? 'details' : 'div'"
          v-for="section in SECTIONS"
          :key="section.titre"
          class="group border-gray-200 max-md:rounded-2xl max-md:border"
        >
          <component
            :is="estMobile ? 'summary' : 'div'"
            class="flex items-center justify-between max-md:min-h-12 max-md:cursor-pointer max-md:list-none max-md:px-4 max-md:[&::-webkit-details-marker]:hidden"
          >
            <h2 class="font-body text-sm font-bold uppercase tracking-wider text-gray-900 md:mb-4">
              {{ section.titre }}
            </h2>
            <ChevronDown :size="18" class="text-gray-500 transition-transform duration-200 group-open:rotate-180 md:hidden" aria-hidden="true" />
          </component>

          <div class="max-md:px-4 max-md:pb-4">
            <ul v-if="section.type === 'liens'" class="space-y-1">
              <li v-for="item in footerNavItems" :key="item.to">
                <router-link :to="item.to" class="lien-pied">{{ item.label }}</router-link>
              </li>
            </ul>

            <address v-else-if="section.type === 'contact'" class="space-y-1 not-italic">
              <a href="tel:+237658555600" class="lien-pied">
                <Phone :size="16" class="text-gold-600" aria-hidden="true" />
                <span><span class="sr-only">Téléphone : </span>+237 658 55 56 00</span>
              </a>
              <p class="flex min-h-9 items-center gap-2 text-sm text-gray-600">
                <MapPin :size="16" class="flex-shrink-0 text-gold-600" aria-hidden="true" />
                Yaoundé, Olembé Échangeur
              </p>
              <a href="https://instagram.com/Choukran.Patisserie" target="_blank" rel="noopener noreferrer" class="lien-pied">
                <Instagram :size="16" class="text-gold-600" aria-hidden="true" />
                <span>@Choukran.Patisserie<span class="sr-only"> sur Instagram (nouvel onglet)</span></span>
              </a>
            </address>

            <dl v-else class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm text-gray-600">
              <template v-for="horaire in HORAIRES" :key="horaire.jours">
                <dt class="font-medium text-gray-800">{{ horaire.jours }}</dt>
                <dd>{{ horaire.heures }}</dd>
              </template>
            </dl>
          </div>
        </component>
      </div>

      <div class="divider-ornament my-8"></div>

      <div class="flex flex-col items-center justify-between gap-2 text-center text-sm text-gray-600 md:flex-row md:text-left">
        <p>&copy; {{ currentYear }} Choukrane Pâtisserie. Tous droits réservés.</p>
        <p class="font-display italic text-gold-700">L'art de sublimer vos moments gourmands</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { Phone, MapPin, Instagram, ChevronDown } from 'lucide-vue-next'

const authStore = useAuthStore()
const currentYear = new Date().getFullYear()

const SECTIONS = [
  { titre: 'Navigation', type: 'liens' },
  { titre: 'Contact', type: 'contact' },
  { titre: 'Horaires', type: 'horaires' },
]

const HORAIRES = [
  { jours: 'Lun – Ven', heures: '8 h – 18 h' },
  { jours: 'Samedi', heures: '9 h – 17 h' },
  { jours: 'Dimanche', heures: 'Fermé' },
]

// Accordéons (<details>) sous md seulement : un seul balisage pour les deux tailles
const requete = window.matchMedia('(max-width: 767px)')
const estMobile = ref(requete.matches)
const suivre = (event) => {
  estMobile.value = event.matches
}
onMounted(() => requete.addEventListener('change', suivre))
onBeforeUnmount(() => requete.removeEventListener('change', suivre))

// Liens adaptés au profil : pas de lien vers des pages qui redirigeraient
const footerNavItems = computed(() => {
  const items = [
    { label: 'Accueil', to: '/' },
    { label: 'Nos produits', to: '/produits' },
  ]

  if (!authStore.isAuthenticated) {
    items.push({ label: 'Connexion', to: '/connexion' })
    items.push({ label: 'Créer un compte', to: '/inscription' })
    return items
  }

  items.push({ label: 'Mon compte', to: '/profil' })
  if (authStore.isClient) {
    items.push({ label: 'Mes commandes', to: '/mes-commandes' })
  } else {
    items.push({ label: 'Gestion des commandes', to: '/admin/commandes' })
  }
  return items
})
</script>

<style scoped>
.lien-pied {
  @apply inline-flex min-h-9 items-center gap-2 text-sm text-gray-600 underline-offset-4 transition-colors hover:text-gray-900 hover:underline;
}
</style>
