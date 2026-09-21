<!-- ===================================
COMPOSANT MODALE ACCESSIBLE
File: src/components/common/BaseModal.vue
=================================== -->
<!--
  Toutes les fenêtres (confirmation, adresse, filtres, menu mobile…) passent par ici :
  - role="dialog" + aria-modal, titre relié par aria-labelledby ;
  - focus piégé dans la fenêtre, rendu à l'élément déclencheur à la fermeture ;
  - Échap et clic sur le fond ferment (sauf `fermable: false`) ;
  - le reste de l'application (#app) devient inerte et ne défile plus.
  Variantes : `centre` (défaut), `feuille` (tiroir bas sur mobile, centrée dès sm), `tiroir` (panneau droit).
  Focus initial : l'élément marqué `data-autofocus`, sinon la fenêtre elle-même (le titre est lu).
-->
<template>
  <Teleport to="body">
    <Transition :name="`modale-${variante}`" appear @after-leave="$emit('apres-fermeture')">
      <div
        v-if="ouvert"
        class="fixed inset-0 flex"
        :class="POSITIONS[variante]"
        :style="{ zIndex: 70 + profondeur }"
      >
        <div class="modale-fond absolute inset-0 bg-black/55" aria-hidden="true" @click="fermerParFond"></div>

        <div
          ref="panneau"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="idTitre"
          :aria-describedby="description ? idDescription : undefined"
          tabindex="-1"
          class="modale-panneau relative flex w-full flex-col bg-surface shadow-elegant-lg"
          :class="[PANNEAUX[variante], LARGEURS[taille]]"
        >
          <header class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4 sm:px-6">
            <div class="min-w-0 pt-1.5">
              <h2 :id="idTitre" class="font-display text-xl font-bold leading-snug text-gray-900">
                <slot name="titre">{{ titre }}</slot>
              </h2>
              <p v-if="description" :id="idDescription" class="mt-1 text-sm text-gray-600">
                {{ description }}
              </p>
            </div>
            <button
              v-if="fermable"
              type="button"
              class="btn-icone -mr-2"
              :aria-label="libelleFermer"
              @click="fermer"
            >
              <X :size="20" aria-hidden="true" />
            </button>
          </header>

          <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-5 sm:px-6">
            <slot />
          </div>

          <footer
            v-if="$slots.actions"
            class="safe-bottom flex flex-col-reverse gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:justify-end sm:px-6"
          >
            <slot name="actions" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
// Pile partagée par toutes les modales : seule la dernière ouverte gère Tab et Échap
const pile = []

const definirInerte = () => {
  const app = document.getElementById('app')
  if (!app) return
  app.inert = pile.length > 0
  document.documentElement.style.overflow = pile.length > 0 ? 'hidden' : ''
}
</script>

<script setup>
import { ref, watch, nextTick, onBeforeUnmount, useId } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
  ouvert: {
    type: Boolean,
    default: true
  },
  titre: {
    type: String,
    default: ''
  },
  description: {
    type: String,
    default: ''
  },
  variante: {
    type: String,
    default: 'centre',
    validator: (valeur) => ['centre', 'feuille', 'tiroir'].includes(valeur)
  },
  taille: {
    type: String,
    default: 'md',
    validator: (valeur) => ['sm', 'md', 'lg', 'xl'].includes(valeur)
  },
  // false : ni Échap, ni fond, ni bouton de fermeture (choix obligatoire)
  fermable: {
    type: Boolean,
    default: true
  },
  libelleFermer: {
    type: String,
    default: 'Fermer'
  }
})

const emit = defineEmits(['fermer', 'apres-fermeture'])

const POSITIONS = {
  centre: 'items-center justify-center p-4',
  feuille: 'items-end justify-center sm:items-center sm:p-4',
  tiroir: 'items-stretch justify-end',
}

const PANNEAUX = {
  centre: 'max-h-[calc(100dvh-2rem)] rounded-elegant',
  feuille: 'max-h-[88dvh] rounded-t-[1.5rem] sm:max-h-[calc(100dvh-2rem)] sm:rounded-elegant',
  tiroir: 'h-full max-w-[22rem] safe-top',
}

const LARGEURS = {
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-lg',
  lg: 'sm:max-w-2xl',
  xl: 'sm:max-w-4xl',
}

