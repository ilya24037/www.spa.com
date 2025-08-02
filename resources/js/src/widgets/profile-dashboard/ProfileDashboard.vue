<!-- resources/js/src/widgets/profile-dashboard/ProfileDashboard.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Tabs навигация -->
    <div :class="TABS_WRAPPER_CLASSES">
      <div :class="TABS_CONTAINER_CLASSES">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="getTabClasses(tab.key)"
        >
          <component :is="tab.icon" :class="TAB_ICON_CLASSES" />
          <span>{{ tab.label }}</span>
          <span v-if="getTabCount(tab.key) > 0" :class="TAB_COUNT_CLASSES">
            {{ getTabCount(tab.key) }}
          </span>
        </button>
      </div>
    </div>

    <!-- Контент активной вкладки -->
    <div :class="CONTENT_CLASSES">
      <!-- Объявления -->
      <div v-if="activeTab === 'ads'" :class="TAB_CONTENT_CLASSES">
        <MyAdsTab 
          :ads="ads"
          :counts="counts"
          @refresh="$emit('refresh')"
        />
      </div>

      <!-- Бронирования -->
      <div v-else-if="activeTab === 'bookings'" :class="TAB_CONTENT_CLASSES">
        <BookingsTab 
          :bookings="[]"
          :counts="counts"
          @refresh="$emit('refresh')"
        />
      </div>

      <!-- Отзывы -->
      <div v-else-if="activeTab === 'reviews'" :class="TAB_CONTENT_CLASSES">
        <ReviewsTab 
          :reviews="[]"
          :counts="counts"
          @refresh="$emit('refresh')"
        />
      </div>

      <!-- Статистика -->
      <div v-else-if="activeTab === 'stats'" :class="TAB_CONTENT_CLASSES">
        <StatsTab 
          :stats="stats"
          @refresh="$emit('refresh')"
        />
      </div>

      <!-- Настройки -->
      <div v-else-if="activeTab === 'settings'" :class="TAB_CONTENT_CLASSES">
        <SettingsTab 
          @refresh="$emit('refresh')"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { 
  CollectionIcon,
  CalendarIcon,
  ChatAltIcon,
  ChartBarIcon,
  CogIcon
} from '@heroicons/vue/outline'

// Компоненты вкладок
import MyAdsTab from './tabs/MyAdsTab.vue'
import BookingsTab from './tabs/BookingsTab.vue'
import ReviewsTab from './tabs/ReviewsTab.vue'
import StatsTab from './tabs/StatsTab.vue'
import SettingsTab from './tabs/SettingsTab.vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-6'
const TABS_WRAPPER_CLASSES = 'bg-white rounded-lg shadow-sm overflow-hidden'
const TABS_CONTAINER_CLASSES = 'flex flex-wrap md:flex-nowrap border-b border-gray-200'
const TAB_BASE_CLASSES = 'flex items-center gap-2 px-4 py-3 font-medium text-sm transition-colors border-b-2 whitespace-nowrap'
const TAB_ACTIVE_CLASSES = 'text-blue-600 border-blue-600 bg-blue-50'
const TAB_INACTIVE_CLASSES = 'text-gray-600 border-transparent hover:text-gray-900 hover:bg-gray-50'
const TAB_ICON_CLASSES = 'w-5 h-5'
const TAB_COUNT_CLASSES = 'ml-1 px-2 py-0.5 text-xs bg-gray-200 rounded-full'
const CONTENT_CLASSES = 'bg-white rounded-lg shadow-sm'
const TAB_CONTENT_CLASSES = 'p-6'

const props = defineProps({
  ads: {
    type: Array,
    default: () => []
  },
  counts: {
    type: Object,
    default: () => ({
      ads: 0,
      bookings: 0,
      reviews: 0,
      favorites: 0,
      waiting: 0,
      active: 0,
      drafts: 0,
      archived: 0
    })
  },
  stats: {
    type: Object,
    default: () => ({
      views: 0,
      calls: 0,
      bookings: 0,
      revenue: 0
    })
  }
})

const emit = defineEmits(['refresh'])

// Состояние
const activeTab = ref('ads')

// Конфигурация вкладок
const tabs = [
  {
    key: 'ads',
    label: 'Мои объявления',
    icon: CollectionIcon
  },
  {
    key: 'bookings',
    label: 'Бронирования',
    icon: CalendarIcon
  },
  {
    key: 'reviews',
    label: 'Отзывы',
    icon: ChatAltIcon
  },
  {
    key: 'stats',
    label: 'Статистика',
    icon: ChartBarIcon
  },
  {
    key: 'settings',
    label: 'Настройки',
    icon: CogIcon
  }
]

// Методы
const getTabClasses = (tabKey) => {
  return [
    TAB_BASE_CLASSES,
    activeTab.value === tabKey ? TAB_ACTIVE_CLASSES : TAB_INACTIVE_CLASSES
  ].join(' ')
}

const getTabCount = (tabKey) => {
  switch (tabKey) {
    case 'ads':
      return props.counts.ads || 0
    case 'bookings':
      return props.counts.bookings || 0
    case 'reviews':
      return props.counts.reviews || 0
    default:
      return 0
  }
}
</script>