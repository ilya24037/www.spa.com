<template>
  <div :class="SECTION_CLASSES" :id="sectionId">
    <div v-if="title" :class="HEADER_CLASSES">
      <h2 :class="TITLE_CLASSES">{{ title }}</h2>
      <div v-if="subtitle" :class="SUBTITLE_CLASSES">{{ subtitle }}</div>
    </div>
    
    <div :class="CONTENT_CLASSES">
      <slot />
    </div>
    
    <div v-if="hasActions" :class="ACTIONS_CLASSES">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// 🎯 Стили в соответствии с Tailwind CSS
const SECTION_CLASSES = 'mb-10 py-6 last:mb-0'
const HEADER_CLASSES = 'mb-6'
const TITLE_CLASSES = 'text-xl font-semibold text-gray-900 mb-2'
const SUBTITLE_CLASSES = 'text-sm text-gray-600'
const CONTENT_CLASSES = ''
const ACTIONS_CLASSES = 'mt-5 flex gap-3 items-center'

const props = defineProps({
  title: {
    type: String,
    default: null
  },
  subtitle: {
    type: String,
    default: null
  },
  id: {
    type: String,
    default: null
  }
})

const sectionId = computed(() => {
  return props.id || (props.title ? props.title.toLowerCase().replace(/\s+/g, '-') : null)
})

const hasActions = computed(() => {
  // В Composition API проверяем наличие слотов через $slots
  return !!getCurrentInstance()?.slots.actions
})

// Получаем экземпляр компонента для доступа к слотам
import { getCurrentInstance } from 'vue'
</script>