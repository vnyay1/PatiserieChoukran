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
        // Fixes dans les deux thèmes : fond des logos tiers, voile sur image ou derrière une modale
        plaque: jeton('plaque'),
        voile: jeton('voile'),
        // Barres des graphiques (tableau de bord), un pas par thème
        graphique: jeton('graphique'),
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
      // Mouvement : réponses aux actions (transform et opacity seulement), coupées par
      // prefers-reduced-motion (règle globale de tailwind.css)
      transitionDuration: {
        rapide: '150ms',
        base: '250ms',
        lente: '450ms',
      },
      transitionTimingFunction: {
        douce: 'cubic-bezier(0.2, 0, 0, 1)',
        // Léger dépassement : confirmation d'une action (pastille du panier)
        emphase: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
      },
      keyframes: {
        rebond: {
          '0%': { transform: 'scale(1)' },
          '40%': { transform: 'scale(1.3)' },
          '100%': { transform: 'scale(1)' },
        },
        apparition: {
          from: { opacity: '0', transform: 'translateY(10px) scale(0.98)' },
          to: { opacity: '1', transform: 'none' },
        },
        reflet: {
          from: { backgroundPosition: '-100% 0' },
          to: { backgroundPosition: '200% 0' },
        },
        deroule: {
          from: { opacity: '0', transform: 'translateY(-4px)' },
          to: { opacity: '1', transform: 'none' },
        },
      },
      animation: {
        rebond: 'rebond 450ms cubic-bezier(0.34, 1.56, 0.64, 1)',
        apparition: 'apparition 550ms cubic-bezier(0.2, 0, 0, 1) both',
        reflet: 'reflet 1.6s ease-in-out infinite',
        deroule: 'deroule 200ms cubic-bezier(0.2, 0, 0, 1)',
      },
    },
  },
  plugins: [],
}
