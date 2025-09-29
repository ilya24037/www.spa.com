<template>
  <div class="yandex-address-input">
    <div class="input-wrapper">
      <input 
        ref="inputRef"
        type="text"
        :value="modelValue"
        :placeholder="placeholder"
        @input="handleInput"
        @focus="showSuggestions = true"
        @blur="hideSuggestions"
        class="input"
      />
      
      <!-- Индикатор загрузки -->
      <div v-if="loading" class="loading-indicator">
        <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>

    <!-- Выпадающий список подсказок -->
    <div v-if="showSuggestions && suggestions.length > 0" class="suggestions-dropdown">
      <div
        v-for="(suggestion, index) in suggestions"
        :key="index"
        @mousedown.prevent="selectSuggestion(suggestion)"
        class="suggestion-item"
      >
        <svg class="suggestion-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="suggestion-text">{{ suggestion.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  modelValue?: string
  placeholder?: string
}

interface Suggestion {
  value: string
  label: string
  coordinates?: {
    lat: number
    lng: number
  }
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'Начните вводить адрес'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'coordinates-selected': [coordinates: { lat: number, lng: number }]
}>()

// Состояние компонента
const inputRef = ref<HTMLInputElement>()
const suggestions = ref<Suggestion[]>([])
const showSuggestions = ref(false)
const loading = ref(false)
let searchAbortController: AbortController | null = null

// Обработка ввода
const handleInput = (event: Event) => {
  const value = (event.target as HTMLInputElement).value
  emit('update:modelValue', value)
  
  // Если пустая строка - очищаем подсказки
  if (!value || value.length < 2) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }
  
  // Запускаем поиск с задержкой
  debouncedSearch(value)
}

// Функция поиска с debounce (300мс задержка)
const debouncedSearch = debounce(async (query: string) => {
  // Отменяем предыдущий запрос
  if (searchAbortController) {
    searchAbortController.abort()
  }
  
  // Создаем новый контроллер для отмены запроса
  searchAbortController = new AbortController()
  
  loading.value = true
  
  try {
    const response = await fetch(`/api/yandex/geocode/suggest?text=${encodeURIComponent(query)}&limit=5`, {
      signal: searchAbortController.signal,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (!response.ok) {
      throw new Error('Network response was not ok')
    }
    
    const data = await response.json()
    
    if (data.suggestions && Array.isArray(data.suggestions)) {
      suggestions.value = data.suggestions
      showSuggestions.value = true
    } else {
      suggestions.value = []
    }
    
  } catch (error: any) {
    // Игнорируем ошибки отмены запроса
    if (error.name !== 'AbortError') {
      console.error('Ошибка получения подсказок:', error)
      suggestions.value = []
    }
  } finally {
    loading.value = false
  }
}, 300)

// Выбор подсказки
const selectSuggestion = (suggestion: Suggestion) => {
  emit('update:modelValue', suggestion.value)
  
  // Если есть координаты, отправляем их
  if (suggestion.coordinates) {
    emit('coordinates-selected', suggestion.coordinates)
  }
  
  suggestions.value = []
  showSuggestions.value = false
}

// Скрытие подсказок с задержкой (чтобы успеть кликнуть)
const hideSuggestions = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

// Синхронизация с внешним значением
watch(() => props.modelValue, (newValue) => {
  if (inputRef.value && inputRef.value.value !== newValue) {
    inputRef.value.value = newValue || ''
  }
})

// Утилита debounce (если нет в проекте, создаем здесь)
function debounce<T extends (...args: any[]) => any>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void {
  let timeout: NodeJS.Timeout | null = null
  
  return function executedFunction(...args: Parameters<T>) {
    const later = () => {
      timeout = null
      func(...args)
    }
    
    if (timeout) {
      clearTimeout(timeout)
    }
    
    timeout = setTimeout(later, wait)
  }
}
</script>

<style scoped>
.yandex-address-input {
  position: relative;
  width: 100%;
}

.input-wrapper {
  position: relative;
  width: 100%;
}

.input {
  width: 100%;
  padding: 12px 16px;
  padding-right: 40px; /* Место для индикатора загрузки */
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 16px;
  line-height: 24px;
  transition: all 0.2s ease;
  background-color: white;
}

.input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.input:hover {
  border-color: #d1d5db;
}

.input::placeholder {
  color: #9ca3af;
}

/* Индикатор загрузки */
.loading-indicator {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

/* Выпадающий список подсказок */
.suggestions-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  max-height: 240px;
  overflow-y: auto;
  z-index: 1000;
}

.suggestion-item {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  cursor: pointer;
  transition: background-color 0.15s ease;
  border-bottom: 1px solid #f3f4f6;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-item:hover {
  background-color: #f9fafb;
}

.suggestion-icon {
  width: 16px;
  height: 16px;
  margin-right: 10px;
  color: #6b7280;
  flex-shrink: 0;
}

.suggestion-text {
  font-size: 14px;
  color: #111827;
  line-height: 1.4;
}

/* Анимация появления */
.suggestions-dropdown {
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Анимация вращения для индикатора загрузки */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>