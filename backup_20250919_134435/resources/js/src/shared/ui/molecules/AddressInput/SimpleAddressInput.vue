<template>
  <div class="simple-address-input">
    <input
      v-model="localValue"
      type="text"
      :placeholder="placeholder"
      @input="handleInput"
      @blur="hideSuggestions"
      class="input"
    />
    
    <div v-if="showSuggestions && suggestions.length > 0" class="suggestions">
      <div
        v-for="(suggestion, index) in suggestions"
        :key="index"
        @mousedown="selectSuggestion(suggestion)"
        class="suggestion-item"
      >
        {{ suggestion }}
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

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'Введите адрес'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const localValue = ref(props.modelValue)
const suggestions = ref<string[]>([])
const showSuggestions = ref(false)
let searchTimeout: NodeJS.Timeout | null = null

// Поиск адресов через Яндекс geocode
async function searchAddress(query: string) {
  if (!window.ymaps || !query || query.length < 2) {
    return []
  }
  
  try {
    // Используем geocode API который работает из браузера
    const result = await window.ymaps.geocode(query, {
      results: 5
    })
    
    const items: string[] = []
    const geoObjects = result.geoObjects
    
    for (let i = 0; i < geoObjects.getLength(); i++) {
      const obj = geoObjects.get(i)
      
      // Получаем полный адрес
      const address = obj.getAddressLine()
      
      // Добавляем только если это похоже на адрес
      if (address && !address.includes('\\') && !address.includes('C:') && !address.includes('/')) {
        items.push(address)
      }
    }
    
    return items
  } catch (error) {
    console.error('Ошибка geocode:', error)
    return []
  }
}

// Обработка ввода
function handleInput() {
  emit('update:modelValue', localValue.value)
  
  // Отменяем предыдущий поиск
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  // Задержка перед поиском
  searchTimeout = setTimeout(async () => {
    if (localValue.value.length >= 2) {
      suggestions.value = await searchAddress(localValue.value)
      showSuggestions.value = suggestions.value.length > 0
    } else {
      suggestions.value = []
      showSuggestions.value = false
    }
  }, 300)
}

// Выбор подсказки
function selectSuggestion(suggestion: string) {
  localValue.value = suggestion
  emit('update:modelValue', suggestion)
  suggestions.value = []
  showSuggestions.value = false
}

// Скрытие подсказок
function hideSuggestions() {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

// Синхронизация с внешним значением
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
})
</script>

<style scoped>
.simple-address-input {
  position: relative;
  width: 100%;
}

.input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-top: none;
  border-radius: 0 0 4px 4px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 1000;
}

.suggestion-item {
  padding: 8px 12px;
  cursor: pointer;
  font-size: 14px;
}

.suggestion-item:hover {
  background: #f5f5f5;
}
</style>