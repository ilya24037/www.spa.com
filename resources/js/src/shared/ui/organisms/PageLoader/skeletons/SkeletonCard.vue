<!-- SkeletonCard.vue - Скелетон для карточек мастеров/объявлений -->
<template>
  <div 
    class="bg-white rounded-lg shadow-sm p-6 animate-pulse"
    data-testid="skeleton-card"
    role="presentation"
    aria-hidden="true"
  >
    <div class="flex gap-4">
      <!-- Аватар/фото -->
      <div class="w-16 h-16 bg-gray-500 rounded-full flex-shrink-0" />
      
      <!-- Основной контент -->
      <div class="flex-1 min-w-0">
        <!-- Заголовок -->
        <div class="h-5 bg-gray-500 rounded w-3/4 mb-3" />
        
        <!-- Подзаголовок -->
        <div class="h-4 bg-gray-500 rounded w-1/2 mb-3" />
        
        <!-- Рейтинг и локация -->
        <div class="flex items-center gap-4 mb-3">
          <div class="h-4 bg-gray-500 rounded w-20" />
          <div class="h-4 bg-gray-500 rounded w-24" />
        </div>
        
        <!-- Описание -->
        <div class="space-y-2 mb-4">
          <div class="h-3 bg-gray-500 rounded w-full" />
          <div class="h-3 bg-gray-500 rounded w-5/6" />
        </div>
        
        <!-- Услуги/теги -->
        <div class="flex gap-2 mb-4">
          <div class="h-6 bg-gray-500 rounded-full w-16" />
          <div class="h-6 bg-gray-500 rounded-full w-20" />
          <div class="h-6 bg-gray-500 rounded-full w-14" />
        </div>
        
        <!-- Цена и действия -->
        <div class="flex items-center justify-between">
          <div class="h-6 bg-gray-500 rounded w-24" />
          <div class="flex gap-2">
            <div class="h-8 bg-gray-500 rounded w-20" />
            <div class="h-8 bg-gray-500 rounded w-24" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Компонент скелетона для карточек не требует логики
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

.animate-pulse > div {
  animation: skeleton-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Разные задержки для более реалистичной анимации */
.animate-pulse > div:nth-child(2) {
  animation-delay: 0.1s;
}

.animate-pulse > div:nth-child(3) {
  animation-delay: 0.2s;
}

.animate-pulse > div:nth-child(4) {
  animation-delay: 0.3s;
}
</style>