<!-- ===================================
COMPOSANT BOÎTE DE CONFIRMATION
File: src/components/common/ConfirmDialog.vue
=================================== -->
<!--
  Monté une fois dans App.vue, piloté par useConfirm().
  Focus initial : la saisie s'il y en a une, sinon « Annuler » pour une action
  destructrice (Entrée ne supprime jamais par accident), sinon « Confirmer ».
-->
<template>
  <BaseModal
    :ouvert="etat.ouvert"
    :titre="etat.titre"
    taille="sm"
    @fermer="annuler"
  >
    <form :id="idFormulaire" @submit.prevent="valider">
      <p v-if="etat.message" class="whitespace-pre-line text-gray-700">
        {{ etat.message }}
      </p>

      <div v-if="etat.champ" :class="{ 'mt-4': etat.message }">
        <label class="label" :for="idChamp">{{ etat.champ }}</label>
        <input
          :id="idChamp"
          v-model="etat.valeur"
          type="text"
          class="input"
          :placeholder="etat.placeholder"
          data-autofocus
        />
      </div>
    </form>

    <template #actions>
      <Button
        type="button"
        variant="outline"
        :data-autofocus="!etat.champ && etat.danger ? '' : undefined"
        @click="annuler"
      >
        {{ etat.libelleAnnuler }}
      </Button>
      <Button
        type="submit"
        :form="idFormulaire"
        :variant="etat.danger ? 'danger' : 'primary'"
        :data-autofocus="!etat.champ && !etat.danger ? '' : undefined"
      >
        {{ etat.libelleConfirmer }}
      </Button>
    </template>
  </BaseModal>
</template>

<script setup>
import { useId } from 'vue'
import BaseModal from '@/components/common/BaseModal.vue'
import Button from '@/components/common/Button.vue'
import { etatConfirmation as etat, fermerConfirmation } from '@/composables/useConfirm'

const idFormulaire = useId()
const idChamp = useId()

const annuler = () => fermerConfirmation(etat.champ ? null : false)
const valider = () => fermerConfirmation(etat.champ ? etat.valeur : true)
</script>
