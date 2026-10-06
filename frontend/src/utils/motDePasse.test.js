// Tests : npm test (node --test, sans dépendance)
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { aideNouveauMotDePasse, aideConfirmation, longueurSuffisante } from './motDePasse.js'

test('aideNouveauMotDePasse suit la saisie jusqu\'à 8 caractères', () => {
  assert.equal(aideNouveauMotDePasse(''), '8 caractères minimum.')
  assert.equal(aideNouveauMotDePasse('abcde'), 'Encore 3 caractères (8 minimum).')
  assert.equal(aideNouveauMotDePasse('abcdefg'), 'Encore 1 caractère (8 minimum).')
  assert.equal(aideNouveauMotDePasse('abcdefgh'), '8 caractères ou plus : longueur suffisante.')
})

test('longueurSuffisante applique la règle du backend', () => {
  assert.equal(longueurSuffisante('1234567'), false)
  assert.equal(longueurSuffisante('12345678'), true)
  assert.equal(longueurSuffisante(null), false)
})

test('aideConfirmation ne dit rien tant que la confirmation est vide', () => {
  assert.equal(aideConfirmation('secret12', ''), '')
  assert.equal(aideConfirmation('secret12', 'secret1'), 'Les deux mots de passe ne correspondent pas encore.')
  assert.equal(aideConfirmation('secret12', 'secret12'), 'Les deux mots de passe correspondent.')
})
