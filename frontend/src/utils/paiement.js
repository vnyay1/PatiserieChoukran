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
