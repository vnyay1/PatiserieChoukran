// ===================================
// TÉLÉCHARGEMENT DE FICHIERS (factures, rapports)
// File: src/utils/telechargement.js
// ===================================

// Nom du fichier annoncé par le backend (Content-Disposition), sinon le nom par défaut
const nomDepuisEntete = (entete, nomParDefaut) => {
  if (!entete) return nomParDefaut
  const utf8 = /filename\*=UTF-8''([^;]+)/i.exec(entete)
  if (utf8) return decodeURIComponent(utf8[1])
  const simple = /filename="?([^";]+)"?/i.exec(entete)
  return simple ? simple[1] : nomParDefaut
}

// Réponse axios en responseType "blob" -> fichier enregistré par le navigateur
export const telechargerBlob = (response, nomParDefaut) => {
  const nom = nomDepuisEntete(response.headers?.['content-disposition'], nomParDefaut)
  const url = URL.createObjectURL(response.data)
  const lien = document.createElement('a')
  lien.href = url
  lien.download = nom
  document.body.appendChild(lien)
  lien.click()
  lien.remove()
  // Laisse au navigateur le temps de démarrer le téléchargement
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
