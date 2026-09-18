/** @type {import('tailwindcss').Config} */

// Les couleurs sont lues dans les variables CSS de src/assets/styles/tailwind.css
// (canaux RGB) : le thème sombre redéfinit ces variables et les classes ne changent pas.
// Les échelles s'inversent en sombre (gray-900 reste « le texte le plus contrasté »).
const TEINTES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]
const echelle = (nom) => Object.fromEntries(
  TEINTES.map((teinte) => [teinte, `rgb(var(--${nom}-${teinte}) / <alpha-value>)`])
)
const jeton = (nom) => `rgb(var(--${nom}) / <alpha-value>)`

export default {
  // Classe .dark posée sur <html> par stores/theme.js (et par index.html avant le premier rendu)
  darkMode: 'class',
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}',
  ],
  theme: {
    container: {
      center: true,
      padding: {
        DEFAULT: '1rem',
        sm: '1.25rem',
        lg: '2rem',
      },
    },
    extend: {
      colors: {
        gray: echelle('gray'),
        gold: echelle('gold'),
        red: echelle('red'),
        orange: echelle('orange'),
        yellow: echelle('yellow'),
        amber: echelle('yellow'),
        green: echelle('green'),
        blue: echelle('blue'),
        purple: echelle('purple'),
        indigo: echelle('indigo'),
        // Fond de page et surfaces (cartes, en-tête, modales)
        cream: jeton('bg'),
        surface: jeton('surface'),
        // Texte posé sur un fond or (gold-500) ou bronze (gold-600)
        'on-gold': jeton('on-gold'),
        'on-accent': jeton('on-accent'),
        // Aplats qui gardent un texte blanc dans les deux thèmes
        danger: jeton('danger'),
        success: jeton('success'),
      },
      fontFamily: {
        display: ['"Playfair Display"', 'Georgia', 'Cambria', 'serif'],
        body: ['Manrope', 'system-ui', '-apple-system', '"Segoe UI"', 'Roboto', 'sans-serif'],
      },
      boxShadow: {
        card: 'var(--shadow-card)',
        elegant: 'var(--shadow-elegant)',
        'elegant-lg': 'var(--shadow-elegant-lg)',
      },
      borderRadius: {
        // Échelle : 8 px (vignettes), 12 px (champs, puces), 20 px (cartes), full (boutons)
        elegant: '20px',
      },
      transitionTimingFunction: {
        douce: 'cubic-bezier(0.2, 0, 0, 1)',
      },
    },
  },
  plugins: [],
}
