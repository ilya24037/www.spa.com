<template>
  <FormField
    label="Уровень образования"
    hint="Укажите ваш уровень образования"
    :error="error"
  >
    <BaseSelect
      v-model="localValue"
      :options="educationOptions"
      placeholder="Выберите уровень"
      @update:modelValue="handleChange"
    />
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
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

// Опции образования
const educationOptions = [
  { value: '2', label: '📖 Среднее' },
  { value: '3', label: '🎓 Среднее специальное' },
  { value: '4', label: '📚 Неоконченное высшее' },
  { value: '5', label: '🎓 Высшее' },
  { value: '6', label: '🎓🎓 Несколько высших' },
  { value: '7', label: '🎓👨‍🔬 Кандидат наук' }
]

// Методы
const handleChange = (value) => {
  localValue.value = value
  emit('update:modelValue', value)
}
</script>