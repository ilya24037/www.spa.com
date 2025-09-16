<template>
  <div :class="isSubcategory ? 'mb-4' : 'mb-6'">
    <!-- Заголовок категории -->
    <div class="border border-gray-200 rounded-lg px-4 py-3 hover:bg-gray-50 transition-colors duration-200 mb-3 cursor-pointer select-none" @click="toggleExpanded">
      <div class="flex items-center justify-between">
        <h3 :class="[
          'flex items-center',
          isSubcategory 
            ? 'text-sm font-semibold text-gray-900' 
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
    </div>
    
    <!-- Описание категории (если есть и категория развернута) -->
    <p v-if="category.description && isExpanded" class="text-sm text-gray-600 mt-2 mb-3 px-2">
      {{ category.description }}
    </p>

    <!-- Список услуг в виде таблицы -->
    <div v-show="isExpanded" class="rounded-lg overflow-hidden border border-gray-200 mt-4 md:overflow-x-auto">
      <table class="w-full border-collapse md:min-w-[500px]">
        <thead class="border-b border-gray-200">
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
      <div class="px-2 py-2 text-xs text-gray-500 border-t border-gray-100">
        <div class="flex">
          <div class="w-10"></div>
          <div class="flex-1"></div>
          <div class="w-48"></div>
          <div class="flex-1">Максимум 100 символов</div>
        </div>
      </div>
    </div>

    <!-- Кнопки управления (показываются только если есть услуги) -->
    <div v-if="category.services.length > 0 && isExpanded" class="mt-4 flex space-x-2">
      <button
        @click="selectAll"
        type="button"
        class="px-3 py-1 text-xs text-blue-600 hover:text-blue-800 transition-colors underline hover:no-underline"
      >
        Выбрать все
      </button>
      <button
        @click="clearAll"
        type="button"
        class="px-3 py-1 text-xs text-gray-600 hover:text-gray-800 transition-colors underline hover:no-underline"
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
// Секции комфорта свернуты по умолчанию, если не выбраны услуги
const isExpanded = ref(
  props.category.id === 'intimate_services' || 
  (hasSelectedServices() && !props.category.is_amenity)
)

// Функция переключения состояния раскрытия
const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}

// При монтировании проверяем, нужно ли раскрыть категорию
onMounted(async () => {
  await nextTick()
  if (props.category.id === 'intimate_services' || (hasSelectedServices() && !props.category.is_amenity)) {
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
      // Обновляем или создаем новую услугу при синхронизации
      serviceValues[serviceId] = { ...newValue[serviceId] }
    })
  }
}, { deep: true })

// Инициализируем при монтировании
initializeServices()
</script>

<!-- Все стили теперь используют Tailwind CSS в template --> 