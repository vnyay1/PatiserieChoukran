// Erreur d'un formulaire de gestion (tiroir) : affichée en tête du formulaire et focalisée à chaque
// échec, pour être vue (le tiroir défile jusqu'à elle) et lue, même quand on a défilé jusqu'au
// bouton d'envoi. Une même erreur signalée deux fois est focalisée deux fois.
//
//   const { erreur, alerte, signaler } = useErreurFormulaire()
//   <AlertMessage v-if="erreur" ref="alerte" type="error" tabindex="-1">{{ erreur }}</AlertMessage>
import { nextTick, ref } from 'vue'

export function useErreurFormulaire () {
  const erreur = ref('')
  const alerte = ref(null)

  const signaler = async (message) => {
    erreur.value = message
    await nextTick()
    alerte.value?.$el?.focus()
  }

  return { erreur, alerte, signaler }
}
