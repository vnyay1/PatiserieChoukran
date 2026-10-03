<!-- ===================================
COMPOSANT TÉLÉVERSEMENT D'IMAGE AVEC APERÇU
File: src/components/common/TeleversementImage.vue
=================================== -->
<!--
  v-model : le fichier choisi (File) ou null ; imageActuelle : chemin déjà enregistré (modification).
  Le fichier est vérifié dès le choix (format, poids : verifierImage, mêmes règles que le backend) :
  un refus est émis par « erreur » et le champ est vidé. L'URL locale de l'aperçu est libérée à
  chaque changement et au démontage. Les attributs (id, required, aria-*) vont sur le champ
  fichier : à placer dans <FormField v-slot="{ attrs }"> ou derrière un <label for>.
-->
<template>
  <div class="flex items-center gap-4">
    <div
      class="flex flex-shrink-0 items-center justify-center overflow-hidden border border-gray-200"
      :class="logo ? 'h-24 w-24 rounded-elegant bg-plaque' : 'h-20 w-20 rounded-xl bg-gray-100'"
    >
      <img
        v-if="apercu"
        :src="apercu"
        alt="Aperçu de l'image"
        class="h-full w-full"
        :class="logo ? 'object-contain' : 'object-cover'"
        @error="onImageError"
      />
      <component :is="icone || ImageIcon" v-else :size="28" class="text-gray-500" aria-hidden="true" />
    </div>

    <div class="min-w-0 flex-1">
      <input
        :key="cleChamp"
        v-bind="$attrs"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        class="input"
        :aria-describedby="idsAide"
        @change="choisir"
      />
      <p :id="idAide" class="aide">
        JPEG, PNG ou WebP, {{ TAILLE_MAX_IMAGE_MO }} Mo maximum.{{ aide ? ' ' + aide : '' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, useAttrs, useId, watch } from 'vue'
import { Image as ImageIcon } from 'lucide-vue-next'
import { resolveImageUrl, onImageError, verifierImage, TAILLE_MAX_IMAGE_MO } from '@/utils/images'

defineOptions({ inheritAttrs: false })

const fichier = defineModel({ type: File, default: null })

const props = defineProps({
  imageActuelle: {
    type: String,
    default: null,
  },
  // Logo : image entière (contain) sur fond blanc fixe, dans les deux thèmes
  logo: {
    type: Boolean,
    default: false,
  },
  // Phrase ajoutée après les formats acceptés
  aide: {
    type: String,
    default: '',
  },
  // Icône affichée tant qu'il n'y a pas d'image
  icone: {
    type: [Object, Function],
    default: null,
  },
})

const emit = defineEmits(['erreur'])

const attrs = useAttrs()
const idAide = useId()
const idsAide = computed(() => [attrs['aria-describedby'], idAide].filter(Boolean).join(' '))

const cleChamp = ref(0)
const apercuLocal = ref(null)
const apercu = computed(() => apercuLocal.value
  || (props.imageActuelle ? resolveImageUrl(props.imageActuelle, { placeholder: false }) : null))

const libererApercu = () => {
  if (apercuLocal.value) {
    URL.revokeObjectURL(apercuLocal.value)
    apercuLocal.value = null
  }
}

const choisir = (event) => {
  const choisi = event.target.files?.[0] || null
  libererApercu()

  const probleme = verifierImage(choisi)
  if (probleme) {
    fichier.value = null
    cleChamp.value += 1
    emit('erreur', probleme)
    return
  }

  emit('erreur', '')
  fichier.value = choisi
  apercuLocal.value = choisi ? URL.createObjectURL(choisi) : null
}

// Formulaire remis à zéro par le parent (v-model à null) : aperçu et champ vidés
watch(fichier, (valeur) => {
  if (!valeur && apercuLocal.value) {
    libererApercu()
    cleChamp.value += 1
  }
})

onBeforeUnmount(libererApercu)
</script>
