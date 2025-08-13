<template>
  <div class="services-module">

    <div v-if="allowedCategories.length > 0 && allowedCategories.length < allCategories.length" class="category-filters mb-6">
      <div class="text-sm text-gray-700 mb-2">Доступные категории:</div>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="category in filteredCategories" 
          :key="category.id"
          class="inline-flex items-center px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full"
        >
          {{ category.name }}
        </span>
      </div>
    </div>
    <div class="services-categories">
      <!-- Основные услуги (всегда развернуты) -->
      <ServiceCategory
        v-if="mainCategory"
        :key="mainCategory.id"
        :category="mainCategory"
        v-model="localServices[mainCategory.id]"
        @update:modelValue="updateCategory(mainCategory.id, $event)"
      />
      
      <!-- Дополнительные услуги (свернуты по умолчанию) -->
      <div v-if="additionalCategories.length > 0" class="additional-services-wrapper">
        <div class="category-header cursor-pointer select-none" @click="toggleAdditionalServices">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
              Дополнительные услуги
              <span v-if="totalAdditionalSelected > 0" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full">
                {{ totalAdditionalSelected }}
              </span>
            </h3>
            <svg 
              class="w-5 h-5 text-gray-500 transition-transform duration-200" 
              :class="{ 'rotate-180': isAdditionalExpanded }"
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
        
        <div v-show="isAdditionalExpanded" class="mt-4 space-y-6">
          <ServiceCategory
            v-for="category in additionalCategories"
            :key="category.id"
            :category="category"
            v-model="localServices[category.id]"
            @update:modelValue="updateCategory(category.id, $event)"
          />
        </div>
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

<script setup lang="ts">
import { ref, computed, watch, reactive } from 'vue'
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
}

const props = defineProps({
  services: { 
    type: Object, 
    default: () => ({}) 
  },
  allowedCategories: { 
    type: Array as () => string[], 
    default: () => [] 
  },
  errors: { 
    type: Object, 
    default: () => ({}) 
  }
})

const emit = defineEmits(['update:services'])

const allCategories = servicesConfig.categories as Category[]
const filteredCategories = computed(() => {
  if (props.allowedCategories.length === 0) {
    return allCategories
  }
  return allCategories.filter(category => 
    props.allowedCategories.includes(category.id)
  )
})

const localServices = reactive<Record<string, Record<string, ServiceData>>>({})

// Состояние раскрытия дополнительных услуг
const isAdditionalExpanded = ref(false)

// Основная категория (Основные услуги)
const mainCategory = computed(() => {
  return filteredCategories.value.find(cat => cat.id === 'intimate_services')
})

// Дополнительные категории (все остальные)
const additionalCategories = computed(() => {
  return filteredCategories.value.filter(cat => cat.id !== 'intimate_services')
})

// Подсчет выбранных дополнительных услуг
const totalAdditionalSelected = computed(() => {
  let count = 0
  additionalCategories.value.forEach(category => {
    const categoryServices = localServices[category.id]
    if (categoryServices) {
      Object.values(categoryServices).forEach(service => {
        if (service?.enabled) count++
      })
    }
  })
  return count
})

// Функция переключения дополнительных услуг
const toggleAdditionalServices = () => {
  isAdditionalExpanded.value = !isAdditionalExpanded.value
}

const initializeServicesData = () => {
  filteredCategories.value.forEach(category => {
    if (!localServices[category.id]) {
      localServices[category.id] = {}
    }
    category.services.forEach(service => {
      if (!localServices[category.id][service.id]) {
        localServices[category.id][service.id] = {
          enabled: false,
          price_comment: ''
        }
      }
    })
  })
}

const totalSelectedServices = computed(() => {
  let count = 0
  Object.values(localServices).forEach(categoryServices => {
    Object.values(categoryServices).forEach(service => {
      if (service?.enabled) count++
    })
  })
  return count
})

const clearAllServices = () => {
  Object.keys(localServices).forEach(categoryId => {
    Object.keys(localServices[categoryId]).forEach(serviceId => {
      localServices[categoryId][serviceId] = {
        enabled: false,
        price_comment: ''
      }
    })
  })
  emitAll()
}

// Обработчик изменений категории
const updateCategory = (categoryId: string, categoryData: Record<string, ServiceData>) => {
  localServices[categoryId] = { ...categoryData }
  emitAll()
}

watch(() => props.services, (val) => {
  if (val && typeof val === 'object') {
    Object.keys(val).forEach(categoryId => {
      if (!localServices[categoryId]) localServices[categoryId] = {}
      Object.keys(val[categoryId]).forEach(serviceId => {
        localServices[categoryId][serviceId] = { ...val[categoryId][serviceId] }
      })
    })
  }
}, { deep: true })

watch(() => props.servicesAdditionalInfo, (val) => {
  localAdditionalInfo.value = val || ''
})

const emitAll = () => {
  emit('update:services', JSON.parse(JSON.stringify(localServices)))
  emit('update:servicesAdditionalInfo', localAdditionalInfo.value)
}


// Инициализация
if (props.services && typeof props.services === 'object') {
  Object.keys(props.services).forEach(categoryId => {
    if (!localServices[categoryId]) localServices[categoryId] = {}
    Object.keys(props.services[categoryId]).forEach(serviceId => {
      localServices[categoryId][serviceId] = { ...props.services[categoryId][serviceId] }
    })
  })
}
if (props.servicesAdditionalInfo) {
  localAdditionalInfo.value = props.servicesAdditionalInfo
}
initializeServicesData()
</script>

<style scoped>
.services-module {}
.module-header {}
.category-filters {}
.services-categories {}
.additional-info {}
.services-stats {}

.additional-services-wrapper {
  margin-top: 24px;
}

.category-header {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px 16px;
  background: #f9fafb;
  transition: background-color 0.2s;
}

.category-header:hover {
  background: #f3f4f6;
}
</style>
