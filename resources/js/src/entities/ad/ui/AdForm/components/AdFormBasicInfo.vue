<!-- resources/js/src/entities/ad/ui/AdForm/components/AdFormBasicInfo.vue -->
<template>
  <div :class="SECTION_CLASSES">
    <h3 :class="TITLE_CLASSES">Базовая информация</h3>
    <p :class="SUBTITLE_CLASSES">Укажите основные данные об услуге</p>
    
    <div :class="FIELDS_CONTAINER_CLASSES">
      <!-- Формат работы -->
      <AdFormWorkFormat :errors="errors" />
      
      <!-- Поставщик услуг -->
      <AdFormServiceProvider :errors="errors" />
      
      <!-- Тип клиентов -->
      <AdFormClientsType :errors="errors" />
      
      <!-- Описание -->
      <AdFormDescription :errors="errors" />
    </div>
  </div>
</template>

<script setup>
// Модули базовой информации
import AdFormWorkFormat from './AdFormWorkFormat.vue'
import AdFormServiceProvider from './AdFormServiceProvider.vue'
import AdFormClientsType from './AdFormClientsType.vue'
import AdFormDescription from './AdFormDescription.vue'

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