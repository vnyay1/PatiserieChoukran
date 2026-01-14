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
                  <input v-model="profileForm.telephone" type="tel" class="input" required />
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
              <Button variant="outline" size="sm" @click="showAddAddress = true">
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
                      {{ adresse.telephone_contact }}
                    </p>
                  </div>
                  <button class="text-red-500 hover:text-red-600">
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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

const profileForm = ref({
  nom_complet: authStore.user?.nom_complet || '',
  telephone: authStore.user?.telephone || '',
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

const updateProfile = async () => {
  updating.value = true
  try {
    await api.auth.updateProfile(profileForm.value)
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

const logout = async () => {
  await authStore.logout()
  router.push('/connexion')
}

onMounted(() => {
  fetchAdresses()
})
</script>