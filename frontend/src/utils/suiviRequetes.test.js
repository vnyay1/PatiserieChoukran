// Tests : npm test (node --test, sans dépendance)
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { setTimeout as attendre } from 'node:timers/promises'
import { creerSuiviRequetes } from './suiviRequetes.js'

// Chargement d'une liste comme dans les vues de gestion : requête, puis affichage si encore actuelle
const creerListe = () => {
  const nouvelleRequete = creerSuiviRequetes()
  const affichees = []
  const charger = async (recherche, delaiReponse) => {
    const estActuelle = nouvelleRequete()
    await attendre(delaiReponse)
    if (estActuelle()) affichees.push(recherche)
  }
  return { charger, affichees }
}

test('creerSuiviRequetes : la réponse lente d\'une recherche dépassée ne remplace pas la liste', async () => {
  const { charger, affichees } = creerListe()

  // « Je » est lancée d'abord mais répond après « Jean »
  await Promise.all([charger('Je', 40), charger('Jean', 5)])

  assert.deepEqual(affichees, ['Jean'])
})

test('creerSuiviRequetes : des chargements successifs s\'affichent tous', async () => {
  const { charger, affichees } = creerListe()

  await charger('Je', 1)
  await charger('Jean', 1)

  assert.deepEqual(affichees, ['Je', 'Jean'])
})

test('creerSuiviRequetes : chaque liste a son propre suivi', async () => {
  const utilisateurs = creerListe()
  const commandes = creerListe()

  await Promise.all([utilisateurs.charger('Jean', 20), commandes.charger('livree', 1)])

  assert.deepEqual(utilisateurs.affichees, ['Jean'])
  assert.deepEqual(commandes.affichees, ['livree'])
})
