// Filtres d'une liste gardés dans l'URL (?statut=…&page=…), comme le catalogue :
// un lien partagé, un rafraîchissement ou le bouton retour retrouvent la même vue.
// L'URL fait foi : auChangement(estActuel) (le chargement de la liste) est appelé au montage de
// la page (après le setup : la fonction peut être déclarée plus bas) et à chaque changement de la
// query. Le chargement n'applique sa réponse que si estActuel() : une réponse lente d'une recherche
// dépassée ne remplace pas la liste. Après une action (suppression…), recharger().
//
//   const { filtres, mettreAJour, recharger } = useFiltresUrl({ search: '', statut: '', page: 1 }, (estActuel) => fetchListe(estActuel))
//   <select v-model="filtres.statut" @change="mettreAJour({ page: 1 })">
//   <Pagination :page="filtres.page" @update:page="(page) => mettreAJour({ page })" />
import { onMounted, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { creerSuiviRequetes } from '@/utils/suiviRequetes'

export function useFiltresUrl (defauts, auChangement) {
  const route = useRoute()
  const router = useRouter()
  const nomRoute = route.name

  // Valeurs de la query, ramenées au type des valeurs par défaut (page : entier ≥ 1)
  const lire = () => Object.fromEntries(Object.entries(defauts).map(([cle, defaut]) => {
    const valeur = route.query[cle]
    if (typeof valeur !== 'string' || valeur === '') return [cle, defaut]
    if (typeof defaut === 'number') {
      const nombre = Number.parseInt(valeur, 10)
      return [cle, Number.isFinite(nombre) && nombre >= 1 ? nombre : defaut]
    }
    return [cle, valeur]
  }))

  const filtres = reactive(lire())

  const nouvelleRequete = creerSuiviRequetes()
  const recharger = () => auChangement(nouvelleRequete())

  // Seules les valeurs différentes du défaut vont dans l'URL
  const versQuery = () => Object.fromEntries(Object.entries(filtres)
    .filter(([cle, valeur]) => valeur !== '' && valeur !== null && valeur !== undefined && valeur !== defauts[cle])
    .map(([cle, valeur]) => [cle, String(valeur)]))

  const memeQuery = (a, b) => {
    const cles = new Set([...Object.keys(a), ...Object.keys(b)])
    return [...cles].every((cle) => String(a[cle] ?? '') === String(b[cle] ?? ''))
  }

  // Applique des changements (ex. { page: 1 } après un filtre) ; même URL : simple rechargement
  const mettreAJour = (changements = {}) => {
    Object.assign(filtres, changements)
    const query = versQuery()
    if (memeQuery(query, route.query)) {
      recharger()
      return
    }
    router.replace({ query })
  }

  const reinitialiser = () => mettreAJour({ ...defauts })

  watch(
    () => route.query,
    () => {
      // Pendant la navigation vers une autre page, la query change aussi : ignorée
      if (route.name !== nomRoute) return
      Object.assign(filtres, lire())
      recharger()
    },
  )

  onMounted(() => recharger())

  return { filtres, mettreAJour, reinitialiser, recharger }
}
