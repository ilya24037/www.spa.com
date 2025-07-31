<template>
  <FormField
    label="Номер телефона"
    hint="Основной номер для связи с клиентами"
    required
    :error="error"
  >
    <!-- Используем BaseInput вместо кастомного инпута -->
    <BaseInput
      v-model="localValue"
      type="tel"
      placeholder="(999) 123-45-67"
      prefix="+7"
      @keypress="handleKeypress"
      :class="{ 
        'border-green-500': validation.isValid && localValue,
        'border-red-500': !validation.isValid && localValue 
      }"
    />
    
    <!-- Валидация телефона -->
    <div v-if="validation.message && localValue" 
         :class="[
           'flex items-center gap-2 mt-2 text-sm',
           { 'text-green-600': validation.isValid, 'text-red-600': !validation.isValid }
         ]">
      <span>{{ validation.isValid ? '✓' : '⚠️' }}</span>
      <span>{{ validation.message }}</span>
    </div>
  </FormField>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
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

// Валидация телефона
const validation = computed(() => {
  if (!localValue.value) {
    return { isValid: false, message: 'Введите номер телефона' }
  }
  
  const cleanPhone = localValue.value.replace(/\D/g, '')
  
  if (cleanPhone.length < 10) {
    return { isValid: false, message: 'Номер слишком короткий' }
  }
  
  if (cleanPhone.length > 11) {
    return { isValid: false, message: 'Номер слишком длинный' }
  }
  
  if (cleanPhone.length === 10 && !cleanPhone.startsWith('9')) {
    return { isValid: false, message: 'Номер должен начинаться с 9' }
  }
  
  if (cleanPhone.length === 11 && !cleanPhone.startsWith('7')) {
    return { isValid: false, message: 'Номер должен начинаться с 7' }
  }
  
  return { isValid: true, message: 'Номер корректный' }
})

// Единый watcher для форматирования и отправки изменений
watch(localValue, (newValue) => {
  let cleanValue = newValue.replace(/\D/g, '')
  
  // Ограничиваем длину
  if (cleanValue.length > 10) {
    cleanValue = cleanValue.substring(0, 10)
  }
  
  // Форматируем номер
  let formatted = cleanValue
  if (cleanValue.length >= 3) {
    formatted = `(${cleanValue.substring(0, 3)}) ${cleanValue.substring(3)}`
  }
  if (formatted.length >= 9) {
    formatted = `${formatted.substring(0, 9)}-${formatted.substring(9)}`
  }
  if (formatted.length >= 12) {
    formatted = `${formatted.substring(0, 12)}-${formatted.substring(12)}`
  }
  
  // Если нужно переформатировать, делаем это без повторного вызова watcher
  if (formatted !== newValue) {
    localValue.value = formatted
    return
  }
  
  // Отправляем изменения родителю только если значение реально изменилось
  emit('update:modelValue', newValue)
})

const handleKeypress = (event) => {
  // Разрешаем только цифры и служебные клавиши
  if (!/\d/.test(event.key) && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(event.key)) {
    event.preventDefault()
  }
}
</script>