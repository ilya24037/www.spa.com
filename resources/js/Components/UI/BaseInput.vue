<!-- Базовый инпут в стиле Авито -->
<template>
  <div class="input-container">
    <label v-if="label" class="input-label">{{ label }}</label>
    
          <div class="input-wrapper" :class="{ 
        'has-error': error,
        'has-prefix': prefix,
        'has-suffix': suffix
      }">
      <input
        ref="inputRef"
        v-model="inputValue"
        :type="type"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        class="base-input"
        :class="{
          'disabled': disabled,
          'readonly': readonly,
          'has-clear': clearable && inputValue,
          'has-prefix': prefix,
          'has-suffix': suffix
        }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown.enter="$emit('enter')"
      />
      
      <!-- Кнопка очистки -->
             <button
         v-if="clearable && inputValue && !disabled && !readonly"
         type="button"
         class="clear-button"
         @click="clearInput"
         tabindex="-1"
       >
         <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
           <path
             d="M12 4L4 12M4 4L12 12"
             stroke="currentColor"
             stroke-width="2"
             stroke-linecap="round"
             stroke-linejoin="round"
           />
         </svg>
       </button>
      
      <!-- Префикс -->
      <div v-if="prefix" class="input-prefix">{{ prefix }}</div>
      
      <!-- Суффикс -->
      <div v-if="suffix" class="input-suffix">{{ suffix }}</div>
    </div>
    
    <!-- Ошибка -->
    <div v-if="error" class="input-error">{{ error }}</div>
    
    <!-- Подсказка -->
    <div v-if="hint && !error" class="input-hint">{{ hint }}</div>
    
    <!-- Счетчик символов -->
    <div v-if="maxlength && showCounter" class="input-counter">
      {{ inputValue.length }}/{{ maxlength }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  type: {
    type: String,
    default: 'text',
    validator: (value) => [
      'text', 'email', 'password', 'number', 
      'tel', 'url', 'search'
    ].includes(value)
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  clearable: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  maxlength: {
    type: Number,
    default: null
  },
  showCounter: {
    type: Boolean,
    default: false
  },
  min: {
    type: Number,
    default: null
  },
  max: {
    type: Number,
    default: null
  },
  step: {
    type: Number,
    default: null
  },
  prefix: {
    type: String,
    default: ''
  },
  suffix: {
    type: String,
    default: ''
  }
})

// Events
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'enter', 'clear'])

// Refs
const inputRef = ref(null)

// Computed
const inputValue = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
  }
})

// Methods
const handleInput = (event) => {
  let value = event.target.value
  
  // Для числовых инпутов - более мягкая обработка
  if (props.type === 'number') {
    // Разрешаем пустое значение, минус, точку и цифры
    if (value === '' || value === '-' || value === '.' || value === '-.') {
      emit('update:modelValue', value)
      return
    }
    
    // Проверяем что это допустимое число (включая частично введенные)
    if (/^-?\d*\.?\d*$/.test(value)) {
      const numValue = parseFloat(value)
      
      // Если это полное число, проверяем ограничения
      if (!isNaN(numValue)) {
        if (props.min !== null && numValue < props.min) {
          // Не блокируем ввод, просто не применяем ограничение сразу
          emit('update:modelValue', value)
          return
        } else if (props.max !== null && numValue > props.max) {
          // Не блокируем ввод, просто не применяем ограничение сразу
          emit('update:modelValue', value)
          return
        }
      }
      
      emit('update:modelValue', value)
    }
    // Если паттерн не подходит, игнорируем ввод
    return
  }
  
  emit('update:modelValue', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  // Валидация числовых значений при потере фокуса
  if (props.type === 'number' && event.target.value !== '') {
    const numValue = parseFloat(event.target.value)
    
    if (!isNaN(numValue)) {
      let finalValue = numValue
      
      // Применяем ограничения min/max
      if (props.min !== null && numValue < props.min) {
        finalValue = props.min
      } else if (props.max !== null && numValue > props.max) {
        finalValue = props.max
      }
      
      // Если значение изменилось, обновляем
      if (finalValue !== numValue) {
        emit('update:modelValue', finalValue)
      }
    }
  }
  
  emit('blur', event)
}

const clearInput = () => {
  emit('update:modelValue', '')
  emit('clear')
  nextTick(() => {
    inputRef.value?.focus()
  })
}

const focus = () => {
  inputRef.value?.focus()
}

const blur = () => {
  inputRef.value?.blur()
}

// Expose methods
defineExpose({
  focus,
  blur
})
</script>

<style scoped>
.input-container {
  width: 100%;
}

.input-label {
  display: block;
  font-size: 16px;
  font-weight: 500;
  color: #000000;
  margin-bottom: 8px;
}

.input-wrapper {
  position: relative;
  width: 100%;
}

.base-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e5e5;
  border-radius: 8px;
  background: #f5f5f5;
  font-size: 16px;
  color: #1a1a1a;
  transition: all 0.2s ease;
  min-height: 48px;
  box-sizing: border-box;
}

.base-input::placeholder {
  color: #8c8c8c;
}

.base-input:hover:not(:disabled):not(:readonly) {
  border-color: #d0d0d0;
}

.base-input:focus {
  outline: none;
  border-color: #2196f3;
  background: #fff;
}

.base-input.disabled {
  cursor: not-allowed;
  background: #f5f5f5;
  color: #8c8c8c;
}

.base-input.readonly {
  cursor: default;
  background: #f9f9f9;
}

.base-input.has-clear {
  padding-right: 40px;
}

/* Отступы для префикса */
.base-input.has-prefix {
  padding-left: 48px;
}

/* Отступы для суффикса */
.base-input.has-suffix {
  padding-right: 48px;
}

/* Отступы для суффикса + кнопка очистки */
.base-input.has-suffix.has-clear {
  padding-right: 80px;
}

.input-wrapper.has-error .base-input {
  border-color: #ff4d4f;
}

.input-wrapper.has-error .base-input:focus {
  border-color: #ff4d4f;
}

.clear-button {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  color: #8c8c8c;
  transition: color 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 4px;
}

/* Кнопка очистки когда есть суффикс */
.input-wrapper.has-suffix .clear-button {
  right: 48px;
}

.clear-button:hover {
  color: #1a1a1a;
  background: #f0f0f0;
}

.input-prefix {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  color: #8c8c8c;
  pointer-events: none;
}

.input-suffix {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  color: #8c8c8c;
  pointer-events: none;
}

.base-input:not(:placeholder-shown) + .input-prefix,
.base-input:focus + .input-prefix {
  color: #1a1a1a;
}

.input-error {
  margin-top: 4px;
  font-size: 14px;
  color: #ff4d4f;
  line-height: 1.4;
}

.input-hint {
  margin-top: 4px;
  font-size: 14px;
  color: #666666;
  line-height: 1.4;
}

.input-counter {
  margin-top: 4px;
  font-size: 12px;
  color: #8c8c8c;
  text-align: right;
}

/* Стили для числовых инпутов */
.base-input[type="number"] {
  -moz-appearance: textfield;
}

.base-input[type="number"]::-webkit-outer-spin-button,
.base-input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
  .base-input {
    font-size: 16px; /* Предотвращает zoom на iOS */
  }
}
</style> 