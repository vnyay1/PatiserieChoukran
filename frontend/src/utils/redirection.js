// ===================================
// REDIRECTION APRÈS CONNEXION / INSCRIPTION
// File: src/utils/redirection.js
// ===================================

import { cheminInterne } from '@/utils/url'

// La garde du router ajoute ?redirect=/page-demandee : on y renvoie l'utilisateur.
// Seuls les chemins internes sont acceptés (pas de redirection vers un site externe).
export const destinationApresConnexion = (route, authStore) => {
  const redirect = cheminInterne(route.query.redirect)
  if (redirect) {
    return redirect
  }

  if (authStore.isAdmin) return { name: 'admin-dashboard' }
  if (authStore.isVendeur) return { name: 'admin-commandes' }
  return { name: 'home' }
}
