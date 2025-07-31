<template>
  <FormField
    label="Скидка новым клиентам"
    hint="Процент скидки для первого визита"
    :error="error"
  >
    <div class="space-y-4">
      <!-- Поле ввода с суффиксом -->
      <BaseInput
        v-model="localValue"
        type="number"
        placeholder="10"
        suffix="%"
        min="0"
        max="50"
        step="5"
        class="w-48"

      />
      
      <!-- Быстрые скидки -->
      <div class="space-y-2">
        <p class="text-sm font-medium text-gray-700">Популярные скидки:</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="discount in quickDiscounts"
            :key="discount"
            type="button"
            @click="setQuickDiscount(discount)"
            :class="[
              'px-3 py-2 text-sm border rounded-lg transition-all duration-200',
              localValue == discount
                ? 'bg-blue-500 border-blue-500 text-white'
                : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100 hover:border-gray-300'
            ]"
          >
            {{ discount }}%
          </button>
        </div>
      </div>
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

// Быстрые скидки
const quickDiscounts = [5, 10, 15, 20, 25]

// Методы
const setQuickDiscount = (discount) => {
  localValue.value = String(discount)
  emit('update:modelValue', String(discount))
}
</script>