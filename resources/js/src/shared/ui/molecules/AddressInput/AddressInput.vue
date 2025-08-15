<template>
  <div class="address-input">
    <!-- Скрытый label для доступности -->
    <label v-if="label" :for="inputId" class="sr-only">{{ label }}</label>
    
    <div class="address-input__container">
      <div class="address-input__field-wrapper">
        <input
          :id="inputId"
          v-model="localValue"
          type="text"
          :name="name"
          :placeholder="placeholder"
          :disabled="disabled || loading"
          :aria-label="label || placeholder"
          :aria-invalid="!!error"
          :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
          class="address-input__field"
          :class="{ 'pr-10': localValue && showClearButton && !showSearchButton }"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
          @keyup.enter="handleSearch"
        >
        
        <!-- Кнопка очистки -->
        <button
          v-if="localValue && showClearButton && !showSearchButton"
          type="button"
          class="address-input__clear"
          @click="clearInput"
          :disabled="disabled"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <!-- Кнопка поиска -->
      <button
        v-if="showSearchButton"
        type="button"
        class="address-input__search"
        :class="{ 'address-input__search--loading': loading }"
        @click="handleSearch"
        :disabled="disabled || loading || !localValue.trim()"
      >
        <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span v-else>Найти</span>
      </button>
    </div>
    
    <!-- Подсказка под полем -->
    <p v-if="hint" :id="`${inputId}-hint`" class="address-input__hint">
      {{ hint }}
    </p>
    
    <!-- Ошибка -->
    <p v-if="error" :id="`${inputId}-error`" class="address-input__error" role="alert">
      {{ error }}
    </p>
    
    <!-- Статус поиска -->
    <p v-if="searchStatus" class="address-input__status" :class="searchStatusClass">
      {{ searchStatus }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useId } from '@/src/shared/composables/useId'

interface Props {
  modelValue?: string
  placeholder?: string
  hint?: string
  error?: string
  disabled?: boolean
  showClearButton?: boolean
  showSearchButton?: boolean
  loading?: boolean
  name?: string
  label?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'Начните вводить адрес',
  hint: '',
  error: '',
  disabled: false,
  showClearButton: true,
  showSearchButton: false,
  loading: false,
  name: 'address',
  label: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'focus': []
  'blur': []
  'clear': []
  'search': [value: string]
}>()

// Локальные данные
const localValue = ref(props.modelValue)
const searchStatus = ref('')
const inputId = useId('address-input')

// Вычисляемые свойства
const searchStatusClass = computed(() => {
  if (searchStatus.value.includes('найден')) {
    return 'address-input__status--success'
  }
  if (searchStatus.value.includes('не найден') || searchStatus.value.includes('ошибка')) {
    return 'address-input__status--error'
  }
  return 'address-input__status--info'
})

// Следим за изменениями props
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
})

// Следим за ошибками
watch(() => props.error, (newError) => {
  if (newError) {
    searchStatus.value = ''
  }
})

// Обработчики
const handleInput = () => {
  searchStatus.value = ''
  emit('update:modelValue', localValue.value)
}

const handleFocus = () => {
  emit('focus')
}

const handleBlur = () => {
  emit('blur')
}

const clearInput = () => {
  localValue.value = ''
  searchStatus.value = ''
  emit('update:modelValue', '')
  emit('clear')
}

const handleSearch = () => {
  if (!localValue.value.trim() || props.loading) return
  
  searchStatus.value = ''
  emit('search', localValue.value.trim())
}

// Методы для внешнего управления статусом
const setSearchStatus = (status: string) => {
  searchStatus.value = status
}

const clearStatus = () => {
  searchStatus.value = ''
}

// Экспорт методов
defineExpose({
  setSearchStatus,
  clearStatus
})
</script>

<style scoped>
.address-input {
  width: 100%;
}

.address-input__container {
  display: flex;
  gap: 12px;
}

.address-input__field-wrapper {
  flex: 1;
  position: relative;
}

.address-input__field {
  width: 100%;
  padding: 12px 16px;
  font-size: 16px;
  line-height: 1.5;
  color: #1a1a1a;
  background-color: #fff;
  border: 1px solid #d6d6d6;
  border-radius: 8px;
  transition: all 0.2s ease;
  outline: none;
}

.address-input__field:hover:not(:disabled) {
  border-color: #b3b3b3;
}

.address-input__field:focus:not(:disabled) {
  border-color: #0066ff;
  box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
}

.address-input__field:disabled {
  background-color: #f5f5f5;
  color: #999;
  cursor: not-allowed;
}

.address-input__clear {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  padding: 4px;
  color: #999;
  background: transparent;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.address-input__clear:hover:not(:disabled) {
  color: #666;
  background-color: #f0f0f0;
}

.address-input__clear:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.address-input__search {
  padding: 12px 24px;
  font-size: 16px;
  font-weight: 500;
  color: white;
  background-color: #0066ff;
  border: 1px solid #0066ff;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  min-width: 80px;
}

.address-input__search:hover:not(:disabled) {
  background-color: #0052cc;
  border-color: #0052cc;
}

.address-input__search:disabled {
  background-color: #cccccc;
  border-color: #cccccc;
  cursor: not-allowed;
}

.address-input__search--loading {
  background-color: #0052cc;
  border-color: #0052cc;
}

.address-input__hint {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.4;
  color: #666;
}

.address-input__error {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.4;
  color: #ff3333;
}

.address-input__status {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.4;
}

.address-input__status--success {
  color: #00b894;
}

.address-input__status--error {
  color: #ff3333;
}

.address-input__status--info {
  color: #666;
}

/* Класс для скрытия label визуально, но оставляя доступным для скринридеров */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
  .address-input__container {
    flex-direction: column;
    gap: 8px;
  }
  
  .address-input__search {
    width: 100%;
  }
}
</style>