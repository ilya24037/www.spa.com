<!-- SkeletonForm.vue - Скелетон для форм -->
<template>
  <div 
    class="bg-white rounded-lg shadow-sm p-6 animate-pulse"
    data-testid="skeleton-form"
    role="presentation"
    aria-hidden="true"
  >
    <!-- Заголовок формы -->
    <div class="h-6 bg-gray-500 rounded w-64 mb-8" />

    <!-- Группа полей 1 -->
    <div class="mb-6">
      <div class="h-4 bg-gray-500 rounded w-32 mb-2" />
      <div class="h-10 bg-gray-500 rounded w-full" />
    </div>

    <!-- Группа полей 2 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div>
        <div class="h-4 bg-gray-500 rounded w-24 mb-2" />
        <div class="h-10 bg-gray-500 rounded w-full" />
      </div>
      <div>
        <div class="h-4 bg-gray-500 rounded w-28 mb-2" />
        <div class="h-10 bg-gray-500 rounded w-full" />
      </div>
    </div>

    <!-- Текстовая область -->
    <div class="mb-6">
      <div class="h-4 bg-gray-500 rounded w-20 mb-2" />
      <div class="h-24 bg-gray-500 rounded w-full" />
    </div>

    <!-- Чекбоксы/радио кнопки -->
    <div class="mb-6">
      <div class="h-4 bg-gray-500 rounded w-36 mb-4" />
      <div class="space-y-3">
        <div class="flex items-center gap-3">
          <div class="w-4 h-4 bg-gray-500 rounded" />
          <div class="h-4 bg-gray-500 rounded w-48" />
        </div>
        <div class="flex items-center gap-3">
          <div class="w-4 h-4 bg-gray-500 rounded" />
          <div class="h-4 bg-gray-500 rounded w-40" />
        </div>
        <div class="flex items-center gap-3">
          <div class="w-4 h-4 bg-gray-500 rounded" />
          <div class="h-4 bg-gray-500 rounded w-56" />
        </div>
      </div>
    </div>

    <!-- Селект -->
    <div class="mb-6">
      <div class="h-4 bg-gray-500 rounded w-28 mb-2" />
      <div class="h-10 bg-gray-500 rounded w-full" />
    </div>

    <!-- Загрузка файлов -->
    <div class="mb-8">
      <div class="h-4 bg-gray-500 rounded w-32 mb-3" />
      <div class="border-2 border-dashed border-gray-500 rounded-lg p-8">
        <div class="text-center">
          <div class="w-12 h-12 bg-gray-500 rounded-full mx-auto mb-4" />
          <div class="h-4 bg-gray-500 rounded w-48 mx-auto mb-2" />
          <div class="h-3 bg-gray-500 rounded w-32 mx-auto" />
        </div>
      </div>
    </div>

    <!-- Кнопки действий -->
    <div class="flex gap-3 justify-end">
      <div class="h-10 bg-gray-500 rounded w-24" />
      <div class="h-10 bg-gray-500 rounded w-32" />
    </div>
  </div>
</template>

<script setup lang="ts">
// Компонент скелетона для форм не требует логики
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

.animate-pulse > div:nth-child(6n) {
  animation-delay: 0.5s;
}
</style>