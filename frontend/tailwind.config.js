/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#FFF5F0',
          100: '#FFE8DC',
          200: '#FFD1BE',
          300: '#FFB8A0',
          400: '#FFA082',
          500: '#FF8864',
          600: '#E6714A',
          700: '#CC5A30',
        },
        gold: {
          50: '#FFFBF0',
          100: '#FFF4D6',
          200: '#FFE9AD',
          300: '#FFE084',
          400: '#F5D460',
          500: '#D4AF37',
          600: '#B8941E',
          700: '#9C7A10',
        },
        cream: '#FFFBF5',
        peach: '#FFB8A0',
        'text-dark': '#5A4A3A',
      },
      fontFamily: {
        display: ['Playfair Display', 'serif'],
        body: ['Poppins', 'sans-serif'],
      },
      boxShadow: {
        'elegant': '0 4px 20px rgba(212, 175, 55, 0.15)',
        'card': '0 2px 12px rgba(0, 0, 0, 0.08)',
        'elegant-lg': '0 10px 40px rgba(212, 175, 55, 0.2)',
      },
      borderRadius: {
        'elegant': '20px',
      },
      animation: {
        'spin': 'spin 1s linear infinite',
      },
    },
  },
  plugins: [],
}