// ===================================
// SONDAGE PARTAGÉ ENTRE ONGLETS
// File: src/utils/sondagePartage.js
// ===================================
// Rafraîchit une valeur (compteur de notifications, badge des commandes…) au plus une
// fois par intervalle pour TOUS les onglets ouverts : le dernier résultat et la date du
// prochain appel sont partagés dans localStorage, les autres onglets les reçoivent par
// l'événement « storage ». En cas d'erreur, l'attente double à chaque échec (plafonnée).
// Aucun appel quand l'onglet est en arrière-plan.

const lire = (cle) => {
  try {
    return JSON.parse(localStorage.getItem(cle) || 'null')
  } catch {
    return null
  }
}

const ecrire = (cle, etat) => {
  try {
    localStorage.setItem(cle, JSON.stringify(etat))
  } catch {
    // Stockage indisponible (navigation privée…) : le sondage reste propre à l'onglet
  }
}

export const creerSondagePartage = ({ cle, intervalleMs, attenteMaxMs = 15 * 60 * 1000, charger, appliquer }) => {
  let minuterie = null
  let enCours = null
  let echecs = 0
  // Repli si localStorage est indisponible
  let etatLocal = null

  const etat = () => lire(cle) || etatLocal || {}

  const enregistrer = (nouvelEtat) => {
    etatLocal = nouvelEtat
    ecrire(cle, nouvelEtat)
  }

  const rafraichir = async ({ force = false } = {}) => {
    const maintenant = Date.now()
    const courant = etat()

    if (!force && courant.prochainAppel && maintenant < courant.prochainAppel) {
      if (courant.valeur !== undefined) appliquer(courant.valeur)
      return courant.valeur
    }
    if (enCours) return enCours

    // Réservation immédiate : un autre onglet qui se réveille au même moment attend
    enregistrer({ ...courant, prochainAppel: maintenant + intervalleMs })

    enCours = (async () => {
      try {
        const valeur = await charger()
        echecs = 0
        enregistrer({ valeur, prochainAppel: Date.now() + intervalleMs })
        appliquer(valeur)
        return valeur
      } catch (error) {
        echecs += 1
        const attente = Math.min(intervalleMs * 2 ** echecs, attenteMaxMs)
        enregistrer({ ...etat(), prochainAppel: Date.now() + attente })
        return courant.valeur
      } finally {
        enCours = null
      }
    })()

    return enCours
  }

  const surStockage = (event) => {
    if (event.key !== cle || !event.newValue) return
    const partage = lire(cle)
    if (partage?.valeur !== undefined) appliquer(partage.valeur)
  }

  const surVisibilite = () => {
    if (document.visibilityState === 'visible') rafraichir()
  }

  const demarrer = () => {
    if (minuterie) return rafraichir()

    window.addEventListener('storage', surStockage)
    document.addEventListener('visibilitychange', surVisibilite)
    // Le minuteur vérifie souvent, mais l'appel réseau n'a lieu qu'à l'échéance partagée
    minuterie = setInterval(() => {
      if (document.visibilityState === 'visible') rafraichir()
    }, Math.min(30000, intervalleMs))

    return rafraichir()
  }

  const arreter = ({ oublier = false } = {}) => {
    if (minuterie) {
      clearInterval(minuterie)
      minuterie = null
      window.removeEventListener('storage', surStockage)
      document.removeEventListener('visibilitychange', surVisibilite)
    }
    echecs = 0
    if (oublier) {
      etatLocal = null
      try {
        localStorage.removeItem(cle)
      } catch {
        // rien à nettoyer
      }
    }
  }

  return { demarrer, arreter, rafraichir }
}
