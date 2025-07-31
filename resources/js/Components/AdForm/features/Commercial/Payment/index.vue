<template>
  <FormSection
    title="Способы оплаты"
    hint="Укажите удобные для вас способы оплаты услуг"
    :error="errors.payment_methods"
  >
    <div class="space-y-6">
      <!-- Основные способы оплаты -->
      <PaymentGrid 
        v-model="selectedMethods"
        :error="errors.payment_methods"
      />

      <!-- Настройки предоплаты -->
      <PrepaymentSettings
        v-model:type="prepaymentType"
        v-model:amount="prepaymentAmount"
        v-model:note="prepaymentNote"
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
import { ref, watch, computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'

// Микрокомпоненты
import PaymentGrid from './components/PaymentGrid.vue'
import PrepaymentSettings from './components/PrepaymentSettings.vue'
import PaymentPreview from './components/PaymentPreview.vue'
import PaymentTips from './components/PaymentTips.vue'

const props = defineProps({
  paymentMethods: { 
    type: [Array, String, Object], 
    default: () => [] 
  },
  errors: { 
    type: Object, 
    default: () => ({}) 
  }
})

const emit = defineEmits(['update:paymentMethods'])

// Локальное состояние
const selectedMethods = ref([])
const prepaymentType = ref('none')
const prepaymentAmount = ref('30')
const prepaymentNote = ref('')

// Инициализация данных
const initializeData = () => {
  let methods = props.paymentMethods
  
  // Обработка разных форматов данных
  if (typeof methods === 'string') {
    try {
      const parsed = JSON.parse(methods)
      methods = parsed.methods || parsed || []
    } catch (e) {
      methods = []
    }
  } else if (methods && typeof methods === 'object' && !Array.isArray(methods)) {
    // Объект с methods и prepayment
    selectedMethods.value = Array.isArray(methods.methods) ? [...methods.methods] : []
    if (methods.prepayment) {
      prepaymentType.value = methods.prepayment.type || 'none'
      prepaymentAmount.value = methods.prepayment.amount || '30'
      prepaymentNote.value = methods.prepayment.note || ''
    }
    return
  }
  
  selectedMethods.value = Array.isArray(methods) ? [...methods] : []
}

// Отслеживание изменений пропсов
watch(() => props.paymentMethods, () => {
  initializeData()
}, { immediate: true })

// Отправка данных родителю
const updatePaymentMethods = () => {
  const data = {
    methods: [...selectedMethods.value],
    prepayment: {
      type: prepaymentType.value,
      amount: prepaymentAmount.value,
      note: prepaymentNote.value
    }
  }
  
  emit('update:paymentMethods', data)
}

// Отслеживание изменений локальных данных
watch([selectedMethods, prepaymentType, prepaymentAmount, prepaymentNote], () => {
  updatePaymentMethods()
}, { deep: true })
</script>

<style scoped>
/* Все стили заменены на Tailwind классы */
</style>