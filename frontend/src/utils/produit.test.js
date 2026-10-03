// Tests : npm test (node --test, sans dépendance)
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { estIndisponible, pourcentageReduction } from './produit.js'

test('estIndisponible : retiré de la vente ou stock épuisé', () => {
  assert.equal(estIndisponible({ est_disponible: true, stock_disponible: 3 }), false)
  assert.equal(estIndisponible({ est_disponible: true, stock_disponible: 0 }), true)
  assert.equal(estIndisponible({ est_disponible: false, stock_disponible: 8 }), true)
  assert.equal(estIndisponible(null), true)
})

test('pourcentageReduction : remise arrondie, 0 sans promotion', () => {
  assert.equal(pourcentageReduction({ prix_unitaire: 600, prix_promo: 450 }), 25)
  assert.equal(pourcentageReduction({ prix_unitaire: 3000, prix_promo: 2000 }), 33)
  // Montants décimaux renvoyés en chaînes par l'API
  assert.equal(pourcentageReduction({ prix_unitaire: '1500.00', prix_promo: '1200.00' }), 20)
  assert.equal(pourcentageReduction({ prix_unitaire: 600, prix_promo: null }), 0)
  assert.equal(pourcentageReduction({ prix_unitaire: 0, prix_promo: 100 }), 0)
  assert.equal(pourcentageReduction(undefined), 0)
})
