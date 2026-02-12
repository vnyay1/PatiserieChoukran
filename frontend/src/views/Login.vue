<!-- ===================================
2. PAGE DE CONNEXION (Mobile-First)
File: src/views/Login.vue
=================================== -->

<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-peach">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <img src="/logo.png" alt="Choukrane" class="h-16 mx-auto mb-4" />
        <h1 class="font-display text-3xl font-bold text-gold-700">Connexion</h1>
        <p class="text-gray-600 mt-2">Bienvenue chez Choukrane</p>
      </div>

      <!-- Formulaire -->
      <Card padding="lg">
        <form @submit.prevent="handleLogin">
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
                placeholder="699123456"
                class="input rounded-l-none border-l-0"
                required
              />
            </div>
            <p class="text-xs text-gray-500 mt-1">Indicatif non modifiable</p>
          </div>

          <!-- Mot de passe -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Mot de passe
            </label>
            <input
              v-model="form.mot_de_passe"
              type="password"
              placeholder="••••••••"
              class="input"
              required
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
            Se connecter
          </Button>
        </form>

        <!-- Lien inscription -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            Pas encore de compte ?
            <router-link to="/inscription" class="text-gold-600 hover:text-gold-700 font-medium">
              S'inscrire
            </router-link>
          </p>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Button from '@/components/common/Button.vue'
import Card from '@/components/common/Card.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  telephone: '',
  mot_de_passe: ''
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

const handleLogin = async () => {
  loading.value = true
  error.value = null

  const result = await authStore.login({
    telephone: buildTelephone(form.value.telephone),
    mot_de_passe: form.value.mot_de_passe
  })

  loading.value = false

  if (result.success) {
    router.push('/')
  } else {
    error.value = result.error || 'Erreur de connexion'
  }
}
</script>
