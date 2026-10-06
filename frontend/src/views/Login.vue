<!-- ===================================
2. PAGE DE CONNEXION (Mobile-First)
File: src/views/Login.vue
=================================== -->
<!--
  Deux champs, erreurs sous le champ concerné (le focus y va), message serveur en alerte.
  Arrivée depuis un ajout au panier : un bandeau explique pourquoi se connecter.
-->
<template>
  <div class="bg-gradient-peach">
    <div class="container mx-auto flex justify-center py-10 md:py-16">
      <div class="w-full max-w-md">
        <div class="mb-7 text-center">
          <h1>Bon retour parmi nous</h1>
          <p class="mt-2 text-gray-700">Connectez-vous pour commander et suivre vos livraisons.</p>
        </div>

        <div class="card p-6 sm:p-8">
          <AlertMessage v-if="route.query.redirect" type="info" class="mb-6">
            Connectez-vous pour continuer : vous reviendrez ensuite à la page que vous consultiez.
          </AlertMessage>

          <form class="space-y-5" novalidate @submit.prevent="handleLogin">
            <FormField v-slot="{ attrs }" label="Numéro de téléphone" requis :erreur="erreurs.telephone">
              <TelephoneInput v-model="form.telephone" v-bind="attrs" />
            </FormField>

            <FormField v-slot="{ attrs }" label="Mot de passe" requis :erreur="erreurs.mot_de_passe">
              <PasswordInput v-model="form.mot_de_passe" v-bind="attrs" autocomplete="current-password" />
            </FormField>

            <AlertMessage v-if="error" type="error">{{ error }}</AlertMessage>

            <Button type="submit" variant="primary" size="lg" :loading="loading" full-width>
              Se connecter
            </Button>
          </form>

          <div class="mt-7 border-t border-gray-200 pt-6 text-center">
            <p class="text-sm text-gray-600">Nouveau chez Choukrane ?</p>
            <Button :to="{ name: 'register', query: route.query }" variant="secondary" full-width class="mt-3">
              Créer un compte
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Button from '@/components/common/Button.vue'
import FormField from '@/components/common/FormField.vue'
import AlertMessage from '@/components/common/AlertMessage.vue'
import PasswordInput from '@/components/common/PasswordInput.vue'
import TelephoneInput from '@/components/common/TelephoneInput.vue'
import { destinationApresConnexion } from '@/utils/redirection'
import { estTelephoneComplet, telephoneComplet } from '@/utils/telephone'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  telephone: '',
  mot_de_passe: ''
})

const loading = ref(false)
const error = ref(null)
const erreurs = ref({})

const valider = () => {
  const manquants = {}
  if (!estTelephoneComplet(form.value.telephone)) {
    manquants.telephone = 'Saisissez les 9 chiffres de votre numéro (ex. 699 12 34 56).'
  }
  if (!form.value.mot_de_passe) {
    manquants.mot_de_passe = 'Saisissez votre mot de passe.'
  }
  erreurs.value = manquants
  return Object.keys(manquants).length === 0
}

const handleLogin = async () => {
  error.value = null
  if (!valider()) {
    await nextTick()
    document.querySelector('[aria-invalid="true"]')?.focus()
    return
  }

  loading.value = true

  const result = await authStore.login({
    telephone: telephoneComplet(form.value.telephone),
    mot_de_passe: form.value.mot_de_passe
  })

  loading.value = false

  if (result.success) {
    // Retour à la page demandée avant la connexion (ex. panier, commande)
    router.push(destinationApresConnexion(route, authStore))
  } else {
    error.value = result.error || 'Erreur de connexion'
  }
}
</script>
