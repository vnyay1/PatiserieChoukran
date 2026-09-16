<!-- ===================================
VENDEUR - PROFIL BOUTIQUE (obligatoire avant de vendre)
File: src/views/vendeur/ProfilBoutique.vue
=================================== -->

<template>
  <div class="profil-boutique-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6 max-w-3xl">
      <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            Ma boutique
          </h1>
          <p class="text-gray-600 text-sm">
            Ces informations sont affichées sur votre page vendeur, visible par tous les clients.
          </p>
        </div>
        <router-link
          v-if="complet"
          :to="{ name: 'vendeur-profil', params: { id: authStore.user.id } }"
          class="btn-outline py-2 px-4 text-sm inline-flex items-center gap-2"
        >
          <ExternalLink :size="16" />
          Voir ma page publique
        </router-link>
      </div>

      <!-- Premier passage : le vendeur ne peut rien faire d'autre avant d'avoir complété son profil -->
      <div
        v-if="!loading && !complet"
        class="mb-6 p-4 rounded-elegant border border-gold-300 bg-gold-50 text-gold-900"
      >
        <p class="font-semibold mb-1">Bienvenue parmi nos vendeurs !</p>
        <p class="text-sm">
          Pour commencer à vendre, renseignez l'adresse e-mail et le logo de votre entreprise,
          décrivez-la et acceptez les conditions des vendeurs. Vos produits restent invisibles
          et votre espace vendeur bloqué tant que ce profil n'est pas complet.
        </p>
      </div>

      <div v-if="loading" class="space-y-4">
        <div v-for="n in 3" :key="n" class="skeleton h-24 rounded-elegant"></div>
      </div>

      <Card v-else padding="lg">
        <form class="space-y-6" @submit.prevent="enregistrer">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom affiché</label>
            <input :value="authStore.user?.nom_complet" type="text" class="input bg-gray-100 text-gray-500" disabled />
            <p class="text-xs text-gray-500 mt-1">Modifiable depuis « Mon profil » une fois la boutique complétée.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Adresse e-mail de l'entreprise *</label>
            <input
              v-model="form.email"
              type="email"
              class="input"
              placeholder="contact@maboutique.cm"
              autocomplete="email"
              required
            />
            <p class="text-xs text-gray-500 mt-1">Vous y recevez les nouvelles commandes ; elle est affichée sur votre page.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Logo de l'entreprise *</label>
            <div class="flex items-center gap-4">
              <div class="h-24 w-24 flex-shrink-0 rounded-elegant border bg-white overflow-hidden flex items-center justify-center">
                <img
                  v-if="apercuLogo"
                  :src="apercuLogo"
                  alt="Aperçu du logo"
                  class="h-full w-full object-contain"
                  @error="onImageError"
                />
                <Store v-else :size="32" class="text-gray-300" />
              </div>
              <div class="flex-1 min-w-0">
                <input
                  :key="cleChampLogo"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="input"
                  :required="!logoActuel"
                  @change="choisirLogo"
                />
                <p class="text-xs text-gray-500 mt-1">
                  JPEG, PNG ou WebP, {{ TAILLE_MAX_IMAGE_MO }} Mo maximum. De préférence carré.
                </p>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description de l'entreprise *</label>
            <textarea
              v-model="form.description_boutique"
              rows="5"
              maxlength="2000"
              class="input resize-y"
              placeholder="Votre histoire, vos spécialités, votre quartier, vos horaires..."
              required
            ></textarea>
            <p class="text-xs mt-1" :class="descriptionTropCourte ? 'text-orange-600' : 'text-gray-500'">
              {{ longueurDescription }} / 2000 caractères{{ descriptionTropCourte ? ` (minimum ${DESCRIPTION_MIN})` : '' }}
            </p>
          </div>

          <div>
            <p class="block text-sm font-medium text-gray-700 mb-2">Conditions des vendeurs *</p>
            <div class="max-h-56 overflow-y-auto whitespace-pre-line text-sm text-gray-700 p-4 rounded-xl border border-gray-200 bg-gray-50">
              {{ conditions || 'Conditions indisponibles pour le moment.' }}
            </div>
            <label class="mt-3 inline-flex items-start gap-2">
              <input
                v-model="form.conditions_acceptees"
                type="checkbox"
                class="mt-0.5 rounded border-gray-300 text-gold-600 focus:ring-gold-500"
                required
              />
              <span class="text-sm text-gray-700">
                J'ai lu et j'accepte les conditions des vendeurs
                <span v-if="accepteesLe" class="block text-xs text-gray-500">Acceptées le {{ formatDateLongue(accepteesLe) }}</span>
              </span>
            </label>
          </div>

          <p v-if="erreur" class="text-sm text-red-600">{{ erreur }}</p>

          <div class="flex flex-wrap items-center justify-between gap-3">
            <Button type="submit" variant="primary" :loading="enregistrement">
              {{ complet ? 'Enregistrer les modifications' : 'Valider mon profil boutique' }}
            </Button>
            <button
              v-if="!complet"
              type="button"
              class="inline-flex items-center gap-2 text-sm text-red-500 hover:text-red-600"
              @click="deconnexion"
            >
              <LogOut :size="16" />
              Se déconnecter
            </button>
          </div>
        </form>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import api, { messageErreur } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { resolveImageUrl, onImageError, verifierImage, TAILLE_MAX_IMAGE_MO } from '@/utils/images'
