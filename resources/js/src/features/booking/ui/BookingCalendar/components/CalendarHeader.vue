<template>
  <div class="calendar-header" role="banner">
    <!-- РќР°РІРёРіР°С†РёСЏ РїРѕ РјРµСЃСЏС†Р°Рј -->
    <div class="calendar-navigation">
      <button
        type="button"
        :disabled="!navigation.canGoPrevious || disabled"
        class="calendar-nav-button calendar-nav-button--prev"
        :aria-label="`РџСЂРµРґС‹РґСѓС‰РёР№ РјРµСЃСЏС†`"
        @click="$emit('previousMonth')"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <!-- РўРµРєСѓС‰РёР№ РјРµСЃСЏС† Рё РіРѕРґ -->
      <div class="calendar-month-year" :aria-live="announce ? 'polite' : 'off'">
        <h2 class="calendar-title">
          {{ navigation.currentMonthName }} {{ navigation.currentYear }}
        </h2>
        
        <!-- РћРїС†РёРѕРЅР°Р»СЊРЅС‹Р№ СЃРµР»РµРєС‚РѕСЂ РјРµСЃСЏС†Р°/РіРѕРґР° -->
        <div v-if="showQuickNavigation" class="calendar-quick-nav">
          <select
            :value="navigation.currentMonth"
            class="calendar-month-select"
            :disabled="disabled"
            aria-label="Р’С‹Р±РµСЂРёС‚Рµ РјРµСЃСЏС†"
            @change="handleMonthChange"
          >
            <option
              v-for="(month, index) in monthNames"
              :key="index"
              :value="index"
            >
              {{ month }}
            </option>
          </select>
          
          <select
            :value="navigation.currentYear"
            class="calendar-year-select"
            :disabled="disabled"
            aria-label="Р’С‹Р±РµСЂРёС‚Рµ РіРѕРґ"
            @change="handleYearChange"
          >
            <option
              v-for="year in availableYears"
              :key="year"
              :value="year"
            >
              {{ year }}
            </option>
          </select>
        </div>
      </div>

      <button
        type="button"
        :disabled="!navigation.canGoNext || disabled"
        class="calendar-nav-button calendar-nav-button--next"
        :aria-label="`РЎР»РµРґСѓСЋС‰РёР№ РјРµСЃСЏС†`"
        @click="$emit('nextMonth')"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>

    <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РґРµР№СЃС‚РІРёСЏ -->
    <div v-if="showActions" class="calendar-actions">
      <button
        v-if="showTodayButton"
        type="button"
        class="calendar-today-button"
        :disabled="disabled"
        @click="$emit('goToday')"
      >
        РЎРµРіРѕРґРЅСЏ
      </button>
      
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CalendarNavigation } from '../../../model/calendar.types'

interface Props {
  navigation: CalendarNavigation
  monthNames?: string[]
  disabled?: boolean
  loading?: boolean
  showQuickNavigation?: boolean
  showActions?: boolean
  showTodayButton?: boolean
  announce?: boolean
  minYear?: number
  maxYear?: number
}

const props = withDefaults(defineProps<Props>(), {
  monthNames: () => [
    'РЇРЅРІР°СЂСЊ', 'Р¤РµРІСЂР°Р»СЊ', 'РњР°СЂС‚', 'РђРїСЂРµР»СЊ', 'РњР°Р№', 'РСЋРЅСЊ',
    'РСЋР»СЊ', 'РђРІРіСѓСЃС‚', 'РЎРµРЅС‚СЏР±СЂСЊ', 'РћРєС‚СЏР±СЂСЊ', 'РќРѕСЏР±СЂСЊ', 'Р”РµРєР°Р±СЂСЊ'
  ],
  disabled: false,
  loading: false,
  showQuickNavigation: false,
  showActions: true,
  showTodayButton: true,
  announce: true,
  minYear: () => new Date().getFullYear(),
  maxYear: () => new Date().getFullYear() + 2
})

const emit = defineEmits<{
  previousMonth: []
  nextMonth: []
  goToMonth: [year: number, month: number]
  goToday: []
}>()

// Р”РѕСЃС‚СѓРїРЅС‹Рµ РіРѕРґС‹ РґР»СЏ СЃРµР»РµРєС‚РѕСЂР°
const availableYears = computed(() => {
  const years: number[] = []
  for (let year = props.minYear; year <= props.maxYear; year++) {
    years.push(year)
  }
  return years
})

// РћР±СЂР°Р±РѕС‚С‡РёРєРё РёР·РјРµРЅРµРЅРёСЏ РјРµСЃСЏС†Р°/РіРѕРґР°
const handleMonthChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  const month = parseInt(target.value)
  emit('goToMonth', props.navigation.currentYear, month)
}

const handleYearChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  const year = parseInt(target.value)
  emit('goToMonth', year, props.navigation.currentMonth)
}
</script>

<style scoped>
.calendar-header {
  @apply flex items-center justify-between mb-4 p-2;
}

.calendar-navigation {
  @apply flex items-center gap-4;
}

.calendar-nav-button {
  @apply p-2 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
}

.calendar-nav-button:disabled {
  @apply text-gray-400;
}

.calendar-nav-button:hover:not(:disabled) {
  @apply bg-gray-100;
}

.calendar-month-year {
  @apply text-center min-w-0 flex-1;
}

.calendar-title {
  @apply text-lg font-semibold text-gray-900 whitespace-nowrap;
}

.calendar-quick-nav {
  @apply flex items-center gap-2 mt-2 justify-center;
}

.calendar-month-select,
.calendar-year-select {
  @apply px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed;
}

.calendar-actions {
  @apply flex items-center gap-2;
}

.calendar-today-button {
  @apply px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 640px) {
  .calendar-header {
    @apply flex-col gap-3;
  }
  
  .calendar-navigation {
    @apply w-full justify-between;
  }
  
  .calendar-title {
    @apply text-base;
  }
  
  .calendar-quick-nav {
    @apply mt-1;
  }
  
  .calendar-month-select,
  .calendar-year-select {
    @apply text-xs px-1 py-1;
  }
  
  .calendar-actions {
    @apply w-full justify-center;
  }
}

/* РђРЅРёРјР°С†РёСЏ РґР»СЏ РєРЅРѕРїРѕРє */
.calendar-nav-button {
  transition: all 0.2s ease;
}

.calendar-nav-button:active:not(:disabled) {
  transform: scale(0.95);
}

/* Р’С‹СЃРѕРєРёР№ РєРѕРЅС‚СЂР°СЃС‚ */
@media (prefers-contrast: high) {
  .calendar-nav-button {
    @apply border border-gray-600;
  }
  
  .calendar-today-button {
    @apply border-2 border-blue-600;
  }
}

/* РЈРјРµРЅСЊС€РµРЅРЅР°СЏ Р°РЅРёРјР°С†РёСЏ */
@media (prefers-reduced-motion: reduce) {
  .calendar-nav-button {
    transition: none;
  }
  
  .calendar-nav-button:active:not(:disabled) {
    transform: none;
  }
}
</style>

