<template>
  <tr class="service-item border-b border-gray-100 hover:bg-gray-50">
    <!-- Чекбокс -->
    <td class="py-3 px-2 w-10">
      <input 
        :id="service.id"
        v-model="enabled"
        type="checkbox"
        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
      >
    </td>
    
    <!-- Название услуги -->
    <td class="py-3 px-2">
      <label 
        :for="service.id" 
        class="text-sm font-medium text-gray-700 cursor-pointer flex items-center"
      >
        {{ service.name }}
        <span v-if="service.popular" class="ml-2 px-2 py-1 text-xs bg-orange-100 text-orange-600 rounded-full">
          Популярно
        </span>
      </label>
    </td>
    
    <!-- Доплата -->
    <td class="py-3 px-2 w-48">
      <div v-if="enabled" class="flex items-center gap-2">
        <button 
          @click="increasePrice"
          type="button"
          class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded transition-all"
          title="Добавить 1000₽"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
        <div class="relative flex items-center">
          <input 
            v-model="serviceData.price"
            @input="emitUpdate"
            type="number"
            placeholder="0"
            :class="[
              'w-24 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
              serviceData.price && serviceData.price > 0 ? 'pr-12' : 'pr-6'
            ]"
            min="0"
            step="1000"
          >
          <!-- Символ рубля всегда видим -->
          <span 
            class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-gray-500 pointer-events-none"
            :class="{ 'right-7': serviceData.price && serviceData.price > 0 }"
          >₽</span>
          <!-- Кнопка сброса -->
          <button 
            v-if="serviceData.price && serviceData.price > 0"
            @click="resetPrice"
            type="button"
            class="absolute right-1 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center text-gray-400 hover:text-red-600 rounded transition-all"
            title="Сбросить"
          >
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
              <path d="M9 3L3 9M3 3l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </div>
    </td>
    
    <!-- Комментарий -->
    <td class="py-3 px-2">
      <input 
        v-if="enabled"
        v-model="serviceData.comment"
        @input="emitUpdate"
        type="text"
        placeholder="Укажите комментарий"
        class="w-full px-3 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        :maxlength="100"
      >
    </td>
  </tr>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  service: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Object,
    default: () => ({ enabled: false, price_comment: '' })
  }
})

const emit = defineEmits(['update:modelValue'])

// Локальное состояние - разделяем price и comment
const serviceData = ref({
  enabled: props.modelValue?.enabled || false,
  price: props.modelValue?.price || '',
  comment: props.modelValue?.comment || ''
})

// Парсим price_comment если нужно (для обратной совместимости)
if (!serviceData.value.price && !serviceData.value.comment && props.modelValue?.price_comment) {
  // Пробуем извлечь цену из начала строки
  const match = props.modelValue.price_comment.match(/^(\d+)\s*(.*)$/)
  if (match) {
    serviceData.value.price = match[1]
    serviceData.value.comment = match[2] || ''
  } else {
    // Если не число в начале - всё в комментарий
    serviceData.value.comment = props.modelValue.price_comment
  }
}

// Computed для enabled
const enabled = computed({
  get: () => serviceData.value.enabled,
  set: (value) => {
    serviceData.value.enabled = value
    if (!value) {
      // Очищаем данные при отключении
      serviceData.value.price = ''
      serviceData.value.comment = ''
    }
    emitUpdate()
  }
})

// Функция увеличения цены на 1000
const increasePrice = () => {
  const currentPrice = parseInt(serviceData.value.price) || 0
  serviceData.value.price = currentPrice + 1000
  emitUpdate()
}

// Функция сброса цены
const resetPrice = () => {
  serviceData.value.price = ''
  emitUpdate()
}

// Функция для emit при изменениях пользователем
const emitUpdate = () => {
  // Формируем price_comment для обратной совместимости
  let price_comment = ''
  
  if (serviceData.value.price && serviceData.value.comment) {
    // Есть и цена и комментарий
    price_comment = `${serviceData.value.price} ${serviceData.value.comment}`
  } else if (serviceData.value.price) {
    // Только цена
    price_comment = serviceData.value.price.toString()
  } else if (serviceData.value.comment) {
    // Только комментарий
    price_comment = serviceData.value.comment
  }
    
  emit('update:modelValue', { 
    enabled: serviceData.value.enabled,
    price: serviceData.value.price || '',
    comment: serviceData.value.comment || '',
    price_comment // для обратной совместимости
  })
}

// Отслеживаем изменения из родителя
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    serviceData.value.enabled = newValue.enabled || false
    serviceData.value.price = newValue.price || ''
    serviceData.value.comment = newValue.comment || ''
    
    // Если нет price и comment, но есть price_comment - парсим
    if (!newValue.price && !newValue.comment && newValue.price_comment) {
      const match = newValue.price_comment.match(/^(\d+)\s*(.*)$/)
      if (match) {
        serviceData.value.price = match[1]
        serviceData.value.comment = match[2] || ''
      } else {
        serviceData.value.comment = newValue.price_comment
      }
    }
  }
}, { deep: true })
</script>

<style scoped>
.service-item {
  transition: background-color 0.2s;
}

.service-item:last-child {
  border-bottom: none;
}

/* Анимация появления полей */
.service-item input[type="text"],
.service-item input[type="number"] {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Убираем стрелки у input[type="number"] */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}
</style> 