<!-- resources/js/Components/Masters/BookingWidget/WorkSchedule.vue -->
<template>
  <div class="work-schedule border-t pt-6">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-gray-900">График работы</h3>
      <button 
        @click="showDetails = !showDetails"
        class="text-sm text-indigo-600 hover:text-indigo-700"
      >
        {{ showDetails ? 'Скрыть' : 'Подробнее' }}
      </button>
    </div>
    
    <!-- Краткое описание -->
    <div v-if="!showDetails && scheduleDescription" class="text-sm text-gray-600">
      {{ scheduleDescription }}
    </div>
    
    <!-- Подробное расписание -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-if="showDetails" class="space-y-2 text-sm overflow-hidden">
        <div 
          v-for="day in formattedSchedule"
          :key="day.name"
          class="flex justify-between py-1.5"
          :class="{
            'text-gray-900 font-medium': day.isToday,
            'text-gray-600': !day.isToday && day.isWorking,
            'text-gray-400': !day.isWorking
          }"
        >
          <span class="flex items-center gap-2">
            <span 
              v-if="day.isToday"
              class="w-2 h-2 bg-indigo-600 rounded-full"
            />
            {{ day.name }}
          </span>
          <span>
            {{ day.isWorking ? day.hours : 'Выходной' }}
          </span>
        </div>
        
        <!-- Ближайшие свободные окна -->
        <div v-if="nearestSlots.length" class="mt-4 pt-4 border-t">
          <p class="text-xs font-medium text-gray-700 mb-2">Ближайшее время:</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="slot in nearestSlots"
              :key="slot.datetime"
              @click="$emit('show-calendar', slot.date)"
              class="px-3 py-1.5 bg-green-50 text-green-700 rounded-md text-xs font-medium hover:bg-green-100 transition-colors"
            >
              {{ slot.label }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
    
    <!-- Кнопка календаря -->
    <button 
      @click="$emit('show-calendar')"
      class="mt-4 w-full text-center text-sm text-indigo-600 hover:text-indigo-700 font-medium"
    >
      Посмотреть календарь записи →
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  schedule: {
    type: Object,
    default: () => ({})
  },
  scheduleDescription: {
    type: String,
    default: null
  },
  nearestAvailableSlots: {
    type: Array,
    default: () => []
  }
})

defineEmits(['show-calendar'])

// Состояние
const showDetails = ref(false)

// Дни недели
const weekDays = [
  { key: 'monday', name: 'Понедельник', short: 'Пн' },
  { key: 'tuesday', name: 'Вторник', short: 'Вт' },
  { key: 'wednesday', name: 'Среда', short: 'Ср' },
  { key: 'thursday', name: 'Четверг', short: 'Чт' },
  { key: 'friday', name: 'Пятница', short: 'Пт' },
  { key: 'saturday', name: 'Суббота', short: 'Сб' },
  { key: 'sunday', name: 'Воскресенье', short: 'Вс' }
]

// Форматированное расписание
const formattedSchedule = computed(() => {
  const today = new Date().getDay()
  const todayIndex = today === 0 ? 6 : today - 1 // Конвертируем в понедельник = 0
  
  return weekDays.map((day, index) => {
    const daySchedule = props.schedule[day.key] || defaultSchedule[day.key]
    const isWorking = daySchedule && daySchedule.start && daySchedule.end
    
    return {
      name: day.name,
      isToday: index === todayIndex,
      isWorking,
      hours: isWorking ? `${daySchedule.start} - ${daySchedule.end}` : null
    }
  })
})

// Расписание по умолчанию
const defaultSchedule = {
  monday: { start: '10:00', end: '21:00' },
  tuesday: { start: '10:00', end: '21:00' },
  wednesday: { start: '10:00', end: '21:00' },
  thursday: { start: '10:00', end: '21:00' },
  friday: { start: '10:00', end: '21:00' },
  saturday: { start: '10:00', end: '20:00' },
  sunday: { start: '10:00', end: '20:00' }
}

// Ближайшие слоты
const nearestSlots = computed(() => {
  // Если есть данные о ближайших слотах
  if (props.nearestAvailableSlots.length) {
    return props.nearestAvailableSlots.slice(0, 3).map(slot => {
      const date = new Date(slot.date)
      const today = new Date()
      const tomorrow = new Date(today)
      tomorrow.setDate(tomorrow.getDate() + 1)
      
      let label = ''
      if (date.toDateString() === today.toDateString()) {
        label = `Сегодня ${slot.time}`
      } else if (date.toDateString() === tomorrow.toDateString()) {
        label = `Завтра ${slot.time}`
      } else {
        label = `${date.getDate()}.${(date.getMonth() + 1).toString().padStart(2, '0')} ${slot.time}`
      }
      
      return {
        ...slot,
        label
      }
    })
  }
  
  // Иначе генерируем примерные слоты
  const slots = []
  const now = new Date()
  const currentHour = now.getHours()
  
  // Сегодня
  if (currentHour < 19) {
    slots.push({
      date: now.toISOString().split('T')[0],
      time: `${currentHour + 2}:00`,
      label: `Сегодня ${currentHour + 2}:00`
    })
  }
  
  // Завтра
  const tomorrow = new Date(now)
  tomorrow.setDate(tomorrow.getDate() + 1)
  slots.push({
    date: tomorrow.toISOString().split('T')[0],
    time: '10:00',
    label: 'Завтра 10:00'
  })
  
  return slots.slice(0, 2)
})
</script>