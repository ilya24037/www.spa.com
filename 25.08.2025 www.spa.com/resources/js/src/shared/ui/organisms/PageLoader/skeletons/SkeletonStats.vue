<!-- SkeletonStats.vue - Скелетон для статистики и дашборда -->
<template>
  <div 
    class="animate-pulse"
    data-testid="skeleton-stats"
    role="presentation"
    aria-hidden="true"
  >
    <!-- Заголовок секции -->
    <div class="h-6 bg-gray-500 rounded w-48 mb-6" />

    <!-- Карточки статистики -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Карточка 1 -->
      <div class="bg-white rounded-lg p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-gray-500 rounded-lg" />
          <div class="h-4 bg-gray-500 rounded w-16" />
        </div>
        <div class="h-8 bg-gray-500 rounded w-20 mb-2" />
        <div class="h-3 bg-gray-500 rounded w-24" />
      </div>

      <!-- Карточка 2 -->
      <div class="bg-white rounded-lg p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-gray-500 rounded-lg" />
          <div class="h-4 bg-gray-500 rounded w-12" />
        </div>
        <div class="h-8 bg-gray-500 rounded w-16 mb-2" />
        <div class="h-3 bg-gray-500 rounded w-28" />
      </div>

      <!-- Карточка 3 -->
      <div class="bg-white rounded-lg p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-gray-500 rounded-lg" />
          <div class="h-4 bg-gray-500 rounded w-20" />
        </div>
        <div class="h-8 bg-gray-500 rounded w-24 mb-2" />
        <div class="h-3 bg-gray-500 rounded w-20" />
      </div>

      <!-- Карточка 4 -->
      <div class="bg-white rounded-lg p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-gray-500 rounded-lg" />
          <div class="h-4 bg-gray-500 rounded w-14" />
        </div>
        <div class="h-8 bg-gray-500 rounded w-28 mb-2" />
        <div class="h-3 bg-gray-500 rounded w-32" />
      </div>
    </div>

    <!-- График/диаграмма -->
    <div class="bg-white rounded-lg p-6 shadow-sm mb-8">
      <div class="flex items-center justify-between mb-6">
        <div class="h-5 bg-gray-500 rounded w-40" />
        <div class="flex gap-2">
          <div class="h-8 bg-gray-500 rounded w-20" />
          <div class="h-8 bg-gray-500 rounded w-24" />
        </div>
      </div>
      
      <!-- Макет графика -->
      <div class="h-64 bg-gray-500 rounded-lg mb-4" />
      
      <!-- Легенда -->
      <div class="flex justify-center gap-6">
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 bg-gray-500 rounded-full" />
          <div class="h-3 bg-gray-500 rounded w-16" />
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 bg-gray-500 rounded-full" />
          <div class="h-3 bg-gray-500 rounded w-20" />
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 bg-gray-500 rounded-full" />
          <div class="h-3 bg-gray-500 rounded w-18" />
        </div>
      </div>
    </div>

    <!-- Список последних элементов -->
    <div class="bg-white rounded-lg shadow-sm">
      <div class="p-6 border-b border-gray-500">
        <div class="flex items-center justify-between">
          <div class="h-5 bg-gray-500 rounded w-36" />
          <div class="h-4 bg-gray-500 rounded w-20" />
        </div>
      </div>
      
      <div class="divide-y divide-gray-100">
        <!-- Элемент списка 1 -->
        <div class="p-6 flex items-center gap-4">
          <div class="w-10 h-10 bg-gray-500 rounded-full flex-shrink-0" />
          <div class="flex-1">
            <div class="h-4 bg-gray-500 rounded w-3/4 mb-2" />
            <div class="h-3 bg-gray-500 rounded w-1/2" />
          </div>
          <div class="text-right">
            <div class="h-4 bg-gray-500 rounded w-16 mb-1" />
            <div class="h-3 bg-gray-500 rounded w-12" />
          </div>
        </div>

        <!-- Элемент списка 2 -->
        <div class="p-6 flex items-center gap-4">
          <div class="w-10 h-10 bg-gray-500 rounded-full flex-shrink-0" />
          <div class="flex-1">
            <div class="h-4 bg-gray-500 rounded w-2/3 mb-2" />
            <div class="h-3 bg-gray-500 rounded w-2/5" />
          </div>
          <div class="text-right">
            <div class="h-4 bg-gray-500 rounded w-20 mb-1" />
            <div class="h-3 bg-gray-500 rounded w-14" />
          </div>
        </div>

        <!-- Элемент списка 3 -->
        <div class="p-6 flex items-center gap-4">
          <div class="w-10 h-10 bg-gray-500 rounded-full flex-shrink-0" />
          <div class="flex-1">
            <div class="h-4 bg-gray-500 rounded w-4/5 mb-2" />
            <div class="h-3 bg-gray-500 rounded w-3/5" />
          </div>
          <div class="text-right">
            <div class="h-4 bg-gray-500 rounded w-18 mb-1" />
            <div class="h-3 bg-gray-500 rounded w-10" />
          </div>
        </div>

        <!-- Элемент списка 4 -->
        <div class="p-6 flex items-center gap-4">
          <div class="w-10 h-10 bg-gray-500 rounded-full flex-shrink-0" />
          <div class="flex-1">
            <div class="h-4 bg-gray-500 rounded w-3/5 mb-2" />
            <div class="h-3 bg-gray-500 rounded w-2/3" />
          </div>
          <div class="text-right">
            <div class="h-4 bg-gray-500 rounded w-16 mb-1" />
            <div class="h-3 bg-gray-500 rounded w-12" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Компонент скелетона для статистики не требует логики
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

/* Разные задержки для более реалистичной анимации */
.animate-pulse .grid > div:nth-child(1) {
  animation-delay: 0s;
}

.animate-pulse .grid > div:nth-child(2) {
  animation-delay: 0.1s;
}

.animate-pulse .grid > div:nth-child(3) {
  animation-delay: 0.2s;
}

.animate-pulse .grid > div:nth-child(4) {
  animation-delay: 0.3s;
}

/* Анимация для элементов списка */
.animate-pulse .divide-y > div:nth-child(1n) {
  animation-delay: 0.4s;
}

.animate-pulse .divide-y > div:nth-child(2n) {
  animation-delay: 0.5s;
}

.animate-pulse .divide-y > div:nth-child(3n) {
  animation-delay: 0.6s;
}

.animate-pulse .divide-y > div:nth-child(4n) {
  animation-delay: 0.7s;
}
</style>