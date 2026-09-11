// ===================================
// UTILITAIRES IMAGES
// File: src/utils/images.js
// ===================================

export const PLACEHOLDER_PRODUIT = '/placeholder-product.svg'

const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
const apiOrigin = (() => {
  try {
    return new URL(apiBase).origin
  } catch {
    // URL relative (ex. "/api/v1" en Docker) : fichiers servis par la même origine
    return ''
  }
})()

// Chemin stocké par le backend ("produits/x.jpg") -> URL publique (".../storage/produits/x.jpg")
export const resolveImageUrl = (path, { placeholder = true } = {}) => {
  if (!path) return placeholder ? PLACEHOLDER_PRODUIT : null
  if (path.startsWith('http') || path.startsWith('/') || path.startsWith('data:') || path.startsWith('blob:')) {
    return path
  }
  return apiOrigin ? `${apiOrigin}/storage/${path}` : `/storage/${path}`
}

// Mêmes limites que le backend (image, mimes:jpeg,png,jpg,webp, max:5120)
export const TAILLE_MAX_IMAGE_MO = 5
const TYPES_IMAGE_ACCEPTES = ['image/jpeg', 'image/png', 'image/webp']

// Message d'erreur si le fichier n'est pas une image acceptée, sinon ''
export const verifierImage = (fichier) => {
  if (!fichier) return ''
  if (!TYPES_IMAGE_ACCEPTES.includes(fichier.type)) {
    return `« ${fichier.name} » : format non accepté. Utilisez une image JPEG, PNG ou WebP.`
  }
  if (fichier.size > TAILLE_MAX_IMAGE_MO * 1024 * 1024) {
    const tailleMo = (fichier.size / 1024 / 1024).toFixed(1).replace('.', ',')
    return `« ${fichier.name} » pèse ${tailleMo} Mo : ${TAILLE_MAX_IMAGE_MO} Mo maximum.`
  }
  return ''
}

// <img @error="onImageError"> : image introuvable -> placeholder (une seule fois, pas de boucle)
export const onImageError = (event) => {
  const img = event?.target
  if (img && !img.src.endsWith(PLACEHOLDER_PRODUIT)) {
    img.src = PLACEHOLDER_PRODUIT
  }
}
