<!-- resources/js/src/entities/ad/ui/AdForm/components/AdFormLocationInfo.vue -->
<template>
  <div :class="SECTION_CLASSES">
    <h3 :class="TITLE_CLASSES">
      Локация и контакты
    </h3>
    <p :class="SUBTITLE_CLASSES">
      Местоположение, география услуг и контактная информация
    </p>
    
    <div :class="FIELDS_CONTAINER_CLASSES">
      <!-- Место оказания услуг -->
      <AdFormServiceLocation :errors="errors" />
      
      <!-- География -->
      <AdFormGeography :errors="errors" />
      
      <!-- Контакты -->
      <AdFormContacts :errors="errors" />
    </div>
  </div>
</template>

<script setup>
// Модули локации и контактов
import AdFormServiceLocation from './AdFormServiceLocation.vue'
import AdFormGeography from './AdFormGeography.vue'
import AdFormContacts from './AdFormContacts.vue'

// 🎯 Стили согласно дизайн-системе
const SECTION_CLASSES = 'space-y-6'
const TITLE_CLASSES = 'text-xl font-semibold text-gray-500'
const SUBTITLE_CLASSES = 'text-sm text-gray-500'
const FIELDS_CONTAINER_CLASSES = 'space-y-6'

defineProps({
    errors: {
        type: Object,
        default: () => ({})
    }
})
</script>