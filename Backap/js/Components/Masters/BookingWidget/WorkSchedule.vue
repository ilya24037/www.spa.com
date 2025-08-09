<!-- resources/js/Components/Masters/BookingWidget/WorkSchedule.vue -->
<template>
  <div class="work-schedule">
    <!-- Заголовок -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900">График работы</h3>
      <button 
        @click="$emit('show-calendar')"
        class="text-indigo-600 text-sm hover:text-indigo-700"
      >
        Календарь
      </button>
    </div>
    
    <!-- Описание графика, если есть -->
    <div v-if="scheduleDescription" class="mb-4 text-sm text-gray-600">
      {{ scheduleDescription }}
    </div>
    
    <!-- График по дням -->
    <div class="space-y-2">
      <div 
        v-for="day in weekSchedule"
        :key="day.name"
        class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
        :class="{ 'font-medium': day.isToday }"
      >
        <span class="text-sm" :class="day.isToday ? 'text-indigo-600' : 'text-gray-700'">
          {{ day.name }}
          <span v-if="day.isToday" class="text-xs text-indigo-600 ml-1">(сегодня)</span>
        </span>
        <span class="text-sm" :class="day.isWorking ? 'text-gray-900' : 'text-gray-400'">
          {{ day.hours || 'Выходной' }}
        </span>
      </div>
    </div>
    
    <!-- Ближайшие свободные слоты -->
    <div v-if="nearestSlots.length > 0" class="mt-4 pt-4 border-t">
      <p class="text-sm font-medium text-gray-900 mb-2">Ближайшие слоты:</p>
      <div class="space-y-1">
        <button
          v-for="slot in nearestSlots"
          :key="slot.date + slot.time"
          @click="$emit('show-calendar')"
          class="w-full text-left px-3 py-2 text-sm bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors"
        >
          <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ slot.label }}
        </button>
      </div>
    </div>
    
    <!-- Информация о выезде -->
    <div v-if="master.provides_home_service" class="mt-4 pt-4 border-t">
      <div class="flex items-start gap-2">
        <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <div>
          <p class="text-sm font-medium text-gray-900">Выезд на дом</p>
          <p class="text-xs text-gray-600 mt-0.5">
            {{ master.home_service_info || 'Доступен выезд к клиенту' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  schedule: {
    type: Object,
    default: () => ({})
  },
  scheduleDescription: {
    type: String,
    default: ''
  },
  master: {
    type: Object,
    default: () => ({})
  },
  nearestAvailableSlots: {
    type: Array,
    default: () => []
  }
})

defineEmits(['show-calendar'])

// Дни недели
const weekDays = [
  { key: 'monday', name: 'Понедельник' },
  { key: 'tuesday', name: 'Вторник' },
  { key: 'wednesday', name: 'Среда' },
  { key: 'thursday', name: 'Четверг' },
  { key: 'friday', name: 'Пятница' },
  { key: 'saturday', name: 'Суббота' },
  { key: 'sunday', name: 'Воскресенье' }
]

// Форматированный график
const weekSchedule = computed(() => {
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
        time: `${(currentHour + 2).toString().padStart(2, '0')}:00`,
        label: `Сегодня ${(currentHour + 2).toString().padStart(2, '0')}:00`
      })
    }
  
    // Завтра
    const tomorrow = new Date(now)
    tomorrow.setDate(now.getDate() + 1)
    slots.push({
      date: tomorrow.toISOString().split('T')[0],
      time: '12:00',
      label: `Завтра 12:00`
    })
  
    // Послезавтра
    const dayAfterTomorrow = new Date(now)
    dayAfterTomorrow.setDate(now.getDate() + 2)
    slots.push({
      date: dayAfterTomorrow.toISOString().split('T')[0],
      time: '15:00',
      label: `${dayAfterTomorrow.getDate()}.${(dayAfterTomorrow.getMonth() + 1).toString().padStart(2, '0')} 15:00`
    })
  
    return slots
  }) 
  </script>