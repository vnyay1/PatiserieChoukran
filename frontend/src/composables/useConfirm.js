// ===================================
// COMPOSABLE BOÎTE DE CONFIRMATION
// File: src/composables/useConfirm.js
// ===================================
// Remplace window.confirm / window.prompt par <ConfirmDialog> (monté une fois dans App.vue).
//   const { confirmer, demander } = useConfirm()
//   if (await confirmer({ message: 'Supprimer ?', danger: true })) { ... }
//   const reference = await demander({ titre: 'Paiement', champ: 'Référence' })  // null si annulé

import { reactive } from 'vue'

export const etatConfirmation = reactive({
  ouvert: false,
  titre: '',
  message: '',
  libelleConfirmer: 'Confirmer',
  libelleAnnuler: 'Annuler',
  danger: false,
  champ: null,
  placeholder: '',
  valeur: '',
  resoudre: null,
})

const ouvrir = (options, champ, resoudre) => {
  // Une boîte déjà ouverte est considérée comme annulée
  etatConfirmation.resoudre?.(null)

  Object.assign(etatConfirmation, {
    ouvert: true,
    titre: options.titre || 'Confirmation',
    message: options.message || '',
    libelleConfirmer: options.libelleConfirmer || 'Confirmer',
    libelleAnnuler: options.libelleAnnuler || 'Annuler',
    danger: Boolean(options.danger),
    champ,
    placeholder: options.placeholder || '',
    valeur: options.valeurInitiale || '',
    resoudre,
  })
}

export const fermerConfirmation = (resultat) => {
  const resoudre = etatConfirmation.resoudre
  etatConfirmation.ouvert = false
  etatConfirmation.resoudre = null
  resoudre?.(resultat)
}

export const useConfirm = () => {
  // Promise<boolean>
  const confirmer = (options = {}) => new Promise((resolve) => {
    ouvrir(options, null, (resultat) => resolve(resultat === true))
  })

  // Promise<string|null> : la saisie, ou null si annulé
  const demander = (options = {}) => new Promise((resolve) => {
    ouvrir(options, options.champ || 'Valeur', (resultat) => {
      resolve(resultat === null || resultat === false ? null : String(resultat))
    })
  })

  return { confirmer, demander }
}
