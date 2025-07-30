<template>
  <div class="payment-methods-section">
    <h2 class="form-group-title">Способы оплаты</h2>
    <CheckboxGroup 
      v-model="localPaymentMethods"
      :options="paymentOptions"
      @update:modelValue="emitPaymentMethods"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

const props = defineProps({
  paymentMethods: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:paymentMethods'])

console.log('PaymentMethodsSection INIT: props.paymentMethods =', props.paymentMethods)
const localPaymentMethods = ref(Array.isArray(props.paymentMethods) ? [...props.paymentMethods] : ['cash'])
console.log('PaymentMethodsSection INIT: localPaymentMethods =', localPaymentMethods.value)

watch(() => props.paymentMethods, (val) => {
  console.log('PaymentMethodsSection WATCH: new props.paymentMethods =', val)
  localPaymentMethods.value = Array.isArray(val) ? [...val] : ['cash']
  console.log('PaymentMethodsSection WATCH: updated localPaymentMethods =', localPaymentMethods.value)
})

// Опции для CheckboxGroup
const paymentOptions = computed(() => [
  { value: 'cash', label: 'Наличные' },
  { value: 'transfer', label: 'Перевод' }
])

const emitPaymentMethods = () => {
  console.log('PaymentMethodsSection EMIT: emitting', [...localPaymentMethods.value])
  emit('update:paymentMethods', [...localPaymentMethods.value])
}
</script>

<style scoped>
.payment-methods-section {
  margin-bottom: 24px;
}

.form-group-title {
  font-size: 20px;
  font-weight: 500;
  color: #000000;
  margin: 0 0 20px 0;
  line-height: 1.3;
}
</style>