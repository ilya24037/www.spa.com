<template>
  <div class="services-module">
    <div class="module-header mb-6">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Услуги</h2>
      <div class="field-hint text-gray-600 text-sm">
        Выберите услуги, которые вы предоставляете. Укажите цены и дополнительную информацию для каждой услуги.
      </div>
    </div>
    <div v-if="allowedCategories.length > 0 && allowedCategories.length < allCategories.length" class="category-filters mb-6">
      <div class="text-sm text-gray-700 mb-2">Доступные категории:</div>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="category in filteredCategories" 
          :key="category.id"
          class="inline-flex items-center px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full"
        >
          {{ category.icon }} {{ category.name }}
        </span>
      </div>
    </div>
    <div class="services-categories">
      <ServiceCategory
        v-for="category in filteredCategories"
        :key="category.id"
        :category="category"
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
  servicesAdditionalInfo: { 
    type: String, 
    default: '' 
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

const emit = defineEmits(['update:services', 'update:servicesAdditionalInfo'])

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
const localAdditionalInfo = ref(props.servicesAdditionalInfo || '')

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
</style>
