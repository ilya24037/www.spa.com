<!-- SkeletonList.vue - Скелетон для списков -->
<template>
  <div 
    class="bg-white rounded-lg shadow-sm divide-y divide-gray-100 animate-pulse"
    data-testid="skeleton-list"
    role="presentation"
    aria-hidden="true"
  >
    <!-- Элемент списка 1 -->
    <div class="p-4 flex items-center gap-4">
      <div class="w-12 h-12 bg-gray-500 rounded-full flex-shrink-0" />
      <div class="flex-1">
        <div class="h-4 bg-gray-500 rounded w-3/4 mb-2" />
        <div class="h-3 bg-gray-500 rounded w-1/2" />
      </div>
      <div class="h-8 bg-gray-500 rounded w-20" />
    </div>

    <!-- Элемент списка 2 -->
    <div class="p-4 flex items-center gap-4">
      <div class="w-12 h-12 bg-gray-500 rounded-full flex-shrink-0" />
      <div class="flex-1">
        <div class="h-4 bg-gray-500 rounded w-2/3 mb-2" />
        <div class="h-3 bg-gray-500 rounded w-2/5" />
      </div>
      <div class="h-8 bg-gray-500 rounded w-24" />
    </div>

    <!-- Элемент списка 3 -->
    <div class="p-4 flex items-center gap-4">
      <div class="w-12 h-12 bg-gray-500 rounded-full flex-shrink-0" />
      <div class="flex-1">
        <div class="h-4 bg-gray-500 rounded w-4/5 mb-2" />
        <div class="h-3 bg-gray-500 rounded w-3/5" />
      </div>
      <div class="h-8 bg-gray-500 rounded w-16" />
    </div>

    <!-- Элемент списка 4 -->
    <div class="p-4 flex items-center gap-4">
      <div class="w-12 h-12 bg-gray-500 rounded-full flex-shrink-0" />
      <div class="flex-1">
        <div class="h-4 bg-gray-500 rounded w-3/5 mb-2" />
        <div class="h-3 bg-gray-500 rounded w-2/3" />
      </div>
      <div class="h-8 bg-gray-500 rounded w-18" />
    </div>

    <!-- Элемент списка 5 -->
    <div class="p-4 flex items-center gap-4">
      <div class="w-12 h-12 bg-gray-500 rounded-full flex-shrink-0" />
      <div class="flex-1">
        <div class="h-4 bg-gray-500 rounded w-5/6 mb-2" />
        <div class="h-3 bg-gray-500 rounded w-1/3" />
      </div>
      <div class="h-8 bg-gray-500 rounded w-22" />
    </div>
  </div>
</template>

<script setup lang="ts">
// Компонент скелетона для списков не требует логики
</script>

<style scoped>
/* Анимация пульсации с разными задержками */
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

/* Разные задержки для элементов списка */
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
</style>