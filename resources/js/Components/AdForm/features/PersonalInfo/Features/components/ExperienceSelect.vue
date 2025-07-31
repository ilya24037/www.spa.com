<template>
  <FormField
    label="Опыт работы"
    hint="Укажите ваш опыт работы в сфере массажа"
    :error="error"
    required
  >
    <BaseSelect
      v-model="localValue"
      :options="experienceOptions"
      placeholder="Выберите опыт"
      @update:modelValue="handleChange"
    />
    
    <!-- Подсказка -->
    <div v-if="localValue" class="mt-2 flex items-center space-x-2 text-sm">
      <span class="text-blue-600">{{ experienceIcon }}</span>
      <span class="text-gray-600">{{ experienceHint }}</span>
    </div>
  </FormField>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue || '')

watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue || ''
})

// Опции опыта
const experienceOptions = [
  { value: '3260137', label: '🌱 Без опыта' },
  { value: '3260142', label: '📚 До 1 года' },
  { value: '3260146', label: '💼 1-3 года' },
  { value: '3260149', label: '🎯 3-6 лет' },
  { value: '3260152', label: '👑 Более 6 лет' }
]

// Computed подсказки
const experienceIcon = computed(() => {
  const option = experienceOptions.find(opt => opt.value === localValue.value)
  return option?.label.split(' ')[0] || ''
})

const experienceHint = computed(() => {
  switch (localValue.value) {
    case '3260137': return 'Отличный старт! Клиенты ценят свежий взгляд'
    case '3260142': return 'Активная фаза обучения и развития навыков'
    case '3260146': return 'Хороший опыт, доверие клиентов растет'
    case '3260149': return 'Серьезный профессионал с устоявшейся базой'
    case '3260152': return 'Эксперт в своем деле, премиум сегмент'
    default: return ''
  }
})

// Методы
const handleChange = (value) => {
  localValue.value = value
  emit('update:modelValue', value)
}
</script>