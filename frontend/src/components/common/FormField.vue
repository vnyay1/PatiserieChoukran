<!-- ===================================
COMPOSANT CHAMP DE FORMULAIRE
File: src/components/common/FormField.vue
=================================== -->
<!--
  Relie le libellé, l'aide et l'erreur au champ (1.3.1, 3.3.1, 3.3.2) :
    <FormField v-slot="{ attrs }" label="Nom complet" requis :erreur="erreurs.nom">
      <input v-model="nom" v-bind="attrs" class="input" autocomplete="name" />
    </FormField>
  `attrs` contient id, aria-describedby, aria-invalid et required.
-->
<template>
  <div>
    <label :for="id" class="label">
      {{ label }}
      <span v-if="requis" class="text-red-600" aria-hidden="true">*</span>
      <span v-else-if="facultatif" class="font-normal text-gray-500">(facultatif)</span>
    </label>
    <slot :id="id" :attrs="attrs" />
    <p v-if="aide" :id="idAide" class="aide">{{ aide }}</p>
    <p v-if="erreur" :id="idErreur" class="erreur-champ">
      <AlertCircle :size="15" class="mt-px flex-shrink-0" aria-hidden="true" />
      <span>{{ erreur }}</span>
    </p>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue'
import { AlertCircle } from 'lucide-vue-next'

const props = defineProps({
  label: {
    type: String,
    required: true
  },
  // Identifiant imposé (sinon généré)
  idChamp: {
    type: String,
    default: ''
  },
  aide: {
    type: String,
    default: ''
  },
  erreur: {
    type: String,
    default: ''
  },
  requis: {
    type: Boolean,
    default: false
  },
  facultatif: {
    type: Boolean,
    default: false
  }
})

const idGenere = useId()
const id = computed(() => props.idChamp || idGenere)
const idAide = computed(() => `${id.value}-aide`)
const idErreur = computed(() => `${id.value}-erreur`)

const attrs = computed(() => {
  const decritPar = [props.erreur ? idErreur.value : null, props.aide ? idAide.value : null]
    .filter(Boolean)
    .join(' ')

  return {
    id: id.value,
    'aria-describedby': decritPar || undefined,
    'aria-invalid': props.erreur ? 'true' : undefined,
    required: props.requis || undefined,
  }
})
</script>
