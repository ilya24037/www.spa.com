<template>
  <div 
    class="booking-calendar"
    :class="{
      'booking-calendar--loading': loading,
      'booking-calendar--disabled': disabled
    }"
  >
    <!-- Р—Р°РіРѕР»РѕРІРѕРє РєР°Р»РµРЅРґР°СЂСЏ -->
    <CalendarHeader
      :navigation="navigation"
      :disabled="disabled"
      :loading="loading"
      :show-quick-navigation="showQuickNavigation"
      :show-today-button="showTodayButton"
      @previous-month="goToPreviousMonth"
      @next-month="goToNextMonth"
      @go-to-month="goToMonth"
      @go-today="goToToday"
    >
      <template #actions>
        <slot name="header-actions" />
      </template>
    </CalendarHeader>

    <!-- РЎРµС‚РєР° РєР°Р»РµРЅРґР°СЂСЏ -->
    <div class="booking-calendar-main">
      <CalendarGrid
        :calendar-days="calendarDays"
        :week-days="weekDays"
        :current-month-name="navigation.currentMonthName"
        :current-year="navigation.currentYear"
        :disabled="disabled"
        :loading="loading"
        :show-booking-indicators="showBookingIndicators"
        :keyboard-navigation="keyboardNavigation"
        @day-click="handleDayClick"
        @day-hover="handleDayHover"
        @day-focus="handleDayFocus"
        @date-select="handleDateSelect"
      />
    </div>

    <!-- Р›РµРіРµРЅРґР° РєР°Р»РµРЅРґР°СЂСЏ -->
    <CalendarLegend
      :show-legend="showLegend"
      :show-statistics="showStatistics"
      :legend-items="legendItems"
      :statistics="bookingStatistics"
      :compact="compact"
    />

    <!-- РњРѕР±РёР»СЊРЅС‹Р№ СЃРїРёСЃРѕРє РґР°С‚ -->
    <MobileDateList
      :available-dates="nextAvailableDates"
      :selected-date="selectedDate"
      :title="mobileListTitle"
      :empty-state-text="mobileEmptyText"
      :show-mobile-list="showMobileList"
      :max-visible-dates="maxMobileDates"
      :disabled="disabled"
      :show-additional-info="showMobileAdditionalInfo"
      :get-status-for-date="getBookingStatus"
      :get-additional-info-for-date="getMobileAdditionalInfo"
      @date-select="handleMobileDateSelect"
    />

    <!-- Slot РґР»СЏ РґРѕРїРѕР»РЅРёС‚РµР»СЊРЅРѕРіРѕ РєРѕРЅС‚РµРЅС‚Р° -->
    <div v-if="$slots.footer" class="booking-calendar-footer">
      <slot name="footer" :selected-date="selectedDate" :calendar-state="state" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch, toRefs } from 'vue'
import CalendarHeader from './components/CalendarHeader.vue'
import CalendarGrid from './components/CalendarGrid.vue'
import CalendarLegend from './components/CalendarLegend.vue'
import MobileDateList from './components/MobileDateList.vue'

// Composables
import { useCalendar } from './composables/useCalendar'
import { useDateSelection } from './composables/useDateSelection'
import { useBookingStatus } from './composables/useBookingStatus'

// Types
import type {
    BookingCalendarProps,
    BookingCalendarEmits,
    CalendarDay,
    // DateBookingInfo
} from '../../model/calendar.types'

// Props
interface Props extends BookingCalendarProps {
  // Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РїСЂРѕРїСЃС‹ РґР»СЏ РєР°СЃС‚РѕРјРёР·Р°С†РёРё
  showQuickNavigation?: boolean
  showTodayButton?: boolean
  showLegend?: boolean
  showStatistics?: boolean
  showBookingIndicators?: boolean
  showMobileList?: boolean
  showMobileAdditionalInfo?: boolean
  keyboardNavigation?: boolean
  compact?: boolean
  maxMobileDates?: number
  mobileListTitle?: string
  mobileEmptyText?: string
}

const props = withDefaults(defineProps<Props>(), {
    availableDates: () => [],
    minDate: () => new Date(),
    maxDate: () => {
        const date = new Date()
        date.setMonth(date.getMonth() + 3)
        return date
    },
    bookingData: () => ({}),
    disabled: false,
    loading: false,
    locale: 'ru-RU',
    showQuickNavigation: false,
    showTodayButton: true,
    showLegend: true,
    showStatistics: false,
    showBookingIndicators: true,
    showMobileList: true,
    showMobileAdditionalInfo: false,
    keyboardNavigation: true,
    compact: false,
    maxMobileDates: 5,
    mobileListTitle: 'Р‘Р»РёР¶Р°Р№С€РёРµ РґРѕСЃС‚СѓРїРЅС‹Рµ РґР°С‚С‹',
    mobileEmptyText: 'РќРµС‚ РґРѕСЃС‚СѓРїРЅС‹С… РґР°С‚'
})

// Emits
const emit = defineEmits<BookingCalendarEmits>()

// Reactive refs РґР»СЏ composables
const availableDatesRef = toRefs(props).availableDates
const bookingDataRef = toRefs(props).bookingData

// РћСЃРЅРѕРІРЅС‹Рµ composables
const {
    state,
    navigation,
    calendarDays,
    // monthNames,
    weekDays,
    goToPreviousMonth,
    goToNextMonth,
    goToMonth,
    goToToday,
    selectDate,
    setHoveredDate,
    initializeCalendar
} = useCalendar(props)

