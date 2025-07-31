<template>
  <Card v-if="hasSelectedLocations" variant="elevated" class="bg-blue-50 border-blue-200">
    <div class="flex items-center space-x-2 mb-4">
      <span class="text-lg">📍</span>
      <span class="text-sm font-medium text-blue-800">
        Ваши варианты работы:
      </span>
    </div>
    
    <!-- Выбранные типы локаций -->
    <div class="space-y-2 mb-4">
      <div
        v-for="location in selectedLocationDetails"
        :key="location.value"
        class="flex items-center space-x-2"
      >
        <span class="text-sm">{{ location.icon }}</span>
        <span class="text-sm text-blue-900">{{ location.label }}</span>
      </div>
    </div>
    
    <!-- Районы выезда -->
    <div v-if="hasOutcallService && districts.length > 0" class="pt-3 border-t border-blue-200 mb-4">
      <p class="text-sm font-medium text-blue-700 mb-2">Районы выезда:</p>
      <div class="flex flex-wrap gap-1">
        <span
          v-for="district in districts"
          :key="district"
          class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"
        >
          {{ district }}
        </span>
      </div>
    </div>
    
    <!-- Опция такси -->
    <div v-if="taxiOption" class="pt-3 border-t border-blue-200">
      <div class="flex items-center space-x-2">
        <span class="text-sm">🚗</span>
        <span class="text-sm text-blue-900">Встречаю на такси</span>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  serviceTypes: { type: Array, default: () => [] },
  districts: { type: Array, default: () => [] },
  taxiOption: { type: Boolean, default: false }
})

// Варианты местоположения
const locationOptions = [
  { value: 'incall', label: 'У себя', icon: '🏠' },
  { value: 'outcall', label: 'Выезд к клиенту', icon: '🚗' },
  { value: 'salon', label: 'В салоне', icon: '🏢' },
  { value: 'hotel', label: 'В отеле', icon: '🏨' }
]

// Computed
const hasSelectedLocations = computed(() => {
  return props.serviceTypes.length > 0
})

const hasOutcallService = computed(() => {
  return props.serviceTypes.includes('outcall')
})

const selectedLocationDetails = computed(() => {
  return locationOptions.filter(option => 
    props.serviceTypes.includes(option.value)
  )
})
</script>