<template>
  <div class="calendar-grid-wrapper">
    <!-- Р—Р°РіРѕР»РѕРІРєРё РґРЅРµР№ РЅРµРґРµР»Рё -->
    <div class="calendar-weekdays" role="row">
      <div
        v-for="day in weekDays"
        :key="day"
        class="calendar-weekday"
        role="columnheader"
        :aria-label="`РЎС‚РѕР»Р±РµС† ${day}`"
      >
        <span class="calendar-weekday-text">{{ day }}</span>
      </div>
    </div>

    <!-- РЎРµС‚РєР° РґРЅРµР№ -->
    <div 
      class="calendar-days"
      role="grid"
      :aria-label="`РљР°Р»РµРЅРґР°СЂСЊ РЅР° ${currentMonthName} ${currentYear}`"
      @keydown="handleKeyNavigation"
    >
      <CalendarDay
        v-for="(day, index) in calendarDays"
        :key="`${day.dateString}-${index}`"
        :day="day"
        :disabled="disabled"
        :loading="loading"
        :show-booking-indicators="showBookingIndicators"
        :aria-label="getDayAriaLabel(day)"
        :tabindex="getDayTabIndex(day, index)"
        role="gridcell"
        @click="handleDayClick(day)"
        @mouseenter="handleDayHover(day.dateString)"
        @mouseleave="handleDayHover(null)"
        @focus="handleDayFocus(day, index)"
      />
    </div>

    <!-- Skeleton loader -->
    <div v-if="loading" class="calendar-skeleton">
      <div class="calendar-weekdays">
        <div
          v-for="n in 7"
          :key="`skeleton-weekday-${n}`"
          class="calendar-weekday"
        >
          <div class="animate-pulse h-4 bg-gray-200 rounded w-6"></div>
        </div>
      </div>
      <div class="calendar-days">
        <div
          v-for="n in 42"
          :key="`skeleton-day-${n}`"
          class="calendar-day-skeleton"
        >
          <div class="animate-pulse h-8 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import CalendarDay from './CalendarDay.vue'
import type { CalendarDay as CalendarDayType } from '../../../model/calendar.types'

interface Props {
  calendarDays: CalendarDayType[]
  weekDays?: string[]
  currentMonthName: string
  currentYear: number
  disabled?: boolean
  loading?: boolean
  showBookingIndicators?: boolean
  keyboardNavigation?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  weekDays: () => ['РџРЅ', 'Р’С‚', 'РЎСЂ', 'Р§С‚', 'РџС‚', 'РЎР±', 'Р’СЃ'],
  disabled: false,
  loading: false,
  showBookingIndicators: true,
  keyboardNavigation: true
})

const emit = defineEmits<{
  dayClick: [day: CalendarDayType]
  dayHover: [dateString: string | null]
  dayFocus: [day: CalendarDayType]
  dateSelect: [dateString: string]
}>()

// РЎРѕСЃС‚РѕСЏРЅРёРµ С„РѕРєСѓСЃР° РґР»СЏ РєР»Р°РІРёР°С‚СѓСЂРЅРѕР№ РЅР°РІРёРіР°С†РёРё
const focusedDayIndex = ref<number>(-1)

// РћР±СЂР°Р±РѕС‚С‡РёРєРё СЃРѕР±С‹С‚РёР№
const handleDayClick = (day: CalendarDayType) => {
  if (props.disabled || !day.isAvailable) return
  
  emit('dayClick', day)
  emit('dateSelect', day.dateString)
}

const handleDayHover = (dateString: string | null) => {
  if (props.disabled) return
  emit('dayHover', dateString)
}

const handleDayFocus = (day: CalendarDayType, index: number) => {
  focusedDayIndex.value = index
  emit('dayFocus', day)
}

// РџРѕР»СѓС‡РµРЅРёРµ ARIA label РґР»СЏ РґРЅСЏ
const getDayAriaLabel = (day: CalendarDayType): string => {
  let label = `${day.day} ${props.currentMonthName}`
  
  if (!day.isCurrentMonth) {
    const monthNames = [
      'РЇРЅРІР°СЂСЊ', 'Р¤РµРІСЂР°Р»СЊ', 'РњР°СЂС‚', 'РђРїСЂРµР»СЊ', 'РњР°Р№', 'РСЋРЅСЊ',
      'РСЋР»СЊ', 'РђРІРіСѓСЃС‚', 'РЎРµРЅС‚СЏР±СЂСЊ', 'РћРєС‚СЏР±СЂСЊ', 'РќРѕСЏР±СЂСЊ', 'Р”РµРєР°Р±СЂСЊ'
    ]
    label = `${day.day} ${monthNames[day.date.getMonth()]}`
  }
  
  if (day.isToday) label += ', СЃРµРіРѕРґРЅСЏ'
  if (day.isSelected) label += ', РІС‹Р±СЂР°РЅРѕ'
  if (day.isWeekend) label += ', РІС‹С…РѕРґРЅРѕР№'
  if (!day.isAvailable) label += ', РЅРµРґРѕСЃС‚СѓРїРЅРѕ'
  else if (day.bookingInfo) {
    const slots = day.bookingInfo.availableSlots
    if (slots === 0) label += ', РІСЃРµ Р·Р°РЅСЏС‚Рѕ'
    else if (slots === 1) label += ', 1 СЃРІРѕР±РѕРґРЅРѕРµ РјРµСЃС‚Рѕ'
    else label += `, ${slots} СЃРІРѕР±РѕРґРЅС‹С… РјРµСЃС‚`
  }
  
  return label
}

