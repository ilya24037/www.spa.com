<template>
  <div class="booking-calendar">
    <!-- Навигация по месяцам -->
    <div class="calendar-header">
      <button 
        type="button"
        @click="previousMonth"
        :disabled="!canGoPrevious"
        class="p-2 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <div class="calendar-month-year">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ monthNames[currentMonth] }} {{ currentYear }}
        </h3>
      </div>

      <button 
        type="button"
        @click="nextMonth"
        :disabled="!canGoNext"
        class="p-2 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>

    <!-- Дни недели -->
    <div class="calendar-weekdays">
      <div 
        v-for="day in weekDays" 
        :key="day"
        class="calendar-weekday"
      >
        {{ day }}
      </div>
    </div>

    <!-- Дни месяца -->
    <div class="calendar-days">
      <!-- Пустые ячейки в начале месяца -->
      <div 
        v-for="n in firstDayOfMonth"
        :key="`empty-start-${n}`"
        class="calendar-day-empty"
      />

      <!-- Дни месяца -->
      <button
        v-for="day in daysInMonth"
        :key="`day-${day}`"
        type="button"
        @click="selectDate(day)"
        :disabled="!isDateAvailable(day)"
        class="calendar-day"
        :class="{
          'calendar-day--today': isToday(day),
          'calendar-day--selected': isSelected(day),
          'calendar-day--available': isDateAvailable(day) && !isSelected(day),
          'calendar-day--unavailable': !isDateAvailable(day),
          'calendar-day--past': isPastDate(day),
          'calendar-day--weekend': isWeekend(day)
        }"
      >
        <span class="calendar-day-number">{{ day }}</span>
        
        <!-- Индикатор загруженности -->
        <div 
          v-if="getDateBookingStatus(day) && isDateAvailable(day)"
          class="calendar-day-indicator"
        >
          <div 
            class="calendar-day-indicator-bar"
            :class="{
              'bg-green-500': getDateBookingStatus(day) === 'available',
              'bg-yellow-500': getDateBookingStatus(day) === 'busy',
              'bg-red-500': getDateBookingStatus(day) === 'full'
            }"
            :style="{
              width: getDateBookingPercent(day) + '%'
            }"
          />
        </div>
      </button>

      <!-- Пустые ячейки в конце месяца -->
      <div 
        v-for="n in (42 - firstDayOfMonth - daysInMonth)"
        :key="`empty-end-${n}`"
        class="calendar-day-empty"
      />
    </div>

    <!-- Легенда -->
    <div class="calendar-legend">
      <div class="flex items-center gap-4 text-xs text-gray-600">
        <div class="flex items-center gap-1">
          <div class="w-3 h-3 bg-green-500 rounded-full"></div>
          <span>Много свободных слотов</span>
        </div>
        <div class="flex items-center gap-1">
          <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
          <span>Есть свободные слоты</span>
        </div>
        <div class="flex items-center gap-1">
          <div class="w-3 h-3 bg-red-500 rounded-full"></div>
          <span>Почти все занято</span>
        </div>
      </div>
    </div>

    <!-- Мобильный вид (список дат) -->
    <div class="calendar-mobile-list md:hidden mt-4">
      <h4 class="text-sm font-medium text-gray-700 mb-2">Ближайшие доступные даты:</h4>
      <div class="space-y-2">
        <button
          v-for="date in nextAvailableDates"
          :key="date"
          type="button"
          @click="selectDateFromList(date)"
          class="w-full p-3 text-left border rounded-lg hover:border-blue-500 transition-colors"
          :class="{
            'border-blue-600 bg-blue-50': isSelectedDate(date),
            'border-gray-200': !isSelectedDate(date)
          }"
        >
          <div class="flex items-center justify-between">
            <div>
              <div class="font-medium text-gray-900">
                {{ formatDateForList(date) }}
              </div>
              <div class="text-sm text-gray-500">
                {{ getAvailableSlotsCount(date) }} свободных слотов
              </div>
            </div>
            <svg 
              v-if="isSelectedDate(date)"
              class="w-5 h-5 text-blue-600" 
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: [String, Date, null],
    default: null
  },
  availableDates: {
    type: Array,
    default: () => []
  },
  minDate: {
    type: [String, Date],
    default: () => new Date()
  },
  maxDate: {
    type: [String, Date],
    default: () => {
      const date = new Date()
      date.setMonth(date.getMonth() + 3) // 3 месяца вперед
      return date
    }
  },
  bookingData: {
    type: Object,
    default: () => ({})
  }
})

