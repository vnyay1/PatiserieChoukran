// Tests : npm test (node --test, sans dépendance)
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { segment, cheminInterne, estUrlPaiementSure } from './url.js'

test('segment encode une valeur de route pour un chemin d\'API', () => {
  assert.equal(segment(12), '12')
  assert.equal(segment('gateau-vanille'), 'gateau-vanille')
  assert.equal(segment('trx.test_abc'), 'trx.test_abc')
  // vue-router décode %2F : sans encodage, « ../admin/users » sortirait du chemin prévu
  assert.equal(segment('../admin/users'), '..%2Fadmin%2Fusers')
  assert.equal(segment('a b?c#d'), 'a%20b%3Fc%23d')
})

test('cheminInterne n\'accepte que les chemins du site', () => {
  assert.equal(cheminInterne('/mes-commandes/3'), '/mes-commandes/3')
  assert.equal(cheminInterne('/admin/rapports?mois=2026-08'), '/admin/rapports?mois=2026-08')
  for (const externe of ['//pirate.test/x', '/\\pirate.test', 'https://pirate.test', 'javascript:alert(1)', 'mes-commandes', '', null, undefined, ['/a']]) {
    assert.equal(cheminInterne(externe), null, String(externe))
  }
})

test('estUrlPaiementSure n\'accepte que la page de paiement NotchPay en https', () => {
  assert.equal(estUrlPaiementSure('https://pay.notchpay.co/test.abc'), true)
  assert.equal(estUrlPaiementSure('https://notchpay.co/paiement'), true)
  for (const url of [
    'http://pay.notchpay.co/x',
    'https://pay.notchpay.co.pirate.test/x',
    'https://pirate-notchpay.co/x',
    'https://pirate.test/?r=notchpay.co',
    'javascript:alert(1)',
    '/paiement/retour',
    null,
  ]) {
    assert.equal(estUrlPaiementSure(url), false, String(url))
  }
})
