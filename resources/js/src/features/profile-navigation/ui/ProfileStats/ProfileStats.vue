<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div
      v-for="stat in stats"
      :key="stat.key"
      class="bg-white p-4 rounded-lg shadow-sm border"
    >
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-600">{{ stat.label }}</p>
          <p class="text-2xl font-bold text-gray-900">{{ stat.value }}</p>
        </div>
        <div :class="['p-2 rounded-lg', stat.bgColor]">
          <component
            :is="getIconComponent(stat.icon)"
            :class="['w-5 h-5', stat.iconColor]"
          />
        </div>
      </div>
      
      <!-- Trend -->
      <div v-if="stat.trend !== undefined && stat.trend !== null" class="mt-2 flex items-center text-sm">
        <span :class="stat.trend > 0 ? 'text-green-600' : stat.trend < 0 ? 'text-red-600' : 'text-gray-600'">
          {{ stat.trend > 0 ? '+' : '' }}{{ stat.trend }}%
        </span>
        <span class="text-gray-500 ml-1">Р·Р° РјРµСЃСЏС†</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, withDefaults } from 'vue'
import {
  EyeIcon,
  HeartIcon,
  ChatBubbleLeftIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'

interface Stat {
  key: string
  label: string
  value: number | string
  icon: string
  bgColor: string
  iconColor: string
  trend?: number
}

interface Props {
  stats?: {
    views?: number
    favorites?: number
    messages?: number
    earnings?: number
    viewsTrend?: number
    favoritesTrend?: number
    messagesTrend?: number
    earningsTrend?: number
  }
}

const props = withDefaults(defineProps<Props>(), {
  stats: () => ({
    views: 0,
    favorites: 0,
    messages: 0,
    earnings: 0
  })
})

// Computed
const stats = computed<Stat[]>(() => {
  const safeStats = props.stats || {}
  
  return [
    {
      key: 'views',
      label: 'РџСЂРѕСЃРјРѕС‚СЂС‹',
      value: (safeStats.views || 0).toLocaleString(),
      icon: 'eye',
      bgColor: 'bg-blue-100',
      iconColor: 'text-blue-600',
      trend: safeStats.viewsTrend
    },
    {
      key: 'favorites',
      label: 'Р’ РёР·Р±СЂР°РЅРЅРѕРј',
      value: safeStats.favorites || 0,
      icon: 'heart',
      bgColor: 'bg-red-100',
      iconColor: 'text-red-600',
      trend: safeStats.favoritesTrend
    },
    {
      key: 'messages',
      label: 'РЎРѕРѕР±С‰РµРЅРёСЏ',
      value: safeStats.messages || 0,
      icon: 'chat',
      bgColor: 'bg-green-100',
      iconColor: 'text-green-600',
      trend: safeStats.messagesTrend
    },
    {
      key: 'earnings',
      label: 'Р—Р°СЂР°Р±РѕС‚РѕРє',
      value: `${(safeStats.earnings || 0).toLocaleString()} в‚Ѕ`,
      icon: 'currency',
      bgColor: 'bg-yellow-100',
      iconColor: 'text-yellow-600',
      trend: safeStats.earningsTrend
    }
  ]
})

// Methods
const getIconComponent = (iconName: string) => {
  const iconMap = {
    'eye': EyeIcon,
    'heart': HeartIcon,
    'chat': ChatBubbleLeftIcon,
    'currency': CurrencyDollarIcon
  }
  
  return iconMap[iconName as keyof typeof iconMap] || EyeIcon
}
</script>
