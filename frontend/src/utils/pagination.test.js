// Tests : npm test (node --test, sans dépendance)
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { chargerToutesLesPages } from './pagination.js'

// Faux endpoint Laravel : per_page plafonné comme Controller::parPage() côté API
const endpoint = (total, plafond, appels = []) => async (params = {}) => {
  appels.push(params)
  const page = params.page || 1
  const taille = Math.min(params.per_page || 15, plafond)
  const debut = (page - 1) * taille
  const data = Array.from({ length: Math.max(0, Math.min(taille, total - debut)) }, (_, i) => ({ id: debut + i + 1 }))
  return { data: { success: true, data: { data, current_page: page, last_page: Math.max(1, Math.ceil(total / taille)) } } }
}

test('chargerToutesLesPages : tous les éléments, au-delà du plafond de l\'API', async () => {
  const categories = await chargerToutesLesPages(endpoint(250, 200))

  assert.deepEqual(categories.map((c) => c.id), Array.from({ length: 250 }, (_, i) => i + 1))
})

test('chargerToutesLesPages : une seule requête quand tout tient dans une page', async () => {
  const appels = []

  const categories = await chargerToutesLesPages(endpoint(30, 200, appels))

  assert.equal(categories.length, 30)
  assert.equal(appels.length, 1)
})

test('chargerToutesLesPages : accepte une réponse non paginée', async () => {
  const charger = async () => ({ data: { success: true, data: [{ id: 1 }, { id: 2 }] } })

  assert.deepEqual(await chargerToutesLesPages(charger), [{ id: 1 }, { id: 2 }])
})
