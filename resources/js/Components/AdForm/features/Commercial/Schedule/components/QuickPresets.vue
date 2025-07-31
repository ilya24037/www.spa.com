<template>
  <div class="space-y-4">
    <p class="text-sm font-medium text-gray-700">Быстрые настройки:</p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <button
        v-for="preset in schedulePresets"
        :key="preset.name"
        type="button"
        @click="applyPreset(preset)"
        class="p-3 bg-white border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 text-left group"
      >
        <div class="text-sm font-medium text-gray-900 group-hover:text-blue-700 mb-1">
          {{ preset.name }}
        </div>
        <div class="text-xs text-gray-500 group-hover:text-blue-600">
          {{ preset.description }}
        </div>
      </button>
    </div>
    
    <!-- Подсказка -->
    <Card variant="bordered" class="bg-blue-50 border-blue-200 p-3">
      <div class="flex items-center space-x-2">
        <span class="text-blue-600">⚡</span>
        <span class="text-sm text-blue-800">
          Клик на кнопку автоматически установит соответствующий график работы
        </span>
      </div>
    </Card>
  </div>
</template>

<script setup>
import Card from '@/Components/UI/Cards/Card.vue'

const emit = defineEmits(['apply-preset'])

// Готовые расписания
const schedulePresets = [
  {
    name: '9:00-18:00 (пн-пт)',
    description: 'Стандартный офисный график',
    schedule: {
      monday: { start: '09:00', end: '18:00' },
      tuesday: { start: '09:00', end: '18:00' },
      wednesday: { start: '09:00', end: '18:00' },
      thursday: { start: '09:00', end: '18:00' },
      friday: { start: '09:00', end: '18:00' }
    }
  },
  {
    name: '10:00-20:00 (ежедневно)',
    description: 'Работа без выходных',
    schedule: {
      monday: { start: '10:00', end: '20:00' },
      tuesday: { start: '10:00', end: '20:00' },
      wednesday: { start: '10:00', end: '20:00' },
      thursday: { start: '10:00', end: '20:00' },
      friday: { start: '10:00', end: '20:00' },
      saturday: { start: '10:00', end: '20:00' },
      sunday: { start: '10:00', end: '20:00' }
    }
  },
  {
    name: '12:00-22:00 (пн-сб)',
    description: 'Вечерний график',
    schedule: {
      monday: { start: '12:00', end: '22:00' },
      tuesday: { start: '12:00', end: '22:00' },
      wednesday: { start: '12:00', end: '22:00' },
      thursday: { start: '12:00', end: '22:00' },
      friday: { start: '12:00', end: '22:00' },
      saturday: { start: '12:00', end: '22:00' }
    }
  }
]

// Методы
const applyPreset = (preset) => {
  emit('apply-preset', preset.schedule)
}
</script>