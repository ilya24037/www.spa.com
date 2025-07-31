<template>
  <Card v-if="hasSelectedMethods" variant="elevated" class="bg-green-50 border-green-200">
    <div class="flex items-center space-x-2 mb-3">
      <span class="text-lg">💳</span>
      <span class="text-sm font-medium text-green-800">
        Способы оплаты в объявлении:
      </span>
    </div>
    
    <!-- Выбранные способы -->
    <div class="flex flex-wrap gap-2 mb-3">
      <span
        v-for="method in selectedMethodsInfo"
        :key="method.value"
        class="inline-flex items-center space-x-1 px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full"
      >
        <span>{{ method.icon }}</span>
        <span>{{ method.title }}</span>
      </span>
    </div>
    
    <!-- Условия предоплаты -->
    <div v-if="prepayment.type !== 'none'" class="pt-3 border-t border-green-200">
      <div class="text-sm font-medium text-green-800 mb-1">
        Условия предоплаты:
      </div>
      <div class="text-sm text-green-700">
        {{ prepaymentText }}
      </div>
      <div v-if="prepayment.note" class="text-sm text-green-600 italic mt-1">
        {{ prepayment.note }}
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  selectedMethods: { type: Array, default: () => [] },
  prepayment: { 
    type: Object, 
    default: () => ({ type: 'none', amount: '30', note: '' }) 
  }
})

// Информация о способах оплаты
const paymentMethodsInfo = {
  cash: { title: 'Наличные', icon: '💵' },
  card: { title: 'Банковская карта', icon: '💳' },
  sbp: { title: 'СБП', icon: '📱' },
  transfer: { title: 'Перевод', icon: '🏦' },
  yandex_money: { title: 'ЮMoney', icon: '🟡' },
  qiwi: { title: 'QIWI', icon: '🟠' }
}

const hasSelectedMethods = computed(() => {
  return props.selectedMethods.length > 0
})

const selectedMethodsInfo = computed(() => {
  return props.selectedMethods.map(method => ({
    value: method,
    ...paymentMethodsInfo[method]
  }))
})

const prepaymentText = computed(() => {
  switch (props.prepayment.type) {
    case 'partial':
      return `Предоплата ${props.prepayment.amount}%, остальное после оказания услуги`
    case 'full':
      return 'Полная предоплата до начала сеанса'
    default:
      return ''
  }
})
</script>