const {
    nextAvailableDates,
    // formatDateForDisplay,
    // getAvailableSlotsText
} = useDateSelection(availableDatesRef, bookingDataRef, computed(() => state.value.selectedDate))

const {
    getBookingStatus,
    legendItems,
    bookingStatistics
} = useBookingStatus(bookingDataRef)

// Computed СЃРІРѕР№СЃС‚РІР°
const selectedDate = computed(() => state.value.selectedDate)

// РћР±СЂР°Р±РѕС‚С‡РёРєРё СЃРѕР±С‹С‚РёР№
const handleDayClick = (day: CalendarDay) => {
    selectDate(day.dateString)
    emit('dateSelected', day.dateString)
}

const handleDayHover = (dateString: string | null) => {
    setHoveredDate(dateString)
}

const handleDayFocus = (_day: CalendarDay) => {
    // РњРѕР¶РЅРѕ РґРѕР±Р°РІРёС‚СЊ Р»РѕРіРёРєСѓ РґР»СЏ С„РѕРєСѓСЃР°
}

const handleDateSelect = (dateString: string) => {
    emit('update:modelValue', dateString)
}

const handleMobileDateSelect = (dateString: string) => {
    selectDate(dateString)
    emit('dateSelected', dateString)
    emit('update:modelValue', dateString)
  
    // РџРµСЂРµРєР»СЋС‡Р°РµРј РєР°Р»РµРЅРґР°СЂСЊ РЅР° РјРµСЃСЏС† РІС‹Р±СЂР°РЅРЅРѕР№ РґР°С‚С‹
    const date = new Date(dateString)
    goToMonth(date.getFullYear(), date.getMonth())
}

// РњРµС‚РѕРґС‹ РґР»СЏ РјРѕР±РёР»СЊРЅРѕРіРѕ СЃРїРёСЃРєР°
const getMobileAdditionalInfo = (dateString: string): string => {
    const bookingInfo = props.bookingData[dateString]
    if (!bookingInfo) return ''
  
    const status = getBookingStatus(dateString)
    const statusTexts = {
        available: 'РњРЅРѕРіРѕ СЃРІРѕР±РѕРґРЅС‹С… РјРµСЃС‚',
        busy: 'РњР°Р»Рѕ СЃРІРѕР±РѕРґРЅС‹С… РјРµСЃС‚', 
        full: 'РџРѕС‡С‚Рё РІСЃРµ Р·Р°РЅСЏС‚Рѕ',
        unavailable: 'РќРµРґРѕСЃС‚СѓРїРЅРѕ'
    }
  
    return statusTexts[status] || ''
}

// РћС‚СЃР»РµР¶РёРІР°РЅРёРµ РёР·РјРµРЅРµРЅРёР№ РјРµСЃСЏС†Р°
watch([() => navigation.value.currentMonth, () => navigation.value.currentYear], 
    ([month, year]) => {
        emit('monthChanged', { month, year })
    }
)

// РЎРёРЅС…СЂРѕРЅРёР·Р°С†РёСЏ СЃ РІРЅРµС€РЅРёРј modelValue
watch(() => props.modelValue, (newValue) => {
    if (newValue && selectedDate.value !== newValue) {
        const dateString = typeof newValue === 'string' ? newValue : newValue.toISOString().split('T')[0]
        if (dateString) selectDate(dateString)
    }
}, { immediate: true })

// РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ
initializeCalendar()
</script>

<style scoped>
.booking-calendar {
  @apply w-full max-w-md mx-auto bg-white rounded-lg shadow-sm border border-gray-500 overflow-hidden;
}

.booking-calendar--loading {
  @apply relative;
}

.booking-calendar--loading::after {
  @apply absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center;
  content: '';
  z-index: 10;
}

.booking-calendar--disabled {
  @apply opacity-60 pointer-events-none;
}

.booking-calendar-main {
  @apply px-4 pb-4;
}

.booking-calendar-footer {
  @apply border-t border-gray-500 p-4;
}

/* РљРѕРјРїР°РєС‚РЅС‹Р№ СЂРµР¶РёРј */
.booking-calendar--compact {
  @apply shadow-none border-0 rounded-none;
}

.booking-calendar--compact .booking-calendar-main {
  @apply px-2 pb-2;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 640px) {
  .booking-calendar {
    @apply max-w-none mx-0 shadow-none border-0 rounded-none;
  }
  
  .booking-calendar-main {
    @apply px-2;
  }
}

/* РЎРѕСЃС‚РѕСЏРЅРёСЏ Р·Р°РіСЂСѓР·РєРё */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.booking-calendar--loading .booking-calendar-main {
  animation: pulse 2s infinite;
}

@media (prefers-reduced-motion: reduce) {
  .booking-calendar--loading .booking-calendar-main {
    animation: none;
  }
}

/* РўРµРјРЅР°СЏ С‚РµРјР° */
@media (prefers-color-scheme: dark) {
  .booking-calendar {
    @apply bg-gray-500 border-gray-500;
  }
  
  .booking-calendar-footer {
    @apply border-gray-500;
  }
}

/* Р’С‹СЃРѕРєРёР№ РєРѕРЅС‚СЂР°СЃС‚ */
@media (prefers-contrast: high) {
  .booking-calendar {
    @apply border-2 border-gray-500;
  }
}
</style>

