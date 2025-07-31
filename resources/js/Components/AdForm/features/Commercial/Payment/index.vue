<template>
  <FormSection
    title="Способы оплаты"
    hint="Укажите удобные для вас способы оплаты услуг"
    :error="errors.payment_methods"
  >
    <div class="space-y-6">
      <!-- Основные способы оплаты -->
      <PaymentGrid 
        :model-value="selectedMethods"
        @update:model-value="updateSelectedMethods"
        :error="errors.payment_methods"
      />

      <!-- Настройки предоплаты -->
      <PrepaymentSettings
        :type="prepaymentType"
        :amount="prepaymentAmount"
        :note="prepaymentNote"
        @update:type="updatePrepaymentType"
        @update:amount="updatePrepaymentAmount"
        @update:note="updatePrepaymentNote"
      />

      <!-- Предварительный просмотр -->
      <PaymentPreview
        :selected-methods="selectedMethods"
        :prepayment="{
          type: prepaymentType,
          amount: prepaymentAmount,
          note: prepaymentNote
        }"
      />

      <!-- Советы по оплате -->
      <PaymentTips />
    </div>
  </FormSection>
</template>

<script setup>
import { computed, ref } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Микрокомпоненты
import PaymentGrid from './components/PaymentGrid.vue'
import PrepaymentSettings from './components/PrepaymentSettings.vue'
import PaymentPreview from './components/PaymentPreview.vue'
import PaymentTips from './components/PaymentTips.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: { 
    type: Object, 
    default: () => ({}) 
  }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const paymentMethods = computed(() => store.formData.payment_methods || [])
const selectedMethods = computed(() => {
  const methods = paymentMethods.value
  if (Array.isArray(methods)) return methods
  if (typeof methods === 'object' && methods && methods.methods) {
    return Array.isArray(methods.methods) ? methods.methods : []
  }
  return []
})
const prepaymentType = computed(() => {
  const methods = paymentMethods.value
  return methods?.prepayment?.type || 'none'
})
const prepaymentAmount = computed(() => {
  const methods = paymentMethods.value
  return methods?.prepayment?.amount || '30'
})
const prepaymentNote = computed(() => {
  const methods = paymentMethods.value
  return methods?.prepayment?.note || ''
})

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateSelectedMethods = (methods) => {
  console.log('updateSelectedMethods called:', methods)
  const currentData = paymentMethods.value || {}
  const newData = {
    ...currentData,
    methods: Array.isArray(methods) ? methods : []
  }
  store.updateField('payment_methods', newData)
}

const updatePrepaymentType = (type) => {
  console.log('updatePrepaymentType called:', type)
  const currentData = paymentMethods.value || {}
  const newData = {
    ...currentData,
    prepayment: {
      ...(currentData.prepayment || {}),
      type: type
    }
  }
  store.updateField('payment_methods', newData)
}

const updatePrepaymentAmount = (amount) => {
  console.log('updatePrepaymentAmount called:', amount)
  const currentData = paymentMethods.value || {}
  const newData = {
    ...currentData,
    prepayment: {
      ...(currentData.prepayment || {}),
      amount: amount
    }
  }
  store.updateField('payment_methods', newData)
}

const updatePrepaymentNote = (note) => {
  console.log('updatePrepaymentNote called:', note)
  const currentData = paymentMethods.value || {}
  const newData = {
    ...currentData,
    prepayment: {
      ...(currentData.prepayment || {}),
      note: note
    }
  }
  store.updateField('payment_methods', newData)
}
</script>

<style scoped>
/* Все стили заменены на Tailwind классы */
</style>