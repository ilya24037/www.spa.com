<template>
  <div
    v-if="showLegend"
    class="calendar-legend"
    role="region"
    aria-label="Р›РµРіРµРЅРґР° РєР°Р»РµРЅРґР°СЂСЏ"
  >
    <div class="calendar-legend-content">
      <div class="calendar-legend-items">
        <div
          v-for="item in legendItems"
          :key="item.id"
          class="calendar-legend-item"
        >
          <div
            class="calendar-legend-dot"
            :style="{ backgroundColor: item.color }"
            :aria-hidden="true"
          />
          <span class="calendar-legend-text">{{ item.label }}</span>
        </div>
      </div>
      
      <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
      <div v-if="showStatistics && statistics" class="calendar-legend-stats">
        <div class="calendar-legend-stats-title">
          РЎС‚Р°С‚РёСЃС‚РёРєР°:
        </div>
        <div class="calendar-legend-stats-content">
          <span class="calendar-legend-stat">
            Р’СЃРµРіРѕ РґР°С‚: {{ statistics.total }}
          </span>
          <span class="calendar-legend-stat">
            Р”РѕСЃС‚СѓРїРЅРѕ: {{ statistics.available }}
          </span>
          <span class="calendar-legend-stat">
            Р—Р°РЅСЏС‚РѕСЃС‚СЊ: {{ statistics.averageOccupancy }}%
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CalendarLegendItem } from '../../../model/calendar.types'

interface BookingStatistics {
  total: number
  available: number
  busy: number
  full: number
  unavailable: number
  averageOccupancy: number
}

interface Props {
  showLegend?: boolean
  showStatistics?: boolean
  legendItems?: CalendarLegendItem[]
  statistics?: BookingStatistics | null
  compact?: boolean
}

const _props = withDefaults(defineProps<Props>(), {
    showLegend: true,
    showStatistics: false,
    legendItems: () => [
        {
            id: 'available',
            color: '#10B981',
            label: 'РњРЅРѕРіРѕ СЃРІРѕР±РѕРґРЅС‹С… СЃР»РѕС‚РѕРІ',
            status: 'available'
        },
        {
            id: 'busy',
            color: '#F59E0B',
            label: 'Р•СЃС‚СЊ СЃРІРѕР±РѕРґРЅС‹Рµ СЃР»РѕС‚С‹',
            status: 'busy'
        },
        {
            id: 'full',
            color: '#EF4444',
            label: 'РџРѕС‡С‚Рё РІСЃРµ Р·Р°РЅСЏС‚Рѕ',
            status: 'full'
        }
    ],
    statistics: null,
    compact: false
})
</script>

<style scoped>
.calendar-legend {
  @apply mt-4 pt-4 border-t border-gray-500;
}

.calendar-legend-content {
  @apply space-y-3;
}

.calendar-legend-items {
  @apply flex flex-wrap items-center gap-4;
}

.calendar-legend-item {
  @apply flex items-center gap-2;
}

.calendar-legend-dot {
  @apply w-3 h-3 rounded-full flex-shrink-0;
}

.calendar-legend-text {
  @apply text-xs text-gray-500;
}

.calendar-legend-stats {
  @apply text-xs text-gray-500;
}

.calendar-legend-stats-title {
  @apply font-medium mb-1;
}

.calendar-legend-stats-content {
  @apply flex flex-wrap gap-3;
}

.calendar-legend-stat {
  @apply whitespace-nowrap;
}

/* РљРѕРјРїР°РєС‚РЅС‹Р№ СЂРµР¶РёРј */
.calendar-legend.compact {
  @apply mt-2 pt-2;
}

.calendar-legend.compact .calendar-legend-items {
  @apply gap-2;
}

.calendar-legend.compact .calendar-legend-dot {
  @apply w-2 h-2;
}

.calendar-legend.compact .calendar-legend-text {
  @apply text-xs;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 640px) {
  .calendar-legend-items {
    @apply flex-col items-start gap-2;
  }
  
  .calendar-legend-item {
    @apply w-full;
  }
  
  .calendar-legend-stats-content {
    @apply flex-col gap-1;
  }
}

/* Р’С‹СЃРѕРєРёР№ РєРѕРЅС‚СЂР°СЃС‚ */
@media (prefers-contrast: high) {
  .calendar-legend-dot {
    @apply ring-2 ring-gray-500;
  }
  
  .calendar-legend-text,
  .calendar-legend-stats {
    @apply text-black;
  }
}
</style>
