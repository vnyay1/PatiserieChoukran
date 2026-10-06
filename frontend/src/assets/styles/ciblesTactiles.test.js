// Tests : npm test (node --test). La feuille est compilée par Tailwind comme au build.
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import postcss from 'postcss'
import tailwindcss from 'tailwindcss'
import config from '../../../tailwind.config.js'

// Variantes compactes qui doivent retrouver 44 px (min-h-11) sur écran tactile
const COMPACTES = ['btn-sm', 'btn-icone', 'puce']

test('écran tactile : les variantes compactes passent à 44 px', async () => {
  const source = await readFile(new URL('./tailwind.css', import.meta.url), 'utf8')
  const { root } = await postcss([tailwindcss({ ...config, content: [{ raw: [...COMPACTES, 'block'].join(' ') }] })])
    .process(source, { from: undefined })

  for (const classe of COMPACTES) {
    // À spécificité égale, la dernière déclaration de la feuille l'emporte au doigt
    let derniere = null
    root.walkRules((regle) => {
      if (!regle.selectors.includes(`.${classe}`)) return
      regle.walkDecls('min-height', (declaration) => {
        derniere = { valeur: declaration.value, media: regle.parent?.type === 'atrule' ? regle.parent.params : '' }
      })
    })

    assert.deepEqual(derniere, { valeur: '2.75rem', media: '(pointer: coarse)' }, `.${classe} au doigt`)
  }
})
