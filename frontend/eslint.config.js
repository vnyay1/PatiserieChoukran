import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import globals from 'globals'

export default [
  {
    ignores: ['dist/**', 'node_modules/**', 'public/**', 'coverage/**'],
  },

  js.configs.recommended,
  ...pluginVue.configs['flat/recommended'],

  {
    files: ['**/*.{js,mjs,cjs,vue}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.es2021,
      },
    },
    rules: {
      // Les composants de vue/layout portent volontairement un nom d'un seul mot
      // (Accueil.vue, Panier.vue, Header.vue...) : c'est la convention du projet.
      'vue/multi-word-component-names': 'off',
      // Tailwind produit de longues listes de classes : le formatage des attributs
      // est laissé à l'auteur du composant.
      'vue/max-attributes-per-line': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/html-self-closing': ['error', {
        html: { void: 'any', normal: 'any', component: 'always' },
      }],
      'vue/html-indent': ['error', 2],
      'no-unused-vars': ['error', {
        argsIgnorePattern: '^_',
        caughtErrors: 'none',
      }],
    },
  },

  // Fichiers de configuration exécutés par Node (vite, tailwind, postcss)
  {
    files: ['*.config.js', 'vite.config.js'],
    languageOptions: {
      globals: { ...globals.node },
    },
  },
]
