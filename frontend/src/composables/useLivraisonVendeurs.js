// ===================================
// COMPOSABLE LIVRAISON PAR VENDEUR
// File: src/composables/useLivraisonVendeurs.js
// ===================================
// Le panier peut contenir des produits de plusieurs vendeurs : le backend crée une
// commande par vendeur. Chacune paie les frais standard de la plateforme, à condition que
// le vendeur livre la ville de l'adresse et que ses produits atteignent son minimum.

import { ref } from 'vue'
import api from '@/services/api'

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

// Conditions d'un vendeur telles que renvoyées par GET /livraison/vendeur/{id}
const livraisonParDefaut = () => ({ frais: 0, minimum: 0, villes: [] })

export const useLivraisonVendeurs = () => {
  // vendeurId -> { frais, minimum, villes: ['yaoundé', ...] }
  const livraisonParVendeur = ref({})
  const chargement = ref(false)
  const erreur = ref('')

  const estCharge = (vendeurId) => Boolean(vendeurId) && vendeurId in livraisonParVendeur.value

  const chargerLivraisonVendeurs = async (vendeurIds = []) => {
    const aCharger = [...new Set(vendeurIds.filter(Boolean))].filter((id) => !estCharge(id))
    if (aCharger.length === 0) return

    chargement.value = true
    erreur.value = ''

    try {
      const reponses = await Promise.all(aCharger.map((id) => api.livraison.vendeur(id)))
      const livraisons = { ...livraisonParVendeur.value }
      reponses.forEach((reponse, index) => {
        const data = reponse.data?.data || {}
        livraisons[aCharger[index]] = {
          frais: Number(data.frais_livraison) || 0,
          minimum: Number(data.montant_minimum_livraison) || 0,
          villes: data.villes || [],
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

  const livreDans = (vendeurId, ville) => Boolean(ville) && livraisonDe(vendeurId).villes.includes(ville)

  // Montant manquant pour atteindre le minimum de livraison du vendeur (0 si atteint)
  const manquePourMinimum = (vendeurId, montantProduits) => {
    const { minimum } = livraisonDe(vendeurId)
    return minimum > 0 ? Math.max(0, minimum - (Number(montantProduits) || 0)) : 0
  }

  // Livraison de chaque groupe (cf. grouperParVendeur) vers une ville.
  // statut : ok | non_couvert | minimum_non_atteint | sans_ville | sans_vendeur | chargement | erreur
  const livraisonDesGroupes = (groupes, ville) => groupes.map((groupe) => {
    const base = { ...groupe, frais: 0, minimum: 0, manque: 0 }

    if (!groupe.vendeurId) {
      return { ...base, statut: 'sans_vendeur' }
    }
    if (!estCharge(groupe.vendeurId)) {
      return { ...base, statut: chargement.value ? 'chargement' : 'erreur' }
    }

    const livraison = livraisonDe(groupe.vendeurId)
    const avecMinimum = { ...base, minimum: livraison.minimum, manque: manquePourMinimum(groupe.vendeurId, groupe.sousTotal) }

    if (!ville) {
      return { ...avecMinimum, statut: 'sans_ville' }
    }
    if (!livreDans(groupe.vendeurId, ville)) {
      return { ...avecMinimum, statut: 'non_couvert' }
    }
    if (avecMinimum.manque > 0) {
      return { ...avecMinimum, statut: 'minimum_non_atteint' }
    }

    return { ...avecMinimum, statut: 'ok', frais: livraison.frais }
  })

  return {
    chargement,
    erreur,
    estCharge,
    chargerLivraisonVendeurs,
    livraisonDe,
    livreDans,
    manquePourMinimum,
    livraisonDesGroupes,
  }
}
