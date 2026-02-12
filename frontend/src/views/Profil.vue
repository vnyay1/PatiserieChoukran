<!-- ===================================
3. PAGE PROFIL
File: src/views/Profil.vue
=================================== -->

<template>
  <div class="profil-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6">
      <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600 mb-6">
        Mon Profil
      </h1>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Menu -->
        <div class="lg:col-span-1">
          <Card padding="md">
            <div class="text-center mb-6">
              <div class="w-20 h-20 rounded-full bg-gradient-gold flex items-center justify-center text-white text-2xl font-bold mx-auto mb-3">
                {{ initiales }}
              </div>
              <h2 class="font-display font-semibold text-lg">{{ authStore.user?.nom_complet }}</h2>
              <p class="text-sm text-gray-600">{{ authStore.user?.telephone }}</p>
            </div>

            <nav class="space-y-1">
              <button
                v-for="item in menuItems"
                :key="item.id"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
                :class="activeTab === item.id ? 'bg-gold-500 text-white' : 'hover:bg-gray-50 text-gray-700'"
                @click="activeTab = item.id"
              >
                <component :is="item.icon" :size="20" />
                <span>{{ item.label }}</span>
              </button>
            </nav>

            <div class="mt-6 pt-6 border-t">
              <button
                class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-red-500 hover:bg-red-50 transition-colors"
                @click="logout"
              >
                <LogOut :size="20" />
                <span>Déconnexion</span>
              </button>
            </div>
          </Card>
        </div>

        <!-- Contenu -->
        <div class="lg:col-span-2">
          <!-- Informations personnelles -->
          <Card v-if="activeTab === 'infos'" padding="lg">
            <h2 class="font-display text-xl font-bold text-gray-800 mb-6">
              Informations personnelles
            </h2>
            <form @submit.prevent="updateProfile">
              <div class="space-y-4 mb-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet</label>
                  <input v-model="profileForm.nom_complet" type="text" class="input" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                  <div class="flex">
                    <span
                      class="inline-flex items-center px-4 py-3 rounded-l-xl border border-gray-200 border-r-0 bg-gray-100 text-gray-500"
                    >
                      +237
                    </span>
                    <input
                      v-model="profileTelephoneInput"
                      type="tel"
                      class="input rounded-l-none border-l-0"
                      placeholder="699123456"
                      required
                    />
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Indicatif non modifiable</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                  <input v-model="profileForm.email" type="email" class="input" />
                </div>
              </div>
              <Button type="submit" variant="primary" :loading="updating">
                Enregistrer les modifications
              </Button>
            </form>
          </Card>

          <!-- Adresses -->
          <Card v-if="activeTab === 'adresses'" padding="lg">
            <div class="flex items-center justify-between mb-6">
              <h2 class="font-display text-xl font-bold text-gray-800">Mes adresses</h2>
              <Button variant="outline" size="sm" @click="openAddAddress">
                + Ajouter
              </Button>
            </div>
            <div class="space-y-3">
              <div
                v-for="adresse in adresses"
                :key="adresse.id"
                class="p-4 border border-gray-200 rounded-lg hover:border-gold-500 transition-colors"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="font-semibold">{{ adresse.libelle }}</span>
                    <span v-if="adresse.est_principale" class="badge badge-primary text-xs">Principale</span>
                  </div>
                  <p class="text-sm text-gray-600">
                    {{ adresse.quartier }}, {{ adresse.ville }}<br />
                    <span v-if="adresse.zone_livraison?.nom_zone">
                      Zone: {{ adresse.zone_livraison?.nom_zone }}<br />
                    </span>
                    {{ adresse.telephone_contact }}
                  </p>
                  </div>
                  <button class="text-red-500 hover:text-red-600" @click="deleteAdresse(adresse)">
                    <Trash2 :size="18" />
                  </button>
                </div>
              </div>
            </div>
          </Card>

          <!-- Sécurité -->
          <Card v-if="activeTab === 'securite'" padding="lg">
            <h2 class="font-display text-xl font-bold text-gray-800 mb-6">
              Changer le mot de passe
            </h2>
            <form @submit.prevent="changePassword">
              <div class="space-y-4 mb-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe actuel</label>
                  <input v-model="passwordForm.ancien_mot_de_passe" type="password" class="input" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                  <input v-model="passwordForm.nouveau_mot_de_passe" type="password" class="input" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
                  <input v-model="passwordForm.nouveau_mot_de_passe_confirmation" type="password" class="input" required />
                </div>
              </div>
              <Button type="submit" variant="primary" :loading="updatingPassword">
                Changer le mot de passe
              </Button>
            </form>
          </Card>
        </div>
      </div>
    </div>

    <!-- Modal ajout adresse -->
    <div
      v-if="showAddAddress"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4 py-6"
    >
      <div class="bg-white w-full max-w-2xl rounded-elegant shadow-card overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="font-display text-xl font-bold text-gray-800">
            Ajouter une adresse
          </h2>
          <button class="text-sm text-gray-500 hover:text-gray-700" @click="closeAddAddress">
            Fermer
          </button>
        </div>

        <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submitAddress">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Libellé (optionnel)</label>
            <input v-model="addressForm.libelle" type="text" class="input" placeholder="Maison, Bureau..." />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Quartier *</label>
            <input v-model="addressForm.quartier" type="text" class="input" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
            <input v-model="addressForm.ville" type="text" class="input" required />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Zone de livraison *</label>
            <select v-model="addressForm.zone_livraison_id" class="input" required>
              <option value="">Sélectionner une zone</option>
              <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                {{ zone.ville }} - {{ zone.nom_zone }} ({{ formatPrice(zone.tarif_livraison) }} FCFA)
              </option>
            </select>
            <p v-if="addressForm.ville && zones.length === 0" class="text-xs text-red-600 mt-1">
              Aucune zone active pour cette ville.
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
            <input
              v-model="addressForm.telephone_contact"
              type="tel"
              class="input"
              placeholder="+237699123456"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Point de repère</label>
            <input v-model="addressForm.point_repere" type="text" class="input" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Complément d'adresse</label>
            <textarea v-model="addressForm.complement_adresse" rows="2" class="input resize-none"></textarea>
          </div>

          <div class="md:col-span-2">
            <label class="inline-flex items-center gap-2">
              <input
                v-model="addressForm.est_principale"
                type="checkbox"
                class="rounded border-gray-300 text-gold-600 focus:ring-gold-500"
              />
              <span class="text-sm text-gray-700">Définir comme adresse principale</span>
            </label>
          </div>

          <p v-if="addressError" class="text-sm text-red-600 md:col-span-2">
            {{ addressError }}
          </p>

          <div class="md:col-span-2 flex gap-3">
            <Button type="submit" variant="primary" :loading="savingAddress">
              Enregistrer
            </Button>
            <Button type="button" variant="outline" @click="closeAddAddress">
              Annuler
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import { User, MapPin, Lock, LogOut, Trash2 } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const activeTab = ref('infos')
const updating = ref(false)
const updatingPassword = ref(false)
const showAddAddress = ref(false)
const adresses = ref([])
const zones = ref([])
const savingAddress = ref(false)
const addressError = ref('')

