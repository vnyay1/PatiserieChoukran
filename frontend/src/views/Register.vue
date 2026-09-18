<!-- ===================================
3. PAGE D'INSCRIPTION (Mobile-First)
File: src/views/Register.vue
=================================== -->
<!--
  Champs dans l'ordre naturel, autocomplete complet (remplissage automatique du téléphone),
  règle du mot de passe affichée avant la saisie, erreurs sous chaque champ.
-->
<template>
  <div class="bg-gradient-peach">
    <div class="container mx-auto flex justify-center py-10 md:py-16">
      <div class="w-full max-w-md">
        <div class="mb-7 text-center">
          <h1>Créer mon compte</h1>
          <p class="mt-2 text-gray-700">Commandez en quelques secondes et suivez chaque livraison.</p>
        </div>

        <div class="card p-6 sm:p-8">
          <form class="space-y-5" novalidate @submit.prevent="handleRegister">
            <FormField v-slot="{ attrs }" label="Nom complet" requis :erreur="erreurs.nom_complet">
              <input
                v-model="form.nom_complet"
                v-bind="attrs"
                type="text"
                autocomplete="name"
                autocapitalize="words"
                class="input"
              />
            </FormField>

            <FormField
              v-slot="{ attrs }"
              label="Numéro de téléphone"
              requis
              aide="Il vous servira d'identifiant et au vendeur pour vous livrer."
              :erreur="erreurs.telephone"
            >
              <TelephoneInput v-model="form.telephone" v-bind="attrs" />
            </FormField>

            <FormField
              v-slot="{ attrs }"
              label="E-mail"
              facultatif
              aide="Pour recevoir vos factures."
              :erreur="erreurs.email"
            >
              <input v-model="form.email" v-bind="attrs" type="email" autocomplete="email" inputmode="email" class="input" />
            </FormField>

            <FormField
              v-slot="{ attrs }"
              label="Mot de passe"
              requis
              aide="6 caractères minimum."
              :erreur="erreurs.mot_de_passe"
            >
              <PasswordInput v-model="form.mot_de_passe" v-bind="attrs" autocomplete="new-password" minlength="6" />
            </FormField>

            <FormField v-slot="{ attrs }" label="Confirmer le mot de passe" requis :erreur="erreurs.mot_de_passe_confirmation">
              <PasswordInput v-model="form.mot_de_passe_confirmation" v-bind="attrs" autocomplete="new-password" minlength="6" />
            </FormField>

            <AlertMessage v-if="error" type="error">{{ error }}</AlertMessage>

            <Button type="submit" variant="primary" size="lg" :loading="loading" full-width>
              Créer mon compte
            </Button>
          </form>

          <div class="mt-7 border-t border-gray-200 pt-6 text-center">
            <p class="text-sm text-gray-600">Déjà un compte ?</p>
            <Button :to="{ name: 'login', query: route.query }" variant="secondary" full-width class="mt-3">
              Se connecter
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
  nom_complet: '',
  telephone: '',
  email: '',
  mot_de_passe: '',
  mot_de_passe_confirmation: ''
})

const loading = ref(false)
const error = ref(null)
const erreurs = ref({})

const EMAIL_VALIDE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const valider = () => {
  const manquants = {}
  if (!form.value.nom_complet.trim()) manquants.nom_complet = 'Indiquez votre nom et votre prénom.'
  if (!estTelephoneComplet(form.value.telephone)) manquants.telephone = 'Le numéro doit comporter 9 chiffres (ex. 699 12 34 56).'
  if (form.value.email && !EMAIL_VALIDE.test(form.value.email)) manquants.email = 'Cette adresse e-mail n\'est pas valide (ex. nom@exemple.com).'
  if (form.value.mot_de_passe.length < 6) manquants.mot_de_passe = 'Le mot de passe doit contenir au moins 6 caractères.'
  if (form.value.mot_de_passe_confirmation !== form.value.mot_de_passe) {
    manquants.mot_de_passe_confirmation = 'Les deux mots de passe ne correspondent pas.'
  }
  erreurs.value = manquants
  return Object.keys(manquants).length === 0
}

const handleRegister = async () => {
  error.value = null
  if (!valider()) {
    await nextTick()
    document.querySelector('[aria-invalid="true"]')?.focus()
    return
  }

  loading.value = true

  const result = await authStore.register({
    nom_complet: form.value.nom_complet.trim(),
    telephone: telephoneComplet(form.value.telephone),
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
