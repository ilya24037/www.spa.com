<!-- resources/js/src/widgets/profile-dashboard/tabs/MyAdsTab.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Фильтры статусов -->
    <div :class="FILTERS_CLASSES">
      <button
        v-for="status in statuses"
        :key="status.key"
        @click="activeStatus = status.key"
        :class="getStatusButtonClasses(status.key)"
      >
        {{ status.label }}
        <span v-if="getStatusCount(status.key) > 0" :class="STATUS_COUNT_CLASSES">
          {{ getStatusCount(status.key) }}
        </span>
      </button>
    </div>

    <!-- Список объявлений -->
    <div v-if="filteredAds.length > 0" :class="ADS_LIST_CLASSES">
      <AdCard
        v-for="ad in filteredAds"
        :key="ad.id"
        :ad="ad"
        :editable="true"
        @edit="handleEdit(ad)"
        @delete="handleDelete(ad)"
        @toggle-status="handleToggleStatus(ad)"
      />
    </div>

    <!-- Пустое состояние -->
    <div v-else :class="EMPTY_STATE_CLASSES">
      <CollectionIcon :class="EMPTY_ICON_CLASSES" />
      <h3 :class="EMPTY_TITLE_CLASSES">Нет объявлений</h3>
      <p :class="EMPTY_DESCRIPTION_CLASSES">
        {{ getEmptyMessage() }}
      </p>
      <button
        @click="createNewAd"
        :class="CREATE_BUTTON_CLASSES"
      >
        Создать объявление
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { CollectionIcon } from '@heroicons/vue/outline'
import { AdCard } from '@/src/entities/ad'

// 🎯 Стили
const CONTAINER_CLASSES = 'space-y-6'
const FILTERS_CLASSES = 'flex flex-wrap gap-2'
const STATUS_BUTTON_BASE_CLASSES = 'px-4 py-2 rounded-lg font-medium text-sm transition-colors'
const STATUS_BUTTON_ACTIVE_CLASSES = 'bg-blue-600 text-white'
const STATUS_BUTTON_INACTIVE_CLASSES = 'bg-gray-100 text-gray-700 hover:bg-gray-200'
const STATUS_COUNT_CLASSES = 'ml-1 px-2 py-0.5 text-xs bg-white/20 rounded-full'
const ADS_LIST_CLASSES = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'
const EMPTY_STATE_CLASSES = 'text-center py-12'
const EMPTY_ICON_CLASSES = 'w-12 h-12 mx-auto text-gray-400 mb-4'
const EMPTY_TITLE_CLASSES = 'text-lg font-medium text-gray-900 mb-2'
const EMPTY_DESCRIPTION_CLASSES = 'text-gray-600 mb-4'
const CREATE_BUTTON_CLASSES = 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors'

const props = defineProps({
  ads: {
    type: Array,
    default: () => []
  },
  counts: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['refresh'])

// Состояние
const activeStatus = ref('all')

// Статусы
const statuses = [
  { key: 'all', label: 'Все' },
  { key: 'active', label: 'Активные' },
  { key: 'drafts', label: 'Черновики' },
  { key: 'waiting', label: 'На модерации' },
  { key: 'archived', label: 'Архив' }
]

// Вычисляемые свойства
const filteredAds = computed(() => {
  if (activeStatus.value === 'all') {
    return props.ads
  }
  return props.ads.filter(ad => ad.status === activeStatus.value)
})

// Методы
const getStatusButtonClasses = (statusKey) => {
  return [
    STATUS_BUTTON_BASE_CLASSES,
    activeStatus.value === statusKey ? STATUS_BUTTON_ACTIVE_CLASSES : STATUS_BUTTON_INACTIVE_CLASSES
  ].join(' ')
}

const getStatusCount = (statusKey) => {
  if (statusKey === 'all') {
    return props.counts.ads || 0
  }
  return props.counts[statusKey] || 0
}

const getEmptyMessage = () => {
  switch (activeStatus.value) {
    case 'active':
      return 'У вас нет активных объявлений'
    case 'drafts':
      return 'У вас нет черновиков'
    case 'waiting':
      return 'Нет объявлений на модерации'
    case 'archived':
      return 'В архиве пусто'
    default:
      return 'У вас пока нет объявлений'
  }
}

const createNewAd = () => {
  router.visit('/ads/create')
}

const handleEdit = (ad) => {
  router.visit(`/ads/${ad.id}/edit`)
}

const handleDelete = async (ad) => {
  if (confirm('Удалить объявление?')) {
    // API вызов для удаления
    emit('refresh')
  }
}

const handleToggleStatus = async (ad) => {
  // API вызов для изменения статуса
  emit('refresh')
}
</script>