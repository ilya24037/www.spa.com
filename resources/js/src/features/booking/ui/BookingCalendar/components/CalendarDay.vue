<template>
  <button
    type="button"
    :disabled="!day.isAvailable || disabled"
    :class="dayClasses"
    :aria-selected="day.isSelected"
    :aria-label="ariaLabel"
    :data-date="day.dateString"
    :data-day-index="dayIndex"
    @click="handleClick"
    @mouseenter="$emit('mouseenter')"
    @mouseleave="$emit('mouseleave')"
    @focus="$emit('focus')"
  >
    <!-- РќРѕРјРµСЂ РґРЅСЏ -->
    <span class="calendar-day-number" :class="{ 'sr-only': !day.isCurrentMonth }">
      {{ day.day }}
    </span>

    <!-- РРЅРґРёРєР°С‚РѕСЂ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ -->
    <div
      v-if="showBookingIndicators && day.bookingInfo && day.isAvailable"
      class="calendar-day-indicator"
      :aria-hidden="true"
    >
      <div
        class="calendar-day-indicator-bar"
        :class="getIndicatorClasses(day.status)"
        :style="{ width: getIndicatorWidth(day.bookingInfo) + '%' }"
      />
    </div>

    <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РјРµС‚РєРё -->
    <div v-if="day.isToday" class="calendar-day-today-dot" aria-hidden="true" />
    
    <!-- РРєРѕРЅРєР° РІС‹Р±СЂР°РЅРЅРѕРіРѕ РґРЅСЏ -->
    <div
      v-if="day.isSelected"
      class="calendar-day-selected-icon"
      aria-hidden="true"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path
          fill-rule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clip-rule="evenodd"
        />
      </svg>
    </div>

    <!-- Tooltip СЃ РёРЅС„РѕСЂРјР°С†РёРµР№ Рѕ СЃР»РѕС‚Р°С… -->
    <div
      v-if="showTooltip && day.bookingInfo"
      class="calendar-day-tooltip"
      role="tooltip"
    >
      <div class="calendar-day-tooltip-content">
        <div class="font-medium">{{ formatDate(day.date) }}</div>
        <div class="text-sm">
          {{ getSlotText(day.bookingInfo) }}
        </div>
        <div v-if="day.status !== 'available'" class="text-xs text-gray-500 mt-1">
          {{ getStatusText(day.status) }}
        </div>
      </div>
    </div>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CalendarDay as CalendarDayType, DateBookingInfo, DateAvailabilityStatus } from '../../../model/calendar.types'

interface Props {
  day: CalendarDayType
  disabled?: boolean
  loading?: boolean
  showBookingIndicators?: boolean
  showTooltip?: boolean
  ariaLabel?: string
  dayIndex?: number
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
  loading: false,
  showBookingIndicators: true,
  showTooltip: false
})

const emit = defineEmits<{
  click: []
  mouseenter: []
  mouseleave: []
  focus: []
}>()

// РљР»Р°СЃСЃС‹ РґР»СЏ РґРЅСЏ РєР°Р»РµРЅРґР°СЂСЏ
const dayClasses = computed(() => {
  const classes = ['calendar-day']

  // Р‘Р°Р·РѕРІС‹Рµ СЃРѕСЃС‚РѕСЏРЅРёСЏ
  if (props.day.isToday) classes.push('calendar-day--today')
  if (props.day.isSelected) classes.push('calendar-day--selected')
  if (props.day.isHovered) classes.push('calendar-day--hovered')
  if (props.day.isWeekend) classes.push('calendar-day--weekend')
  if (props.day.isPast) classes.push('calendar-day--past')
  if (!props.day.isCurrentMonth) classes.push('calendar-day--other-month')

  // РЎРѕСЃС‚РѕСЏРЅРёСЏ РґРѕСЃС‚СѓРїРЅРѕСЃС‚Рё
  if (props.day.isAvailable) {
    classes.push('calendar-day--available')
    if (props.day.status) {
      classes.push(`calendar-day--${props.day.status}`)
    }
  } else {
    classes.push('calendar-day--unavailable')
  }

  // РЎРѕСЃС‚РѕСЏРЅРёСЏ РІР·Р°РёРјРѕРґРµР№СЃС‚РІРёСЏ
  if (props.disabled || props.loading) {
    classes.push('calendar-day--disabled')
  }

  return classes
})

