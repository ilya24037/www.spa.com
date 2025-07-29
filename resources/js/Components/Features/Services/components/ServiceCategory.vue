<template>
  <div class="service-category mb-8">
    <!-- Единая строка: категория + доплата + комментарий -->
    <div class="category-table-header">
      <div class="category-header-title">
        {{ category.name }}
        <span v-if="selectedCount > 0" class="selected-count">
          {{ selectedCount }}
        </span>
        <p v-if="category.description" class="category-description">
          {{ category.description }}
        </p>
      </div>
      <div class="category-header-price">Доплата</div>
      <div class="category-header-comment">Комментарий</div>
    </div>
    <ul class="services-list">
      <ServiceItem
        v-for="service in category.services"
        :key="service.id"
        :service="service"
        v-model="serviceValues[service.id]"
        @update:modelValue="updateService(service.id, $event)"
      />
    </ul>
    <!-- Кнопки управления под списком услуг -->
    <div v-if="category.services.length > 0" class="category-footer-controls mt-4">
      <button
        @click="selectAll"
        type="button"
        class="btn-select-all"
      >
        Выбрать все
      </button>
      <button
        @click="clearAll"
        type="button"
        class="btn-clear-all"
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
  },
  isFirst: {
    type: Boolean,
    default: false
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
        price: '',
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
      serviceValues[service.id].price = ''
      serviceValues[service.id].price_comment = ''
    }
  })
  emit('update:modelValue', { ...serviceValues })
}

// Оптимизированный обработчик изменений отдельной услуги
let lastCategoryData = null
let categoryUpdateTimeout = null

const updateService = (serviceId, serviceData) => {
  serviceValues[serviceId] = { ...serviceData }
  
  // Debounced emit для всей категории
  if (categoryUpdateTimeout) clearTimeout(categoryUpdateTimeout)
  categoryUpdateTimeout = setTimeout(() => {
    const currentCategoryData = JSON.stringify(serviceValues)
    
    if (currentCategoryData !== lastCategoryData) {
      lastCategoryData = currentCategoryData
      emit('update:modelValue', { ...serviceValues })
      // console.log('Category updated:', props.category.name, 'services changed')
    }
  }, 20)
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
/* Старые стили заголовка удалены */

/* Счетчик выбранных услуг */
.selected-count {
  margin-left: 12px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
}

/* Описание категории */
.category-description {
  font-size: 14px;
  color: #64748b;
  margin: 4px 0 0 0;
  line-height: 1.4;
}

/* Кнопки управления под списком услуг */
.category-footer-controls {
  display: flex;
  gap: 8px;
  justify-content: flex-start;
  margin-top: 16px;
}

.btn-select-all,
.btn-clear-all {
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-select-all {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.btn-select-all:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
  transform: translateY(-1px);
}

.btn-clear-all {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.btn-clear-all:hover {
  background: #e2e8f0;
  color: #475569;
  transform: translateY(-1px);
}

/* Список услуг */
.services-list {
  list-style: none;
  padding: 0 16px;
  margin: 0;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

/* Общие стили для категории */
.service-category {
  margin-bottom: 2rem;
}

/* Старые стили первой категории удалены */

/* Адаптивность */
@media (max-width: 768px) {
  .category-title {
    font-size: 18px;
  }

  .category-footer-controls {
    flex-direction: column;
    gap: 6px;
  }
  
  .btn-select-all,
  .btn-clear-all {
    padding: 6px 12px;
    font-size: 12px;
  }
}

/* --- ДОБАВЛЯЮ СТИЛИ --- */
.category-table-header {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 15px;
  padding: 0 16px;
  margin-bottom: 8px;
  align-items: baseline;
}
.category-header-title {
  font-weight: 600;
  font-size: 16px;
  color: #1e293b;
  line-height: 1.2;
}
.category-header-price,
.category-header-comment {
  text-align: center;
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}
</style> 