<!-- resources/js/src/entities/ad/ui/AdForm/components/AdFormCommercialInfo.vue -->
<template>
  <div :class="SECTION_CLASSES">
    <h3 :class="TITLE_CLASSES">Коммерческая информация</h3>
    <p :class="SUBTITLE_CLASSES">Цены, услуги, график работы и условия оплаты</p>
    
    <div :class="FIELDS_CONTAINER_CLASSES">
      <!-- Цены -->
      <AdFormPrice :errors="errors" />
      
      <!-- Акции и скидки -->
      <AdFormPromo :errors="errors" />
      
      <!-- Услуги -->
      <AdFormServices :errors="errors" />
      
      <!-- График работы -->
      <AdFormSchedule :errors="errors" />
      
      <!-- Условия оплаты -->
      <AdFormPayment :errors="errors" />
    </div>
  </div>
</template>

<script setup>
// Модули коммерческой информации
import AdFormPrice from './AdFormPrice.vue'
import AdFormPromo from './AdFormPromo.vue'
import AdFormServices from './AdFormServices.vue'
import AdFormSchedule from './AdFormSchedule.vue'
import AdFormPayment from './AdFormPayment.vue'

// 🎯 Стили согласно дизайн-системе
const SECTION_CLASSES = 'space-y-6'
const TITLE_CLASSES = 'text-xl font-semibold text-gray-900'
const SUBTITLE_CLASSES = 'text-sm text-gray-600'
const FIELDS_CONTAINER_CLASSES = 'space-y-6'

defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
})
</script>