// РџРѕР»СѓС‡РµРЅРёРµ РєР»Р°СЃСЃРѕРІ РґР»СЏ РёРЅРґРёРєР°С‚РѕСЂР° Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ
const getIndicatorClasses = (status: DateAvailabilityStatus) => {
  const statusClasses = {
    available: 'bg-green-500',
    busy: 'bg-yellow-500', 
    full: 'bg-red-500',
    unavailable: 'bg-gray-400'
  }
  
  return statusClasses[status] || statusClasses.unavailable
}

// Р’С‹С‡РёСЃР»РµРЅРёРµ С€РёСЂРёРЅС‹ РёРЅРґРёРєР°С‚РѕСЂР°
const getIndicatorWidth = (bookingInfo: DateBookingInfo): number => {
  if (!bookingInfo || bookingInfo.totalSlots === 0) return 100
  
  const occupiedPercent = (bookingInfo.bookedSlots / bookingInfo.totalSlots) * 100
  return Math.max(10, occupiedPercent) // РњРёРЅРёРјСѓРј 10% РґР»СЏ РІРёРґРёРјРѕСЃС‚Рё
}

// Р¤РѕСЂРјР°С‚РёСЂРѕРІР°РЅРёРµ РґР°С‚С‹ РґР»СЏ tooltip
const formatDate = (date: Date): string => {
  return date.toLocaleDateString('ru-RU', {
    weekday: 'long',
    day: 'numeric',
    month: 'long'
  })
}

// РўРµРєСЃС‚ Рѕ СЃР»РѕС‚Р°С… РґР»СЏ tooltip
const getSlotText = (bookingInfo: DateBookingInfo): string => {
  const available = bookingInfo.availableSlots
  const total = bookingInfo.totalSlots
  
  if (available === 0) {
    return 'Р’СЃРµ РјРµСЃС‚Р° Р·Р°РЅСЏС‚С‹'
  } else if (available === 1) {
    return '1 СЃРІРѕР±РѕРґРЅРѕРµ РјРµСЃС‚Рѕ'
  } else {
    return `${available} РёР· ${total} СЃРІРѕР±РѕРґРЅС‹С… РјРµСЃС‚`
  }
}

// РўРµРєСЃС‚ СЃС‚Р°С‚СѓСЃР°
const getStatusText = (status: DateAvailabilityStatus): string => {
  const statusTexts = {
    available: 'РњРЅРѕРіРѕ СЃРІРѕР±РѕРґРЅС‹С… РјРµСЃС‚',
    busy: 'РњР°Р»Рѕ СЃРІРѕР±РѕРґРЅС‹С… РјРµСЃС‚',
    full: 'РџРѕС‡С‚Рё РІСЃРµ Р·Р°РЅСЏС‚Рѕ',
    unavailable: 'РќРµРґРѕСЃС‚СѓРїРЅРѕ'
  }
  
  return statusTexts[status] || ''
}

// РћР±СЂР°Р±РѕС‚С‡РёРє РєР»РёРєР°
const handleClick = () => {
  if (!props.disabled && props.day.isAvailable) {
    emit('click')
  }
}
</script>

<style scoped>
.calendar-day {
  @apply relative aspect-square flex flex-col items-center justify-center text-sm font-medium transition-all duration-200 rounded-lg border border-transparent;
}

/* Р‘Р°Р·РѕРІС‹Рµ СЃРѕСЃС‚РѕСЏРЅРёСЏ */
.calendar-day--available {
  @apply text-gray-900 hover:bg-gray-100 cursor-pointer;
}

.calendar-day--unavailable {
  @apply text-gray-400 cursor-not-allowed;
}

.calendar-day--disabled {
  @apply opacity-50 cursor-not-allowed;
}

.calendar-day--other-month {
  @apply text-gray-300;
}

