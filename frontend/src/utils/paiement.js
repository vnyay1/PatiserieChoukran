// Référence du dernier paiement NotchPay ouvert : retrouvée au retour même si
// NotchPay n'ajoute à l'URL que sa propre référence
const CLE = 'choukrane_paiement_reference'

export const memoriserReferencePaiement = (reference) => {
  try {
    sessionStorage.setItem(CLE, reference)
  } catch {
    // Stockage indisponible (navigation privée) : l'URL de retour suffit
  }
}

export const referencePaiementMemorisee = () => {
  try {
    return sessionStorage.getItem(CLE)
  } catch {
    return null
  }
}

export const oublierReferencePaiement = () => {
  try {
    sessionStorage.removeItem(CLE)
  } catch {
    // rien à oublier
  }
}

// Explication affichée par PaiementRetour.vue pour les états sans texte dédié. « Aucun montant
// débité » seulement quand NotchPay l'a établi (refus, annulation, expiration) : pendant la
// vérification, le client vient peut-être de payer et ne doit pas être invité à repayer.
export const messageRetourPaiement = (etat, erreur = '') => {
  if (erreur) {
    return erreur
  }
  if (etat === 'verification') {
    return 'Validez la demande sur votre téléphone si ce n\'est pas encore fait. Cette page se met à jour toute seule.'
  }
  if (etat === 'en_attente') {
    return 'NotchPay n\'a pas encore confirmé la transaction. Vous pouvez revenir plus tard : la commande sera marquée payée dès la confirmation.'
  }
  if (['echec', 'annule', 'expire'].includes(etat)) {
    return 'Aucun montant n\'a été débité. Vous pouvez relancer le paiement depuis le détail de la commande.'
  }
  return 'Retrouvez vos commandes et l\'état de leur paiement dans « Mes commandes ».'
}
