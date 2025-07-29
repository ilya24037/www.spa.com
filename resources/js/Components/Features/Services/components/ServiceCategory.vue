<template>
  <div class="service-category mb-8">
    <!-- Красивый заголовок категории в стиле Avito -->
    <div class="category-header-card mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="category-icon-wrapper">
            <span class="category-icon">{{ category.icon }}</span>
          </div>
          <div class="ml-4">
            <h3 class="category-title">
              {{ category.name }}
              <span v-if="selectedCount > 0" class="selected-count">
                {{ selectedCount }}
              </span>
            </h3>
            <p v-if="category.description" class="category-description">
              {{ category.description }}
            </p>
          </div>
        </div>
        
        <!-- Кнопки управления в заголовке -->
        <div v-if="category.services.length > 0" class="category-header-controls">
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
    </div>

    <!-- Список услуг -->
    <ul class="services-list">
      <ServiceItem
        v-for="service in category.services"
        :key="service.id"
        :service="service"
        v-model="serviceValues[service.id]"
        @update:modelValue="updateService(service.id, $event)"
      />
    </ul>
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
/* Красивая карточка категории в стиле Avito */
.category-header-card {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
}

.category-header-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-1px);
}

/* Wrapper для иконки */
.category-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border-radius: 14px;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.category-icon {
  font-size: 28px;
  line-height: 1;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
}

/* Заголовок категории */
.category-title {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  line-height: 1.2;
}

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

/* Кнопки управления в заголовке */
.category-header-controls {
  display: flex;
  gap: 8px;
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
  padding: 0;
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

/* Адаптивность */
@media (max-width: 768px) {
  .category-header-card {
    padding: 16px 20px;
  }
  
  .category-icon-wrapper {
    width: 48px;
    height: 48px;
  }
  
  .category-icon {
    font-size: 24px;
  }
  
  .category-title {
    font-size: 18px;
  }
  
  .category-header-controls {
    flex-direction: column;
    gap: 6px;
  }
  
  .btn-select-all,
  .btn-clear-all {
    padding: 6px 12px;
    font-size: 12px;
  }
}
</style> 