<!-- ===================================
3. PAGE PROFIL
File: src/views/Profil.vue
=================================== -->
<!--
  Onglets ARIA (tablist / tab / tabpanel) : flèches, Début et Fin déplacent le focus,
  un seul onglet dans l'ordre de tabulation. Horizontaux sur mobile, verticaux dès lg.
-->
<template>
  <div class="container mx-auto pb-6 pt-6 md:pt-8">
    <h1 class="mb-6">Mon compte</h1>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[18rem_minmax(0,1fr)] lg:gap-8">
      <!-- Identité et navigation -->
      <div class="card p-4 sm:p-5">
        <div class="flex items-center gap-3 lg:flex-col lg:text-center">
          <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full bg-gold-500 text-lg font-bold text-on-gold lg:h-20 lg:w-20 lg:text-2xl" aria-hidden="true">
            {{ initiales }}
          </span>
          <div class="min-w-0">
            <p class="truncate font-display text-lg font-semibold text-gray-900">{{ authStore.user?.nom_complet }}</p>
            <p class="text-sm text-gray-600">{{ authStore.user?.telephone }}</p>
          </div>
        </div>

        <div
          role="tablist"
          aria-label="Rubriques du compte"
          :aria-orientation="estLarge ? 'vertical' : 'horizontal'"
          class="-mx-1 mt-5 flex gap-1 overflow-x-auto px-1 pb-1 scrollbar-hide lg:flex-col lg:overflow-visible"
          @keydown="naviguerOnglets"
        >
          <button
            v-for="item in menuItems"
            :id="`onglet-${item.id}`"
            :key="item.id"
            type="button"
            role="tab"
            class="onglet"
            :aria-selected="activeTab === item.id"
            :aria-controls="`panneau-${item.id}`"
            :tabindex="activeTab === item.id ? 0 : -1"
            @click="activeTab = item.id"
          >
            <component :is="item.icon" :size="19" aria-hidden="true" />
            {{ item.label }}
          </button>
        </div>

        <div class="mt-5 space-y-4 border-t border-gray-200 pt-5">
          <ThemeToggle variante="segments" />
          <button type="button" class="btn-ghost w-full justify-start text-red-700 hover:bg-red-50" @click="logout">
            <LogOut :size="19" aria-hidden="true" />
            Se déconnecter
          </button>
        </div>
      </div>

      <!-- Informations personnelles -->
      <section
        v-if="activeTab === 'infos'"
        id="panneau-infos"
        role="tabpanel"
        aria-labelledby="onglet-infos"
        tabindex="0"
        class="card p-5 sm:p-8"
      >
        <h2 class="text-xl">Informations personnelles</h2>
        <form class="mt-6 max-w-lg space-y-5" novalidate @submit.prevent="updateProfile">
          <FormField v-slot="{ attrs }" label="Nom complet" requis :erreur="erreursProfil.nom_complet">
            <input v-model="profileForm.nom_complet" v-bind="attrs" type="text" autocomplete="name" class="input" />
          </FormField>

          <FormField v-slot="{ attrs }" label="Numéro de téléphone" :requis="!isClientProfileLocked" :erreur="erreursProfil.telephone">
            <TelephoneInput v-model="profileForm.telephone" v-bind="attrs" :disabled="isClientProfileLocked" />
          </FormField>

          <FormField v-slot="{ attrs }" label="E-mail" :facultatif="!isClientProfileLocked" :erreur="erreursProfil.email">
            <input
              v-model="profileForm.email"
              v-bind="attrs"
              type="email"
              autocomplete="email"
              class="input"
              :disabled="isClientProfileLocked"
            />
          </FormField>

          <AlertMessage v-if="isClientProfileLocked" type="info">
            Le numéro de téléphone et l'e-mail d'un compte client ne sont pas modifiables.
            Contactez-nous au +237 658 55 56 00 pour les changer.
          </AlertMessage>

          <AlertMessage v-if="profileError" type="error">{{ profileError }}</AlertMessage>

          <Button type="submit" variant="primary" :loading="updating">
            Enregistrer les modifications
          </Button>
        </form>
      </section>

      <!-- Adresses -->
      <section
        v-if="activeTab === 'adresses'"
        id="panneau-adresses"
        role="tabpanel"
        aria-labelledby="onglet-adresses"
        tabindex="0"
        class="card p-5 sm:p-8"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-xl">Mes adresses</h2>
          <Button variant="secondary" size="sm" :icon="Plus" @click="openAddAddress">
            Ajouter une adresse
          </Button>
        </div>

        <EmptyState
          v-if="!chargementAdresses && adresses.length === 0"
          :icone="MapPin"
          niveau="h3"
          titre="Aucune adresse enregistrée"
          texte="Ajoutez votre adresse une fois pour toutes : elle sera proposée à chaque commande."
        />

        <ul v-else class="mt-5 space-y-3">
          <li
            v-for="adresse in adresses"
            :key="adresse.id"
            class="flex items-start gap-3 rounded-2xl border border-gray-200 p-4"
          >
            <MapPin :size="20" class="mt-0.5 flex-shrink-0 text-gold-600" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <p class="flex flex-wrap items-center gap-2 font-semibold text-gray-900">
                {{ adresse.libelle || adresse.quartier || 'Adresse' }}
                <span v-if="adresse.est_principale" class="badge badge-primary">Principale</span>
              </p>
              <p class="text-sm text-gray-600">
                {{ [adresse.zone, adresse.quartier, formatVille(villeAdresse(adresse))].filter(Boolean).join(', ') }}
              </p>
              <p v-if="adresse.telephone_contact" class="text-sm text-gray-600">{{ adresse.telephone_contact }}</p>
              <p v-if="!adresse.quartier_id" class="mt-1 flex items-center gap-1.5 text-sm font-medium text-orange-700">
                <AlertTriangle :size="14" aria-hidden="true" />
                Quartier à préciser pour pouvoir être livré
              </p>
            </div>
            <div class="-my-1 -mr-2 flex flex-shrink-0">
              <button
                type="button"
                class="btn-icone"
                :aria-label="`Modifier l'adresse ${adresse.libelle || adresse.quartier}`"
                @click="openEditAddress(adresse)"
              >
                <Pencil :size="18" aria-hidden="true" />
              </button>
              <button
                type="button"
                class="btn-icone hover:bg-red-50 hover:text-red-700"
                :aria-label="`Supprimer l'adresse ${adresse.libelle || adresse.quartier}`"
                @click="deleteAdresse(adresse)"
              >
                <Trash2 :size="18" aria-hidden="true" />
              </button>
            </div>
          </li>
        </ul>
      </section>

      <!-- Sécurité -->
      <section
        v-if="activeTab === 'securite'"
        id="panneau-securite"
        role="tabpanel"
        aria-labelledby="onglet-securite"
        tabindex="0"
        class="card p-5 sm:p-8"
      >
        <h2 class="text-xl">Changer le mot de passe</h2>
        <p class="mt-1 text-sm text-gray-600">Vous serez déconnecté de tous vos appareils et devrez vous reconnecter.</p>

        <form class="mt-6 max-w-lg space-y-5" novalidate @submit.prevent="changePassword">
          <FormField v-slot="{ attrs }" label="Mot de passe actuel" requis :erreur="erreursMotDePasse.ancien_mot_de_passe">
            <PasswordInput v-model="passwordForm.ancien_mot_de_passe" v-bind="attrs" autocomplete="current-password" />
          </FormField>
          <FormField v-slot="{ attrs }" label="Nouveau mot de passe" requis aide="6 caractères minimum." :erreur="erreursMotDePasse.nouveau_mot_de_passe">
            <PasswordInput v-model="passwordForm.nouveau_mot_de_passe" v-bind="attrs" autocomplete="new-password" minlength="6" />
          </FormField>
          <FormField v-slot="{ attrs }" label="Confirmer le nouveau mot de passe" requis :erreur="erreursMotDePasse.nouveau_mot_de_passe_confirmation">
            <PasswordInput v-model="passwordForm.nouveau_mot_de_passe_confirmation" v-bind="attrs" autocomplete="new-password" minlength="6" />
          </FormField>

          <AlertMessage v-if="passwordError" type="error">{{ passwordError }}</AlertMessage>

          <Button type="submit" variant="primary" :loading="updatingPassword">
            Changer le mot de passe
          </Button>
        </form>
      </section>
    </div>

    <!-- Ajout / modification d'adresse -->
    <AdresseFormModal
      v-if="showAddAddress"
      :adresse="adresseEnEdition"
      :telephone-par-defaut="authStore.user?.telephone || ''"
      @close="closeAddAddress"
      @saved="onAdresseSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import api, { messageErreur } from '@/services/api'
