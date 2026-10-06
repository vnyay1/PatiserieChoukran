// Ajout au panier depuis la vitrine (carte et fiche produit).
// Réservé aux clients : l'équipe (admin, vendeur) n'a pas de bouton, un visiteur est envoyé
// vers la connexion puis ramené sur la page. Le résultat est annoncé par un message ;
// le bouton affiche « Ajouté » un instant et la pastille du panier rebondit (useRebondPanier).
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import { useToastStore } from '@/stores/toast'

// Émis après un ajout réussi : seule une action du client fait rebondir la pastille
// (pas le chargement du panier à l'ouverture de l'application)
export const EVENEMENT_AJOUT_PANIER = 'choukrane:panier-ajout'
const DUREE_AJOUTE_MS = 1800

export function useAjoutPanier () {
  const router = useRouter()
  const authStore = useAuthStore()
  const panierStore = usePanierStore()
  const toastStore = useToastStore()

  const ajoutEnCours = ref(false)
  const ajoute = ref(false)
  const estEquipe = computed(() => authStore.isAdmin || authStore.isVendeur)
  let minuteurAjoute = null

  // Renvoie true quand le produit est dans le panier
  const ajouter = async (produit, quantite = 1) => {
    if (estEquipe.value || ajoutEnCours.value) return false

    if (!authStore.isAuthenticated) {
      toastStore.info('Connectez-vous pour ajouter des produits à votre panier.')
      router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
      return false
    }

    ajoutEnCours.value = true
    const result = await panierStore.addItem(produit.id, quantite)
    ajoutEnCours.value = false

    if (!result.success) {
      toastStore.erreur(result.message || 'Impossible d\'ajouter ce produit au panier.')
      return false
    }

    toastStore.succes(quantite > 1
      ? `${quantite} × « ${produit.nom} » ajoutés au panier.`
      : `« ${produit.nom} » ajouté au panier.`)
    ajoute.value = true
    clearTimeout(minuteurAjoute)
    minuteurAjoute = setTimeout(() => {
      ajoute.value = false
    }, DUREE_AJOUTE_MS)
    window.dispatchEvent(new CustomEvent(EVENEMENT_AJOUT_PANIER))
    return true
  }

  onBeforeUnmount(() => clearTimeout(minuteurAjoute))

  return { ajouter, ajoutEnCours, ajoute, estEquipe }
}

// Pastille du panier (en-tête, barre du bas) : rebond à chaque ajout réussi
export function useRebondPanier () {
  const rebond = ref(false)

  const declencher = () => {
    rebond.value = false
    // Retirer puis remettre la classe relance l'animation
    requestAnimationFrame(() => {
      rebond.value = true
    })
  }
  const finRebond = () => {
    rebond.value = false
  }

  onMounted(() => window.addEventListener(EVENEMENT_AJOUT_PANIER, declencher))
  onBeforeUnmount(() => window.removeEventListener(EVENEMENT_AJOUT_PANIER, declencher))

  return { rebond, finRebond }
}