// Emit
const emit = defineEmits(['update:modelValue', 'monthChanged'])

// Состояние
const currentDate = ref(new Date())
const currentMonth = ref(currentDate.value.getMonth())
const currentYear = ref(currentDate.value.getFullYear())
const selectedDate = ref(null)

// Константы
const monthNames = [
  'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
  'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
]

const weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

// Вычисляемые свойства
const firstDayOfMonth = computed(() => {
  const firstDay = new Date(currentYear.value, currentMonth.value, 1).getDay()
  // Преобразуем воскресенье (0) в 6, понедельник (1) в 0 и т.д.
  return firstDay === 0 ? 6 : firstDay - 1
})

const daysInMonth = computed(() => {
  return new Date(currentYear.value, currentMonth.value + 1, 0).getDate()
})

const canGoPrevious = computed(() => {
  const current = new Date(currentYear.value, currentMonth.value, 1)
  const min = new Date(props.minDate)
  return current > new Date(min.getFullYear(), min.getMonth(), 1)
})

const canGoNext = computed(() => {
  const current = new Date(currentYear.value, currentMonth.value, 1)
  const max = new Date(props.maxDate)
  return current < new Date(max.getFullYear(), max.getMonth(), 1)
})

// Следующие доступные даты для мобильного вида
const nextAvailableDates = computed(() => {
  const dates = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  // Ищем следующие 5 доступных дат
  for (let i = 0; i < 60 && dates.length < 5; i++) {
    const checkDate = new Date(today)
    checkDate.setDate(today.getDate() + i)
    
    const dateStr = formatDateString(checkDate)
    if (isDateInAvailableList(dateStr)) {
      dates.push(dateStr)
    }
  }
  
  return dates
})

// Методы
const previousMonth = () => {
  if (!canGoPrevious.value) return
  
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
  
  emit('monthChanged', { month: currentMonth.value, year: currentYear.value })
}

const nextMonth = () => {
  if (!canGoNext.value) return
  
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
  
  emit('monthChanged', { month: currentMonth.value, year: currentYear.value })
}

const getDateObject = (day) => {
  return new Date(currentYear.value, currentMonth.value, day)
}

const formatDateString = (date) => {
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const isToday = (day) => {
  const today = new Date()
  const date = getDateObject(day)
  return date.toDateString() === today.toDateString()
}

const isSelected = (day) => {
  if (!selectedDate.value) return false
  const date = getDateObject(day)
  return formatDateString(date) === selectedDate.value
}

const isSelectedDate = (dateStr) => {
  return selectedDate.value === dateStr
}

const isPastDate = (day) => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const date = getDateObject(day)
  date.setHours(0, 0, 0, 0)
  return date < today
}

const isWeekend = (day) => {
  const date = getDateObject(day)
  const dayOfWeek = date.getDay()
  return dayOfWeek === 0 || dayOfWeek === 6
}

const isDateInAvailableList = (dateStr) => {
  return props.availableDates.includes(dateStr) || props.availableDates.length === 0
}

const isDateAvailable = (day) => {
  if (isPastDate(day)) return false
  
  const dateStr = formatDateString(getDateObject(day))
  
  // Если список доступных дат пуст, считаем все даты доступными
  if (props.availableDates.length === 0) {
    return !isPastDate(day)
  }
  
  return isDateInAvailableList(dateStr)
}

const getDateBookingStatus = (day) => {
  const dateStr = formatDateString(getDateObject(day))
  const bookingInfo = props.bookingData[dateStr]
  
  if (!bookingInfo) return null
  
  const { totalSlots, bookedSlots } = bookingInfo
  const availablePercent = ((totalSlots - bookedSlots) / totalSlots) * 100
  
  if (availablePercent > 70) return 'available'
  if (availablePercent > 30) return 'busy'
  return 'full'
}

const getDateBookingPercent = (day) => {
  const dateStr = formatDateString(getDateObject(day))
  const bookingInfo = props.bookingData[dateStr]
  
  if (!bookingInfo) return 100
  
  const { totalSlots, bookedSlots } = bookingInfo
  return ((totalSlots - bookedSlots) / totalSlots) * 100
}

