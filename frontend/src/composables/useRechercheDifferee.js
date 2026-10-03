// Recherche au fil de la frappe : l'action part 400 ms après la dernière touche
// (une requête par pause, pas une par lettre). lancerMaintenant() court-circuite l'attente (Entrée).
import { onBeforeUnmount } from 'vue'

export function useRechercheDifferee (action, delai = 400) {
  let minuteur = null

  const annuler = () => clearTimeout(minuteur)

  const declencher = () => {
    annuler()
    minuteur = setTimeout(action, delai)
  }

  const lancerMaintenant = () => {
    annuler()
    action()
  }

  onBeforeUnmount(annuler)

  return { declencher, lancerMaintenant, annuler }
}
