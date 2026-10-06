// Thème appliqué avant le premier rendu : pas de flash blanc en mode sombre.
// Chargé de façon synchrone dans le <head> d'index.html (fichier séparé plutôt que script
// inline : la Content-Security-Policy n'autorise que les scripts du site).
// Même logique que src/stores/theme.js (clé choukrane:theme = clair | sombre | systeme).
(function () {
  var preference = null
  try {
    preference = localStorage.getItem('choukrane:theme')
  } catch (e) {
    // Stockage indisponible : on suit le système
  }
  var sombre = preference === 'sombre'
    || (preference !== 'clair' && window.matchMedia('(prefers-color-scheme: dark)').matches)
  document.documentElement.classList.toggle('dark', sombre)
  document.querySelector('meta[name="theme-color"]').setAttribute('content', sombre ? '#1E1813' : '#FFFFFF')
})()
