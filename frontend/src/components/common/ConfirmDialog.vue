<!-- ===================================
COMPOSANT BOÎTE DE CONFIRMATION
File: src/components/common/ConfirmDialog.vue
=================================== -->

<template>
  <Teleport to="body">
    <div
      v-if="etat.ouvert"
      class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4"
      @click.self="annuler"
    >
      <div
        class="w-full max-w-sm rounded-elegant bg-white p-6 shadow-elegant-lg animate-fadeIn"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-titre"
      >
        <h3 id="confirm-titre" class="mb-2 font-display text-xl font-bold text-gray-800">
          {{ etat.titre }}
        </h3>
        <p v-if="etat.message" class="mb-4 whitespace-pre-line text-sm text-gray-600">
          {{ etat.message }}
        </p>

        <form @submit.prevent="valider">
          <div v-if="etat.champ" class="mb-4">
            <label class="mb-2 block text-sm font-medium text-gray-700" for="confirm-champ">{{ etat.champ }}</label>
            <input
              id="confirm-champ"
              ref="champRef"
              v-model="etat.valeur"
              type="text"
              class="input"
              :placeholder="etat.placeholder"
            />
          </div>

          <div class="flex gap-3">
            <Button type="button" variant="outline" full-width @click="annuler">
              {{ etat.libelleAnnuler }}
            </Button>
            <Button
              ref="boutonRef"
              type="submit"
              :variant="etat.danger ? 'danger' : 'primary'"
              full-width
            >
              {{ etat.libelleConfirmer }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, ref, watch, onBeforeUnmount } from 'vue'
import Button from '@/components/common/Button.vue'
import { etatConfirmation as etat, fermerConfirmation } from '@/composables/useConfirm'

const champRef = ref(null)
const boutonRef = ref(null)

const annuler = () => fermerConfirmation(etat.champ ? null : false)
const valider = () => fermerConfirmation(etat.champ ? etat.valeur : true)

const surTouche = (event) => {
  if (event.key === 'Escape' && etat.ouvert) annuler()
}

watch(
  () => etat.ouvert,
  async (ouvert) => {
    if (ouvert) {
      window.addEventListener('keydown', surTouche)
      await nextTick()
      // Focus sur la saisie si présente, sinon sur le bouton de confirmation
      if (champRef.value) {
        champRef.value.focus()
      } else {
        boutonRef.value?.$el?.focus?.()
      }
    } else {
      window.removeEventListener('keydown', surTouche)
    }
  }
)

onBeforeUnmount(() => window.removeEventListener('keydown', surTouche))
</script>
