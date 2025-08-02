<!-- resources/js/src/shared/layouts/components/ContentCard.vue -->
<template>
  <component 
    :is="as"
    :class="[
      BASE_CLASSES,
      customClass
    ]"
  >
    <!-- Заголовок карточки (опционально) -->
    <div v-if="title || $slots.header" :class="['border-b', HEADER_PADDING]">
      <div class="flex items-center justify-between">
        <h2 v-if="title" :class="TITLE_CLASSES">
          {{ title }}
        </h2>
        <slot name="header" />
        <slot name="headerActions" />
      </div>
    </div>
    
    <!-- Основной контент -->
    <div v-if="!noPadding" :class="CONTENT_PADDING">
      <slot />
    </div>
    <slot v-else />
    
    <!-- Футер карточки (опционально) -->
    <div v-if="$slots.footer" :class="['border-t', FOOTER_PADDING]">
      <slot name="footer" />
    </div>
  </component>
</template>

<script setup>
// 🎯 ФИКСИРОВАННЫЕ СТИЛИ ДЛЯ ВСЕГО ПРОЕКТА
const BASE_CLASSES = 'bg-white rounded-lg shadow-sm overflow-hidden'
const CONTENT_PADDING = 'p-6'          // 24px - везде одинаково
const HEADER_PADDING = 'px-6 py-4'     // 24px/16px - везде одинаково  
const FOOTER_PADDING = 'px-6 py-4'     // 24px/16px - везде одинаково
const TITLE_CLASSES = 'text-lg font-semibold text-gray-900'

// Упрощенные пропсы (убрали padding, noShadow, noRounded и т.д.)
const props = defineProps({
  // HTML тег
  as: {
    type: String,
    default: 'div'
  },
  
  // Заголовок
  title: String,
  
  // Убрать отступы у контента (для особых случаев)
  noPadding: Boolean,
  
  // Кастомные классы (для border, hover и т.д.)
  customClass: String
})
</script>