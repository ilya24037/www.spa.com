<!-- resources/js/src/entities/ad/ui/AdForm/components/AdFormPersonalInfo.vue -->
<template>
  <div :class="SECTION_CLASSES">
    <h3 :class="TITLE_CLASSES">Персональная информация</h3>
    <p :class="SUBTITLE_CLASSES">Информация о мастере и особенностях услуг</p>
    
    <div :class="FIELDS_CONTAINER_CLASSES">
      <!-- Параметры мастера -->
      <AdFormParameters :errors="errors" />
      
      <!-- Особенности и дополнительные услуги -->
      <AdFormFeatures :errors="errors" />
    </div>
  </div>
</template>

<script setup>
// Модули персональной информации
import AdFormParameters from './AdFormParameters.vue'
import AdFormFeatures from './AdFormFeatures.vue'

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