/* РЎРѕСЃС‚РѕСЏРЅРёСЏ РІС‹Р±РѕСЂР° */
.calendar-day--selected {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.calendar-day--hovered {
  @apply bg-blue-50 border-blue-200;
}

.calendar-day--today {
  @apply ring-2 ring-blue-400 ring-offset-1;
}

/* Р’С‹С…РѕРґРЅС‹Рµ РґРЅРё */
.calendar-day--weekend {
  @apply text-red-600;
}

.calendar-day--weekend.calendar-day--selected {
  @apply text-white;
}

.calendar-day--weekend.calendar-day--unavailable,
.calendar-day--weekend.calendar-day--past {
  @apply text-red-300;
}

/* РџСЂРѕС€РµРґС€РёРµ РґРЅРё */
.calendar-day--past {
  @apply text-gray-300 cursor-not-allowed;
}

/* РЎС‚Р°С‚СѓСЃС‹ РґРѕСЃС‚СѓРїРЅРѕСЃС‚Рё */
.calendar-day--available:hover {
  @apply bg-green-50 border-green-200;
}

.calendar-day--busy:hover {
  @apply bg-yellow-50 border-yellow-200;
}

.calendar-day--full:hover {
  @apply bg-red-50 border-red-200;
}

/* РќРѕРјРµСЂ РґРЅСЏ */
.calendar-day-number {
  @apply relative z-10 select-none;
}

/* РРЅРґРёРєР°С‚РѕСЂ Р·Р°РіСЂСѓР¶РµРЅРЅРѕСЃС‚Рё */
.calendar-day-indicator {
  @apply absolute bottom-1 left-1/2 transform -translate-x-1/2 w-6 h-1 bg-gray-200 rounded-full overflow-hidden;
}

.calendar-day-indicator-bar {
  @apply absolute left-0 top-0 h-full transition-all duration-300 rounded-full;
}

/* РўРѕС‡РєР° "СЃРµРіРѕРґРЅСЏ" */
.calendar-day-today-dot {
  @apply absolute top-1 right-1 w-2 h-2 bg-blue-500 rounded-full;
}

.calendar-day--selected .calendar-day-today-dot {
  @apply bg-white;
}

/* РРєРѕРЅРєР° РІС‹Р±СЂР°РЅРЅРѕРіРѕ РґРЅСЏ */
.calendar-day-selected-icon {
  @apply absolute top-0.5 right-0.5 text-white;
}

/* Tooltip */
.calendar-day-tooltip {
  @apply absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 z-50 opacity-0 pointer-events-none transition-opacity;
}

.calendar-day:hover .calendar-day-tooltip {
  @apply opacity-100;
}

.calendar-day-tooltip-content {
  @apply bg-gray-900 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap shadow-lg;
}

.calendar-day-tooltip-content::after {
  @apply absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0;
  content: '';
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 5px solid #1f2937;
}

/* Focus states */
.calendar-day:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

.calendar-day--selected:focus {
  @apply ring-white ring-offset-blue-600;
}

/* РђРЅРёРјР°С†РёРё */
.calendar-day:active:not(:disabled) {
  transform: scale(0.95);
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 640px) {
  .calendar-day {
    @apply text-xs;
  }
  
  .calendar-day-indicator {
    @apply w-4 h-0.5 bottom-0.5;
  }
  
  .calendar-day-today-dot {
    @apply w-1.5 h-1.5 top-0.5 right-0.5;
  }
  
  .calendar-day-selected-icon {
    @apply top-0 right-0;
  }
  
  .calendar-day-selected-icon svg {
    @apply w-3 h-3;
  }
  
  /* РЎРєСЂС‹РІР°РµРј РёРЅРґРёРєР°С‚РѕСЂС‹ РЅР° РјРѕР±РёР»СЊРЅС‹С… РґР»СЏ СѓРїСЂРѕС‰РµРЅРёСЏ */
  .calendar-day-indicator {
    @apply hidden;
  }
}

/* Р’С‹СЃРѕРєРёР№ РєРѕРЅС‚СЂР°СЃС‚ */
@media (prefers-contrast: high) {
  .calendar-day {
    @apply border-gray-400;
  }
  
  .calendar-day--selected {
    @apply border-2 border-blue-800;
  }
  
  .calendar-day--today {
    @apply ring-4 ring-blue-600;
  }
}

/* РЈРјРµРЅСЊС€РµРЅРЅР°СЏ Р°РЅРёРјР°С†РёСЏ */
@media (prefers-reduced-motion: reduce) {
  .calendar-day {
    transition: none;
  }
  
  .calendar-day:active:not(:disabled) {
    transform: none;
  }
  
  .calendar-day-indicator-bar {
    transition: none;
  }
}

/* РўРµРјРЅР°СЏ С‚РµРјР° */
@media (prefers-color-scheme: dark) {
  .calendar-day--available {
    @apply text-gray-100 hover:bg-gray-700;
  }
  
  .calendar-day--unavailable {
    @apply text-gray-600;
  }
  
  .calendar-day--other-month {
    @apply text-gray-600;
  }
  
  .calendar-day-tooltip-content {
    @apply bg-gray-800 border border-gray-600;
  }
}
</style>

