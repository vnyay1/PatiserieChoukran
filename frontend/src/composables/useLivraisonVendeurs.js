// ===================================
// COMPOSABLE LIVRAISON PAR VENDEUR
// File: src/composables/useLivraisonVendeurs.js
// ===================================
// Le panier peut contenir des produits de plusieurs vendeurs : le backend crée une
// commande par vendeur, chacune avec le tarif que ce vendeur a fixé pour le quartier.

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

export const useLivraisonVendeurs = () => {
  // vendeurId -> quartiers couverts : [{ id, nom, ville, tarif, delai_min, delai_max }]
  const quartiersParVendeur = ref({})
  const chargement = ref(false)
  const erreur = ref('')

  const estCharge = (vendeurId) => Boolean(vendeurId) && vendeurId in quartiersParVendeur.value

  const chargerQuartiersVendeurs = async (vendeurIds = []) => {
    const aCharger = [...new Set(vendeurIds.filter(Boolean))].filter((id) => !estCharge(id))
    if (aCharger.length === 0) return

    chargement.value = true
    erreur.value = ''

    try {
      const reponses = await Promise.all(aCharger.map((id) => api.livraison.quartiersByVendeur(id)))
      const quartiers = { ...quartiersParVendeur.value }
      reponses.forEach((reponse, index) => {
        quartiers[aCharger[index]] = reponse.data?.data || []
      })
      quartiersParVendeur.value = quartiers
    } catch (error) {
      erreur.value = 'Impossible de charger les tarifs de livraison des vendeurs.'
      console.error('Erreur chargement tarifs vendeurs:', error)
    } finally {
      chargement.value = false
    }
  }

  // Tarif du vendeur pour ce quartier, ou null s'il ne le couvre pas
  const tarifPour = (vendeurId, quartierId) => {
    if (!vendeurId || !quartierId) return null
    const quartiers = quartiersParVendeur.value[vendeurId] || []
    return quartiers.find((quartier) => Number(quartier.id) === Number(quartierId)) || null
  }

  // Livraison de chaque groupe (cf. grouperParVendeur) vers un quartier.
  // statut : ok | non_couvert | sans_quartier | sans_vendeur | chargement | erreur
  const livraisonDesGroupes = (groupes, quartierId) => groupes.map((groupe) => {
    if (!groupe.vendeurId) {
      return { ...groupe, statut: 'sans_vendeur', frais: 0, tarif: null }
    }
    if (!quartierId) {
      return { ...groupe, statut: 'sans_quartier', frais: 0, tarif: null }
    }
    if (!estCharge(groupe.vendeurId)) {
      return { ...groupe, statut: chargement.value ? 'chargement' : 'erreur', frais: 0, tarif: null }
    }

    const tarif = tarifPour(groupe.vendeurId, quartierId)
    if (!tarif) {
      return { ...groupe, statut: 'non_couvert', frais: 0, tarif: null }
    }

    return { ...groupe, statut: 'ok', frais: Number(tarif.tarif) || 0, tarif }
  })

  return {
    quartiersParVendeur,
    chargement,
    erreur,
    estCharge,
    chargerQuartiersVendeurs,
    tarifPour,
    livraisonDesGroupes,
  }
}