import Button from '@/components/common/Button.vue'
import FormField from '@/components/common/FormField.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import PasswordInput from '@/components/common/PasswordInput.vue'
import TelephoneInput from '@/components/common/TelephoneInput.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import AdresseFormModal from '@/components/adresse/AdresseFormModal.vue'
import { formatVille, villeAdresse } from '@/utils/villes'
import { chiffresLocaux, telephoneComplet } from '@/utils/telephone'
import { useConfirm } from '@/composables/useConfirm'
import { User, MapPin, Lock, LogOut, Trash2, Pencil, Plus, AlertTriangle } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()
const { confirmer } = useConfirm()

const activeTab = ref('infos')
const updating = ref(false)
const updatingPassword = ref(false)
const showAddAddress = ref(false)
const adresseEnEdition = ref(null)
const adresses = ref([])
const chargementAdresses = ref(true)
const profileError = ref('')
const passwordError = ref('')
const erreursProfil = ref({})
const erreursMotDePasse = ref({})

const profileForm = ref({
  nom_complet: authStore.user?.nom_complet || '',
  telephone: '',
  email: authStore.user?.email || '',
})

const passwordForm = ref({
  ancien_mot_de_passe: '',
  nouveau_mot_de_passe: '',
  nouveau_mot_de_passe_confirmation: '',
})

