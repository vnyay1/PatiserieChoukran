// ===================================
// COMPOSABLE LIVRAISON PAR VENDEUR
// File: src/composables/useLivraisonVendeurs.js
// ===================================
// Le panier peut contenir des produits de plusieurs vendeurs : le backend crée une
// commande par vendeur. Chacune paie les frais standard de la plateforme, à condition que
// le vendeur desserve le quartier et que ses produits atteignent son montant minimum.

import { ref } from 'vue'
import api from '@/services/api'

const VILLE_LABELS = {
  'yaoundé': 'Yaoundé',
  'douala': 'Douala',
}

export const formatVille = (ville) => {
  if (!ville) return ''
  return VILLE_LABELS[String(ville).toLowerCase()] || ville
}

// Pour les recherches de quartier : "Bonabéri" et "bonaberi" doivent correspondre
export const normaliserTexte = (texte) => String(texte || '')
  .normalize('NFD')
  .replace(/\p{Diacritic}/gu, '')
  .toLowerCase()

export const formatDelai = (tarif) => {
  if (!tarif || tarif.delai_min == null || tarif.delai_max == null) return ''
  return `${tarif.delai_min}-${tarif.delai_max} min`
}

// Regroupe les lignes du panier par vendeur (une commande sera créée par groupe)
export const grouperParVendeur = (items = []) => {
  const groupes = new Map()

  items.forEach((item) => {
    const vendeurId = item.vendeur_id ?? item.produit?.created_by_user_id ?? null
    const cle = vendeurId ?? 'sans-vendeur'

    if (!groupes.has(cle)) {
      groupes.set(cle, {
        vendeurId,
        vendeurNom: item.vendeur?.nom_complet
          || item.produit?.createur?.nom_complet
          || (vendeurId ? `Vendeur #${vendeurId}` : 'Vendeur inconnu'),
        items: [],
        sousTotal: 0,
      })
    }

    const groupe = groupes.get(cle)
    groupe.items.push(item)
    groupe.sousTotal += parseFloat(item.sous_total) || 0
  })

  return Array.from(groupes.values())
}

// Livraison d'un vendeur telle que renvoyée par GET /livraison/quartiers/vendeur/{id}
const livraisonParDefaut = () => ({ frais: 0, minimum: 0, quartiers: [] })

export const useLivraisonVendeurs = () => {
  // vendeurId -> { frais, minimum, quartiers: [{ id, nom, ville, delai_min, delai_max }] }
  // Les frais sont le tarif standard de la plateforme ; le minimum est fixé par le vendeur.
  const livraisonParVendeur = ref({})
  const chargement = ref(false)
  const erreur = ref('')

  const estCharge = (vendeurId) => Boolean(vendeurId) && vendeurId in livraisonParVendeur.value

  const chargerQuartiersVendeurs = async (vendeurIds = []) => {
    const aCharger = [...new Set(vendeurIds.filter(Boolean))].filter((id) => !estCharge(id))
    if (aCharger.length === 0) return

    chargement.value = true
    erreur.value = ''

    try {
      const reponses = await Promise.all(aCharger.map((id) => api.livraison.quartiersByVendeur(id)))
      const livraisons = { ...livraisonParVendeur.value }
      reponses.forEach((reponse, index) => {
        const data = reponse.data?.data || {}
        livraisons[aCharger[index]] = {
          frais: Number(data.frais_livraison) || 0,
          minimum: Number(data.montant_minimum_livraison) || 0,
          quartiers: data.quartiers || [],
        }
      })
      livraisonParVendeur.value = livraisons
    } catch (error) {
      erreur.value = 'Impossible de charger les conditions de livraison des vendeurs.'
      console.error('Erreur chargement livraison vendeurs:', error)
    } finally {
      chargement.value = false
    }
  }

  const livraisonDe = (vendeurId) => livraisonParVendeur.value[vendeurId] || livraisonParDefaut()

  // Quartier desservi par le vendeur (avec ses délais), ou null s'il ne le couvre pas
  const tarifPour = (vendeurId, quartierId) => {
    if (!vendeurId || !quartierId) return null
    return livraisonDe(vendeurId).quartiers.find((quartier) => Number(quartier.id) === Number(quartierId)) || null
  }

  // Montant manquant pour atteindre le minimum de livraison du vendeur (0 si atteint)
  const manquePourMinimum = (vendeurId, montantProduits) => {
    const { minimum } = livraisonDe(vendeurId)
    return minimum > 0 ? Math.max(0, minimum - (Number(montantProduits) || 0)) : 0
  }

  // Livraison de chaque groupe (cf. grouperParVendeur) vers un quartier.
  // statut : ok | non_couvert | minimum_non_atteint | sans_quartier | sans_vendeur | chargement | erreur
  const livraisonDesGroupes = (groupes, quartierId) => groupes.map((groupe) => {
    const base = { ...groupe, frais: 0, tarif: null, minimum: 0, manque: 0 }

    if (!groupe.vendeurId) {
      return { ...base, statut: 'sans_vendeur' }
    }
    if (!estCharge(groupe.vendeurId)) {
      return { ...base, statut: chargement.value ? 'chargement' : 'erreur' }
    }

    const livraison = livraisonDe(groupe.vendeurId)
    const avecMinimum = { ...base, minimum: livraison.minimum, manque: manquePourMinimum(groupe.vendeurId, groupe.sousTotal) }

    if (!quartierId) {
      return { ...avecMinimum, statut: 'sans_quartier' }
    }

    const tarif = tarifPour(groupe.vendeurId, quartierId)
    if (!tarif) {
      return { ...avecMinimum, statut: 'non_couvert' }
    }
    if (avecMinimum.manque > 0) {
      return { ...avecMinimum, tarif, statut: 'minimum_non_atteint' }
    }

    return { ...avecMinimum, statut: 'ok', frais: livraison.frais, tarif }
  })

  return {
    livraisonParVendeur,
    chargement,
    erreur,
    estCharge,
    chargerQuartiersVendeurs,
    livraisonDe,
    tarifPour,
    manquePourMinimum,
    livraisonDesGroupes,
  }
}
