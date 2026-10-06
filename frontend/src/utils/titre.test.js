// Tests : npm test (node --test, sans dépendance)
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { titrePage, TITRE_ACCUEIL } from './titre.js'

test('titrePage met le nom du site après celui de la page', () => {
  assert.equal(titrePage('Mon panier'), 'Mon panier · Choukrane Pâtisserie')
  assert.equal(titrePage(''), TITRE_ACCUEIL)
  assert.equal(titrePage(undefined), TITRE_ACCUEIL)
})
