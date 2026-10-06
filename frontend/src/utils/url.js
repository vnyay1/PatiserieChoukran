// ===================================
// URL SÛRES
// File: src/utils/url.js
// ===================================

// Valeur insérée dans un chemin d'API (identifiant, slug, référence). vue-router décode
// les paramètres de route (%2F devient /) : sans encodage, un lien piégé
// « /produits/..%2Fadmin%2Fusers » enverrait une requête authentifiée ailleurs.
export const segment = (valeur) => encodeURIComponent(String(valeur))

// Chemin du site (« /page ») ou null : rien qui puisse mener vers un autre domaine
// (« //domaine », « /\domaine », URL absolue, javascript:…)
export const cheminInterne = (url) => {
  if (typeof url !== 'string' || !url.startsWith('/')) return null
  if (url.startsWith('//') || url.startsWith('/\\')) return null
  return url
}

// Page de paiement NotchPay en https (nom de domaine notchpay.co ou un sous-domaine) :
// on n'envoie jamais le client ailleurs, même si l'API renvoyait une autre adresse
export const estUrlPaiementSure = (url) => {
  if (typeof url !== 'string') return false
  try {
    const { protocol, hostname } = new URL(url)
    return protocol === 'https:' && (hostname === 'notchpay.co' || hostname.endsWith('.notchpay.co'))
  } catch {
    return false
  }
}
