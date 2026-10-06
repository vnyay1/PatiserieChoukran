// ===================================
// STORE THÈME (clair / sombre / système)
// File: src/stores/theme.js
// ===================================
// La préférence est gardée dans le navigateur. index.html applique la même règle
// avant le premier rendu pour éviter un flash clair en mode sombre.

import { defineStore } from 'pinia'

const CLE_THEME = 'choukrane:theme'
const PREFERENCES = ['clair', 'sombre', 'systeme']
const COULEUR_ENTETE = { clair: '#FFFFFF', sombre: '#1E1813' }

const requeteSysteme = () => (typeof window !== 'undefined' && window.matchMedia
  ? window.matchMedia('(prefers-color-scheme: dark)')
  : null)

const lirePreference = () => {
  try {
    const valeur = localStorage.getItem(CLE_THEME)
    return PREFERENCES.includes(valeur) ? valeur : 'systeme'
  } catch {
    return 'systeme'
  }
}

const ecrirePreference = (valeur) => {
  try {
    if (valeur === 'systeme') localStorage.removeItem(CLE_THEME)
    else localStorage.setItem(CLE_THEME, valeur)
  } catch {
    // Stockage indisponible : le choix vaut pour la session en cours
  }
}

export const useThemeStore = defineStore('theme', {
  state: () => ({
    preference: lirePreference(),
    systemeSombre: Boolean(requeteSysteme()?.matches),
  }),

  getters: {
    estSombre: (state) => state.preference === 'sombre'
      || (state.preference === 'systeme' && state.systemeSombre),
  },

  actions: {
    // Appelé une fois au démarrage (main.js) : suit les changements du système
    initialiser() {
      const requete = requeteSysteme()
      requete?.addEventListener?.('change', (event) => {
        this.systemeSombre = event.matches
        this.appliquer()
      })
      this.appliquer()
    },

    choisir(preference) {
      this.preference = PREFERENCES.includes(preference) ? preference : 'systeme'
      ecrirePreference(this.preference)
      this.appliquer()
    },

    appliquer() {
      const racine = document.documentElement
      racine.classList.toggle('dark', this.estSombre)
      document
        .querySelector('meta[name="theme-color"]')
        ?.setAttribute('content', this.estSombre ? COULEUR_ENTETE.sombre : COULEUR_ENTETE.clair)
    },
  },
})
