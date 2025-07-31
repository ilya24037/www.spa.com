<template>
  <FormField
    label="Местоположение на карте"
    hint="Проверьте правильность адреса на карте"
  >
    <Card variant="bordered" class="p-4">
      <!-- Заглушка карты -->
      <div class="flex items-center justify-center h-48 bg-slate-50 border-2 border-dashed border-slate-300 rounded-lg mb-4">
        <div class="text-center">
          <div class="text-4xl mb-2">🗺️</div>
          <p class="font-semibold text-slate-600 mb-1">Карта будет загружена</p>
          <p class="text-sm text-slate-500">После ввода адреса здесь появится карта с вашим местоположением</p>
        </div>
      </div>
      
      <!-- Координаты (если есть) -->
      <div v-if="hasCoordinates" class="flex items-center justify-between p-3 bg-slate-100 rounded-lg">
        <div class="flex items-center space-x-2">
          <span class="text-sm font-medium text-slate-700">Координаты:</span>
          <span class="text-sm text-slate-900 font-mono">{{ coordinates.lat }}, {{ coordinates.lng }}</span>
        </div>
        <button
          @click="copyCoordinates"
          type="button" 
          class="p-2 text-slate-600 hover:text-slate-800 hover:bg-slate-200 rounded transition-colors"
          title="Копировать координаты"
        >
          📋
        </button>
      </div>
      
      <!-- Информация о точности -->
      <div v-if="accuracy" class="mt-3 text-xs text-slate-500">
        Точность: ±{{ accuracy }}м
      </div>
    </Card>
  </FormField>
</template>

<script setup>
import { computed } from 'vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  coordinates: { 
    type: Object, 
    default: () => ({ lat: null, lng: null }) 
  },
  accuracy: { type: Number, default: null }
})

// Computed
const hasCoordinates = computed(() => {
  return props.coordinates.lat && props.coordinates.lng
})

// Методы
const copyCoordinates = async () => {
  const coords = `${props.coordinates.lat}, ${props.coordinates.lng}`
  try {
    await navigator.clipboard.writeText(coords)
    // Здесь можно добавить toast уведомление
    console.log('Координаты скопированы:', coords)
  } catch (err) {
    console.error('Ошибка копирования:', err)
  }
}
</script>