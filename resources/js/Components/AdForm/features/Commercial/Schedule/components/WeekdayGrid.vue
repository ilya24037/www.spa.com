<template>
  <Card variant="bordered" class="p-4 bg-slate-50">
    <!-- Заголовки дней -->
    <div class="grid grid-cols-7 gap-2 mb-4">
      <div
        v-for="day in daysOfWeek"
        :key="day.key"
        class="text-center"
      >
        <div class="text-xs font-medium text-gray-600 hidden sm:block">{{ day.name }}</div>
        <div class="text-sm font-semibold text-gray-900">{{ day.short }}</div>
      </div>
    </div>

    <!-- Переключатели работы -->
    <div class="grid grid-cols-7 gap-2 mb-4">
      <div
        v-for="day in daysOfWeek"
        :key="day.key"
        class="flex flex-col items-center"
      >
        <BaseCheckbox
          :model-value="isDayEnabled(day.key)"
          @update:model-value="(value) => toggleDay(day.key, value)"
          :label="''"
          class="mb-1"
        />
        <span class="text-xs text-gray-500">Работаю</span>
      </div>
    </div>

    <!-- Время работы -->
    <div class="grid grid-cols-7 gap-2">
      <div
        v-for="day in daysOfWeek"
        :key="day.key"
        class="flex flex-col items-center space-y-2"
      >
        <template v-if="isDayEnabled(day.key)">
          <BaseSelect
            :model-value="getDaySchedule(day.key).start"
            @update:model-value="(value) => updateDayTime(day.key, 'start', value)"
            :options="timeSelectOptions"
            placeholder="Начало"
            class="w-full text-xs"
          />
          
          <span class="text-gray-500">—</span>
          
          <BaseSelect
            :model-value="getDaySchedule(day.key).end"
            @update:model-value="(value) => updateDayTime(day.key, 'end', value)"
            :options="timeSelectOptions"
            placeholder="Конец"
            class="w-full text-xs"
          />
        </template>
        
        <div v-else class="flex items-center justify-center h-16 text-xs text-gray-400">
          Выходной
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import BaseCheckbox from '@/Components/UI/BaseCheckbox.vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  schedule: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:schedule'])

// Дни недели
const daysOfWeek = [
  { key: 'monday', name: 'Понедельник', short: 'Пн' },
  { key: 'tuesday', name: 'Вторник', short: 'Вт' },
  { key: 'wednesday', name: 'Среда', short: 'Ср' },
  { key: 'thursday', name: 'Четверг', short: 'Чт' },
  { key: 'friday', name: 'Пятница', short: 'Пт' },
  { key: 'saturday', name: 'Суббота', short: 'Сб' },
  { key: 'sunday', name: 'Воскресенье', short: 'Вс' }
]

// Варианты времени для BaseSelect
const timeSelectOptions = [
  '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
  '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
  '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30',
  '20:00', '20:30', '21:00', '21:30', '22:00', '22:30', '23:00', '23:30'
].map(time => ({ value: time, label: time }))

// Методы
const isDayEnabled = (day) => {
  return props.schedule[day] && 
         props.schedule[day].start && 
         props.schedule[day].end
}

const getDaySchedule = (day) => {
  return props.schedule[day] || { start: '', end: '' }
}

const toggleDay = (day, enabled) => {
  const newSchedule = { ...props.schedule }
  
  if (enabled) {
    newSchedule[day] = { start: '10:00', end: '18:00' }
  } else {
    delete newSchedule[day]
  }
  
  emit('update:schedule', newSchedule)
}

const updateDayTime = (day, timeType, value) => {
  const newSchedule = { ...props.schedule }
  
  if (!newSchedule[day]) {
    newSchedule[day] = {}
  }
  
  newSchedule[day][timeType] = value
  emit('update:schedule', newSchedule)
}
</script>