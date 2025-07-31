<template>
  <FormField
    label="Цена за выезд"
    hint="Дополнительная плата за выезд к клиенту"
    :error="error"
  >
    <div class="space-y-3">
      <!-- Поле ввода -->
      <BaseInput
        v-model="localValue"
        type="number"
        placeholder="500"
        suffix="₽"
        min="0"
        max="10000"
        step="100"
        class="w-36"

      />
      
      <!-- Быстрые варианты -->
      <div class="flex flex-wrap gap-2">
        <button
          v-for="price in quickPrices"
          :key="price"
          type="button"
          @click="setQuickPrice(price)"
          class="px-2 py-1 text-xs bg-gray-100 border border-gray-200 rounded hover:bg-gray-200 transition-colors"
        >
          {{ price === 0 ? 'Бесплатно' : price + ' ₽' }}
        </button>
      </div>

      <!-- Подсказка -->
      <p class="text-xs text-gray-500">
        💡 Средняя плата за выезд: 300-800 ₽
      </p>
    </div>
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
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

// Watch для отправки изменений родителю
watch(localValue, (newValue) => {
  emit('update:modelValue', newValue)
})

// Быстрые варианты цен за выезд
const quickPrices = [0, 300, 500, 800, 1000]

// Методы
const setQuickPrice = (price) => {
  localValue.value = String(price)
  emit('update:modelValue', String(price))
}
</script>