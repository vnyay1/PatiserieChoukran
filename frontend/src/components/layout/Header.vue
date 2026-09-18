<!-- ===================================
2. HEADER (Desktop & Mobile Top)
File: src/components/layout/Header.vue
=================================== -->
<!--
  Desktop : liens principaux + menu « Administration » / « Ma boutique » (au lieu de dix liens),
  thème, notifications, panier, compte. Mobile : notifications, panier et menu en tiroir (BaseModal).
  Page active : aria-current="page" + filet or sous le lien (pas seulement la couleur).
-->
<template>
  <header class="safe-top sticky top-0 z-40 border-b border-gray-200/80 bg-surface/90 backdrop-blur-md supports-[backdrop-filter]:bg-surface/80">
    <div class="container mx-auto">
      <div class="flex h-14 items-center justify-between gap-3 md:h-[4.5rem]">
        <!-- Logo et ville du client (le catalogue est filtré par ville) -->
        <div class="flex min-w-0 items-center gap-2 md:gap-4">
          <router-link to="/" class="flex-shrink-0 rounded-lg" aria-label="Choukrane Pâtisserie, accueil">
            <img src="/logo.png" alt="" class="h-9 w-auto md:h-11" />
          </router-link>
          <VilleSelecteur v-if="!authStore.isAdmin && !authStore.isVendeur" />
        </div>

        <!-- Navigation desktop -->
        <nav class="hidden md:block" aria-label="Navigation principale">
          <ul class="flex items-center gap-1 lg:gap-2">
            <li v-for="lien in liensPrincipaux" :key="lien.name">
              <router-link
                :to="lien.to"
                class="lien-nav"
                :aria-current="estActif(lien) ? 'page' : undefined"
              >
                {{ lien.label }}
                <span v-if="showVendeurCommandesBadge(lien.name)" class="badge-compteur ring-0">
                  {{ formatBadgeCount(vendeurCommandesCount) }}
                  <span class="sr-only">à traiter</span>
                </span>
              </router-link>
            </li>

            <!-- Pages de gestion regroupées -->
            <li v-if="menuGestion" ref="menuRacine" class="relative">
              <button
                ref="menuBouton"
                type="button"
                class="lien-nav"
                :class="{ 'lien-nav-actif': gestionActive }"
                :aria-expanded="menuOuvert"
                :aria-controls="idMenuGestion"
                @click="menuOuvert = !menuOuvert"
              >
                {{ menuGestion.titre }}
                <ChevronDown :size="16" class="transition-transform duration-200" :class="{ 'rotate-180': menuOuvert }" aria-hidden="true" />
              </button>

              <Transition name="deroulant">
                <ul
                  v-if="menuOuvert"
                  :id="idMenuGestion"
                  class="absolute left-0 top-full z-50 mt-2 w-60 rounded-2xl border border-gray-200 bg-surface p-1.5 shadow-elegant-lg"
                  @keydown.esc.stop="fermerMenu(true)"
                >
                  <li v-for="lien in menuGestion.liens" :key="lien.name">
                    <router-link
                      :to="lien.to"
                      class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900
                             aria-[current=page]:bg-gold-100 aria-[current=page]:font-semibold aria-[current=page]:text-gold-800"
                      :aria-current="estActif(lien) ? 'page' : undefined"
                    >
                      <component :is="lien.icone" :size="18" aria-hidden="true" />
                      {{ lien.label }}
                    </router-link>
                  </li>
                </ul>
              </Transition>
            </li>
          </ul>
        </nav>

        <!-- Actions -->
        <div class="flex items-center gap-1 md:gap-2">
          <ThemeToggle class="hidden md:block" />

          <router-link
            v-if="authStore.isAuthenticated"
            to="/notifications"
            class="btn-icone relative"
            :class="{ 'bg-gold-100 text-gold-800': route.name === 'notifications' }"
            :aria-label="libelleNotifications"
            :aria-current="route.name === 'notifications' ? 'page' : undefined"
          >
            <Bell :size="22" aria-hidden="true" />
            <span v-if="unreadNotificationsCount > 0" class="badge-compteur absolute -right-0.5 -top-0.5" aria-hidden="true">
              {{ formatCompteur(unreadNotificationsCount) }}
            </span>
          </router-link>

          <router-link
            v-if="authStore.isClient"
            to="/panier"
            class="btn-icone relative"
            :class="{ 'bg-gold-100 text-gold-800': ['panier', 'checkout'].includes(route.name) }"
            :aria-label="libellePanier"
            :aria-current="route.name === 'panier' ? 'page' : undefined"
          >
            <ShoppingCart :size="22" aria-hidden="true" />
            <span v-if="panierCount > 0" class="badge-compteur absolute -right-0.5 -top-0.5" aria-hidden="true">
              {{ formatCompteur(panierCount) }}
            </span>
          </router-link>

          <!-- Compte (desktop) -->
          <router-link
            v-if="authStore.isAuthenticated"
            to="/profil"
            class="ml-1 hidden min-h-11 items-center gap-2 rounded-full border border-gray-200 py-1 pl-1 pr-1 text-sm font-semibold text-gray-800 transition-colors hover:border-gray-300 hover:bg-gray-50 md:inline-flex lg:pr-3"
            :aria-current="route.name === 'profil' ? 'page' : undefined"
          >
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold-500 text-xs font-bold text-on-gold" aria-hidden="true">
              {{ initiales }}
            </span>
            <span class="hidden max-w-[10rem] truncate lg:inline">{{ authStore.userName }}</span>
            <span class="sr-only lg:hidden">Mon compte</span>
          </router-link>

          <template v-else>
            <router-link to="/connexion" class="btn-ghost btn-sm hidden md:inline-flex">
              Connexion
            </router-link>
            <router-link to="/inscription" class="btn-primary btn-sm hidden md:inline-flex">
              Créer un compte
            </router-link>
          </template>

          <!-- Menu mobile -->
          <button
            type="button"
            class="btn-icone md:hidden"
            aria-label="Ouvrir le menu"
            :aria-expanded="mobileOpen"
            @click="mobileOpen = true"
          >
            <Menu :size="24" aria-hidden="true" />
          </button>
        </div>
      </div>
    </div>

    <!-- Tiroir mobile -->
    <BaseModal
      :ouvert="mobileOpen"
      titre="Menu"
      variante="tiroir"
      libelle-fermer="Fermer le menu"
      @fermer="mobileOpen = false"
    >
      <div class="space-y-6">
        <router-link
          v-if="authStore.isAuthenticated"
          to="/profil"
          class="flex items-center gap-3 rounded-2xl bg-gold-50 p-3 transition-colors hover:bg-gold-100"
        >
          <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-gold-500 font-bold text-on-gold" aria-hidden="true">
            {{ initiales }}
          </span>
          <span class="min-w-0">
            <span class="block truncate font-semibold text-gray-900">{{ authStore.userName }}</span>
            <span class="block text-sm text-gray-600">Voir mon compte</span>
          </span>
        </router-link>

        <div v-else class="grid grid-cols-2 gap-3">
          <router-link to="/connexion" class="btn-outline">Connexion</router-link>
          <router-link to="/inscription" class="btn-primary">S'inscrire</router-link>
        </div>

        <nav aria-label="Navigation principale (mobile)">
          <ul class="space-y-1">
            <li v-for="lien in liensPrincipaux" :key="lien.name">
              <router-link :to="lien.to" class="lien-tiroir" :aria-current="estActif(lien) ? 'page' : undefined">
                <component :is="lien.icone" :size="20" aria-hidden="true" />
                <span class="flex-1">{{ lien.label }}</span>
                <span v-if="showVendeurCommandesBadge(lien.name)" class="badge-compteur ring-0">
                  {{ formatBadgeCount(vendeurCommandesCount) }}
                  <span class="sr-only">à traiter</span>
                </span>
              </router-link>
            </li>
          </ul>

          <template v-if="menuGestion">
            <h3 class="mb-2 mt-5 px-3 font-body text-xs font-bold uppercase tracking-wider text-gray-500">
              {{ menuGestion.titre }}
            </h3>
            <ul class="space-y-1">
              <li v-for="lien in menuGestion.liens" :key="lien.name">
                <router-link :to="lien.to" class="lien-tiroir" :aria-current="estActif(lien) ? 'page' : undefined">
                  <component :is="lien.icone" :size="20" aria-hidden="true" />
                  <span class="flex-1">{{ lien.label }}</span>
                </router-link>
              </li>
            </ul>
          </template>
        </nav>

        <div class="space-y-4 border-t border-gray-200 pt-5">
          <ThemeToggle variante="segments" />

          <router-link to="/infos-pratiques" class="lien-tiroir">
            <Info :size="20" aria-hidden="true" />
            <span>Contact et horaires</span>
          </router-link>
        </div>
      </div>
    </BaseModal>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import { useNotificationsStore } from '@/stores/notifications'
