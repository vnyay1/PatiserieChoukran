<!-- ===================================
PAGE NOTIFICATIONS
File: src/views/Notifications.vue
=================================== -->
<!--
  Non lue : point or + titre en gras + mention « Non lue » lue par les lecteurs d'écran.
  Actions secondaires discrètes (icône « Supprimer ») pour ne pas aligner un bouton rouge par ligne.
-->
<template>
  <div class="container mx-auto max-w-3xl pb-6 pt-6 md:pt-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1>Notifications</h1>
        <p class="mt-1 text-sm text-gray-600" aria-live="polite">
          {{ unreadCount === 0 ? 'Tout est lu' : `${unreadCount} non lue${unreadCount > 1 ? 's' : ''}` }}
        </p>
      </div>

      <div class="flex flex-wrap gap-2">
        <Button
          variant="outline"
          size="sm"
          :icon="CheckCheck"
          :disabled="!notificationsStore.hasUnread || actionLoading"
          @click="markAllAsRead"
        >
          Tout marquer comme lu
        </Button>
        <Button
          variant="ghost"
          size="sm"
          :icon="Trash2"
          :disabled="!hasReadNotifications || actionLoading"
          @click="clearReadNotifications"
        >
          Supprimer les lues
        </Button>
      </div>
    </div>

    <div class="mb-5 flex flex-wrap items-center gap-2" role="group" aria-label="Afficher">
      <button
        v-for="option in readFilterOptions"
        :key="option.value"
        type="button"
        class="puce"
        :aria-pressed="readFilter === option.value"
        @click="setReadFilter(option.value)"
      >
        {{ option.label }}
      </button>
      <label for="filtre-type-notif" class="sr-only">Type de notification</label>
      <select id="filtre-type-notif" v-model="typeFilter" class="input ml-auto min-h-10 w-auto rounded-full py-1.5 text-sm">
        <option value="">Tous les types</option>
        <option value="commande">Commandes</option>
      </select>
    </div>

    <div v-if="notificationsStore.loading" class="space-y-3" aria-hidden="true">
      <div v-for="n in 4" :key="n" class="skeleton h-28 rounded-elegant"></div>
    </div>

    <ul v-else-if="notifications.length > 0" class="space-y-3">
      <li
        v-for="notification in notifications"
        :key="notification.id"
        class="card relative flex items-start gap-3 p-4 sm:gap-4 sm:p-5"
        :class="{ 'border-gold-300 bg-gold-50/60': !notification.est_lu }"
      >
        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gold-100 text-gold-700">
          <Package v-if="notification.type === 'commande'" :size="18" aria-hidden="true" />
          <Bell v-else :size="18" aria-hidden="true" />
        </span>

        <div class="min-w-0 flex-1">
          <h2 class="flex items-center gap-2 font-body text-base leading-snug" :class="notification.est_lu ? 'font-semibold text-gray-800' : 'font-bold text-gray-900'">
            <span v-if="!notification.est_lu" class="h-2.5 w-2.5 flex-shrink-0 rounded-full bg-gold-500" aria-hidden="true"></span>
            <span v-if="!notification.est_lu" class="sr-only">Non lue :</span>
            <span class="min-w-0 break-words">{{ notification.titre || 'Notification' }}</span>
          </h2>
          <p class="mt-1 whitespace-pre-line break-words text-sm text-gray-700">{{ notification.message }}</p>
          <p class="mt-2 text-xs text-gray-600">
            {{ labelType(notification.type) }} ·
            <time :datetime="notification.date_envoi || notification.created_at">{{ formatDate(notification.date_envoi || notification.created_at) }}</time>
          </p>

          <div class="mt-3 flex flex-wrap items-center gap-2">
            <Button v-if="notification.url_action" variant="secondary" size="sm" @click="openAction(notification)">
              Voir
            </Button>
            <Button v-if="!notification.est_lu" variant="ghost" size="sm" @click="markAsRead(notification.id)">
              Marquer comme lue
            </Button>
          </div>
        </div>

        <button
          type="button"
          class="btn-icone -mr-2 -mt-2 text-gray-600 hover:bg-red-50 hover:text-red-700"
          :aria-label="`Supprimer la notification « ${notification.titre || 'Notification'} »`"
          @click="removeNotification(notification)"
        >
          <Trash2 :size="18" aria-hidden="true" />
        </button>
      </li>
    </ul>

    <EmptyState
      v-else
      :icone="BellOff"
      :titre="readFilter === 'unread' ? 'Aucune notification non lue' : 'Aucune notification'"
      texte="Vous serez prévenu ici de chaque étape de vos commandes."
    />

    <nav v-if="hasPagination" class="mt-6 flex items-center justify-center gap-3" aria-label="Pagination">
      <Button
        variant="outline"
        size="sm"
        :icon="ChevronLeft"
        :disabled="currentPage <= 1 || notificationsStore.loading"
        @click="changePage(currentPage - 1)"
      >
        Précédentes
      </Button>
      <span class="text-sm tabular-nums text-gray-600">Page {{ currentPage }} sur {{ lastPage }}</span>
      <Button
        variant="outline"
        size="sm"
        :disabled="currentPage >= lastPage || notificationsStore.loading"
        @click="changePage(currentPage + 1)"
      >
        Suivantes
        <ChevronRight :size="16" aria-hidden="true" />
      </Button>
    </nav>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Bell, BellOff, Package, Trash2, CheckCheck, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import Button from '@/components/common/Button.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'
