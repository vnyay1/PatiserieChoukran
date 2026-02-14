<template>
  <div class="notifications-page bg-cream min-h-screen pb-20">
    <div class="container mx-auto px-4 py-6">
      <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-6">
        <div>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gold-600">
            Notifications
          </h1>
          <p class="text-sm text-gray-600 mt-1">
            {{ unreadCount }} non lue{{ unreadCount > 1 ? 's' : '' }}
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Button
            variant="outline"
            size="sm"
            :disabled="!notificationsStore.hasUnread || actionLoading"
            @click="markAllAsRead"
          >
            Tout marquer lu
          </Button>
          <Button
            variant="outline"
            size="sm"
            :disabled="!hasReadNotifications || actionLoading"
            @click="clearReadNotifications"
          >
            Supprimer les lues
          </Button>
        </div>
      </div>

      <div class="bg-white rounded-elegant shadow-card p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
              Type
            </label>
            <select v-model="typeFilter" class="input py-2">
              <option value="">Tous les types</option>
              <option value="commande">Commande</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
              Statut
            </label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="option in readFilterOptions"
                :key="option.value"
                class="px-4 py-2 rounded-full text-sm transition-colors"
                :class="readFilter === option.value ? 'bg-gold-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                @click="setReadFilter(option.value)"
              >
                {{ option.label }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="notificationsStore.loading" class="space-y-3">
        <div v-for="n in 4" :key="n" class="skeleton h-28 rounded-elegant"></div>
      </div>

      <div v-else-if="notifications.length > 0" class="space-y-3">
        <article
          v-for="notification in notifications"
          :key="notification.id"
          class="bg-white rounded-elegant shadow-card p-4 border transition-colors"
          :class="notification.est_lu ? 'border-transparent' : 'border-gold-200'"
        >
          <div class="flex items-start gap-3">
            <div class="h-10 w-10 rounded-full bg-gold-50 text-gold-700 flex items-center justify-center flex-shrink-0">
              <Package v-if="notification.type === 'commande'" :size="18" />
              <Bell v-else :size="18" />
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <h2 class="font-semibold text-gray-800 truncate">
                    {{ notification.titre || 'Notification' }}
                  </h2>
                  <p class="text-sm text-gray-600 mt-1 whitespace-pre-line break-words">
                    {{ notification.message }}
                  </p>
                </div>
                <span
                  v-if="!notification.est_lu"
                  class="mt-1 h-2.5 w-2.5 rounded-full bg-gold-500 flex-shrink-0"
                  aria-label="Notification non lue"
                ></span>
              </div>

              <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                <span class="badge badge-primary">{{ labelType(notification.type) }}</span>
                <span>{{ formatDate(notification.date_envoi || notification.created_at) }}</span>
              </div>

              <div class="mt-4 flex flex-wrap gap-2">
                <Button
                  v-if="notification.url_action"
                  variant="secondary"
                  size="sm"
                  @click="openAction(notification)"
                >
                  Ouvrir
                </Button>
                <Button
                  v-if="!notification.est_lu"
                  variant="outline"
                  size="sm"
                  @click="markAsRead(notification.id)"
                >
                  Marquer lue
                </Button>
                <Button
                  variant="danger"
                  size="sm"
                  @click="removeNotification(notification)"
                >
                  Supprimer
                </Button>
              </div>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="text-center py-14">
        <Bell :size="42" class="mx-auto text-gray-300 mb-3" />
        <h2 class="font-display text-xl font-bold text-gray-800 mb-1">
          Aucune notification
        </h2>
        <p class="text-gray-600">
          Vos notifications apparaîtront ici.
        </p>
      </div>

      <div v-if="hasPagination" class="mt-6 flex items-center justify-center gap-3">
        <Button
          variant="outline"
          size="sm"
          :disabled="currentPage <= 1 || notificationsStore.loading"
          @click="changePage(currentPage - 1)"
        >
          Précédent
        </Button>
        <span class="text-sm text-gray-600">
          Page {{ currentPage }} / {{ lastPage }}
        </span>
        <Button
          variant="outline"
          size="sm"
          :disabled="currentPage >= lastPage || notificationsStore.loading"
          @click="changePage(currentPage + 1)"
        >
          Suivant
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Bell, Package } from 'lucide-vue-next'
import Button from '@/components/common/Button.vue'
import { useNotificationsStore } from '@/stores/notifications'

const router = useRouter()
const notificationsStore = useNotificationsStore()

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

const formatDate = (value) => {
  if (!value) return 'Date inconnue'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

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
    alert(result.message || 'Impossible de marquer la notification comme lue.')
  }
}

const markAllAsRead = async () => {
  actionLoading.value = true
  const result = await notificationsStore.markAllAsRead()
  actionLoading.value = false

  if (!result.success) {
    alert(result.message || 'Impossible de marquer toutes les notifications comme lues.')
  }
}

const removeNotification = async (notification) => {
  const confirmed = confirm('Supprimer cette notification ?')
  if (!confirmed) return

  const result = await notificationsStore.remove(notification.id)
  if (!result.success) {
    alert(result.message || 'Impossible de supprimer cette notification.')
    return
  }

  if (notifications.value.length === 0 && currentPage.value > 1) {
    currentPage.value -= 1
  } else {
    fetchNotifications()
  }
}

const clearReadNotifications = async () => {
  const confirmed = confirm('Supprimer toutes les notifications lues ?')
  if (!confirmed) return

  actionLoading.value = true
  const result = await notificationsStore.clearRead()
  actionLoading.value = false

  if (!result.success) {
    alert(result.message || 'Impossible de supprimer les notifications lues.')
    return
  }

  if (result.partial && result.message) {
    alert(result.message)
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
  await notificationsStore.fetchUnreadCount()
  fetchNotifications()
})
</script>
