<template>
  <FormField
    label="Дополнительная информация"
    hint="Особенности расписания, перерывы, праздники"
    :error="error"
  >
    <!-- Используем готовый BaseTextarea вместо кастомного -->
    <BaseTextarea
      v-model="localValue"
      :rows="3"
      placeholder="Например: обеденный перерыв с 13:00 до 14:00, не работаю в праздники..."
      :maxlength="300"
      @input="handleInput"
    />
    
    <!-- Счетчик символов -->
    <div class="flex justify-end mt-2">
      <span class="text-xs text-gray-500">
        {{ localValue.length }}/300
      </span>
    </div>
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseTextarea from '@/Components/UI/BaseTextarea.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue)

watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue || ''
})

// Методы
const handleInput = (value) => {
  localValue.value = value
  emit('update:modelValue', value)
}
</script>