// РџРѕР»СѓС‡РµРЅРёРµ tabindex РґР»СЏ РґРЅСЏ (РґР»СЏ РєР»Р°РІРёР°С‚СѓСЂРЅРѕР№ РЅР°РІРёРіР°С†РёРё)
const getDayTabIndex = (day: CalendarDayType, index: number): string => {
  if (!props.keyboardNavigation || props.disabled) return '-1'
  
  // РџРµСЂРІС‹Р№ РґРѕСЃС‚СѓРїРЅС‹Р№ РґРµРЅСЊ РїРѕР»СѓС‡Р°РµС‚ tabindex="0"
  if (focusedDayIndex.value === -1 && day.isCurrentMonth && day.isAvailable) {
    return '0'
  }
  
  // РЎС„РѕРєСѓСЃРёСЂРѕРІР°РЅРЅС‹Р№ РґРµРЅСЊ РїРѕР»СѓС‡Р°РµС‚ tabindex="0"
  if (focusedDayIndex.value === index) return '0'
  
  return '-1'
}

// РљР»Р°РІРёР°С‚СѓСЂРЅР°СЏ РЅР°РІРёРіР°С†РёСЏ
const handleKeyNavigation = async (event: KeyboardEvent) => {
  if (!props.keyboardNavigation || props.disabled) return
  
  const { key } = event
  const currentIndex = focusedDayIndex.value
  let newIndex = currentIndex

  switch (key) {
    case 'ArrowLeft':
      newIndex = Math.max(0, currentIndex - 1)
      break
    case 'ArrowRight':
      newIndex = Math.min(props.calendarDays.length - 1, currentIndex + 1)
      break
    case 'ArrowUp':
      newIndex = Math.max(0, currentIndex - 7)
      break
    case 'ArrowDown':
      newIndex = Math.min(props.calendarDays.length - 1, currentIndex + 7)
      break
    case 'Home':
      newIndex = 0
      break
    case 'End':
      newIndex = props.calendarDays.length - 1
      break
    case 'Enter':
    case ' ':
      if (currentIndex >= 0) {
        const day = props.calendarDays[currentIndex]
        if (day && day.isAvailable) {
          if (day) handleDayClick(day)
        }
      }
      break
    default:
      return // РќРµ РѕР±СЂР°Р±Р°С‚С‹РІР°РµРј РґСЂСѓРіРёРµ РєР»Р°РІРёС€Рё
  }

  if (newIndex !== currentIndex && newIndex >= 0 && newIndex < props.calendarDays.length) {
    event.preventDefault()
    focusedDayIndex.value = newIndex
    
    await nextTick()
    
    // Р¤РѕРєСѓСЃРёСЂСѓРµРјСЃСЏ РЅР° РЅРѕРІРѕРј РґРЅРµ
    const dayElement = document.querySelector(`[data-day-index="${newIndex}"]`) as HTMLElement
    if (dayElement) {
      dayElement.focus()
    }
  }
}

// РќР°С…РѕРґРёРј С‚РµРєСѓС‰РёР№ РґРµРЅСЊ РґР»СЏ РЅР°С‡Р°Р»СЊРЅРѕР№ СѓСЃС‚Р°РЅРѕРІРєРё С„РѕРєСѓСЃР°
const todayIndex = computed(() => {
  return props.calendarDays.findIndex(day => day.isToday && day.isCurrentMonth)
})

// РЈСЃС‚Р°РЅР°РІР»РёРІР°РµРј РЅР°С‡Р°Р»СЊРЅС‹Р№ С„РѕРєСѓСЃ РЅР° СЃРµРіРѕРґРЅСЏС€РЅРёР№ РґРµРЅСЊ
if (todayIndex.value >= 0) {
  focusedDayIndex.value = todayIndex.value
}
</script>

<style scoped>
.calendar-grid-wrapper {
  @apply select-none;
}

.calendar-weekdays {
  @apply grid grid-cols-7 gap-1 mb-2;
}

.calendar-weekday {
  @apply text-center py-2;
}

.calendar-weekday-text {
  @apply text-sm font-medium text-gray-700;
}

.calendar-days {
  @apply grid grid-cols-7 gap-1;
}

/* Skeleton loading states */
.calendar-skeleton {
  @apply absolute inset-0 bg-white bg-opacity-90;
}

.calendar-day-skeleton {
  @apply aspect-square p-1;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 640px) {
  .calendar-weekday-text {
    @apply text-xs;
  }
  
  .calendar-days {
    @apply gap-0.5;
  }
}

/* Р’С‹СЃРѕРєРёР№ РєРѕРЅС‚СЂР°СЃС‚ */
@media (prefers-contrast: high) {
  .calendar-weekday-text {
    @apply text-black font-bold;
  }
}

/* РђРЅРёРјР°С†РёРё */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.calendar-days {
  animation: fadeIn 0.3s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
  .calendar-days {
    animation: none;
  }
  
  .animate-pulse {
    animation: none;
  }
}
</style>

