<template>
  <Card v-if="hasWorkingDays" variant="elevated" class="bg-green-50 border-green-200">
    <div class="flex items-center space-x-2 mb-4">
      <span class="text-lg">📅</span>
      <span class="text-sm font-medium text-green-800">
        Ваш график работы:
      </span>
    </div>
    
    <div class="space-y-2">
      <div
        v-for="day in daysOfWeek"
        :key="day.key"
        class="flex justify-between items-center"
      >
        <span class="text-sm font-medium text-green-700">{{ day.name }}:</span>
        <span
          v-if="isDayEnabled(day.key)"
          class="text-sm text-green-900 font-mono"
        >
          {{ formatDaySchedule(day.key) }}
        </span>
        <span v-else class="text-sm text-gray-500 italic">выходной</span>
      </div>
    </div>
    
    <!-- Дополнительные заметки -->
    <div v-if="notes" class="mt-4 pt-4 border-t border-green-200">
      <p class="text-sm font-medium text-green-700 mb-2">Дополнительно:</p>
      <p class="text-sm text-green-800">{{ notes }}</p>
    </div>
    
    <!-- Статистика -->
    <div class="mt-4 pt-4 border-t border-green-200">
      <div class="grid grid-cols-2 gap-4 text-center">
        <div>
          <div class="text-lg font-semibold text-green-800">{{ workingDaysCount }}</div>
          <div class="text-xs text-green-600">рабочих дней</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-green-800">{{ totalHours }}ч</div>
          <div class="text-xs text-green-600">в неделю</div>
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  schedule: { type: Object, default: () => ({}) },
  notes: { type: String, default: '' }
})

// Дни недели
const daysOfWeek = [
  { key: 'monday', name: 'Понедельник' },
  { key: 'tuesday', name: 'Вторник' },
  { key: 'wednesday', name: 'Среда' },
  { key: 'thursday', name: 'Четверг' },
  { key: 'friday', name: 'Пятница' },
  { key: 'saturday', name: 'Суббота' },
  { key: 'sunday', name: 'Воскресенье' }
]

// Computed
const hasWorkingDays = computed(() => {
  return daysOfWeek.some(day => isDayEnabled(day.key))
})

const workingDaysCount = computed(() => {
  return daysOfWeek.filter(day => isDayEnabled(day.key)).length
})

const totalHours = computed(() => {
  let total = 0
  daysOfWeek.forEach(day => {
    if (isDayEnabled(day.key)) {
      const schedule = props.schedule[day.key]
      const start = parseTime(schedule.start)
      const end = parseTime(schedule.end)
      if (start && end) {
        total += end - start
      }
    }
  })
  return total
})

// Методы
const isDayEnabled = (day) => {
  return props.schedule[day] && 
         props.schedule[day].start && 
         props.schedule[day].end
}

const formatDaySchedule = (day) => {
  const schedule = props.schedule[day]
  if (schedule && schedule.start && schedule.end) {
    return `${schedule.start} — ${schedule.end}`
  }
  return ''
}

const parseTime = (timeStr) => {
  if (!timeStr) return 0
  const [hours, minutes] = timeStr.split(':').map(Number)
  return hours + minutes / 60
}
</script>