const menuItems = [
  { id: 'infos', label: 'Informations', icon: User },
  { id: 'adresses', label: 'Adresses', icon: MapPin },
  { id: 'securite', label: 'Sécurité', icon: Lock },
]

// Orientation des onglets (verticaux dès lg) : annoncée et utilisée par le clavier
const requeteLarge = window.matchMedia('(min-width: 1024px)')
const estLarge = ref(requeteLarge.matches)
const suivreLargeur = (event) => {
  estLarge.value = event.matches
}
onMounted(() => requeteLarge.addEventListener('change', suivreLargeur))
onBeforeUnmount(() => requeteLarge.removeEventListener('change', suivreLargeur))

const naviguerOnglets = async (event) => {
  const index = menuItems.findIndex((item) => item.id === activeTab.value)
  const dernier = menuItems.length - 1
  const TOUCHES = {
    ArrowRight: index === dernier ? 0 : index + 1,
    ArrowDown: index === dernier ? 0 : index + 1,
    ArrowLeft: index === 0 ? dernier : index - 1,
    ArrowUp: index === 0 ? dernier : index - 1,
    Home: 0,
    End: dernier,
  }
  if (!(event.key in TOUCHES)) return

  event.preventDefault()
  activeTab.value = menuItems[TOUCHES[event.key]].id
  await nextTick()
  document.getElementById(`onglet-${activeTab.value}`)?.focus()
}

const initiales = computed(() => {
  return authStore.user?.nom_complet
    ?.split(' ')
    .filter(Boolean)
    .map((mot) => mot[0])
    .join('')
    .toUpperCase()
    .slice(0, 2) || 'U'
})
const isClientProfileLocked = computed(() => authStore.user?.role === 'client')

const focusPremiereErreur = async () => {
  await nextTick()
  document.querySelector('[aria-invalid="true"]')?.focus()
}

const updateProfile = async () => {
  profileError.value = ''
  erreursProfil.value = {}

  const nomComplet = (profileForm.value.nom_complet || '').trim()
  const localTelephone = chiffresLocaux(profileForm.value.telephone)
  const email = (profileForm.value.email || '').trim()

  if (!nomComplet) {
    erreursProfil.value.nom_complet = 'Le nom complet est requis.'
  }
  if (!isClientProfileLocked.value && localTelephone.length !== 9) {
    erreursProfil.value.telephone = 'Le numéro de téléphone doit contenir 9 chiffres.'
  }
  if (Object.keys(erreursProfil.value).length > 0) {
    focusPremiereErreur()
    return
  }

  updating.value = true
  try {
    const payload = {
      nom_complet: nomComplet,
    }
    if (!isClientProfileLocked.value) {
      payload.telephone = telephoneComplet(localTelephone)
      payload.email = email || null
    }

    const response = await api.auth.updateProfile(payload)
    const updatedUser = response.data?.data

    if (updatedUser) {
      authStore.user = {
        ...authStore.user,
        ...updatedUser,
      }
    } else {
      await authStore.fetchUser()
    }

    profileForm.value.nom_complet = authStore.user?.nom_complet || nomComplet
    profileForm.value.telephone = chiffresLocaux(authStore.user?.telephone || localTelephone)
    profileForm.value.email = authStore.user?.email || ''

    toastStore.succes('Profil mis à jour.')
  } catch (error) {
    const validationErrors = error.response?.data?.errors || {}
    erreursProfil.value = {
      nom_complet: validationErrors.nom_complet?.[0],
      telephone: validationErrors.telephone?.[0],
      email: validationErrors.email?.[0],
    }
    if (!Object.values(erreursProfil.value).some(Boolean)) {
      profileError.value = messageErreur(error, 'Erreur lors de la mise à jour du profil.')
    }
    console.error('Erreur mise à jour profil:', error)
  } finally {
    updating.value = false
  }
}