const idTitre = useId()
const idDescription = useId()
const panneau = ref(null)
const profondeur = ref(0)
const jeton = Symbol('modale')
let declencheur = null

const SELECTEUR_FOCUSABLE = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled]):not([type="hidden"])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

const focusables = () => [...(panneau.value?.querySelectorAll(SELECTEUR_FOCUSABLE) || [])]
  .filter((element) => element.getClientRects().length > 0)

const estAuSommet = () => pile[pile.length - 1] === jeton

const fermer = () => {
  if (props.fermable) emit('fermer')
}

const fermerParFond = () => fermer()

const surTouche = (event) => {
  if (!estAuSommet()) return

  if (event.key === 'Escape') {
    event.stopPropagation()
    fermer()
    return
  }

  if (event.key !== 'Tab') return

  const elements = focusables()
  if (elements.length === 0) {
    event.preventDefault()
    panneau.value?.focus()
    return
  }

  const premier = elements[0]
  const dernier = elements[elements.length - 1]
  const actif = document.activeElement

  if (event.shiftKey && (actif === premier || actif === panneau.value)) {
    event.preventDefault()
    dernier.focus()
  } else if (!event.shiftKey && actif === dernier) {
    event.preventDefault()
    premier.focus()
  }
}

const ouvrir = async () => {
  if (pile.includes(jeton)) return
  declencheur = document.activeElement
  pile.push(jeton)
  profondeur.value = pile.length
  definirInerte()
  document.addEventListener('keydown', surTouche, true)

  await nextTick()
  const cible = panneau.value?.querySelector('[data-autofocus]') || panneau.value
  cible?.focus({ preventScroll: true })
}

const liberer = async () => {
  const index = pile.indexOf(jeton)
  if (index === -1) return
  pile.splice(index, 1)
  definirInerte()
  document.removeEventListener('keydown', surTouche, true)

  // Le focus ne reste jamais dans une fenêtre en cours de fermeture (animation de sortie,
  // onglet en arrière-plan) : au pire il retombe sur <body>.
  if (panneau.value?.contains(document.activeElement)) {
    document.activeElement.blur()
  }

  // Retour au bouton qui a ouvert la fenêtre, s'il est encore affiché.
  // Après nextTick : la fermeture fait souvent re-rendre le parent (liste, formulaire),
  // et un focus rendu avant ce rendu serait perdu.
  const cible = declencheur
  declencheur = null
  await nextTick()
  if (cible && document.contains(cible) && typeof cible.focus === 'function') {
    cible.focus({ preventScroll: true })
  }
}

watch(
  () => props.ouvert,
  (ouvert) => (ouvert ? ouvrir() : liberer()),
  { immediate: true }
)

onBeforeUnmount(liberer)
</script>

<style scoped>
/* Fond : fondu ; panneau : léger zoom (centre), glissement (feuille, tiroir) */
.modale-centre-enter-active,
.modale-centre-leave-active,
.modale-feuille-enter-active,
.modale-feuille-leave-active,
.modale-tiroir-enter-active,
.modale-tiroir-leave-active {
  transition: opacity 0.2s cubic-bezier(0.2, 0, 0, 1);
}

.modale-centre-enter-active .modale-panneau,
.modale-centre-leave-active .modale-panneau,
.modale-feuille-enter-active .modale-panneau,
.modale-feuille-leave-active .modale-panneau,
.modale-tiroir-enter-active .modale-panneau,
.modale-tiroir-leave-active .modale-panneau {
  transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1), opacity 0.2s ease;
}

.modale-centre-enter-from,
.modale-centre-leave-to,
.modale-feuille-enter-from,
.modale-feuille-leave-to,
.modale-tiroir-enter-from,
.modale-tiroir-leave-to {
  opacity: 0;
}

.modale-centre-enter-from .modale-panneau,
.modale-centre-leave-to .modale-panneau {
  transform: translateY(8px) scale(0.97);
}

.modale-feuille-enter-from .modale-panneau,
.modale-feuille-leave-to .modale-panneau {
  transform: translateY(100%);
}

@media (min-width: 640px) {
  .modale-feuille-enter-from .modale-panneau,
  .modale-feuille-leave-to .modale-panneau {
    transform: translateY(8px) scale(0.97);
  }
}

.modale-tiroir-enter-from .modale-panneau,
.modale-tiroir-leave-to .modale-panneau {
  transform: translateX(100%);
}
</style>
