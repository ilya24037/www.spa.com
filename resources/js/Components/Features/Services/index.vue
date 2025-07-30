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
      
      <!-- Новая архитектура с store -->
      <ServiceCategory
        v-if="useModernArchitecture"
        v-for="(category, index) in filteredCategories"
        :key="category.id"
        :category="category"
        :store="servicesStore"
        :is-first="index === 0"
      />
      
      <!-- Legacy архитектура без store -->
      <div
        v-else
        v-for="(category, index) in filteredCategories"
        :key="category.id"
        class="legacy-category"
      >
        <div class="category-header">
          <h3 class="category-title">{{ category.name }}</h3>
          <p v-if="category.description" class="category-description">{{ category.description }}</p>
        </div>
        
        <div class="services-list">
          <div 
            v-for="service in category.services" 
            :key="service.id"
            class="service-item-legacy"
          >
            <label class="service-label">
              <input 
                type="checkbox" 
                :checked="services[category.id]?.[service.id]?.enabled || false"
                @change="updateLegacyService(category.id, service.id, 'enabled', $event.target.checked)"
              />
              <span>{{ service.name }}</span>
              <span v-if="service.popular" class="popular-badge">(Популярно)</span>
            </label>
            
            <div class="service-controls">
              <input 
                type="number"
                :value="services[category.id]?.[service.id]?.price || ''"
                @input="updateLegacyService(category.id, service.id, 'price', $event.target.value)"
                placeholder="Цена"
                min="0"
              />
              <input 
                type="text"
                :value="services[category.id]?.[service.id]?.price_comment || ''"
                @input="updateLegacyService(category.id, service.id, 'price_comment', $event.target.value)"
                placeholder="Комментарий"
                maxlength="100"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="additional-info mt-8">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Дополнительная информация об услугах
      </label>
      <textarea 
        :value="servicesAdditionalInfo"
        @input="updateAdditionalInfo($event.target.value)"
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
        <div>
          <span class="text-sm text-gray-700">
            Выбрано услуг: <strong>{{ totalSelected }}</strong>
          </span>
          <span v-if="totalPrice > 0" class="ml-4 text-sm text-gray-700">
            Общая сумма: <strong>{{ totalPrice }} ₽</strong>
          </span>
        </div>
        <button
          v-if="totalSelected > 0"
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
import { computed, watch, onMounted, onUnmounted, ref } from 'vue'
import ServiceCategory from './components/ServiceCategory.vue'
import servicesConfig from './config/services.json'
import { useServicesSelectionStore } from '@/stores/servicesSelectionStore'
import { useOptimizedUpdates } from '@/Composables/useOptimizedUpdates'

const props = defineProps({
  services: { type: Object, default: () => ({}) },
  servicesAdditionalInfo: { type: String, default: '' },
  allowedCategories: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  // Новый пропс для включения новой архитектуры
  useNewArchitecture: { type: Boolean, default: false }
})

const emit = defineEmits(['update:services', 'update:servicesAdditionalInfo'])

// === РЕЖИМ СОВМЕСТИМОСТИ ===

// Определяем какую архитектуру использовать
const useModernArchitecture = computed(() => props.useNewArchitecture)

// === РЕАКТИВНАЯ АРХИТЕКТУРА ===

// Всегда создаём store (может быть не нужен в legacy)
const servicesStore = useServicesSelectionStore()

// Реактивные computed свойства
const totalSelected = computed(() => {
  if (useModernArchitecture.value) {
    // Новая архитектура - из store
    return servicesStore.selections.size
  } else {
    // Legacy архитектура - простой подсчёт
    let count = 0
    Object.values(props.services || {}).forEach(categoryServices => {
      Object.values(categoryServices || {}).forEach(service => {
        if (service?.enabled) count++
      })
    })
    return count
  }
})

const totalPrice = computed(() => {
  if (useModernArchitecture.value) {
    // Новая архитектура - из store
    let sum = 0
    servicesStore.selections.forEach(selection => {
      sum += Number(selection.price) || 0
    })
    return sum
  } else {
    // Legacy архитектура - простой подсчёт
    let sum = 0
    Object.values(props.services || {}).forEach(categoryServices => {
      Object.values(categoryServices || {}).forEach(service => {
        if (service?.enabled) {
          sum += Number(service.price) || 0
        }
      })
    })
    return sum
  }
})

const categoryStats = computed(() => {
  if (useModernArchitecture.value) {
    // Новая архитектура - из store
    const result = new Map()
    servicesStore.selections.forEach((data, serviceId) => {
      const count = result.get(data.categoryId) || 0
      result.set(data.categoryId, count + 1)
    })
    return result
  } else {
    // Legacy архитектура - пустая карта
    return new Map()
  }
})

// Оптимизированные обновления
const { debouncedApiCall, createOptimisticUpdate } = useOptimizedUpdates()

// === ФИЛЬТРАЦИЯ КАТЕГОРИЙ ===