const addressForm = ref({
  libelle: '',
  quartier: '',
  ville: '',
  zone_livraison_id: '',
  telephone_contact: authStore.user?.telephone || '',
  point_repere: '',
  complement_adresse: '',
  est_principale: false,
})

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

const initiales = computed(() => {
  return authStore.user?.nom_complet
    ?.split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2) || 'U'
})

const sanitizeLocalTelephone = (value) => {
  const digits = (value || '').replace(/\D/g, '')
  const withoutPrefix = digits.startsWith('237') ? digits.slice(3) : digits
  return withoutPrefix.slice(0, 9)
}

const buildTelephone = (value) => {
  const local = sanitizeLocalTelephone(value)
  return local ? `+237${local}` : ''
}

profileForm.value.telephone = sanitizeLocalTelephone(authStore.user?.telephone || '')

const profileTelephoneInput = computed({
  get: () => profileForm.value.telephone,
  set: (value) => {
    profileForm.value.telephone = sanitizeLocalTelephone(value)
  }
})

const updateProfile = async () => {
  updating.value = true
  try {
    const payload = {
      ...profileForm.value,
      telephone: buildTelephone(profileForm.value.telephone)
    }
    await api.auth.updateProfile(payload)
    alert('Profil mis à jour')
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    updating.value = false
  }
}

const changePassword = async () => {
  if (passwordForm.value.nouveau_mot_de_passe !== passwordForm.value.nouveau_mot_de_passe_confirmation) {
    alert('Les mots de passe ne correspondent pas')
    return
  }
  updatingPassword.value = true
  try {
    await api.auth.changePassword(passwordForm.value)
    alert('Mot de passe changé')
    passwordForm.value = { ancien_mot_de_passe: '', nouveau_mot_de_passe: '', nouveau_mot_de_passe_confirmation: '' }
  } catch (error) {
    console.error('Erreur:', error)
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
  }
}

const deleteAdresse = async (adresse) => {
  const confirmed = confirm(`Supprimer l'adresse "${adresse.libelle || adresse.quartier}" ?`)
  if (!confirmed) return

  try {
    const response = await api.adresses.remove(adresse.id)
    if (response.data.success) {
      fetchAdresses()
    } else {
      alert(response.data?.message || 'Erreur lors de la suppression')
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Erreur lors de la suppression')
  }
}

const resetAddressForm = () => {
  addressForm.value = {
    libelle: '',
    quartier: '',
    ville: '',
    zone_livraison_id: '',
    telephone_contact: authStore.user?.telephone || '',
    point_repere: '',
    complement_adresse: '',
    est_principale: false,
  }
  addressError.value = ''
}

const openAddAddress = () => {
  resetAddressForm()
  showAddAddress.value = true
}

const closeAddAddress = () => {
  showAddAddress.value = false
}

const submitAddress = async () => {
  savingAddress.value = true
  addressError.value = ''

  try {
    const response = await api.adresses.create(addressForm.value)
    if (response.data.success) {
      showAddAddress.value = false
      fetchAdresses()
    } else {
      addressError.value = response.data?.message || 'Erreur lors de la création de l\'adresse.'
    }
  } catch (error) {
    addressError.value = error.response?.data?.message || 'Erreur lors de la création de l\'adresse.'
  } finally {
    savingAddress.value = false
  }
}

const logout = async () => {
  await authStore.logout()
  router.push('/connexion')
}

const fetchZonesByVille = async (ville) => {
  if (!ville) {
    zones.value = []
    return
  }

  try {
    const response = await api.zones.byCity(ville)
    if (response.data.success) {
      zones.value = response.data.data || []
    }
  } catch (error) {
    console.error('Erreur chargement zones:', error)
  }
}

const formatPrice = (value) => {
  return new Intl.NumberFormat('fr-FR').format(value || 0)
}

onMounted(() => {
  fetchAdresses()
})

let zoneSearchTimeout = null
watch(
  () => addressForm.value.ville,
  (ville) => {
    clearTimeout(zoneSearchTimeout)
    const trimmed = (ville || '').trim()
    if (!trimmed) {
      zones.value = []
      addressForm.value.zone_livraison_id = ''
      return
    }
    addressForm.value.zone_livraison_id = ''
    zoneSearchTimeout = setTimeout(() => {
      fetchZonesByVille(trimmed)
    }, 300)
  }
)
</script>