import { useVendeurCommandesBadge } from '@/composables/useVendeurCommandesBadge'
import { useNavigation } from '@/composables/useNavigation'
import VilleSelecteur from '@/components/layout/VilleSelecteur.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import BaseModal from '@/components/common/BaseModal.vue'
import { ShoppingCart, Info, Bell, Menu, ChevronDown } from 'lucide-vue-next'

const route = useRoute()
const authStore = useAuthStore()
const panierStore = usePanierStore()
const notificationsStore = useNotificationsStore()
const { vendeurCommandesCount, formatBadgeCount, showVendeurCommandesBadge } = useVendeurCommandesBadge()
const { liensPrincipaux, menuGestion, estActif, gestionActive } = useNavigation()

const mobileOpen = ref(false)
const menuOuvert = ref(false)
const menuRacine = ref(null)
const menuBouton = ref(null)
const idMenuGestion = useId()

const panierCount = computed(() => panierStore.itemCount)
const unreadNotificationsCount = computed(() => notificationsStore.unreadCount)

const formatCompteur = (count) => (count > 99 ? '99+' : count)

// Le nombre est lu avec le libellé : « Notifications, 3 non lues »
const libelleNotifications = computed(() => {
  const count = unreadNotificationsCount.value
  if (count === 0) return 'Notifications'
  return `Notifications, ${count} non lue${count > 1 ? 's' : ''}`
})

