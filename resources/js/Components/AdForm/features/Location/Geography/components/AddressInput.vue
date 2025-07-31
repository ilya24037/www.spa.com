<template>
  <FormField
    label="Адрес или район"
    hint="Начните вводить адрес, и мы подскажем варианты"
    :error="error"
  >
    <!-- Используем BaseInput вместо кастомного инпута -->
    <div class="relative">
      <BaseInput
        v-model="localValue"
        type="text"
        placeholder="Например: Москва, Тверская улица, 1"
        @input="handleInput"
        @focus="showSuggestions = true"
        :suffix="localValue ? '✕' : ''"
        @suffix-click="clearAddress"
      />
      
      <!-- Подсказки адресов -->
      <Card 
        v-if="showSuggestions && filteredSuggestions.length > 0" 
        variant="elevated"
        class="absolute top-full left-0 right-0 z-10 max-h-48 overflow-y-auto"
      >
        <div
          v-for="(suggestion, index) in filteredSuggestions"
          :key="index"
          @click="selectSuggestion(suggestion)"
          class="flex items-center gap-3 p-3 cursor-pointer hover:bg-gray-50 transition-colors"
        >
          <span class="text-lg">📍</span>
          <div class="flex-1">
            <div class="font-medium text-gray-900">{{ suggestion.address }}</div>
            <div class="text-sm text-gray-500">{{ suggestion.details }}</div>
          </div>
        </div>
      </Card>
    </div>
  </FormField>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue', 'suggestion-selected'])

const localValue = ref(props.modelValue)
const showSuggestions = ref(false)

watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue || ''
})

// Подсказки адресов (заглушка - в реальности будет API)
const addressSuggestions = [
  { address: 'Москва, Тверская улица, 1', details: 'Центральный район, м. Охотный Ряд' },
  { address: 'Москва, Арбат, 10', details: 'Центральный район, м. Арбатская' },
  { address: 'Москва, Новый Арбат, 15', details: 'Центральный район, м. Арбатская' },
  { address: 'Москва, Красная площадь, 1', details: 'Центральный район, м. Охотный Ряд' },
  { address: 'Москва, Патриаршие пруды', details: 'Центральный район, м. Маяковская' }
]

// Фильтрация подсказок по введенному тексту
const filteredSuggestions = computed(() => {
  if (!localValue.value || localValue.value.length < 2) return []
  
  const query = localValue.value.toLowerCase()
  return addressSuggestions.filter(suggestion => 
    suggestion.address.toLowerCase().includes(query) ||
    suggestion.details.toLowerCase().includes(query)
  )
})

// Методы
const handleInput = (value) => {
  localValue.value = value
  showSuggestions.value = value.length > 2
  emit('update:modelValue', value)
}

const selectSuggestion = (suggestion) => {
  localValue.value = suggestion.address
  showSuggestions.value = false
  emit('update:modelValue', suggestion.address)
  emit('suggestion-selected', suggestion)
}

const clearAddress = () => {
  localValue.value = ''
  showSuggestions.value = false
  emit('update:modelValue', '')
}

// Скрытие подсказок при клике вне компонента
document.addEventListener('click', (e) => {
  if (!e.target.closest('.relative')) {
    showSuggestions.value = false
  }
})
</script>