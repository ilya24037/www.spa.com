<template>
  <Card v-if="hasLocation" variant="elevated" class="bg-blue-50 border-blue-200">
    <div class="flex items-center space-x-2 mb-4">
      <span class="text-lg">ℹ️</span>
      <span class="text-sm font-medium text-blue-800">
        Детали локации
      </span>
    </div>
    
    <div class="space-y-3">
      <!-- Адрес -->
      <div class="flex items-start justify-between">
        <span class="text-sm font-medium text-blue-700 min-w-24">Адрес:</span>
        <span class="text-sm text-blue-900 text-right flex-1">{{ location.address }}</span>
      </div>
      
      <!-- Район -->
      <div v-if="location.district" class="flex items-start justify-between">
        <span class="text-sm font-medium text-blue-700 min-w-24">Район:</span>
        <span class="text-sm text-blue-900 text-right flex-1">{{ location.district }}</span>
      </div>
      
      <!-- Метро -->
      <div v-if="location.metro" class="flex items-start justify-between">
        <span class="text-sm font-medium text-blue-700 min-w-24">Метро:</span>
        <span class="text-sm text-blue-900 text-right flex-1">{{ location.metro }}</span>
      </div>
      
      <!-- Координаты -->
      <div v-if="hasCoordinates" class="flex items-start justify-between">
        <span class="text-sm font-medium text-blue-700 min-w-24">Координаты:</span>
        <span class="text-sm text-blue-900 text-right flex-1 font-mono">
          {{ location.lat?.toFixed(4) }}, {{ location.lng?.toFixed(4) }}
        </span>
      </div>
    </div>
    
    <!-- Настройки приватности -->
    <div class="mt-4 pt-4 border-t border-blue-200">
      <p class="text-xs text-blue-600 mb-3">Как будет показано в объявлении:</p>
      <div class="p-2 bg-blue-100 rounded text-sm text-blue-800">
        {{ previewText }}
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  location: { 
    type: Object, 
    default: () => ({}) 
  },
  privacyLevel: { 
    type: String, 
    default: 'district' 
  }
})

// Computed
const hasLocation = computed(() => {
  return props.location.address
})

const hasCoordinates = computed(() => {
  return props.location.lat && props.location.lng
})

const previewText = computed(() => {
  const { address, district, metro, privacy } = props.location
  
  switch (props.privacyLevel) {
    case 'exact':
      return address || 'Точный адрес'
    case 'district':
      return district ? `${district}${metro ? `, ${metro}` : ''}` : 'Район не указан'
    case 'metro':
      return metro || 'Метро не указано'
    default:
      return address || 'Адрес не указан'
  }
})
</script>