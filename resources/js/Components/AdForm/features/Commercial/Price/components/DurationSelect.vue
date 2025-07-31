<template>
  <FormField
    label="Минимальное время"
    hint="Минимальная продолжительность сеанса"
    :error="error"
  >
    <BaseSelect
      v-model="localValue"
      :options="durationOptions"
      placeholder="Выберите время"
      @update:modelValue="handleChange"
    />
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(String(props.modelValue || ''))

watch(() => props.modelValue, (newValue) => {
  localValue.value = String(newValue || '')
})

// Опции продолжительности
const durationOptions = [
  { value: '30', label: '30 минут' },
  { value: '60', label: '1 час' },
  { value: '90', label: '1.5 часа' },
  { value: '120', label: '2 часа' },
  { value: '180', label: '3 часа' }
]

// Методы
const handleChange = (value) => {
  localValue.value = value
  emit('update:modelValue', value)
}
</script>