const libellePanier = computed(() => {
  const count = panierCount.value
  if (count === 0) return 'Panier, vide'
  return `Panier, ${count} article${count > 1 ? 's' : ''}`
})

const initiales = computed(() => {
  const mots = (authStore.userName || '').trim().split(/\s+/).filter(Boolean)
  return (mots.slice(0, 2).map((mot) => mot[0]).join('') || '?').toUpperCase()
})

const fermerMenu = (rendreFocus = false) => {
  menuOuvert.value = false
  if (rendreFocus) menuBouton.value?.focus()
}

// Clic ou focus en dehors du menu de gestion : il se referme
const fermerMenuSiExterieur = (event) => {
  if (menuOuvert.value && menuRacine.value && !menuRacine.value.contains(event.target)) fermerMenu()
}

// Le compteur de notifications suit l'utilisateur connecté (et non la simple présence
// d'un token : l'utilisateur est chargé après le premier rendu)
watch(
  () => authStore.user?.id,
  (userId) => {
    if (userId) {
      notificationsStore.startPolling()
      return
    }

    notificationsStore.stopPolling({ oublier: true })
    notificationsStore.reset()
  },
  { immediate: true }
)

// Changer de page ne déclenche aucun appel réseau (le sondage partagé s'en charge)
watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false
    menuOuvert.value = false
  }
)

onMounted(() => {
  document.addEventListener('click', fermerMenuSiExterieur)
  document.addEventListener('focusin', fermerMenuSiExterieur)
})

onBeforeUnmount(() => {
  notificationsStore.stopPolling()
  document.removeEventListener('click', fermerMenuSiExterieur)
  document.removeEventListener('focusin', fermerMenuSiExterieur)
})
</script>

<style scoped>
.lien-nav {
  @apply relative inline-flex min-h-11 items-center gap-1.5 rounded-full px-3 text-[0.9375rem] font-semibold text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900;
}

/* Filet or sous la page active : l'état ne repose pas que sur la couleur */
.lien-nav[aria-current='page'],
.lien-nav-actif {
  @apply text-gray-900;
}

.lien-nav[aria-current='page']::after,
.lien-nav-actif::after {
  content: '';
  @apply absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-gold-500;
}

.lien-tiroir {
  @apply flex min-h-12 items-center gap-3 rounded-xl px-3 font-medium text-gray-800 transition-colors hover:bg-gray-100;
}

.lien-tiroir[aria-current='page'] {
  @apply bg-gold-100 font-semibold text-gold-800;
}

.deroulant-enter-active,
.deroulant-leave-active {
  transition: opacity 0.15s ease, transform 0.15s cubic-bezier(0.2, 0, 0, 1);
}

.deroulant-enter-from,
.deroulant-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
