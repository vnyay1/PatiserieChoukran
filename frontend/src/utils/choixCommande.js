// ===================================
// CHOIX D'UNE COMMANDE (réception, paiement)
// File: src/utils/choixCommande.js
// ===================================
// Mêmes cartes au checkout et dans la modification d'une commande (CarteRadio).
import { Truck, Store } from 'lucide-vue-next'

export const MODES_RECEPTION = [
  { valeur: 'livraison', libelle: 'Livraison à domicile', description: 'Frais fixes par vendeur', icone: Truck },
  { valeur: 'retrait_boutique', libelle: 'Retrait en boutique', description: 'Gratuit, sans minimum d\'achat', icone: Store },
]

export const MOYENS_PAIEMENT_CHOIX = [
  { valeur: 'orange_money', libelle: 'Orange Money', description: 'Validation sur votre téléphone', logo: '/Orange-Money-logo.png' },
  { valeur: 'mtn_momo', libelle: 'MTN Mobile Money', description: 'Validation sur votre téléphone', logo: '/Momo-logo.png' },
  { valeur: 'especes', libelle: 'Espèces', description: 'À régler à la livraison ou au retrait', logo: '/argent.png' },
]
