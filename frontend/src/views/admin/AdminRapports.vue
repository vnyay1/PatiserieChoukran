<!-- ===================================
ADMIN - RAPPORTS MENSUELS DES VENDEURS (PDF / CSV)
File: src/views/admin/AdminRapports.vue
=================================== -->

<template>
  <div class="admin-rapports-page pb-6">
    <div class="container mx-auto max-w-7xl pt-6 md:pt-8">
      <EnTetePage titre="Rapports mensuels">
        <template #sous-titre>
          Activité de tous les vendeurs sur les commandes créées dans le mois.
          Le rapport du mois écoulé est généré automatiquement le 1<sup>er</sup> de chaque mois.
        </template>
        <template #actions>
          <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div>
              <label for="admin-rapports-1" class="label">Mois</label>
              <select id="admin-rapports-1" v-model="moisChoisi" class="input min-w-48" :disabled="loadingMois">
                <option v-for="mois in listeMois" :key="mois.mois" :value="mois.mois">
                  {{ capitaliser(mois.libelle) }}{{ mois.clos ? '' : ' (en cours)' }}
                </option>
              </select>
            </div>
            <Button variant="outline" :icon="FileText" :icon-size="18" :loading="telechargement === 'pdf'" :disabled="!moisChoisi" @click="telecharger('pdf')">
              PDF
            </Button>
            <Button variant="primary" :icon="Sheet" :icon-size="18" :loading="telechargement === 'csv'" :disabled="!moisChoisi" @click="telecharger('csv')">
              CSV
            </Button>
          </div>
        </template>
      </EnTetePage>

      <AlertMessage v-if="erreur" type="error" class="mb-4">{{ erreur }}</AlertMessage>

      <div v-if="loading" class="space-y-4">
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-5">
          <div v-for="n in 5" :key="n" class="skeleton h-[5.5rem] rounded-elegant"></div>
        </div>
        <div class="skeleton h-64 rounded-elegant"></div>
      </div>

      <template v-else-if="rapport">
        <AlertMessage v-if="!rapport.clos" type="info" class="mb-4">
          Mois en cours : chiffres provisoires.
        </AlertMessage>

        <dl class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-5">
          <TuileStat v-for="indicateur in indicateurs" :key="indicateur.libelle" :libelle="indicateur.libelle" :valeur="indicateur.valeur" />
        </dl>

        <Card padding="none">
          <div class="overflow-x-auto" role="region" aria-label="Rapport par vendeur" tabindex="0">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 text-gray-600">
                <tr>
                  <th scope="col" class="text-left font-semibold px-4 py-3">Vendeur</th>
                  <th scope="col" class="text-right font-semibold px-3 py-3">Commandes</th>
                  <th scope="col" class="text-right font-semibold px-3 py-3">Livrées</th>
                  <th scope="col" class="text-right font-semibold px-3 py-3">Annulées</th>
                  <th scope="col" class="text-right font-semibold px-3 py-3">En cours</th>
                  <th scope="col" class="text-right font-semibold px-3 py-3">Articles</th>
                  <th scope="col" class="text-right font-semibold px-3 py-3">Chiffre d'affaires</th>
                  <th scope="col" class="text-right font-semibold px-3 py-3">Encaissé</th>
                  <th scope="col" class="text-right font-semibold px-3 py-3">Livraison</th>
                  <th scope="col" class="text-right font-semibold px-4 py-3">Panier moyen</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="rapport.vendeurs.length === 0">
                  <td colspan="10" class="p-6 text-center text-gray-600">Aucun vendeur.</td>
                </tr>
                <tr v-for="ligne in rapport.vendeurs" :key="ligne.vendeur_id" class="border-t border-gray-100">
                  <td class="px-4 py-3">
                    <div class="font-semibold text-gray-800 flex items-center gap-1">
                      {{ ligne.nom_complet }}
                      <template v-if="ligne.vedette === 'Oui'">
                        <Star :size="14" class="text-gold-500" fill="currentColor" aria-hidden="true" />
                        <span class="sr-only">(vendeur vedette)</span>
                      </template>
                    </div>
                    <div class="text-xs text-gray-600">{{ ligne.telephone }}<template v-if="ligne.email">, {{ ligne.email }}</template></div>
                  </td>
                  <td class="px-3 py-3 text-right">{{ ligne.commandes }}</td>
                  <td class="px-3 py-3 text-right">{{ ligne.livrees }}</td>
                  <td class="px-3 py-3 text-right">{{ ligne.annulees }}</td>
                  <td class="px-3 py-3 text-right">{{ ligne.en_cours }}</td>
                  <td class="px-3 py-3 text-right">{{ ligne.articles_vendus }}</td>
                  <td class="px-3 py-3 text-right whitespace-nowrap">{{ formatPrice(ligne.chiffre_affaires) }}</td>
                  <td class="px-3 py-3 text-right whitespace-nowrap">{{ formatPrice(ligne.encaisse) }}</td>
                  <td class="px-3 py-3 text-right whitespace-nowrap">{{ formatPrice(ligne.frais_livraison) }}</td>
                  <td class="px-4 py-3 text-right whitespace-nowrap">{{ formatPrice(ligne.panier_moyen) }}</td>
                </tr>
              </tbody>
              <tfoot v-if="rapport.vendeurs.length > 0" class="bg-gold-50 font-semibold text-gold-800 border-t-2 border-gold-300">
                <tr>
                  <td class="px-4 py-3">Total</td>
                  <td class="px-3 py-3 text-right">{{ rapport.totaux.commandes }}</td>
                  <td class="px-3 py-3 text-right">{{ rapport.totaux.livrees }}</td>
                  <td class="px-3 py-3 text-right">{{ rapport.totaux.annulees }}</td>
                  <td class="px-3 py-3 text-right">{{ rapport.totaux.en_cours }}</td>
                  <td class="px-3 py-3 text-right">{{ rapport.totaux.articles_vendus }}</td>
                  <td class="px-3 py-3 text-right whitespace-nowrap">{{ formatPrice(rapport.totaux.chiffre_affaires) }}</td>
                  <td class="px-3 py-3 text-right whitespace-nowrap">{{ formatPrice(rapport.totaux.encaisse) }}</td>
                  <td class="px-3 py-3 text-right whitespace-nowrap">{{ formatPrice(rapport.totaux.frais_livraison) }}</td>
                  <td class="px-4 py-3 text-right whitespace-nowrap">{{ formatPrice(rapport.totaux.panier_moyen) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <p class="border-t border-gray-100 px-4 py-3 text-xs text-gray-600">
            Montants en FCFA, hors commandes annulées. « Encaissé » : paiements confirmés.
          </p>
        </Card>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { messageErreur, lireErreurBlob } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import EnTetePage from '@/components/common/EnTetePage.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import TuileStat from '@/components/common/TuileStat.vue'
import { formatPrice } from '@/utils/format'
import { telechargerBlob } from '@/utils/telechargement'
import { FileText, Sheet, Star } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const toastStore = useToastStore()

const listeMois = ref([])
const loadingMois = ref(false)
const moisChoisi = ref('')
const rapport = ref(null)
const loading = ref(false)
const erreur = ref('')
const telechargement = ref('')

const capitaliser = (texte) => (texte ? texte.charAt(0).toUpperCase() + texte.slice(1) : '')

const indicateurs = computed(() => {
  const totaux = rapport.value?.totaux || {}
  return [
    { libelle: 'Commandes', valeur: totaux.commandes ?? 0 },
    { libelle: 'Livrées', valeur: totaux.livrees ?? 0 },
    { libelle: 'Chiffre d\'affaires', valeur: `${formatPrice(totaux.chiffre_affaires)} FCFA` },
    { libelle: 'Encaissé', valeur: `${formatPrice(totaux.encaisse)} FCFA` },
    { libelle: 'Panier moyen', valeur: `${formatPrice(totaux.panier_moyen)} FCFA` },
  ]
})

const chargerMois = async () => {
  loadingMois.value = true
  try {
    const response = await api.admin.rapports.list()
    listeMois.value = response.data.data || []

    // Mois demandé dans l'URL (lien de notification), sinon le dernier mois clos
    const demande = typeof route.query.mois === 'string' ? route.query.mois : ''
    const existe = listeMois.value.some((mois) => mois.mois === demande)
    moisChoisi.value = existe ? demande : (listeMois.value.find((mois) => mois.clos) || listeMois.value[0])?.mois || ''
  } catch (error) {
    erreur.value = messageErreur(error, 'Impossible de charger la liste des mois.')
  } finally {
    loadingMois.value = false
  }
}

const chargerApercu = async () => {
  if (!moisChoisi.value) return

  loading.value = true
  erreur.value = ''
  try {
    const response = await api.admin.rapports.apercu(moisChoisi.value)
    rapport.value = response.data.data
  } catch (error) {
    rapport.value = null
    erreur.value = messageErreur(error, 'Impossible de charger le rapport.')
  } finally {
    loading.value = false
  }
}

const telecharger = async (format) => {
  telechargement.value = format
  try {
    const response = await api.admin.rapports.telecharger(moisChoisi.value, format)
    telechargerBlob(response, `rapport-vendeurs-${moisChoisi.value}.${format}`)
  } catch (error) {
    await lireErreurBlob(error)
    toastStore.erreur(messageErreur(error, 'Impossible de télécharger le rapport.'))
  } finally {
    telechargement.value = ''
  }
}

watch(moisChoisi, (mois) => {
  if (!mois) return
  if (route.query.mois !== mois) {
    router.replace({ query: { ...route.query, mois } })
  }
  chargerApercu()
})

onMounted(chargerMois)
</script>
