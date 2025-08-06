<template>
  <div class="booking-calendar-examples p-8 space-y-8">
    <h1 class="text-2xl font-bold mb-8">Примеры использования BookingCalendar</h1>
    
    <!-- Базовый пример -->
    <div class="example-section">
      <h2 class="text-lg font-semibold mb-4">Базовое использование</h2>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Календарь -->
        <div class="space-y-4">
          <BookingCalendar
            v-model="selectedDate"
            :available-dates="availableDates"
            :booking-data="bookingData"
            :min-date="minDate"
            :max-date="maxDate"
            :loading="isLoading"
            :show-statistics="true"
            @date-selected="handleDateSelected"
            @month-changed="handleMonthChanged"
          >
            <template #footer="{ selectedDate }">
              <div v-if="selectedDate" class="text-sm text-gray-600">
                Выбрана дата: <strong>{{ formatSelectedDate(selectedDate) }}</strong>
              </div>
            </template>
          </BookingCalendar>
          
          <!-- Управление -->
          <div class="space-y-2">
            <button
              @click="loadBookingData"
              :disabled="isLoading"
              class="w-full btn btn-primary"
            >
              {{ isLoading ? 'Загрузка...' : 'Обновить данные' }}
            </button>
            
            <button
              @click="clearSelection"
              class="w-full btn btn-secondary"
            >
              Очистить выбор
            </button>
          </div>
        </div>

        <!-- Информация -->
        <div class="space-y-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-medium mb-2">Состояние календаря</h3>
            <pre class="text-xs text-gray-600">{{ JSON.stringify(calendarState, null, 2) }}</pre>
          </div>
          
          <div class="bg-blue-50 p-4 rounded-lg">
            <h3 class="font-medium mb-2">Статистика бронирования</h3>
            <div v-if="bookingStats" class="text-sm space-y-1">
              <div>Всего дат: {{ bookingStats.total }}</div>
              <div>Доступно: {{ bookingStats.available }}</div>
              <div>Частично занято: {{ bookingStats.busy }}</div>
              <div>Занято: {{ bookingStats.full }}</div>
              <div>Средняя занятость: {{ bookingStats.averageOccupancy }}%</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Компактный пример -->
    <div class="example-section">
      <h2 class="text-lg font-semibold mb-4">Компактная версия</h2>
      
      <div class="max-w-sm">
        <BookingCalendar
          v-model="selectedDateCompact"
          :available-dates="availableDates"
          :booking-data="bookingData"
          :compact="true"
          :show-legend="false"
          :show-mobile-list="false"
          :max-mobile-dates="3"
        />
      </div>
    </div>

    <!-- Кастомизированный пример -->
    <div class="example-section">
      <h2 class="text-lg font-semibold mb-4">Кастомизированная версия</h2>
      
      <BookingCalendar
        v-model="selectedDateCustom"
        :available-dates="customAvailableDates"
        :booking-data="customBookingData"
        :show-quick-navigation="true"
        :show-booking-indicators="true"
        :show-mobile-additional-info="true"
        :keyboard-navigation="true"
        mobile-list-title="Рекомендуемые даты"
        mobile-empty-text="Все даты заняты"
      >
        <template #header-actions>
          <button class="text-sm text-blue-600 hover:text-blue-800">
            Настройки
          </button>
        </template>
      </BookingCalendar>
    </div>

    <!-- Программное управление -->
    <div class="example-section">
      <h2 class="text-lg font-semibold mb-4">Программное управление</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <button @click="goToToday" class="btn btn-primary">
          Перейти к сегодня
        </button>
        <button @click="goToNextWeek" class="btn btn-secondary">
          Следующая неделя
        </button>
        <button @click="selectRandomDate" class="btn btn-secondary">
          Случайная дата
        </button>
      </div>
      
      <BookingCalendar
        ref="programmaticCalendar"
        v-model="selectedDateProgrammatic"
        :available-dates="availableDates"
        :booking-data="bookingData"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { BookingCalendar, useBookingStatus } from '../index'
import type { DateBookingInfo } from '../model/calendar.types'