const getAvailableSlotsCount = (dateStr) => {
  const bookingInfo = props.bookingData[dateStr]
  if (!bookingInfo) return '10+' // Значение по умолчанию
  
  const available = bookingInfo.totalSlots - bookingInfo.bookedSlots
  return available > 10 ? '10+' : available
}

const selectDate = (day) => {
  if (!isDateAvailable(day)) return
  
  const date = getDateObject(day)
  selectedDate.value = formatDateString(date)
  emit('update:modelValue', selectedDate.value)
}

const selectDateFromList = (dateStr) => {
  selectedDate.value = dateStr
  emit('update:modelValue', selectedDate.value)
  
  // Переключаем календарь на выбранный месяц
  const date = new Date(dateStr)
  currentMonth.value = date.getMonth()
  currentYear.value = date.getFullYear()
}

const formatDateForList = (dateStr) => {
  const date = new Date(dateStr)
  const today = new Date()
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)
  
  if (date.toDateString() === today.toDateString()) {
    return 'Сегодня'
  } else if (date.toDateString() === tomorrow.toDateString()) {
    return 'Завтра'
  }
  
  return date.toLocaleDateString('ru-RU', {
    weekday: 'short',
    day: 'numeric',
    month: 'long'
  })
}

// Синхронизация с v-model
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    selectedDate.value = formatDateString(new Date(newValue))
    
    // Переключаем календарь на месяц выбранной даты
    const date = new Date(newValue)
    currentMonth.value = date.getMonth()
    currentYear.value = date.getFullYear()
  } else {
    selectedDate.value = null
  }
}, { immediate: true })

// Инициализация
onMounted(() => {
  // Если есть выбранная дата, показываем её месяц
  if (props.modelValue) {
    const date = new Date(props.modelValue)
    currentMonth.value = date.getMonth()
    currentYear.value = date.getFullYear()
  }
})
</script>

<style scoped>
.booking-calendar {
  @apply select-none;
}

.calendar-header {
  @apply flex items-center justify-between mb-4;
}

.calendar-month-year {
  @apply text-center;
}

.calendar-weekdays {
  @apply grid grid-cols-7 gap-1 mb-2;
}

.calendar-weekday {
  @apply text-center text-sm font-medium text-gray-700 py-2;
}

.calendar-days {
  @apply grid grid-cols-7 gap-1;
}

.calendar-day-empty {
  @apply aspect-square;
}

.calendar-day {
  @apply relative aspect-square flex flex-col items-center justify-center rounded-lg text-sm font-medium transition-all;
  @apply hover:bg-gray-100;
}

.calendar-day--today {
  @apply ring-2 ring-blue-400 ring-offset-2;
}

.calendar-day--selected {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.calendar-day--available {
  @apply text-gray-900 cursor-pointer;
}

.calendar-day--unavailable {
  @apply text-gray-400 cursor-not-allowed hover:bg-transparent;
}

.calendar-day--past {
  @apply text-gray-300 cursor-not-allowed hover:bg-transparent;
}

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

.calendar-day-number {
  @apply relative z-10;
}

.calendar-day-indicator {
  @apply absolute bottom-1 left-1/2 -translate-x-1/2 w-8 h-1 bg-gray-200 rounded-full overflow-hidden;
}

.calendar-day-indicator-bar {
  @apply absolute left-0 top-0 h-full transition-all;
}

.calendar-legend {
  @apply mt-4 pt-4 border-t;
}

/* Мобильные стили */
@media (max-width: 640px) {
  .calendar-day {
    @apply text-xs;
  }
  
  .calendar-weekday {
    @apply text-xs;
  }
  
  .calendar-day-indicator {
    @apply hidden;
  }
}

/* Анимации */
.calendar-day {
  transition: all 0.2s ease;
}

.calendar-day:active:not(:disabled) {
  transform: scale(0.95);
}

/* Темная тема (опционально) */
@media (prefers-color-scheme: dark) {
  .booking-calendar {
    @apply text-gray-100;
  }
  
  .calendar-day--available {
    @apply text-gray-100 hover:bg-gray-700;
  }
  
  .calendar-day--unavailable {
    @apply text-gray-600;
  }
  
  .calendar-weekday {
    @apply text-gray-400;
  }
}
</style>