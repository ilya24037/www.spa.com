<template>
  <FormField
    label="Способы оплаты"
    hint="Выберите все подходящие варианты"
    :error="error"
  >
    <!-- Используем готовый CheckboxGroup вместо кастомных чекбоксов -->
    <CheckboxGroup 
      v-model="localValue"
      :options="paymentOptions"
      direction="column"
    />

    <!-- Быстрые наборы -->
    <div class="mt-4">
      <p class="text-sm text-gray-600 mb-3">Быстрые наборы:</p>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <button
          v-for="set in paymentSets"
          :key="set.name"
          @click="applyPaymentSet(set)"
          type="button"
          class="p-2 text-sm border border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors"
        >
          {{ set.name }}
        </button>
      </div>
    </div>
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref([...props.modelValue])

watch(() => props.modelValue, (newValue) => {
  const normalizedNew = Array.isArray(newValue) ? newValue : []
  if (JSON.stringify(normalizedNew.sort()) !== JSON.stringify(localValue.value.sort())) {
    localValue.value = [...normalizedNew]
  }
}, { deep: true })

watch(localValue, (newValue) => {
  const normalizedProps = Array.isArray(props.modelValue) ? props.modelValue : []
  if (JSON.stringify(newValue.sort()) !== JSON.stringify(normalizedProps.sort())) {
    emit('update:modelValue', [...newValue])
  }
}, { deep: true })

// Опции способов оплаты для CheckboxGroup
const paymentOptions = [
  { value: 'cash', label: 'Наличные 💵', description: 'Оплата наличными при встрече' },
  { value: 'card', label: 'Банковская карта 💳', description: 'Оплата картой через терминал' },
  { value: 'sbp', label: 'СБП (QR-код) 📱', description: 'Быстрые платежи по QR-коду' },
  { value: 'transfer', label: 'Банковский перевод 🏦', description: 'Перевод на карту или счет' },
  { value: 'yandex_money', label: 'ЮMoney 🟡', description: 'Оплата через ЮMoney' },
  { value: 'qiwi', label: 'QIWI 🟠', description: 'Оплата через QIWI кошелек' }
]

// Быстрые наборы
const paymentSets = [
  { name: 'Базовый', values: ['cash', 'sbp'] },
  { name: 'Стандарт', values: ['cash', 'card', 'sbp'] },
  { name: 'Максимум', values: ['cash', 'card', 'sbp', 'transfer'] },
  { name: 'Безнал', values: ['card', 'sbp', 'transfer'] }
]

const applyPaymentSet = (set) => {
  localValue.value = [...set.values]
}
</script>