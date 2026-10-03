// ===================================
// RÈGLE DES NOUVEAUX MOTS DE PASSE
// File: src/utils/motDePasse.js
// ===================================
// Même règle que le backend (Password::min(8)) ; l'aide sous le champ suit la saisie.

export const LONGUEUR_MIN_MOT_DE_PASSE = 8

export const longueurSuffisante = (valeur) => String(valeur || '').length >= LONGUEUR_MIN_MOT_DE_PASSE

// « 8 caractères minimum. » puis « Encore 3 caractères… » puis « longueur suffisante »
export const aideNouveauMotDePasse = (valeur) => {
  const longueur = String(valeur || '').length
  if (longueur === 0) return `${LONGUEUR_MIN_MOT_DE_PASSE} caractères minimum.`
  const reste = LONGUEUR_MIN_MOT_DE_PASSE - longueur
  if (reste > 0) return `Encore ${reste} caractère${reste > 1 ? 's' : ''} (${LONGUEUR_MIN_MOT_DE_PASSE} minimum).`
  return `${LONGUEUR_MIN_MOT_DE_PASSE} caractères ou plus : longueur suffisante.`
}

// Rien tant que la confirmation est vide
export const aideConfirmation = (motDePasse, confirmation) => {
  if (!confirmation) return ''
  return confirmation === motDePasse
    ? 'Les deux mots de passe correspondent.'
    : 'Les deux mots de passe ne correspondent pas encore.'
}
