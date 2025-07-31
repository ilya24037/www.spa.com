<template>
  <FormField
    label="Дополнительные особенности"
    hint="Опишите другие ваши особенности"
    :error="error"
  >
    <BaseTextarea
      v-model="localValue"
      :rows="3"
      placeholder="Опишите дополнительные особенности..."
      :maxlength="500"
      @input="handleInput"
    />
    
    <!-- Подсказки -->
    <Card variant="bordered" class="mt-3 bg-blue-50 border-blue-200 p-3">
      <p class="text-sm font-medium text-blue-800 mb-2">💡 Примеры дополнительных особенностей:</p>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="suggestion in suggestions"
          :key="suggestion"
          type="button"
          @click="addSuggestion(suggestion)"
          class="px-2 py-1 text-xs bg-white border border-blue-200 rounded hover:border-blue-400 hover:bg-blue-50 transition-colors"
        >
          {{ suggestion }}
        </button>
      </div>
    </Card>
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseTextarea from '@/Components/UI/BaseTextarea.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue || '')

watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue || ''
})

// Подсказки
const suggestions = [
  'Опыт работы в спа-центрах',
  'Знание анатомии',
  'Сертифицированный массажист',
  'Работа с беременными',
  'Спортивный массаж',
  'Лимфодренаж',
  'Точечный массаж',
  'Антицеллюлитный массаж'
]

// Методы
const handleInput = (value) => {
  localValue.value = value
  emit('update:modelValue', value)
}

const addSuggestion = (suggestion) => {
  if (localValue.value.trim()) {
    if (!localValue.value.includes(suggestion)) {
      localValue.value += ', ' + suggestion
    }
  } else {
    localValue.value = suggestion
  }
  
  emit('update:modelValue', localValue.value)
}
</script>