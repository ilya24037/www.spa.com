<!-- resources/js/src/widgets/profile-dashboard/tabs/StatsTab.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Статистика карточки -->
    <div :class="STATS_GRID_CLASSES">
      <div v-for="stat in statsCards" :key="stat.key" :class="STAT_CARD_CLASSES">
        <div :class="STAT_ICON_WRAPPER_CLASSES">
          <component :is="stat.icon" :class="STAT_ICON_CLASSES" />
        </div>
        <div>
          <p :class="STAT_LABEL_CLASSES">
            {{ stat.label }}
          </p>
          <p :class="STAT_VALUE_CLASSES">
            {{ formatStatValue(stat.key) }}
          </p>
        </div>
      </div>
    </div>

    <!-- График -->
    <div :class="CHART_SECTION_CLASSES">
      <h3 :class="CHART_TITLE_CLASSES">
        Активность за последние 30 дней
      </h3>
      <div :class="CHART_PLACEHOLDER_CLASSES">
        <ChartBarIcon :class="CHART_ICON_CLASSES" />
        <p :class="CHART_TEXT_CLASSES">
          График статистики будет здесь
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { 
    EyeIcon,
    PhoneIcon,
    CalendarIcon,
    CurrencyDollarIcon,
    ChartBarIcon
} from '@heroicons/vue/outline'

// 🎯 Стили
const CONTAINER_CLASSES = 'space-y-6'
const STATS_GRID_CLASSES = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4'
const STAT_CARD_CLASSES = 'flex items-center gap-4 p-6 bg-gray-500 rounded-lg'
const STAT_ICON_WRAPPER_CLASSES = 'p-3 bg-blue-100 rounded-lg'
const STAT_ICON_CLASSES = 'w-6 h-6 text-blue-600'
const STAT_LABEL_CLASSES = 'text-sm text-gray-500'
const STAT_VALUE_CLASSES = 'text-2xl font-semibold text-gray-500'
const CHART_SECTION_CLASSES = 'bg-gray-500 rounded-lg p-6'
const CHART_TITLE_CLASSES = 'text-lg font-medium text-gray-500 mb-4'
const CHART_PLACEHOLDER_CLASSES = 'h-64 flex flex-col items-center justify-center text-gray-500'
const CHART_ICON_CLASSES = 'w-12 h-12 mb-2'
const CHART_TEXT_CLASSES = 'text-sm'

const props = defineProps({
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

// РљРѕРЅС„РёРіСѓСЂР°С†РёСЏ РєР°СЂС‚РѕС‡РµРє СЃС‚Р°С‚РёСЃС‚РёРєРё
const statsCards = [
    {
        key: 'views',
        label: 'РџСЂРѕСЃРјРѕС‚СЂС‹',
        icon: EyeIcon
    },
    {
        key: 'calls',
        label: 'Р—РІРѕРЅРєРё',
        icon: PhoneIcon
    },
    {
        key: 'bookings',
        label: 'Р‘СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ',
        icon: CalendarIcon
    },
    {
        key: 'revenue',
        label: 'Р”РѕС…РѕРґ',
        icon: CurrencyDollarIcon
    }
]

// РњРµС‚РѕРґС‹
const formatStatValue = (key) => {
    const value = props.stats[key] || 0
  
    if (key === 'revenue') {
        return `${value.toLocaleString()} в‚Ѕ`
    }
  
    if (value >= 1000) {
        return `${(value / 1000).toFixed(1)}K`
    }
  
    return value.toString()
}
</script>
