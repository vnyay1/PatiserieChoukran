// Tests : npm test (node --test, sans dépendance)
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { messageRetourPaiement } from './paiement.js'

test('messageRetourPaiement : pendant la vérification, rien n\'affirme que le paiement a échoué', () => {
  const texte = messageRetourPaiement('verification')

  // Le client vient peut-être de payer : lui dire qu'aucun montant n'est débité l'inviterait à repayer
  assert.doesNotMatch(texte, /Aucun montant/)
  assert.match(texte, /téléphone/)
})

test('messageRetourPaiement : refus, annulation et expiration rassurent sur le débit', () => {
  for (const etat of ['echec', 'annule', 'expire']) {
    assert.match(messageRetourPaiement(etat), /Aucun montant n'a été débité/)
  }
})

test('messageRetourPaiement : un statut inconnu n\'affirme rien sur le débit', () => {
  assert.doesNotMatch(messageRetourPaiement('introuvable'), /Aucun montant/)
})

test('messageRetourPaiement : le message d\'erreur passe en premier', () => {
  assert.equal(messageRetourPaiement('erreur', 'Réseau indisponible.'), 'Réseau indisponible.')
  assert.match(messageRetourPaiement('en_attente'), /pas encore confirmé/)
})
