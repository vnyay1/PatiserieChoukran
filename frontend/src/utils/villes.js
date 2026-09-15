// ===================================
// VILLES DESSERVIES
// File: src/utils/villes.js
// ===================================
// Mêmes valeurs que le backend (Quartier::VILLES, quartiers.ville, vendeur_villes.ville)

export const VILLES = [
  { valeur: 'yaoundé', libelle: 'Yaoundé' },
  { valeur: 'douala', libelle: 'Douala' },
]

const LIBELLES = Object.fromEntries(VILLES.map((ville) => [ville.valeur, ville.libelle]))

export const formatVille = (ville) => {
  if (!ville) return ''
  return LIBELLES[String(ville).toLowerCase()] || ville
}

export const estVilleConnue = (ville) => Boolean(ville && LIBELLES[ville])

// Ville d'une adresse : celle de son quartier (source des règles de livraison)
export const villeAdresse = (adresse) => adresse?.quartier_livraison?.ville || adresse?.ville || null

// « Maison — Carrefour Obili, Obili, Yaoundé »
export const libelleAdresse = (adresse) => {
  if (!adresse) return ''
  const lieu = [adresse.zone, adresse.quartier, formatVille(villeAdresse(adresse))].filter(Boolean).join(', ')
  return adresse.libelle ? `${adresse.libelle} — ${lieu}` : lieu
}