// Состояние
const selectedDate = ref<string | null>(null)
const selectedDateCompact = ref<string | null>(null)
const selectedDateCustom = ref<string | null>(null)
const selectedDateProgrammatic = ref<string | null>(null)
const isLoading = ref(false)

// Даты
const minDate = new Date()
const maxDate = new Date()
maxDate.setMonth(maxDate.getMonth() + 3)

// Доступные даты (пример)
const availableDates = ref<string[]>([])
const customAvailableDates = ref<string[]>([])

// Данные бронирования
const bookingData = ref<Record<string, DateBookingInfo>>({})
const customBookingData = ref<Record<string, DateBookingInfo>>({})

// Статистика
const { bookingStatistics: bookingStats } = useBookingStatus(computed(() => bookingData.value))

// Состояние календаря для отображения
const calendarState = computed(() => ({
  selectedDate: selectedDate.value,
  isLoading: isLoading.value,
  totalAvailableDates: availableDates.value.length,
  hasBookingData: Object.keys(bookingData.value).length > 0
}))

// Генерация тестовых данных
const generateAvailableDates = (count: number = 30): string[] => {
  const dates: string[] = []
  const today = new Date()
  
  for (let i = 0; i < count; i++) {
    const date = new Date(today)
    date.setDate(today.getDate() + i)
    
    // Добавляем дату с вероятностью 70%
    if (Math.random() > 0.3) {
      const dateString = date.toISOString().split('T')[0]
      if (dateString) dates.push(dateString)
    }
  }
  
  return dates
}

const generateBookingData = (dates: string[]): Record<string, DateBookingInfo> => {
  const data: Record<string, DateBookingInfo> = {}
  
  dates.forEach(dateString => {
    const totalSlots = Math.floor(Math.random() * 10) + 5 // 5-15 слотов
    const bookedSlots = Math.floor(Math.random() * totalSlots) // случайное количество занятых
    
    data[dateString] = {
      totalSlots,
      bookedSlots,
      availableSlots: totalSlots - bookedSlots,
      date: dateString,
      status: bookedSlots === totalSlots ? 'full' : 
              bookedSlots > totalSlots * 0.7 ? 'full' :
              bookedSlots > totalSlots * 0.3 ? 'busy' : 'available'
    }
  })
  
  return data
}

// Обработчики событий
const handleDateSelected = (_dateString: string) => {
}

const handleMonthChanged = (_data: { month: number; year: number }) => {
}

const formatSelectedDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('ru-RU', {
    weekday: 'long',
    day: 'numeric',
    month: 'long'
  })
}

// Действия
const loadBookingData = async () => {
  isLoading.value = true
  
  // Симуляция загрузки данных
  await new Promise(resolve => setTimeout(resolve, 1000))
  
  availableDates.value = generateAvailableDates(60)
  bookingData.value = generateBookingData(availableDates.value)
  
  isLoading.value = false
}

const clearSelection = () => {
  selectedDate.value = null
  selectedDateCompact.value = null
  selectedDateCustom.value = null
  selectedDateProgrammatic.value = null
}

const goToToday = () => {
  const today = new Date().toISOString().split('T')[0]
  if (today && availableDates.value.includes(today)) {
    selectedDateProgrammatic.value = today
  }
}

const goToNextWeek = () => {
  const nextWeek = new Date()
  nextWeek.setDate(nextWeek.getDate() + 7)
  const dateString = nextWeek.toISOString().split('T')[0]
  
  if (dateString && availableDates.value.includes(dateString)) {
    selectedDateProgrammatic.value = dateString
  }
}

const selectRandomDate = () => {
  if (availableDates.value.length > 0) {
    const randomIndex = Math.floor(Math.random() * availableDates.value.length)
    const randomDate = availableDates.value[randomIndex]
    if (randomDate) selectedDateProgrammatic.value = randomDate
  }
}

// Инициализация
onMounted(() => {
  // Загружаем базовые данные
  loadBookingData()
  
  // Генерируем кастомные данные
  customAvailableDates.value = generateAvailableDates(45)
  customBookingData.value = generateBookingData(customAvailableDates.value)
})
</script>

<style scoped>
.example-section {
  @apply pb-8 border-b border-gray-200 last:border-b-0;
}

.btn {
  @apply px-4 py-2 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500;
}

.btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}
</style>
