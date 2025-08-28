<!-- resources/js/src/widgets/profile-dashboard/tabs/MyAdsTab.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Фильтры статусов -->
    <div :class="FILTERS_CLASSES">
      <button
        v-for="status in statuses"
        :key="status.key"
        :class="getStatusButtonClasses(status.key)"
        @click="activeStatus = status.key"
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
      <h3 :class="EMPTY_TITLE_CLASSES">
        Нет объявлений
      </h3>
      <p :class="EMPTY_DESCRIPTION_CLASSES">
        {{ getEmptyMessage() }}
      </p>
      <button
        :class="CREATE_BUTTON_CLASSES"
        @click="createNewAd"
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
import AdCard from '@/src/entities/ad/ui/AdCard/AdCard.vue'

// 🎯 Props
const props = defineProps({
    ads: {
        type: Array,
        default: () => []
    }
})

// 🎯 Стили
const CONTAINER_CLASSES = 'space-y-6'
const FILTERS_CLASSES = 'flex flex-wrap gap-3'
const STATUS_BUTTON_BASE = 'px-4 py-2 rounded-lg font-medium text-sm transition-colors relative'
const STATUS_BUTTON_ACTIVE = 'bg-blue-500 text-white'
const STATUS_BUTTON_INACTIVE = 'bg-gray-100 text-gray-700 hover:bg-gray-200'
const STATUS_COUNT_CLASSES = 'ml-2 px-2 py-0.5 bg-white/20 rounded-full text-xs'
const ADS_LIST_CLASSES = 'space-y-4'
const EMPTY_STATE_CLASSES = 'text-center py-16'
const EMPTY_ICON_CLASSES = 'w-16 h-16 mx-auto text-gray-300 mb-4'
const EMPTY_TITLE_CLASSES = 'text-xl font-semibold text-gray-900 mb-2'
const EMPTY_DESCRIPTION_CLASSES = 'text-gray-500 mb-6'
const CREATE_BUTTON_CLASSES = 'px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors'

// 🎯 Состояние
const activeStatus = ref('all')

// 🎯 Статусы
const statuses = [
    { key: 'all', label: 'Все' },
    { key: 'active', label: 'Активные' },
    { key: 'draft', label: 'Черновики' },
    { key: 'moderation', label: 'На модерации' },
    { key: 'rejected', label: 'Отклоненные' },
    { key: 'archived', label: 'В архиве' }
]

// 🎯 Вычисления
const filteredAds = computed(() => {
    if (activeStatus.value === 'all') {
        return props.ads
    }
    return props.ads.filter(ad => ad.status === activeStatus.value)
})

// 🎯 Методы
const getStatusCount = (status) => {
    if (status === 'all') return props.ads.length
    return props.ads.filter(ad => ad.status === status).length
}

const getStatusButtonClasses = (status) => {
    return [
        STATUS_BUTTON_BASE,
        status === activeStatus.value ? STATUS_BUTTON_ACTIVE : STATUS_BUTTON_INACTIVE
    ].join(' ')
}

const getEmptyMessage = () => {
    const messages = {
        all: 'У вас пока нет объявлений. Создайте первое!',
        active: 'Нет активных объявлений',
        draft: 'Нет черновиков',
        moderation: 'Нет объявлений на модерации',
        rejected: 'Нет отклоненных объявлений',
        archived: 'Нет архивных объявлений'
    }
    return messages[activeStatus.value] || messages.all
}

const handleEdit = (ad) => {
    router.visit(`/ads/${ad.id}/edit`)
}

const handleDelete = (ad) => {
    if (confirm('Вы уверены, что хотите удалить это объявление?')) {
        router.delete(`/ads/${ad.id}`, {
            onSuccess: () => {
                // Обновление будет через props из родителя
            }
        })
    }
}

const handleToggleStatus = (ad) => {
    const newStatus = ad.status === 'active' ? 'archived' : 'active'
    router.patch(`/ads/${ad.id}`, {
        status: newStatus
    }, {
        onSuccess: () => {
            // Обновление будет через props из родителя
        }
    })
}

const createNewAd = () => {
    router.visit('/ads/create')
}
</script>