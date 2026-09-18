// ===================================
// NUMÉROS DE TÉLÉPHONE (Cameroun)
// File: src/utils/telephone.js
// ===================================
// Le client saisit ses 9 chiffres ; l'indicatif +237 est fixe (le backend normalise aussi).

// « +237 699 12 34 56 » ou « 699123456 » -> « 699123456 »
export const chiffresLocaux = (valeur) => {
  const chiffres = String(valeur || '').replace(/\D/g, '')
  const sansIndicatif = chiffres.startsWith('237') ? chiffres.slice(3) : chiffres
  return sansIndicatif.slice(0, 9)
}

// « 699123456 » -> « +237699123456 » ('' si vide)
export const telephoneComplet = (valeur) => {
  const local = chiffresLocaux(valeur)
  return local ? `+237${local}` : ''
}

export const estTelephoneComplet = (valeur) => chiffresLocaux(valeur).length === 9