import { formatDateHeure } from '@/utils/format'

const router = useRouter()
const notificationsStore = useNotificationsStore()
const toastStore = useToastStore()
const { confirmer } = useConfirm()

const typeFilter = ref('')
const readFilter = ref('all')
const currentPage = ref(1)
const perPage = ref(20)
const actionLoading = ref(false)

const readFilterOptions = [
  { value: 'all', label: 'Toutes' },
  { value: 'unread', label: 'Non lues' },
  { value: 'read', label: 'Lues' },
]

const notifications = computed(() => notificationsStore.items)
const unreadCount = computed(() => notificationsStore.unreadCount)
const hasReadNotifications = computed(() => notifications.value.some((entry) => entry.est_lu))
const lastPage = computed(() => notificationsStore.pagination.last_page || 1)
const hasPagination = computed(() => lastPage.value > 1)

const labelType = (type) => {
  if (type === 'commande') return 'Commande'
  if (!type) return 'Notification'
  return type
}

const formatDate = (value) => formatDateHeure(value) || 'Date inconnue'

const buildFetchParams = () => {
  const params = {
    page: currentPage.value,
    per_page: perPage.value,
  }

  if (typeFilter.value) {
    params.type = typeFilter.value
  }

  if (readFilter.value === 'read') {
    params.est_lu = 1
  }
  if (readFilter.value === 'unread') {
    params.est_lu = 0
  }

  return params
}

const fetchNotifications = async () => {
  const result = await notificationsStore.fetchNotifications(buildFetchParams())
  if (!result.success) {
    console.error(result.message || 'Erreur chargement notifications')
  }
}

const setReadFilter = (value) => {
  if (readFilter.value === value) return
  readFilter.value = value
}

const changePage = (page) => {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  currentPage.value = page
}

const markAsRead = async (id) => {
  const result = await notificationsStore.markAsRead(id)
  if (!result.success) {
    toastStore.erreur(result.message || 'Impossible de marquer la notification comme lue.')
  }
}

const markAllAsRead = async () => {
  actionLoading.value = true
  const result = await notificationsStore.markAllAsRead()
  actionLoading.value = false

  if (!result.success) {
    toastStore.erreur(result.message || 'Impossible de marquer toutes les notifications comme lues.')
  }
}

const removeNotification = async (notification) => {
  const confirmed = await confirmer({
    titre: 'Supprimer la notification ?',
    message: 'Cette notification sera définitivement supprimée.',
    libelleConfirmer: 'Supprimer',
    danger: true,
  })
  if (!confirmed) return

  const result = await notificationsStore.remove(notification.id)
  if (!result.success) {
    toastStore.erreur(result.message || 'Impossible de supprimer cette notification.')
    return
  }

  if (notifications.value.length === 0 && currentPage.value > 1) {
    currentPage.value -= 1
  } else {
    fetchNotifications()
  }
}

const clearReadNotifications = async () => {
  const confirmed = await confirmer({
    titre: 'Supprimer les notifications lues ?',
    message: 'Toutes les notifications déjà lues seront supprimées.',
    libelleConfirmer: 'Supprimer',
    danger: true,
  })
  if (!confirmed) return

  actionLoading.value = true
  const result = await notificationsStore.clearRead()
  actionLoading.value = false

  if (!result.success) {
    toastStore.erreur(result.message || 'Impossible de supprimer les notifications lues.')
    return
  }

  if (result.partial && result.message) {
    toastStore.info(result.message)
  } else {
    toastStore.succes('Notifications lues supprimées.')
  }

  if (notifications.value.length === 0 && currentPage.value > 1) {
    currentPage.value -= 1
  } else {
    fetchNotifications()
  }
}

const resolveActionUrl = (url) => {
  if (!url) return ''
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  return url.startsWith('/') ? url : `/${url}`
}

const openAction = async (notification) => {
  if (!notification.est_lu) {
    await markAsRead(notification.id)
  }

  const url = resolveActionUrl(notification.url_action)
  if (!url) return

  if (url.startsWith('http://') || url.startsWith('https://')) {
    window.location.href = url
    return
  }

  try {
    await router.push(url)
  } catch (error) {
    console.error("Impossible d'ouvrir l'action de notification:", error)
  }
}

watch([typeFilter, readFilter], () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1
    return
  }
  fetchNotifications()
})

watch(currentPage, () => {
  fetchNotifications()
})

onMounted(async () => {
  await notificationsStore.fetchUnreadCount({ force: true })
  fetchNotifications()
})
</script>
