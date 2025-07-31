<template>
  <FormField
    label="Цена за час"
    hint="Основная стоимость услуги (влияет на позицию в поиске)"
    :error="error"
    required
  >
    <div class="space-y-4">
      <!-- Поле ввода с суффиксом -->
      <BaseInput
        v-model="localValue"
        type="number"
        placeholder="3000"
        suffix="₽/час"
        min="500"
        max="50000"
        step="500"
        class="w-48"

      />
      
      <!-- Быстрые цены -->
      <div class="space-y-2">
        <p class="text-sm font-medium text-gray-700">Популярные цены:</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="price in quickPrices"
            :key="price"
            type="button"
            @click="setQuickPrice(price)"
            :class="[
              'px-3 py-2 text-sm border rounded-lg transition-all duration-200',
              localValue == price
                ? 'bg-blue-500 border-blue-500 text-white'
                : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100 hover:border-gray-300'
            ]"
          >
            {{ formatPrice(price) }}
          </button>
        </div>
      </div>

      <!-- Индикатор конкурентоспособности -->
      <div v-if="localValue" class="flex items-center space-x-2 text-sm">
        <div :class="priceIndicator.class">
          {{ priceIndicator.icon }}
        </div>
        <span class="text-gray-600">{{ priceIndicator.text }}</span>
      </div>
    </div>
  </FormField>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
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

// Быстрые цены
const quickPrices = [2000, 2500, 3000, 3500, 4000, 5000, 6000]

// Индикатор конкурентоспособности
const priceIndicator = computed(() => {
  const price = parseInt(localValue.value)
  if (!price) return { icon: '', text: '', class: '' }
  
  if (price < 2500) {
    return { 
      icon: '💰', 
      text: 'Очень привлекательная цена', 
      class: 'text-green-600' 
    }
  } else if (price <= 4000) {
    return { 
      icon: '⚖️', 
      text: 'Средняя рыночная цена', 
      class: 'text-blue-600' 
    }
  } else {
    return { 
      icon: '👑', 
      text: 'Премиум сегмент', 
      class: 'text-purple-600' 
    }
  }
})

// Методы
const setQuickPrice = (price) => {
  localValue.value = String(price)
  emit('update:modelValue', String(price))
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
}
</script>