<!-- resources/js/src/widgets/profile-dashboard/components/AdList.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Фильтры по статусу -->
    <div :class="FILTERS_CLASSES">
      <AdStatusFilter
        :value="statusFilter"
        :counts="statusCounts"
        @update="$emit('status-change', $event)"
      />
    </div>

    <!-- Список объявлений -->
    <div v-if="!loading && ads.length" :class="ADS_LIST_CLASSES">
      <div
        v-for="ad in ads"
        :key="ad.id"
        :class="AD_ITEM_CLASSES"
      >
        <!-- Основная информация -->
        <div :class="AD_INFO_CLASSES">
          <div :class="AD_IMAGE_CLASSES">
            <img
              :src="ad.main_photo || '/images/placeholders/service.jpg'"
              :alt="ad.title"
              :class="IMAGE_CLASSES"
            >
          </div>
          
          <div :class="AD_DETAILS_CLASSES">
            <h3 :class="AD_TITLE_CLASSES">{{ ad.title }}</h3>
            <p :class="AD_DESCRIPTION_CLASSES">{{ ad.description }}</p>
            
            <div :class="AD_META_CLASSES">
              <span :class="AD_PRICE_CLASSES">
                {{ formatPrice(ad.price_from) }} ₽/час
              </span>
              <span :class="AD_VIEWS_CLASSES">
                {{ ad.views_count || 0 }} просмотров
              </span>
              <span :class="AD_DATE_CLASSES">
                {{ formatDate(ad.created_at) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Статус и действия -->
        <div :class="AD_ACTIONS_CLASSES">
          <div :class="STATUS_SECTION_CLASSES">
            <AdStatus :status="ad.status" />
            <span v-if="ad.expires_at" :class="EXPIRY_TEXT_CLASSES">
              {{ getExpiryText(ad.expires_at) }}
            </span>
          </div>

          <div :class="ACTIONS_BUTTONS_CLASSES">
            <button
              @click="$emit('edit', ad)"
              :class="EDIT_BUTTON_CLASSES"
              title="Редактировать"
            >
              <svg :class="BUTTON_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>

            <button
              @click="$emit('duplicate', ad)"
              :class="DUPLICATE_BUTTON_CLASSES"
              title="Дублировать"
            >
              <svg :class="BUTTON_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
              </svg>
            </button>

            <button
              @click="$emit('delete', ad)"
              :class="DELETE_BUTTON_CLASSES"
              title="Удалить"
            >
              <svg :class="BUTTON_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Состояние загрузки -->
    <div v-else-if="loading" :class="LOADING_CLASSES">
      <svg :class="LOADING_ICON_CLASSES" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
      </svg>
      <span>Загрузка объявлений...</span>
    </div>

    <!-- Пустое состояние -->
    <div v-else :class="EMPTY_STATE_CLASSES">
      <svg :class="EMPTY_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
      </svg>
      <h3 :class="EMPTY_TITLE_CLASSES">У вас пока нет объявлений</h3>
      <p :class="EMPTY_DESCRIPTION_CLASSES">
        Создайте первое объявление, чтобы привлечь клиентов
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { AdStatus, AdStatusFilter } from '@/src/entities/ad'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/ru'

dayjs.extend(relativeTime)
dayjs.locale('ru')

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'p-6 space-y-6'
const FILTERS_CLASSES = 'border-b border-gray-200 pb-4'
const ADS_LIST_CLASSES = 'space-y-4'
const AD_ITEM_CLASSES = 'flex items-start gap-4 p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors'
const AD_INFO_CLASSES = 'flex gap-4 flex-1'
const AD_IMAGE_CLASSES = 'w-20 h-20 flex-shrink-0'
const IMAGE_CLASSES = 'w-full h-full object-cover rounded-lg'
const AD_DETAILS_CLASSES = 'flex-1 min-w-0'
const AD_TITLE_CLASSES = 'font-medium text-gray-900 mb-1'
const AD_DESCRIPTION_CLASSES = 'text-sm text-gray-600 mb-2 line-clamp-2'
const AD_META_CLASSES = 'flex items-center gap-4 text-xs text-gray-500'
const AD_PRICE_CLASSES = 'font-medium text-blue-600'
const AD_VIEWS_CLASSES = ''
const AD_DATE_CLASSES = ''
const AD_ACTIONS_CLASSES = 'flex flex-col items-end gap-3'
const STATUS_SECTION_CLASSES = 'text-right'
const EXPIRY_TEXT_CLASSES = 'text-xs text-orange-600 mt-1'
const ACTIONS_BUTTONS_CLASSES = 'flex gap-2'
const EDIT_BUTTON_CLASSES = 'p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors'
const DUPLICATE_BUTTON_CLASSES = 'p-2 text-gray-600 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors'
const DELETE_BUTTON_CLASSES = 'p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors'
const BUTTON_ICON_CLASSES = 'w-5 h-5'
const LOADING_CLASSES = 'flex flex-col items-center justify-center py-12 text-gray-500'
const LOADING_ICON_CLASSES = 'w-8 h-8 animate-spin mb-4'
const EMPTY_STATE_CLASSES = 'text-center py-12'
const EMPTY_ICON_CLASSES = 'w-12 h-12 mx-auto text-gray-400 mb-4'
const EMPTY_TITLE_CLASSES = 'text-lg font-medium text-gray-900 mb-2'
const EMPTY_DESCRIPTION_CLASSES = 'text-gray-600'

const props = defineProps({
  ads: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  statusFilter: {
    type: String,
    default: 'all'
  }
})

defineEmits(['edit', 'delete', 'duplicate', 'status-change'])

// Вычисляемые свойства
const statusCounts = computed(() => {
  const counts = { all: props.ads.length }
  
  props.ads.forEach(ad => {
    counts[ad.status] = (counts[ad.status] || 0) + 1
  })
  
  return counts
})

// Методы
const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const formatDate = (date) => {
  return dayjs(date).format('DD.MM.YYYY')
}

const getExpiryText = (expiresAt) => {
  const expiry = dayjs(expiresAt)
  const now = dayjs()
  
  if (expiry.isBefore(now)) {
    return 'Истекло'
  }
  
  const diff = expiry.diff(now, 'day')
  
  if (diff === 0) {
    return 'Истекает сегодня'
  } else if (diff === 1) {
    return 'Истекает завтра'
  } else if (diff <= 7) {
    return `Истекает через ${diff} дн.`
  }
  
  return `Истекает ${expiry.format('DD.MM')}`
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>