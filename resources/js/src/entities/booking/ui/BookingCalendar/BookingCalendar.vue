<template>
  <div class="booking-calendar">
    <!-- Заголовок -->
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">
        Выберите дату и время
      </h3>
      <p class="text-sm text-gray-600">
        Доступное время для записи к мастеру
      </p>
    </div>

    <!-- Календарь выбора даты -->
    <div class="mb-6">
      <h4 class="text-md font-medium text-gray-800 mb-3">Дата записи</h4>
      <div class="grid grid-cols-7 gap-1 mb-4">
        <!-- Заголовки дней недели -->
        <div 
          v-for="day in weekDays" 
          :key="day"
          class="p-2 text-center text-xs font-medium text-gray-500"
        >
          {{ day }}
        </div>
        
        <!-- Даты -->
        <button
          v-for="date in calendarDates" 
          :key="date.key"
          @click="selectDate(date)"
          :disabled="!date.available || date.isPast"
          :class="[
            'p-2 text-sm rounded-lg transition-colors',
            date.isToday && 'font-semibold',
            date.available && !date.isPast ? 'hover:bg-blue-50 cursor-pointer' : 'cursor-not-allowed',
            selectedDate?.isSame(date.date, 'day') 
              ? 'bg-blue-600 text-white' 
              : date.available && !date.isPast 
                ? 'text-gray-900' 
                : 'text-gray-300',
            !date.available && !date.isPast && 'bg-red-50 text-red-400'
          ]"
        >
          {{ date.date.format('D') }}
        </button>
      </div>
    </div>

    <!-- Выбор времени -->
    <div v-if="selectedDate" class="mb-6">
      <h4 class="text-md font-medium text-gray-800 mb-3">
        Время записи на {{ selectedDate.format('D MMMM') }}
      </h4>
      
      <div v-if="loadingTimeSlots" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
      </div>
      
      <div v-else-if="availableTimeSlots.length === 0" class="text-center py-8">
        <p class="text-gray-500">На выбранную дату нет свободного времени</p>
        <p class="text-sm text-gray-400 mt-1">Попробуйте выбрать другую дату</p>
      </div>
      
      <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
        <button
          v-for="timeSlot in availableTimeSlots"
          :key="timeSlot.time"
          @click="selectTime(timeSlot)"
          :disabled="!timeSlot.available"
          :class="[
            'p-2 text-sm rounded-lg border transition-colors',
            timeSlot.available ? 'hover:bg-blue-50 cursor-pointer' : 'cursor-not-allowed',
            selectedTime === timeSlot.time
              ? 'bg-blue-600 text-white border-blue-600'
              : timeSlot.available
                ? 'bg-white text-gray-900 border-gray-200'
                : 'bg-gray-50 text-gray-400 border-gray-100'
          ]"
        >
          {{ timeSlot.time }}
        </button>
      </div>
    </div>

    <!-- Выбранное время -->
    <div v-if="selectedDate && selectedTime" class="bg-blue-50 rounded-lg p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-blue-900">
            Выбрано время записи
          </p>
          <p class="text-sm text-blue-700">
            {{ selectedDate.format('DD MMMM YYYY') }} в {{ selectedTime }}
          </p>
        </div>
      </div>
    </div>

    <!-- Ошибки -->
    <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'
import updateLocale from 'dayjs/plugin/updateLocale'

// Настройка dayjs
dayjs.extend(updateLocale)
dayjs.locale('ru')

// Props
const props = defineProps({
  masterId: {
    type: [String, Number],
    required: true
  },
  selectedService: {
    type: Object,
    default: null
  },
  minDate: {
    type: String,
    default: () => dayjs().format('YYYY-MM-DD')
  },
  maxDate: {
    type: String,
    default: () => dayjs().add(30, 'days').format('YYYY-MM-DD')
  }
})

// Events
const emit = defineEmits(['update:selectedDate', 'update:selectedTime', 'selection-change'])