const changePassword = async () => {
  passwordError.value = ''
  const erreurs = {}
  if (!passwordForm.value.ancien_mot_de_passe) erreurs.ancien_mot_de_passe = 'Saisissez votre mot de passe actuel.'
  if (passwordForm.value.nouveau_mot_de_passe.length < 6) erreurs.nouveau_mot_de_passe = 'Le nouveau mot de passe doit contenir au moins 6 caractères.'
  if (passwordForm.value.nouveau_mot_de_passe !== passwordForm.value.nouveau_mot_de_passe_confirmation) {
    erreurs.nouveau_mot_de_passe_confirmation = 'Les deux mots de passe ne correspondent pas.'
  }
  erreursMotDePasse.value = erreurs
  if (Object.keys(erreurs).length > 0) {
    focusPremiereErreur()
    return
  }

  updatingPassword.value = true
  try {
    await api.auth.changePassword(passwordForm.value)
    passwordForm.value = { ancien_mot_de_passe: '', nouveau_mot_de_passe: '', nouveau_mot_de_passe_confirmation: '' }

    // Le backend révoque tous les tokens après un changement de mot de passe
    await authStore.logout({ callApi: false })
    toastStore.succes('Mot de passe changé. Veuillez vous reconnecter.')
    router.push({ name: 'login' })
  } catch (error) {
    passwordError.value = messageErreur(error, 'Erreur lors du changement de mot de passe.')
  } finally {
    updatingPassword.value = false
  }
}

const fetchAdresses = async () => {
  try {
    const response = await api.adresses.getAll()
    if (response.data.success) {
      adresses.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    chargementAdresses.value = false
  }
}

const deleteAdresse = async (adresse) => {
  const confirmed = await confirmer({
    titre: 'Supprimer l\'adresse ?',
    message: `L'adresse « ${adresse.libelle || adresse.quartier} » sera supprimée.`,
    libelleConfirmer: 'Supprimer',
    danger: true,
  })
  if (!confirmed) return

  try {
    const response = await api.adresses.remove(adresse.id)
    if (response.data.success) {
      toastStore.succes('Adresse supprimée.')
      fetchAdresses()
    } else {
      toastStore.erreur(response.data?.message || 'Erreur lors de la suppression.')
    }
  } catch (error) {
    toastStore.erreur(messageErreur(error, 'Erreur lors de la suppression.'))
  }
}

const openAddAddress = () => {
  adresseEnEdition.value = null
  showAddAddress.value = true
}

const openEditAddress = (adresse) => {
  if (!adresse) return
  adresseEnEdition.value = adresse
  showAddAddress.value = true
}

const closeAddAddress = () => {
  showAddAddress.value = false
  adresseEnEdition.value = null
}

const onAdresseSaved = async () => {
  closeAddAddress()
  toastStore.succes('Adresse enregistrée.')
  await fetchAdresses()
}

const logout = async () => {
  await authStore.logout()
  router.push('/connexion')
}

onMounted(() => {
  fetchAdresses()
})

watch(
  () => authStore.user,
  (user) => {
    if (!user) return
    profileForm.value.nom_complet = user.nom_complet || ''
    profileForm.value.telephone = chiffresLocaux(user.telephone || '')
    profileForm.value.email = user.email || ''
  },
  { immediate: true }
)
</script>

<style scoped>
.onglet {
  @apply flex min-h-11 flex-shrink-0 items-center gap-3 whitespace-nowrap rounded-xl px-4 text-[0.9375rem] font-semibold text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900;
}

.onglet[aria-selected='true'] {
  @apply bg-gold-100 text-gold-800;
}
</style>
