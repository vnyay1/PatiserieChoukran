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

// AAAA-MM-JJ dans le fuseau du navigateur (toISOString donnerait la date UTC,
// décalée d'un jour en soirée/matinée selon le fuseau)
export const dateIso = (date) => {
  const mois = String(date.getMonth() + 1).padStart(2, '0')
  const jour = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${mois}-${jour}`
}

// Stock à partir duquel un produit est signalé « bientôt épuisé » (même seuil que le tableau de bord)
export const SEUIL_STOCK_FAIBLE = 5

// classe : badge de ton de tailwind.css (fond 100, texte 800), écrite en entier pour que Tailwind la génère ;
// affichés par BadgeStatut
export const STATUTS_COMMANDE = {
  en_attente: { label: 'En attente', classe: 'badge-warning' },
  confirmee: { label: 'Confirmée', classe: 'badge-info' },
  en_preparation: { label: 'En préparation', classe: 'badge-violet' },
  prete: { label: 'Prête', classe: 'badge-indigo' },
  en_livraison: { label: 'En livraison', classe: 'badge-orange' },
  livree: { label: 'Livrée', classe: 'badge-success' },
  annulee: { label: 'Annulée', classe: 'badge-danger' },
}

export const STATUTS_PAIEMENT = {
  en_attente: { label: 'À payer', classe: 'badge-warning' },
  paye: { label: 'Payé', classe: 'badge-success' },
  echec: { label: 'Échec', classe: 'badge-danger' },
  rembourse: { label: 'Remboursé', classe: 'badge-neutral' },
}

export const STATUTS_COMPTE = {
  actif: { label: 'Actif', classe: 'badge-success' },
  inactif: { label: 'Inactif', classe: 'badge-neutral' },
  suspendu: { label: 'Suspendu', classe: 'badge-danger' },
}

export const MOYENS_PAIEMENT = {
  orange_money: 'Orange Money',
  mtn_momo: 'MTN Mobile Money',
  especes: 'Espèces',
}

export const libelleStatut = (statut) => STATUTS_COMMANDE[statut]?.label || statut
export const libellePaiement = (statut) => STATUTS_PAIEMENT[statut]?.label || statut
export const libelleCompte = (statut) => STATUTS_COMPTE[statut]?.label || statut
export const libelleMoyenPaiement = (moyen) => MOYENS_PAIEMENT[moyen] || moyen

// Pour les recherches : « Bonabéri » et « bonaberi » doivent correspondre
export const normaliserTexte = (texte) => String(texte || '')
  .normalize('NFD')
  .replace(/\p{Diacritic}/gu, '')
  .toLowerCase()
