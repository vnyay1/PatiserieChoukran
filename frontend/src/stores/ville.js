// ===================================
// STORE VILLE DU CLIENT
// File: src/stores/ville.js
// ===================================
// Ville choisie par le visiteur : le catalogue ne propose que les produits des vendeurs
// qui y livrent. Conservée dans le navigateur (visiteurs compris).

import { defineStore } from 'pinia'
import { estVilleConnue, formatVille } from '@/utils/villes'

const CLE_VILLE = 'choukrane:ville'
const CLE_QUESTION = 'choukrane:ville-demandee'

const lire = (cle) => {
  try {
    return localStorage.getItem(cle)
  } catch {
    return null
  }
}

const ecrire = (cle, valeur) => {
  try {
    if (valeur === null) localStorage.removeItem(cle)
    else localStorage.setItem(cle, valeur)
  } catch {
    // Stockage indisponible : le choix reste valable pour la session en cours
  }
}

export const useVilleStore = defineStore('ville', {
  state: () => {
    const ville = lire(CLE_VILLE)
    return {
      ville: estVilleConnue(ville) ? ville : null,
      // La question « Où êtes-vous ? » n'est posée qu'une fois
      questionPosee: lire(CLE_QUESTION) === '1',
    }
  },

  getters: {
    libelle: (state) => formatVille(state.ville),
    doitDemander: (state) => !state.ville && !state.questionPosee,
  },

  actions: {
    // null : voir toute la boutique
    choisir(ville) {
      this.ville = estVilleConnue(ville) ? ville : null
      this.questionPosee = true
      ecrire(CLE_VILLE, this.ville)
      ecrire(CLE_QUESTION, '1')
    },

    // Client connecté sans ville choisie : on part de son adresse principale
    proposerDepuisAdresse(ville) {
      if (!this.ville && estVilleConnue(ville)) {
        this.choisir(ville)
      }
    },
  },
})
