<template>
  <div class="services-module">
    
    <!-- Подсказка валидации -->
    <div v-if="showServicesError" class="text-sm text-red-600 mb-3">
      Выберите минимум одну услугу
    </div>

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
      <!-- Популярные услуги (всегда развернуты) -->
      <ServiceCategory
        v-if="mainCategory"
        :key="mainCategory.id"
        :category="mainCategory"
        v-model="localServices[mainCategory.id]"
        @update:modelValue="updateCategory(mainCategory.id, $event)"
        :is-subcategory="false"
      />
      
      <!-- Дополнительные услуги (свернуты по умолчанию) -->
      <div v-if="additionalCategories.length > 0" class="service-category mb-6">
        <div class="border border-gray-200 rounded-lg px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors duration-200 mb-3 cursor-pointer select-none" @click="toggleAdditionalServices">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">
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
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
        
        <div v-show="isAdditionalExpanded" class="mt-4 space-y-4 pl-6">
          <ServiceCategory
            v-for="category in additionalCategories"
            :key="category.id"
            :category="category"
            v-model="localServices[category.id]"
            @update:modelValue="updateCategory(category.id, $event)"
            :is-subcategory="true"
          />
        </div>
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
  is_amenity?: boolean
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
  },
  forceValidation: { 
    type: Object, 
    default: () => ({}) 
  }
})

const emit = defineEmits(['update:services', 'clearForceValidation'])

const allCategories = servicesConfig.categories as Category[]

// Фильтруем только категории услуг (без удобств)
const filteredCategories = computed(() => {
  const serviceCategories = allCategories.filter(cat => !cat.is_amenity)
  if (props.allowedCategories.length === 0) {
    return serviceCategories
  }
  return serviceCategories.filter(category => 
    props.allowedCategories.includes(category.id)
  )
})

const localServices = reactive<Record<string, Record<string, ServiceData>>>({})

// Состояние раскрытия дополнительных услуг
const isAdditionalExpanded = ref(false)

// Показывать ошибку валидации услуг
const showServicesError = computed(() => {
  return !!props.errors?.services || !!props.forceValidation?.services
})

// Подсчет общего количества выбранных услуг
const totalSelectedServices = computed(() => {
  let count = 0
  filteredCategories.value.forEach(category => {
    const categoryServices = localServices[category.id]
    if (categoryServices) {
      Object.values(categoryServices).forEach(service => {
        if (service?.enabled) count++
      })
    }
  })
  return count
})

// Основная категория (Популярные услуги)
const mainCategory = computed(() => {
  return filteredCategories.value.find(cat => cat.id === 'intimate_services')
})

// Дополнительные категории (все остальные услуги, кроме основных)
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

const emitAll = () => {
  emit('update:services', JSON.parse(JSON.stringify(localServices)))
}

// Автосброс валидации при выборе услуги
watch(() => totalSelectedServices.value, (newCount) => {
  if (newCount > 0 && showServicesError.value) {
    emit('clearForceValidation')
  }
}, { immediate: true })

// Инициализация
// 1. Сначала инициализируем структуру всех категорий
initializeServicesData()

// 2. Затем загружаем сохраненные данные из props
if (props.services && typeof props.services === 'object') {
  Object.keys(props.services).forEach(categoryId => {
    if (!localServices[categoryId]) localServices[categoryId] = {}
    Object.keys(props.services[categoryId]).forEach(serviceId => {
      localServices[categoryId][serviceId] = { ...props.services[categoryId][serviceId] }
    })
  })
}
</script>

<!-- Все стили мигрированы на Tailwind CSS в template -->