<template>
  <div class="yandex-map-container" :class="containerClass">
    <slot />
  </div>
</template>

<script setup lang="ts">
interface Props {
  containerClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  containerClass: ''
})
</script>

<style scoped>
/* Изоляция стилей для Yandex Maps */
.yandex-map-container {
  /* Сброс всех Tailwind стилей */
  all: initial;
  
  /* Восстановление необходимых базовых стилей */
  font-family: inherit;
  box-sizing: border-box;
  
  /* Обеспечиваем правильное позиционирование */
  position: relative;
  width: 100%;
  height: 100%;
}

/* Глубокая изоляция для всех дочерних элементов */
.yandex-map-container :deep(*) {
  box-sizing: border-box;
}

/* Защита кнопок и форм от Tailwind reset */
.yandex-map-container :deep(button),
.yandex-map-container :deep(input),
.yandex-map-container :deep(select),
.yandex-map-container :deep(textarea) {
  background-color: initial;
  border: initial;
  color: initial;
  font-family: inherit;
  font-size: inherit;
  line-height: inherit;
  margin: initial;
  padding: initial;
}

/* Специфичные стили для Yandex Maps API */
.yandex-map-container :deep(.ymaps3x0--map) {
  position: relative !important;
}

.yandex-map-container :deep(.ymaps3x0--controls) {
  position: absolute !important;
  z-index: 1000 !important;
}

.yandex-map-container :deep(.ymaps3x0--zoom-control) {
  background: white !important;
  border-radius: 4px !important;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}
</style>
