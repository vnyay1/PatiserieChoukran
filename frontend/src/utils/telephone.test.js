// Tests : npm test (node --test, sans dépendance)
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { chiffresLocaux, telephoneComplet, estTelephoneComplet } from './telephone.js'

test('chiffresLocaux garde les 9 chiffres locaux, sans indicatif ni séparateurs', () => {
  assert.equal(chiffresLocaux('+237 699 12 34 56'), '699123456')
  assert.equal(chiffresLocaux('699123456'), '699123456')
  assert.equal(chiffresLocaux('237699123456'), '699123456')
  assert.equal(chiffresLocaux('6 99-12.34 56'), '699123456')
  // Saisie trop longue : coupée à 9 chiffres
  assert.equal(chiffresLocaux('6991234567'), '699123456')
  assert.equal(chiffresLocaux(null), '')
  assert.equal(chiffresLocaux(undefined), '')
})

test('telephoneComplet rend le format attendu par le backend (+237 et 9 chiffres)', () => {
  assert.equal(telephoneComplet('699123456'), '+237699123456')
  assert.equal(telephoneComplet('+237699123456'), '+237699123456')
  assert.equal(telephoneComplet(''), '')
})

test('estTelephoneComplet exige les 9 chiffres', () => {
  assert.equal(estTelephoneComplet('699123456'), true)
  assert.equal(estTelephoneComplet('+237 699 12 34 56'), true)
  assert.equal(estTelephoneComplet('69912'), false)
  assert.equal(estTelephoneComplet(''), false)
})
