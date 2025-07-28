<template>
  <div class="service-item">
    <div class="service-content">
      <!-- Используем BaseCheckbox для единообразия -->
      <BaseCheckbox
        v-model="enabled"
        :label="serviceLabel"
      />
      
      <!-- Поле цены и комментария (показывается только при включении) -->
      <div v-if="enabled" class="price-comment-field">
        <input 
          v-model="serviceData.price_comment"
          @input="emitUpdate"
          type="text"
          :placeholder="service.price_placeholder"
          class="price-input"
          maxlength="150"
        >
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseCheckbox from '@/Components/UI/BaseCheckbox.vue'

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

// Лейбл для чекбокса с популярностью
const serviceLabel = computed(() => {
  let label = props.service.name
  if (props.service.popular) {
    label += ' (Популярно)'
  }
  return label
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
  padding: 16px;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  transition: all 0.2s ease;
  background: #fff;
}

.service-item:hover {
  border-color: #007bff;
  box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
}

.service-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.price-comment-field {
  margin-left: 32px; /* Выравниваем с текстом лейбла */
  animation: slideDown 0.3s ease-out;
}

.price-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.2s ease;
}

.price-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.price-input::placeholder {
  color: #999;
}

/* Анимация появления поля цены */
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