// Ajout au panier depuis la vitrine (carte et fiche produit).
// Réservé aux clients : l'équipe (admin, vendeur) n'a pas de bouton, un visiteur est envoyé
// vers la connexion puis ramené sur la page. Le résultat est annoncé par un message.
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePanierStore } from '@/stores/panier'
import { useToastStore } from '@/stores/toast'

export function useAjoutPanier () {
  const router = useRouter()
  const authStore = useAuthStore()
  const panierStore = usePanierStore()
  const toastStore = useToastStore()

  const ajoutEnCours = ref(false)
  const estEquipe = computed(() => authStore.isAdmin || authStore.isVendeur)

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
    return true
  }

  return { ajouter, ajoutEnCours, estEquipe }
}
