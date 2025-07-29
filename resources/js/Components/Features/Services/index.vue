<template>
  <div class="services-module">
    <div class="module-header mb-6">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Услуги <span class="text-red-500">*</span></h2>
      
      <!-- Предупреждение как на скриншоне -->
      <div class="warning-message mb-4 p-4 bg-orange-50 border-l-4 border-orange-400 rounded">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-orange-700">
              На странице анкеты, под услугой можете выводить комментарий, например:<br>
              <span class="font-medium">"Отличная тренировка мышц, Ты будешь приятно удивлен. | Я буду очень стараться, чтобы не разочаровать Тебя. | Просто улетаю..."</span> и т.д.
            </p>
            <p class="text-sm text-orange-700 mt-2">
              <span class="font-medium">Максимум 100 символов для одного комментария.</span>
            </p>
            <p class="text-sm text-orange-700 mt-2">
              Для доп. цен вводите только цифры, не нужно писать +, т.к. + ставится автоматически.
            </p>
          </div>
        </div>
      </div>
      
      <div class="field-hint text-gray-600 text-sm">
        Выберите услуги, которые вы предоставляете. Укажите цены и дополнительную информацию для каждой услуги.
      </div>
    </div>
    <div v-if="allowedCategories.length < allCategories.length" class="category-filters mb-6">
      <div class="text-sm font-medium text-gray-700 mb-3">Доступные категории:</div>
      <div class="flex flex-wrap gap-3">
        <span 
          v-for="category in filteredCategories" 
          :key="category.id"
          class="category-chip"
        >
          <span class="category-chip-icon">{{ category.icon }}</span>
          <span class="category-chip-text">{{ category.name }}</span>
        </span>
      </div>
    </div>
    <div class="services-categories">
      <!-- Заголовки полей один раз для всех категорий -->
      <div class="global-fields-header">
        <div class="global-header-service"></div>
        <div class="global-header-price">Доплата</div>
        <div class="global-header-comment">Комментарий</div>
      </div>
      
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

const localServices = reactive({})
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
          price: '',
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
        price: '',
        price_comment: ''
      }
    })
  })
  emitAll()
}

// Обработчик изменений категории
const updateCategory = (categoryId, categoryData) => {
  localServices[categoryId] = { ...categoryData }
  emitAll()
}

watch(() => props.services, (val) => {
  if (val) {
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

// watch(localServices) убран - вызывает циклические обновления
// emitAll() теперь вызывается только при пользовательских действиях

const emitAll = () => {
  emit('update:services', JSON.parse(JSON.stringify(localServices)))
  emit('update:servicesAdditionalInfo', localAdditionalInfo.value)
}

// Инициализация
if (props.services) {
  Object.keys(props.services).forEach(categoryId => {
    if (!localServices[categoryId]) localServices[categoryId] = {}
    Object.keys(props.services[categoryId]).forEach(serviceId => {
      localServices[categoryId][serviceId] = { 
        enabled: props.services[categoryId][serviceId].enabled || false,
        price: props.services[categoryId][serviceId].price || '',
        price_comment: props.services[categoryId][serviceId].price_comment || ''
      }
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

/* Красивые чипы категорий */
.category-filters {}

.category-chip {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
  box-shadow: 0 2px 6px rgba(59, 130, 246, 0.25);
  transition: all 0.2s ease;
}

.category-chip:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
  transform: translateY(-1px);
}

.category-chip-icon {
  font-size: 16px;
  margin-right: 8px;
  line-height: 1;
}

.category-chip-text {
  line-height: 1.2;
}

.global-fields-header {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 15px;
  padding: 8px 16px;
  margin-bottom: 16px;
  align-items: center;
}

.global-header-service {
  /* Пустое место */
}

.global-header-price {
  text-align: center;
  min-width: 120px;
  font-weight: 600;
  font-size: 14px;
  color: #374151;
}

.global-header-comment {
  text-align: left;
  font-weight: 600;
  font-size: 14px;
  color: #374151;
}

.services-categories {}
.additional-info {}
.services-stats {}
</style> 