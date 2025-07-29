<template>
  <div class="services-module">
    <div class="module-header mb-6">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Услуги <span class="text-red-500">*</span></h2>
      
      <div class="field-hint text-gray-600 text-sm">
        Выберите услуги, которые вы предоставляете. Укажите цены и дополнительную информацию для каждой услуги.
      </div>
    </div>

    <div class="services-categories">
      <!-- УДАЛЕНО: глобальные заголовки полей -->
      
      <ServiceCategory
        v-for="(category, index) in filteredCategories"
        :key="category.id"
        :category="category"
        :is-first="index === 0"
        v-model="localServices[category.id]"
        @update:modelValue="updateCategory(category.id, $event)"
      />
    </div>
    <div class="additional-info mt-8">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Дополнительная информация об услугах
      </label>
      <textarea 
        v-model="localAdditionalInfo"
        @input="emitAll"
        rows="3"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Укажите дополнительную информацию об ваших услугах, особые условия, скидки и т.д."
      ></textarea>
      <div class="text-xs text-gray-500 mt-1">
        Эта информация будет видна клиентам в вашей анкете
      </div>
    </div>
    <div class="services-stats mt-6 p-4 bg-gray-50 rounded-lg">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-700">
          Выбрано услуг: <strong>{{ totalSelectedServices }}</strong>
        </span>
        <button
          v-if="totalSelectedServices > 0"
          @click="clearAllServices"
          type="button"
          class="px-3 py-1 text-sm text-red-600 hover:text-red-800 transition-colors"
        >
          Очистить все
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, reactive } from 'vue'
import ServiceCategory from './components/ServiceCategory.vue'
import servicesConfig from './config/services.json'

const props = defineProps({
  services: { type: Object, default: () => ({}) },
  servicesAdditionalInfo: { type: String, default: '' },
  allowedCategories: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:services', 'update:servicesAdditionalInfo'])

const allCategories = servicesConfig.categories
const filteredCategories = computed(() => {
  if (props.allowedCategories.length === 0) {
    return allCategories
  }
  return allCategories.filter(category => 
    props.allowedCategories.includes(category.id)
  )
})

// Локальное хранилище данных услуг - убираем дубли
const localServices = reactive({})
const localAdditionalInfo = ref(props.servicesAdditionalInfo || '')

// Флаг для предотвращения повторной инициализации
let isInitialized = false

// Функция очистки дублированных данных
const cleanupServices = (services) => {
  const cleaned = {}
  
  Object.keys(services).forEach(categoryId => {
    if (!cleaned[categoryId]) cleaned[categoryId] = {}
    
    const categoryServices = services[categoryId]
    if (categoryServices && typeof categoryServices === 'object') {
      Object.keys(categoryServices).forEach(serviceId => {
        // Берем только первое вхождение каждой услуги
        if (!cleaned[categoryId][serviceId] && categoryServices[serviceId]) {
          cleaned[categoryId][serviceId] = {
            enabled: categoryServices[serviceId].enabled || false,
            price: String(categoryServices[serviceId].price || ''),
            price_comment: String(categoryServices[serviceId].price_comment || '')
          }
        }
      })
    }
  })
  
  return cleaned
}

// Инициализация данных услуг для всех категорий
const initializeServicesData = () => {
  if (isInitialized) return
  
  let hasChanges = false
  
  filteredCategories.value.forEach(category => {
    if (!localServices[category.id]) {
      localServices[category.id] = {}
      hasChanges = true
    }
    category.services.forEach(service => {
      if (!localServices[category.id][service.id]) {
        localServices[category.id][service.id] = {
          enabled: false,
          price: '',
          price_comment: ''
        }
        hasChanges = true
      }
    })
  })
  
  if (hasChanges) {
    // console.log('Services data initialized for', filteredCategories.value.length, 'categories')
  }
  
  isInitialized = true
}

// Инициализация из props если переданы (без дублирования)
if (props.services && typeof props.services === 'object' && Object.keys(props.services).length > 0) {
  const cleanedServices = cleanupServices(props.services)
  Object.keys(cleanedServices).forEach(categoryId => {
    localServices[categoryId] = { ...cleanedServices[categoryId] }
  })
  isInitialized = true
} else {
  // Инициализируем пустыми данными только если props не переданы
  initializeServicesData()
}

// Оптимизированный computed с мемоизацией
const totalSelectedServices = computed(() => {
  let count = 0
  for (const categoryServices of Object.values(localServices)) {
    for (const service of Object.values(categoryServices)) {
      if (service?.enabled) count++
    }
  }
  return count
})

const clearAllServices = () => {
  Object.keys(localServices).forEach(categoryId => {
    Object.keys(localServices[categoryId]).forEach(serviceId => {
      localServices[categoryId][serviceId] = {
        enabled: false,
        price: '',
        price_comment: ''
      }
    })
  })
  emitAll()
}

// Обработчик изменений категории
const updateCategory = (categoryId, categoryData) => {
  // Проверяем что данные действительно изменились
  const currentCategoryData = JSON.stringify(localServices[categoryId] || {})
  const newCategoryData = JSON.stringify(categoryData)
  
  if (currentCategoryData !== newCategoryData) {
    localServices[categoryId] = { ...categoryData }
    emitAll()
  }
}

watch(() => props.services, (val) => {
  if (val && typeof val === 'object' && Object.keys(val).length > 0) {
    // Предотвращаем циклические обновления
    const currentLocal = JSON.stringify(localServices)
    const cleanedVal = cleanupServices(val)
    const incomingData = JSON.stringify(cleanedVal)
    
    if (currentLocal !== incomingData) {
      // Очищаем текущие данные и заменяем очищенными
      Object.keys(localServices).forEach(key => delete localServices[key])
      Object.keys(cleanedVal).forEach(categoryId => {
        localServices[categoryId] = { ...cleanedVal[categoryId] }
      })
    }
  }
}, { immediate: false })

watch(() => props.servicesAdditionalInfo, (val) => {
  localAdditionalInfo.value = val || ''
})

// watch(localServices) убран - вызывает циклические обновления
// emitAll() теперь вызывается только при пользовательских действиях

// Предотвращение циклических обновлений
let lastEmittedServices = null
let emitTimeout = null

const emitAll = () => {
  if (emitTimeout) clearTimeout(emitTimeout)
  emitTimeout = setTimeout(() => {
    const currentServices = JSON.stringify(localServices)
    
    // Emit только если данные действительно изменились
    if (currentServices !== lastEmittedServices) {
      lastEmittedServices = currentServices
      const servicesData = JSON.parse(currentServices)
      console.log('Emitting services:', servicesData)
      emit('update:services', servicesData)
      emit('update:servicesAdditionalInfo', localAdditionalInfo.value)
    }
  }, 50) // Уменьшил задержку для более быстрой реакции
}

// Инициализация additionalInfo
if (props.servicesAdditionalInfo) {
  localAdditionalInfo.value = props.servicesAdditionalInfo
}
</script>

<style scoped>
.services-module {}
.module-header {}



.global-fields-header {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 15px;
  padding: 0 16px;
  margin-bottom: 0px;
  align-items: center;
}

.global-header-service {
  /* Пустое место */
}

.global-header-price {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 120px;
  font-weight: 400;
  font-size: 13px;
  color: #6b7280;
}

.global-header-comment {
  text-align: left;
  font-weight: 400;
  font-size: 13px;
  color: #6b7280;
  min-width: 0;
  padding-left: 4px;
}

.services-categories {}
.additional-info {}
.services-stats {}
</style> 