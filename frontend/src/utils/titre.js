// ===================================
// TITRE DE L'ONGLET
// File: src/utils/titre.js
// ===================================
// « Mon panier · Choukrane Pâtisserie » ; l'accueil garde la phrase complète d'index.html.

export const NOM_SITE = 'Choukrane Pâtisserie'
export const TITRE_ACCUEIL = `${NOM_SITE} : gâteaux et glaces livrés à Yaoundé et Douala`

export const titrePage = (titre) => (titre ? `${titre} · ${NOM_SITE}` : TITRE_ACCUEIL)
