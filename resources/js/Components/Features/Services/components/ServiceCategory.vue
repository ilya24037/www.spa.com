<template>
  <div class="service-category mb-8">
    <!-- Заголовок категории -->
    <div class="category-header mb-4">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center">
        <span class="text-2xl mr-2">{{ category.icon }}</span>
        {{ category.name }}
        <span v-if="selectedCount > 0" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full">
          {{ selectedCount }}
        </span>
      </h3>
      <p v-if="category.description" class="text-sm text-gray-600 mt-1">
        {{ category.description }}
      </p>
    </div>

    <!-- Список услуг -->
    <div class="services-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <ServiceItem
        v-for="service in category.services"
        :key="service.id"
        :service="service"
        v-model="serviceValues[service.id]"
        @update:modelValue="updateService(service.id, $event)"
      />
    </div>

    <!-- Кнопки управления (показываются только если есть услуги) -->
    <div v-if="category.services.length > 0" class="category-controls mt-4 flex space-x-2">
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
import { ref, computed, watch, reactive } from 'vue'
import ServiceItem from './ServiceItem.vue'

const props = defineProps({
  category: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:modelValue'])

// Локальное состояние для всех услуг категории
const serviceValues = reactive({})

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
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 8px;
}

.services-grid {
  /* Адаптивная сетка */
}

@media (max-width: 768px) {
  .services-grid {
    grid-template-columns: 1fr;
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