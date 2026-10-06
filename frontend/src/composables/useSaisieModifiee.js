// Formulaire modifié depuis son ouverture ? figer() prend un instantané à l'ouverture ; `modifie`
// passe à BaseModal, qui demande alors confirmation avant qu'Échap, le fond ou × n'effacent la saisie.
//
//   const { figer, modifie } = useSaisieModifiee(() => ({ ...form.value, image: Boolean(fichier.value) }))
//   const ouvrir = () => { …; figer() }
//   <BaseModal :modifie="modifie" …>
import { computed, ref } from 'vue'

export function useSaisieModifiee (lire) {
  const instantane = ref(null)

  const figer = () => {
    instantane.value = JSON.stringify(lire())
  }

  const modifie = computed(() => instantane.value !== null && JSON.stringify(lire()) !== instantane.value)

  return { figer, modifie }
}
