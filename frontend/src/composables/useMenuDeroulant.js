// Menu déroulant accessible (bouton-disclosure) : aria-expanded + aria-controls sur le bouton,
// fermeture au clic ou au focus en dehors, Échap referme et rend le focus au bouton.
// Usage : const { ouvert, racine, bouton, idMenu, fermer, basculer } = useMenuDeroulant()
//         <div ref="racine"> <button ref="bouton" :aria-expanded="ouvert" :aria-controls="idMenu" @click="basculer">
//         <ul v-if="ouvert" :id="idMenu" @keydown.esc.stop="fermer(true)">
import { onBeforeUnmount, onMounted, ref, useId } from 'vue'

export function useMenuDeroulant () {
  const ouvert = ref(false)
  const racine = ref(null)
  const bouton = ref(null)
  const idMenu = useId()

  const fermer = (rendreFocus = false) => {
    ouvert.value = false
    if (rendreFocus) bouton.value?.focus()
  }

  const basculer = () => {
    ouvert.value = !ouvert.value
  }

  // Un clic ou un focus (Tab) hors du menu le referme
  const fermerSiExterieur = (event) => {
    if (ouvert.value && racine.value && !racine.value.contains(event.target)) fermer()
  }

  onMounted(() => {
    document.addEventListener('click', fermerSiExterieur)
    document.addEventListener('focusin', fermerSiExterieur)
  })

  onBeforeUnmount(() => {
    document.removeEventListener('click', fermerSiExterieur)
    document.removeEventListener('focusin', fermerSiExterieur)
  })

  return { ouvert, racine, bouton, idMenu, fermer, basculer }
}
