<!-- Группа чекбоксов для множественного выбора (как на Авито) -->
<template>
  <div class="checkbox-group" :class="`direction-${direction}`">
    <div 
      v-for="option in options" 
      :key="option.value"
      class="checkbox-item" 
      @click="toggleOption(option.value)"
    >
      <div 
        class="custom-checkbox"
        :class="{ 
          'checked': isSelected(option.value),
          'disabled': disabled || option.disabled 
        }"
      >
        <svg 
          class="check-icon" 
          width="100%" 
          height="100%" 
          viewBox="0 0 10 8" 
          fill="none" 
          xmlns="http://www.w3.org/2000/svg"
        >
          <path 
            d="M1 4.35714L3.4 6.5L9 1.5" 
            stroke="currentColor" 
            stroke-width="2" 
            stroke-linecap="round"
          />
        </svg>
      </div>
      
      <div class="checkbox-content">
        <span class="checkbox-label">{{ option.label }}</span>
        <span v-if="option.description" class="checkbox-description">
          {{ option.description }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watchEffect } from 'vue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  options: {
    type: Array,
    required: true
  },
  disabled: {
    type: Boolean,
    default: false
  },
  direction: {
    type: String,
    default: 'column',
    validator: (value) => ['column', 'row'].includes(value)
  }
})

const emit = defineEmits(['update:modelValue'])

// Инициализация массива (из ClientsSection)
const initializeValue = () => {
  if (!Array.isArray(props.modelValue)) {
    console.log('CheckboxGroup: modelValue не массив, исправляем:', props.modelValue)
    emit('update:modelValue', [])
  }
}

// Проверяем выбран ли элемент
const isSelected = (value) => {
  return props.modelValue.includes(value)
}

// Переключаем выбор элемента (из ClientsSection)
const toggleOption = (value) => {
  if (props.disabled) return
  
  const currentValue = [...props.modelValue]
  
  if (!currentValue.includes(value)) {
    currentValue.push(value)
  } else {
    const index = currentValue.indexOf(value)
    currentValue.splice(index, 1)
  }
  
  emit('update:modelValue', currentValue)
}

// Инициализация только один раз
initializeValue()
</script>

<style scoped>
.checkbox-group {
  display: flex;
  gap: 8px;
}

.direction-column {
  flex-direction: column;
}

.direction-row {
  flex-direction: row;
  flex-wrap: wrap;
}

.checkbox-item {
  display: flex;
  align-items: center;
  cursor: pointer;
  gap: 12px;
  padding: 8px 0;
  user-select: none;
  transition: all 0.2s ease;
}

.checkbox-item:hover:not(.disabled) {
  opacity: 0.8;
}

/* ОБЩИЕ СТИЛИ ЧЕКБОКСОВ (как в BaseCheckbox) */
.custom-checkbox {
  width: 20px;
  height: 20px;
  border: 2px solid #d9d9d9;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  background: #fff;
  flex-shrink: 0;
  cursor: pointer;
}

.custom-checkbox:hover:not(.disabled) {
  border-color: #8c8c8c;
}

.custom-checkbox.checked {
  background: #007bff;
  border-color: #007bff;
}

.custom-checkbox.disabled {
  cursor: not-allowed;
  background: #f5f5f5;
  border-color: #e6e6e6;
}

.check-icon {
  width: 12px;
  height: 10px;
  color: #fff;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.custom-checkbox.checked .check-icon {
  opacity: 1;
}

.checkbox-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.checkbox-label {
  font-size: 16px;
  color: #1a1a1a;
  font-weight: 400;
  line-height: 1.4;
  cursor: pointer;
  user-select: none;
}

.checkbox-description {
  font-size: 14px;
  color: #8c8c8c;
  line-height: 1.3;
  cursor: pointer;
  user-select: none;
}

.disabled .checkbox-label,
.disabled .checkbox-description {
  color: #8c8c8c;
  cursor: not-allowed;
}
</style> 