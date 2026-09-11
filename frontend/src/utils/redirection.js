// ===================================
// REDIRECTION APRÈS CONNEXION / INSCRIPTION
// File: src/utils/redirection.js
// ===================================

// La garde du router ajoute ?redirect=/page-demandee : on y renvoie l'utilisateur.
// Seuls les chemins internes sont acceptés (pas de redirection vers un site externe).
export const destinationApresConnexion = (route, authStore) => {
  const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : ''
  if (redirect.startsWith('/') && !redirect.startsWith('//')) {
    return redirect
  }

  if (authStore.isAdmin) return { name: 'admin-dashboard' }
  if (authStore.isVendeur) return { name: 'admin-commandes' }
  return { name: 'home' }
}
