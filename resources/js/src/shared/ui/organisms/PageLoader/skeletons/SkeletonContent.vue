<!-- SkeletonContent.vue - Скелетон для текстового контента -->
<template>
  <div 
    class="bg-white rounded-lg shadow-sm p-6 animate-pulse"
    data-testid="skeleton-content"
    role="presentation"
    aria-hidden="true"
  >
    <!-- Заголовок страницы -->
    <div class="h-8 bg-gray-500 rounded w-2/3 mb-6" />

    <!-- Подзаголовок -->
    <div class="h-5 bg-gray-500 rounded w-1/2 mb-8" />

    <!-- Основной текст -->
    <div class="space-y-3 mb-8">
      <div class="h-4 bg-gray-500 rounded w-full" />
      <div class="h-4 bg-gray-500 rounded w-11/12" />
      <div class="h-4 bg-gray-500 rounded w-4/5" />
      <div class="h-4 bg-gray-500 rounded w-full" />
      <div class="h-4 bg-gray-500 rounded w-3/4" />
    </div>

    <!-- Изображение в тексте -->
    <div class="w-full h-48 bg-gray-500 rounded-lg mb-8" />

    <!-- Продолжение текста -->
    <div class="space-y-3 mb-8">
      <div class="h-4 bg-gray-500 rounded w-5/6" />
      <div class="h-4 bg-gray-500 rounded w-full" />
      <div class="h-4 bg-gray-500 rounded w-2/3" />
      <div class="h-4 bg-gray-500 rounded w-4/5" />
    </div>

    <!-- Список -->
    <div class="mb-8">
      <div class="h-5 bg-gray-500 rounded w-48 mb-4" />
      <div class="space-y-3 pl-4">
        <div class="flex items-start gap-3">
          <div class="w-2 h-2 bg-gray-500 rounded-full mt-2 flex-shrink-0" />
          <div class="h-4 bg-gray-500 rounded w-3/4" />
        </div>
        <div class="flex items-start gap-3">
          <div class="w-2 h-2 bg-gray-500 rounded-full mt-2 flex-shrink-0" />
          <div class="h-4 bg-gray-500 rounded w-2/3" />
        </div>
        <div class="flex items-start gap-3">
          <div class="w-2 h-2 bg-gray-500 rounded-full mt-2 flex-shrink-0" />
          <div class="h-4 bg-gray-500 rounded w-4/5" />
        </div>
        <div class="flex items-start gap-3">
          <div class="w-2 h-2 bg-gray-500 rounded-full mt-2 flex-shrink-0" />
          <div class="h-4 bg-gray-500 rounded w-1/2" />
        </div>
      </div>
    </div>

    <!-- Цитата -->
    <div class="bg-gray-500 border-l-4 border-gray-500 p-4 mb-8">
      <div class="space-y-2">
        <div class="h-4 bg-gray-500 rounded w-full" />
        <div class="h-4 bg-gray-500 rounded w-5/6" />
      </div>
      <div class="h-3 bg-gray-500 rounded w-32 mt-3" />
    </div>

    <!-- Завершающий текст -->
    <div class="space-y-3 mb-6">
      <div class="h-4 bg-gray-500 rounded w-full" />
      <div class="h-4 bg-gray-500 rounded w-3/4" />
      <div class="h-4 bg-gray-500 rounded w-5/6" />
    </div>

    <!-- Теги или метки -->
    <div class="flex flex-wrap gap-2">
      <div class="h-6 bg-gray-500 rounded-full w-16" />
      <div class="h-6 bg-gray-500 rounded-full w-20" />
      <div class="h-6 bg-gray-500 rounded-full w-14" />
      <div class="h-6 bg-gray-500 rounded-full w-18" />
      <div class="h-6 bg-gray-500 rounded-full w-12" />
    </div>
  </div>
</template>

<script setup lang="ts">
// Компонент скелетона для контента не требует логики
</script>

<style scoped>
/* Анимация пульсации */
@keyframes skeleton-pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: skeleton-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Разные задержки для групп элементов */
.animate-pulse > div:nth-child(1n) {
  animation-delay: 0s;
}

.animate-pulse > div:nth-child(2n) {
  animation-delay: 0.1s;
}

.animate-pulse > div:nth-child(3n) {
  animation-delay: 0.2s;
}

.animate-pulse > div:nth-child(4n) {
  animation-delay: 0.3s;
}

.animate-pulse > div:nth-child(5n) {
  animation-delay: 0.4s;
}

/* Анимация для текстовых блоков */
.space-y-3 > div:nth-child(1n) {
  animation-delay: 0s;
}

.space-y-3 > div:nth-child(2n) {
  animation-delay: 0.05s;
}

.space-y-3 > div:nth-child(3n) {
  animation-delay: 0.1s;
}

.space-y-3 > div:nth-child(4n) {
  animation-delay: 0.15s;
}

.space-y-3 > div:nth-child(5n) {
  animation-delay: 0.2s;
}
</style>