const allCategories = servicesConfig.categories
const filteredCategories = computed(() => {
  if (props.allowedCategories.length === 0) {
    return allCategories
  }
  return allCategories.filter(category => 
    props.allowedCategories.includes(category.id)
  )
})

// === ОПТИМИЗИРОВАННАЯ ОТПРАВКА ДАННЫХ ===

// === РЕАКТИВНЫЕ ФУНКЦИИ ===

// Паттерн "Мгновенный UI + Отложенный API" (как у Ozon)
const emitServicesUpdate = debouncedApiCall(() => {
  if (useModernArchitecture.value) {
    const formattedData = servicesStore.getFormattedData()
    emit('update:services', formattedData)
  } else {
    emit('update:services', props.services)
  }
})

// Оптимистичное обновление дополнительной информации  
const updateAdditionalInfo = createOptimisticUpdate(
  // Мгновенное обновление UI (ничего не делаем, v-model уже обновил)
  () => {},
  // Отложенная отправка родителю
  (value) => emit('update:servicesAdditionalInfo', value)
)

// === ИНИЦИАЛИЗАЦИЯ И ОЧИСТКА ===

onMounted(() => {
  // Инициализируем store данными если новая архитектура
  if (useModernArchitecture.value && props.services && Object.keys(props.services).length > 0) {
    servicesStore.initializeServices(props.services)
  }
})

onUnmounted(() => {
  // Очищаем состояние при размонтировании (опционально)
  // if (useModernArchitecture.value) {
  //   servicesStore.resetStore()
  // }
})

// === ОТСЛЕЖИВАНИЕ ИЗМЕНЕНИЙ ===

// Следим за изменениями в store для новой архитектуры
watch(() => servicesStore.selections.size, () => {
  if (useModernArchitecture.value) {
    emitServicesUpdate()
  }
}, { flush: 'post' })

// Следим за изменениями props для новой архитектуры
watch(() => props.services, (newServices) => {
  if (useModernArchitecture.value && newServices && Object.keys(newServices).length > 0) {
    servicesStore.initializeServices(newServices)
  }
}, { deep: true, immediate: false })

// === ДЕЙСТВИЯ ===

/**
 * Очистка всех услуг (совместимо с обеими архитектурами)
 */
const clearAllServices = () => {
  if (useModernArchitecture.value) {
    // Новая архитектура
    servicesStore.clearAllServices()
    emitServicesUpdate()
  } else {
    // Legacy архитектура - просто emit пустого объекта
    emit('update:services', {})
  }
}

/**
 * Обновление услуги в legacy режиме
 */
const updateLegacyService = (categoryId, serviceId, field, value) => {
  if (!useModernArchitecture.value) {
    // Создаем копию текущих services
    const updatedServices = { ...props.services }
    
    // Инициализируем категорию если её нет
    if (!updatedServices[categoryId]) {
      updatedServices[categoryId] = {}
    }
    
    // Инициализируем услугу если её нет
    if (!updatedServices[categoryId][serviceId]) {
      updatedServices[categoryId][serviceId] = {
        enabled: false,
        price: '',
        price_comment: ''
      }
    }
    
    // Обновляем поле
    updatedServices[categoryId][serviceId][field] = value
    
    // Если отключили услугу, очищаем цену и комментарий
    if (field === 'enabled' && !value) {
      updatedServices[categoryId][serviceId].price = ''
      updatedServices[categoryId][serviceId].price_comment = ''
    }
    
    // Отправляем обновление родителю
    emit('update:services', updatedServices)
  }
}
</script>

<style scoped>
.services-module {}
.module-header {}

/* === СТИЛИ ДЛЯ LEGACY РЕЖИМА === */

.legacy-category {
  margin-bottom: 2rem;
  background: #fff;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  padding: 1.5rem;
}

.category-header {
  margin-bottom: 1rem;
  border-bottom: 1px solid #f0f0f0;
  padding-bottom: 0.75rem;
}

.category-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.category-description {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0.25rem 0 0 0;
}

.service-item-legacy {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 1rem;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f9fafb;
}

.service-item-legacy:last-child {
  border-bottom: none;
}

.service-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  color: #374151;
}

.service-label input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
}

.popular-badge {
  background: #fbbf24;
  color: #92400e;
  font-size: 0.75rem;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-weight: 500;
}

.service-controls {
  display: flex;
  gap: 0.5rem;
}

.service-controls input {
  padding: 0.375rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  width: 80px;
}

.service-controls input:focus {
  outline: none;
  border-color: #3b82f6;
  ring: 2px;
  ring-color: rgba(59, 130, 246, 0.1);
}

.service-controls input[type="text"] {
  width: 120px;
}

/* === СТИЛИ ДЛЯ НОВОЙ АРХИТЕКТУРЫ === */

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

/* === АДАПТИВНОСТЬ === */

@media (max-width: 768px) {
  .service-item-legacy {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
  
  .service-controls {
    justify-self: start;
  }
  
  .service-controls input {
    width: 100px;
  }
}
</style> 