// Состояние компонента
const selectedDate = ref(null)
const selectedTime = ref(null)
const availableTimeSlots = ref([])
const loadingTimeSlots = ref(false)
const error = ref(null)

// Константы
const weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

// Вычисляемые свойства
const calendarDates = computed(() => {
  const startDate = dayjs(props.minDate)
  const endDate = dayjs(props.maxDate)
  const dates = []
  
  // Находим первый понедельник для отображения полной недели
  const firstDay = startDate.startOf('month').startOf('week').add(1, 'day')
  
  // Генерируем даты для календаря
  let currentDate = firstDay
  const today = dayjs()
  
  for (let i = 0; i < 42; i++) { // 6 недель максимум
    if (currentDate.isAfter(endDate)) break
    
    dates.push({
      date: currentDate,
      key: currentDate.format('YYYY-MM-DD'),
      available: currentDate.isBetween(startDate, endDate, 'day', '[]'),
      isToday: currentDate.isSame(today, 'day'),
      isPast: currentDate.isBefore(today, 'day')
    })
    
    currentDate = currentDate.add(1, 'day')
  }
  
  return dates
})

// Методы
const selectDate = async (dateObj) => {
  if (!dateObj.available || dateObj.isPast) return
  
  selectedDate.value = dateObj.date
  selectedTime.value = null
  error.value = null
  
  emit('update:selectedDate', dateObj.date.format('YYYY-MM-DD'))
  emit('update:selectedTime', null)
  
  await loadTimeSlots(dateObj.date)
}

const selectTime = (timeSlot) => {
  if (!timeSlot.available) return
  
  selectedTime.value = timeSlot.time
  emit('update:selectedTime', timeSlot.time)
  
  // Отправляем полную информацию о выборе
  emit('selection-change', {
    date: selectedDate.value.format('YYYY-MM-DD'),
    time: timeSlot.time,
    datetime: selectedDate.value.format('YYYY-MM-DD') + ' ' + timeSlot.time,
    service: props.selectedService
  })
}

const loadTimeSlots = async (date) => {
  loadingTimeSlots.value = true
  error.value = null
  
  try {
    // Имитация API вызова - замените на реальный API
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Генерируем временные слоты (9:00 - 21:00 с интервалом в час)
    const slots = []
    const startHour = 9
    const endHour = 21
    
    for (let hour = startHour; hour < endHour; hour++) {
      const timeString = `${hour.toString().padStart(2, '0')}:00`
      
      // Случайно делаем некоторые слоты недоступными для демонстрации
      const available = Math.random() > 0.3
      
      slots.push({
        time: timeString,
        available: available,
        duration: props.selectedService?.duration || 60,
        price: props.selectedService?.price || 0
      })
    }
    
    availableTimeSlots.value = slots
    
  } catch (err) {
    error.value = 'Ошибка загрузки доступного времени. Попробуйте еще раз.'
  } finally {
    loadingTimeSlots.value = false
  }
}

// Наблюдатели
watch(() => props.masterId, () => {
  // Сброс при смене мастера
  selectedDate.value = null
  selectedTime.value = null
  availableTimeSlots.value = []
})

watch(() => props.selectedService, () => {
  // Перезагрузка слотов при смене услуги
  if (selectedDate.value) {
    loadTimeSlots(selectedDate.value)
  }
})

// Инициализация
onMounted(() => {
  // Можно автоматически выбрать ближайшую доступную дату
  const tomorrow = dayjs().add(1, 'day')
  if (tomorrow.isBetween(dayjs(props.minDate), dayjs(props.maxDate), 'day', '[]')) {
    selectDate({
      date: tomorrow,
      available: true,
      isPast: false
    })
  }
})
</script>

<style scoped>
.booking-calendar {
  @apply max-w-full;
}

/* Анимация для выбранных элементов */
.booking-calendar button {
  transition: all 0.2s ease-in-out;
}

.booking-calendar button:hover:not(:disabled) {
  transform: translateY(-1px);
}

/* Стилизация загрузки */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>