import { formatDateLongue } from '@/utils/format'
import { Store, LogOut, ExternalLink } from 'lucide-vue-next'

// Mêmes règles que Vendeur\ProfilBoutiqueController
const DESCRIPTION_MIN = 30

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const enregistrement = ref(false)
const erreur = ref('')
const conditions = ref('')
const logoActuel = ref(null)
const accepteesLe = ref(null)
const fichierLogo = ref(null)
const apercuLocal = ref(null)
const cleChampLogo = ref(0)

const form = ref({
  email: '',
  description_boutique: '',
  conditions_acceptees: false,
})

const complet = computed(() => authStore.user?.profil_vendeur_complet === true)
const apercuLogo = computed(() => apercuLocal.value || (logoActuel.value ? resolveImageUrl(logoActuel.value, { placeholder: false }) : null))
const longueurDescription = computed(() => form.value.description_boutique.trim().length)
const descriptionTropCourte = computed(() => longueurDescription.value > 0 && longueurDescription.value < DESCRIPTION_MIN)

const libererApercu = () => {
  if (apercuLocal.value) {
    URL.revokeObjectURL(apercuLocal.value)
    apercuLocal.value = null
  }
}

const choisirLogo = (event) => {
  const fichier = event.target.files?.[0] || null
  libererApercu()
  erreur.value = ''

  const probleme = verifierImage(fichier)
  if (probleme) {
    erreur.value = probleme
    fichierLogo.value = null
    cleChampLogo.value++
    return
  }

  fichierLogo.value = fichier
  apercuLocal.value = fichier ? URL.createObjectURL(fichier) : null
}

const charger = async () => {
  loading.value = true
  try {
    const response = await api.vendeur.profil.get()
    const { profil, conditions: texte } = response.data.data
    conditions.value = texte
    logoActuel.value = profil.logo_boutique
    accepteesLe.value = profil.conditions_acceptees_le
    form.value = {
      email: profil.email || '',
      description_boutique: profil.description_boutique || '',
      conditions_acceptees: Boolean(profil.conditions_acceptees_le),
    }
  } catch (error) {
    erreur.value = messageErreur(error, 'Impossible de charger votre profil boutique.')
  } finally {
    loading.value = false
  }
}

const enregistrer = async () => {
  erreur.value = ''

  if (!logoActuel.value && !fichierLogo.value) {
    erreur.value = 'Le logo de votre entreprise est obligatoire.'
    return
  }
  if (longueurDescription.value < DESCRIPTION_MIN) {
    erreur.value = `Décrivez votre entreprise en au moins ${DESCRIPTION_MIN} caractères.`
    return
  }

  const data = new FormData()
  data.append('_method', 'PUT')
  data.append('email', form.value.email.trim())
  data.append('description_boutique', form.value.description_boutique.trim())
  data.append('conditions_acceptees', form.value.conditions_acceptees ? '1' : '0')
  if (fichierLogo.value) {
    data.append('logo_boutique', fichierLogo.value)
  }

  const etaitComplet = complet.value
  enregistrement.value = true
  try {
    const response = await api.vendeur.profil.update(data)
    const { profil, user } = response.data.data
    authStore.user = user
    logoActuel.value = profil.logo_boutique
    accepteesLe.value = profil.conditions_acceptees_le
    fichierLogo.value = null
    libererApercu()
    cleChampLogo.value++

    if (etaitComplet) {
      toastStore.succes('Profil boutique mis à jour.')
    } else {
      toastStore.succes('Votre boutique est prête : vous pouvez vendre !')
      router.push({ name: 'admin-produits' })
    }
  } catch (error) {
    erreur.value = messageErreur(error, 'Impossible d\'enregistrer votre profil boutique.')
  } finally {
    enregistrement.value = false
  }
}

const deconnexion = async () => {
  await authStore.logout()
  router.push({ name: 'login' })
}

onMounted(charger)
onBeforeUnmount(libererApercu)
</script>
