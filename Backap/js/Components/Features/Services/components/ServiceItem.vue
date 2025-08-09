<template>
  <div class="service-item p-3 border border-gray-200 rounded-lg transition-all hover:border-blue-300 hover:shadow-sm">
    <div class="flex items-start space-x-3">
      <!-- Чекбокс -->
      <input 
        :id="service.id"
        v-model="enabled"
        type="checkbox"
        class="mt-1 w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
      >
      
      <!-- Название услуги -->
      <div class="flex-1">
        <label 
          :for="service.id" 
          class="text-sm font-medium text-gray-700 cursor-pointer flex items-center"
        >
          {{ service.name }}
          <span v-if="service.popular" class="ml-2 px-2 py-1 text-xs bg-orange-100 text-orange-600 rounded-full">
            Популярно
          </span>
        </label>
        
        <!-- Поле цены и комментария (показывается только при включении) -->
        <div v-if="enabled" class="mt-2 transition-all">
          <input 
            v-model="serviceData.price_comment"
            @input="emitUpdate"
            type="text"
            :placeholder="service.price_placeholder"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            maxlength="150"
          >
        </div>
      </div>
    </div>
  </div>
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

// Локальное состояние
const serviceData = ref({
  enabled: props.modelValue?.enabled || false,
  price_comment: props.modelValue?.price_comment || ''
})

// Computed для enabled
const enabled = computed({
  get: () => serviceData.value.enabled,
  set: (value) => {
    serviceData.value.enabled = value
    if (!value) {
      // Очищаем данные при отключении
      serviceData.value.price_comment = ''
    }
    emit('update:modelValue', { ...serviceData.value })
  }
})

// Функция для emit при изменениях пользователем
const emitUpdate = () => {
  emit('update:modelValue', { ...serviceData.value })
}

// Отслеживаем изменения из родителя
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    serviceData.value = { ...newValue }
  }
}, { deep: true })
</script>

<style scoped>
.service-item {
  /* Дополнительные стили если нужны */
}

/* Анимация появления поля цены */
.service-item input[type="text"] {
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style> 