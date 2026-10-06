/**
 * Suivi des chargements d'une liste : nouvelleRequete() renvoie estActuelle(), vrai tant
 * qu'aucun chargement plus récent n'a été lancé. Une réponse lente d'une recherche dépassée
 * (« Je » puis « Jean ») ne doit pas remplacer la liste affichée.
 */
export function creerSuiviRequetes () {
  let derniere = 0

  return () => {
    const numero = ++derniere
    return () => numero === derniere
  }
}
