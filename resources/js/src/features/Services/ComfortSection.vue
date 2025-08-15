<template>
  <div class="comfort-module">
    <div class="comfort-categories space-y-4">
      <ServiceCategory
        v-for="category in comfortCategories"
        :key="category.id"
        :category="category"
        v-model="servicesData[category.id]"
        @update:modelValue="updateCategory(category.id, $event)"
        :is-subcategory="false"
      />
    </div>
    
    <div class="services-stats mt-6 p-4 bg-gray-50 rounded-lg">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-700">
          Выбрано удобств: <strong>{{ totalSelectedComfort }}</strong>
        </span>
        <button
          v-if="totalSelectedComfort > 0"
          @click="clearAllComfort"
          type="button"
          class="px-3 py-1 text-sm text-red-600 hover:text-red-800 transition-colors"
        >
          Очистить все
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import ServiceCategory from './components/ServiceCategory.vue'
import servicesConfig from './config/services.json'

interface ServiceData {
  enabled: boolean
  price_comment: string
}

interface Service {
  id: string
  name: string
  popular?: boolean
  price_placeholder?: string
}

interface Category {
  id: string
  name: string
  icon: string
  description: string
  services: Service[]
  is_amenity?: boolean
}

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:modelValue'])

// Получаем только категории удобств (с флагом is_amenity: true)
const allCategories = servicesConfig.categories as Category[]
const comfortCategories = computed(() => {
  return allCategories.filter(cat => cat.is_amenity === true)
})

// Работаем напрямую с полным объектом services (не создаём локальную копию)
const servicesData = computed({
  get: () => props.modelValue || {},
  set: (value) => emit('update:modelValue', value)
})

// Инициализация данных удобств в основном объекте services
const initializeComfortData = () => {
  const updatedServices = { ...servicesData.value }
  
  comfortCategories.value.forEach(category => {
    if (!updatedServices[category.id]) {
      updatedServices[category.id] = {}
    }
    category.services.forEach(service => {
      if (!updatedServices[category.id][service.id]) {
        updatedServices[category.id][service.id] = {
          enabled: false,
          price_comment: ''
        }
      }
    })
  })
  
  servicesData.value = updatedServices
}

// Подсчет выбранных удобств
const totalSelectedComfort = computed(() => {
  let count = 0
  comfortCategories.value.forEach(category => {
    const categoryServices = servicesData.value[category.id]
    if (categoryServices) {
      Object.values(categoryServices).forEach(service => {
        if (service?.enabled) count++
      })
    }
  })
  return count
})

// Очистка всех удобств
const clearAllComfort = () => {
  const updatedServices = { ...servicesData.value }
  
  comfortCategories.value.forEach(category => {
    if (updatedServices[category.id]) {
      Object.keys(updatedServices[category.id]).forEach(serviceId => {
        updatedServices[category.id][serviceId] = {
          enabled: false,
          price_comment: ''
        }
      })
    }
  })
  
  servicesData.value = updatedServices
}

// Обработчик изменений категории
const updateCategory = (categoryId: string, categoryData: Record<string, ServiceData>) => {
  const updatedServices = { ...servicesData.value }
  updatedServices[categoryId] = { ...categoryData }
  servicesData.value = updatedServices
}

// Инициализация при монтировании компонента
initializeComfortData()
</script>

<style scoped>
.comfort-module {
  /* Стили для модуля комфорта */
}

.comfort-categories {
  /* Стили для категорий */
}

.services-stats {
  /* Стили для статистики */
}
</style>