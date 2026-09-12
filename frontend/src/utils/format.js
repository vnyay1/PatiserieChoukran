// ===================================
// UTILITAIRES DE FORMATAGE ET LIBELLÉS
// File: src/utils/format.js
// ===================================

export const formatPrice = (valeur) => new Intl.NumberFormat('fr-FR').format(Number(valeur) || 0)

// "AAAA-MM-JJ" (date sans heure) est lue comme une date locale, pas comme minuit UTC
const versDate = (valeur) => {
  if (!valeur) return null
  const date = typeof valeur === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(valeur)
    ? new Date(`${valeur}T00:00:00`)
    : new Date(valeur)
  return Number.isNaN(date.getTime()) ? null : date
}

export const formatDate = (valeur, options = { day: '2-digit', month: 'short', year: 'numeric' }) => {
  const date = versDate(valeur)
  return date ? date.toLocaleDateString('fr-FR', options) : ''
}

export const formatDateLongue = (valeur) => formatDate(valeur, { day: 'numeric', month: 'long', year: 'numeric' })

export const formatDateHeure = (valeur) => {
  const date = versDate(valeur)
  return date
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
    : ''
}

// "14:30:00" -> "14:30" (colonne TIME renvoyée avec les secondes)
export const formatHeure = (heure) => (heure ? String(heure).slice(0, 5) : '')

// AAAA-MM-JJ dans le fuseau du navigateur (toISOString donnerait la date UTC,
// décalée d'un jour en soirée/matinée selon le fuseau)
export const dateIso = (date) => {
  const mois = String(date.getMonth() + 1).padStart(2, '0')
  const jour = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${mois}-${jour}`
}

export const aujourdhuiIso = () => dateIso(new Date())

export const STATUTS_COMMANDE = {
  en_attente: { label: 'En attente', classe: 'bg-yellow-100 text-yellow-700' },
  confirmee: { label: 'Confirmée', classe: 'bg-blue-100 text-blue-700' },
  en_preparation: { label: 'En préparation', classe: 'bg-purple-100 text-purple-700' },
  prete: { label: 'Prête', classe: 'bg-indigo-100 text-indigo-700' },
  en_livraison: { label: 'En livraison', classe: 'bg-orange-100 text-orange-700' },
  livree: { label: 'Livrée', classe: 'bg-green-100 text-green-700' },
  annulee: { label: 'Annulée', classe: 'bg-red-100 text-red-700' },
}

export const STATUTS_PAIEMENT = {
  en_attente: { label: 'À payer', classe: 'bg-yellow-100 text-yellow-700' },
  paye: { label: 'Payé', classe: 'bg-green-100 text-green-700' },
  echec: { label: 'Échec', classe: 'bg-red-100 text-red-700' },
  rembourse: { label: 'Remboursé', classe: 'bg-gray-100 text-gray-700' },
}

export const MOYENS_PAIEMENT = {
  orange_money: 'Orange Money',
  mtn_momo: 'MTN Mobile Money',
  especes: 'Espèces',
}

export const libelleStatut = (statut) => STATUTS_COMMANDE[statut]?.label || statut
export const classeStatut = (statut) => STATUTS_COMMANDE[statut]?.classe || 'bg-gray-100 text-gray-700'
export const libellePaiement = (statut) => STATUTS_PAIEMENT[statut]?.label || statut
export const classePaiement = (statut) => STATUTS_PAIEMENT[statut]?.classe || 'bg-gray-100 text-gray-700'
export const libelleMoyenPaiement = (moyen) => MOYENS_PAIEMENT[moyen] || moyen
