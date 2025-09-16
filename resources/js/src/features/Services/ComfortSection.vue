<template>
  <div class="comfort-module">
    <!-- Комфорт как свернутая категория (как Дополнительные услуги) -->
    <div v-if="comfortCategories.length > 0" class="service-category">
      <div class="border border-gray-200 rounded-lg px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors duration-200 mb-3 cursor-pointer select-none" @click="toggleComfortServices">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-semibold text-gray-900">
            Комфорт
            <span v-if="totalSelectedComfort > 0" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full">
              {{ totalSelectedComfort }}
            </span>
          </h3>
          <svg 
            class="w-5 h-5 text-gray-500 transition-transform duration-200" 
            :class="{ 'rotate-180': isComfortExpanded }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>
      
      <div v-show="isComfortExpanded" class="mt-4 space-y-4 pl-6">
        <ServiceCategory
          v-for="category in comfortCategories"
          :key="category.id"
          :category="category"
          v-model="servicesData[category.id]"
          @update:modelValue="updateCategory(category.id, $event)"
          :is-subcategory="true"
        />
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
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

// Состояние раскрытия секции Комфорт (свернуто по умолчанию)
const isComfortExpanded = ref(false)

// Функция переключения секции Комфорт
const toggleComfortServices = () => {
  isComfortExpanded.value = !isComfortExpanded.value
}

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