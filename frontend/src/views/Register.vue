<!-- ===================================
3. PAGE D'INSCRIPTION (Mobile-First)
File: src/views/Register.vue
=================================== -->

<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-peach">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <img src="/logo.png" alt="Choukrane" class="h-16 mx-auto mb-4" />
        <h1 class="font-display text-3xl font-bold text-gold-700">Inscription</h1>
        <p class="text-gray-600 mt-2">Rejoignez Choukrane</p>
      </div>

      <!-- Formulaire -->
      <Card padding="lg">
        <form @submit.prevent="handleRegister">
          <!-- Nom complet -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nom complet
            </label>
            <input
              v-model="form.nom_complet"
              type="text"
              placeholder="Jean Dupont"
              class="input"
              required
            />
          </div>

          <!-- Téléphone -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Numéro de téléphone
            </label>
            <div class="flex">
              <span
                class="inline-flex items-center px-4 py-3 rounded-l-xl border border-gray-200 border-r-0 bg-gray-100 text-gray-500"
              >
                +237
              </span>
              <input
                v-model="telephoneInput"
                type="tel"
                inputmode="numeric"
                autocomplete="tel-national"
                placeholder="699123456"
                class="input rounded-l-none border-l-0"
                required
              />
            </div>
            <p class="text-xs text-gray-500 mt-1">Indicatif non modifiable</p>
          </div>

          <!-- Email (optionnel) -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Email (optionnel)
            </label>
            <input
              v-model="form.email"
              type="email"
              placeholder="jean@example.com"
              class="input"
            />
          </div>

          <!-- Mot de passe -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Mot de passe
            </label>
            <PasswordInput
              v-model="form.mot_de_passe"
              placeholder="••••••••"
              autocomplete="new-password"
              required
              minlength="6"
            />
            <p class="text-xs text-gray-500 mt-1">Minimum 6 caractères</p>
          </div>

          <!-- Confirmation mot de passe -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Confirmer le mot de passe
            </label>
            <PasswordInput
              v-model="form.mot_de_passe_confirmation"
              placeholder="••••••••"
              autocomplete="new-password"
              required
              minlength="6"
            />
          </div>

          <!-- Erreur -->
          <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ error }}</p>
          </div>

          <!-- Bouton -->
          <Button
            type="submit"
            variant="primary"
            size="lg"
            :loading="loading"
            full-width
          >
            S'inscrire
          </Button>
        </form>

        <!-- Lien connexion -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            Déjà un compte ?
            <router-link
              :to="{ name: 'login', query: route.query }"
              class="text-gold-600 hover:text-gold-700 font-medium"
            >
              Se connecter
            </router-link>
          </p>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Button from '@/components/common/Button.vue'
import Card from '@/components/common/Card.vue'
import PasswordInput from '@/components/common/PasswordInput.vue'
import { destinationApresConnexion } from '@/utils/redirection'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  nom_complet: '',
  telephone: '',
  email: '',
  mot_de_passe: '',
  mot_de_passe_confirmation: ''
})

const loading = ref(false)
const error = ref(null)

const sanitizeLocalTelephone = (value) => {
  const digits = (value || '').replace(/\D/g, '')
  const withoutPrefix = digits.startsWith('237') ? digits.slice(3) : digits
  return withoutPrefix.slice(0, 9)
}

const buildTelephone = (value) => {
  const local = sanitizeLocalTelephone(value)
  return local ? `+237${local}` : ''
}

const telephoneInput = computed({
  get: () => form.value.telephone,
  set: (value) => {
    form.value.telephone = sanitizeLocalTelephone(value)
  }
})

const handleRegister = async () => {
  if (sanitizeLocalTelephone(form.value.telephone).length !== 9) {
    error.value = 'Le numéro de téléphone doit comporter 9 chiffres.'
    return
  }

  // Vérifier que les mots de passe correspondent
  if (form.value.mot_de_passe !== form.value.mot_de_passe_confirmation) {
    error.value = 'Les mots de passe ne correspondent pas'
    return
  }

  loading.value = true
  error.value = null

  const result = await authStore.register({
    nom_complet: form.value.nom_complet,
    telephone: buildTelephone(form.value.telephone),
    email: form.value.email || null,
    mot_de_passe: form.value.mot_de_passe,
    mot_de_passe_confirmation: form.value.mot_de_passe_confirmation
  })

  loading.value = false

  if (result.success) {
    router.push(destinationApresConnexion(route, authStore))
  } else {
    error.value = result.error || 'Erreur lors de l\'inscription'
  }
}
</script>
