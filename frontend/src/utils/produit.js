// ===================================
// RÈGLES D'AFFICHAGE D'UN PRODUIT (carte, fiche, badges)
// File: src/utils/produit.js
// ===================================

// Indisponible : retiré de la vente ou stock épuisé
export const estIndisponible = (produit) => !produit?.est_disponible || produit?.stock_disponible === 0

// Remise en pour cent, arrondie (0 sans prix promotionnel)
export const pourcentageReduction = (produit) => {
  const prix = Number(produit?.prix_unitaire) || 0
  const promo = Number(produit?.prix_promo) || 0
  if (!promo || !prix) return 0
  return Math.round(((prix - promo) / prix) * 100)
}
