// Taille de page demandée : le plafond de l'API pour les catégories (Admin\CategorieController)
const PAR_PAGE = 200

/**
 * Liste complète d'une ressource paginée par l'API (listes déroulantes qui doivent tout proposer) :
 * les pages sont lues jusqu'à la dernière, quel que soit le plafond de per_page côté serveur.
 * `charger(params)` est une méthode de services/api.js ; accepte aussi une réponse non paginée.
 */
export async function chargerToutesLesPages (charger) {
  const elements = []

  for (let page = 1; ; page++) {
    const { data } = await charger({ page, per_page: PAR_PAGE })
    const corps = data?.data
    if (Array.isArray(corps)) return corps

    const lignes = corps?.data || []
    elements.push(...lignes)
    if (lignes.length === 0 || page >= (corps?.last_page || 1)) return elements
  }
}
