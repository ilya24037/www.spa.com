<template>
  <div :class="isSubcategory ? 'service-category mb-4' : 'service-category mb-6'">
    <!-- Заголовок категории -->
    <div class="category-header mb-3 cursor-pointer select-none hover:bg-gray-50 rounded-lg p-2 -mx-2 transition-colors" @click="toggleExpanded">
      <div class="flex items-center justify-between">
        <h3 :class="[
          'flex items-center',
          isSubcategory 
            ? 'text-sm font-normal text-gray-600' 
            : 'text-base font-semibold text-gray-900'
        ]">
          {{ category.name }}
          <span v-if="selectedCount > 0" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full">
            {{ selectedCount }}
          </span>
        </h3>
        <svg 
          :class="[
            'text-gray-500 transition-transform duration-200',
            { 'rotate-180': isExpanded },
            isSubcategory ? 'w-4 h-4' : 'w-5 h-5'
          ]"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
      <p v-if="category.description && isExpanded" class="text-sm text-gray-600 mt-1">
        {{ category.description }}
      </p>
    </div>

    <!-- Список услуг в виде таблицы -->
    <div v-show="isExpanded" class="services-table mt-4">
      <table class="w-full">
        <thead>
          <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
            <th class="py-2 px-2 w-10"></th>
            <th class="py-2 px-2">Услуга</th>
            <th class="py-2 px-2 w-48">Доплата</th>
            <th class="py-2 px-2">
              Комментарий
            </th>
          </tr>
        </thead>
        <tbody>
          <ServiceItem
            v-for="service in category.services"
            :key="service.id"
            :service="service"
            v-model="serviceValues[service.id]"
            @update:modelValue="updateService(service.id, $event)"
          />
        </tbody>
      </table>
      <!-- Подсказка под таблицей -->
      <div class="px-2 py-2 text-xs text-gray-500 bg-gray-50 border-t border-gray-100">
        <div class="flex">
          <div class="w-10"></div>
          <div class="flex-1"></div>
          <div class="w-48"></div>
          <div class="flex-1">Максимум 100 символов</div>
        </div>
      </div>
    </div>

    <!-- Кнопки управления (показываются только если есть услуги) -->
    <div v-if="category.services.length > 0 && isExpanded" class="category-controls mt-4 flex space-x-2">
      <button
        @click="selectAll"
        type="button"
        class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 transition-colors"
      >
        Выбрать все
      </button>
      <button
        @click="clearAll"
        type="button"
        class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 transition-colors"
      >
        Отменить все
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, reactive, onMounted, nextTick } from 'vue'
import ServiceItem from './ServiceItem.vue'

const props = defineProps({
  category: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Object,
    default: () => ({})
  },
  isSubcategory: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

// Локальное состояние для всех услуг категории
const serviceValues = reactive({})

// Проверяем, есть ли в категории выбранные услуги при инициализации
const hasSelectedServices = () => {
  if (!props.modelValue) return false
  return Object.values(props.modelValue).some(service => service?.enabled)
}

// Состояние раскрытия - "Основные услуги" развернуты по умолчанию
// Также раскрываем категории с выбранными услугами
const isExpanded = ref(props.category.id === 'intimate_services' || hasSelectedServices())

// Функция переключения состояния раскрытия
const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}

// При монтировании проверяем, нужно ли раскрыть категорию
onMounted(async () => {
  await nextTick()
  if (props.category.id === 'intimate_services' || hasSelectedServices()) {
    isExpanded.value = true
  }
})

// Инициализируем состояние услуг
const initializeServices = () => {
  props.category.services.forEach(service => {
    if (!serviceValues[service.id]) {
      serviceValues[service.id] = props.modelValue[service.id] || {
        enabled: false,
        price_comment: ''
      }
    }
  })
}

// Вычисляемое свойство для подсчета выбранных услуг
const selectedCount = computed(() => {
  return Object.values(serviceValues).filter(service => service?.enabled).length
})

// Функция выбора всех услуг
const selectAll = () => {
  props.category.services.forEach(service => {
    if (serviceValues[service.id]) {
      serviceValues[service.id].enabled = true
    }
  })
  emit('update:modelValue', { ...serviceValues })
}

// Функция очистки всех услуг
const clearAll = () => {
  props.category.services.forEach(service => {
    if (serviceValues[service.id]) {
      serviceValues[service.id].enabled = false
      serviceValues[service.id].price_comment = ''
    }
  })
  emit('update:modelValue', { ...serviceValues })
}

// Обработчик изменений отдельной услуги
const updateService = (serviceId, serviceData) => {
  serviceValues[serviceId] = { ...serviceData }
  emit('update:modelValue', { ...serviceValues })
}

// watch(serviceValues) убран - вызывает циклические обновления
// emit вызывается только при пользовательских действиях

// Отслеживаем изменения из родителя
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    Object.keys(newValue).forEach(serviceId => {
      if (serviceValues[serviceId]) {
        serviceValues[serviceId] = { ...newValue[serviceId] }
      }
    })
  }
}, { deep: true })

// Инициализируем при монтировании
initializeServices()
</script>

<style scoped>
.service-category {
  /* Дополнительные стили если нужны */
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

.services-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
}

.services-table table {
  border-collapse: collapse;
}

.services-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
  .services-table {
    overflow-x: auto;
  }
  
  .services-table table {
    min-width: 500px;
  }
}

.category-controls button {
  font-size: 12px;
  text-decoration: underline;
}

.category-controls button:hover {
  text-decoration: none;
}
</style> 