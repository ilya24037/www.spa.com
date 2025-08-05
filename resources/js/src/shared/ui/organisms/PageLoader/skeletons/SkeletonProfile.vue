<!-- SkeletonProfile.vue - Скелетон для профилей пользователей/мастеров -->
<template>
  <div 
    class="bg-white rounded-lg shadow-sm animate-pulse"
    data-testid="skeleton-profile"
    role="presentation"
    aria-hidden="true"
  >
    <!-- Хедер профиля -->
    <div class="p-6 border-b border-gray-100">
      <div class="flex items-start gap-6">
        <!-- Большой аватар -->
        <div class="w-24 h-24 bg-gray-200 rounded-full flex-shrink-0" />
        
        <!-- Основная информация -->
        <div class="flex-1">
          <!-- Имя и статус -->
          <div class="h-7 bg-gray-200 rounded w-48 mb-3" />
          <div class="h-5 bg-gray-200 rounded w-32 mb-4" />
          
          <!-- Рейтинг и статистика -->
          <div class="flex items-center gap-6 mb-4">
            <div class="h-5 bg-gray-200 rounded w-20" />
            <div class="h-5 bg-gray-200 rounded w-24" />
            <div class="h-5 bg-gray-200 rounded w-28" />
          </div>
          
          <!-- Кнопки действий -->
          <div class="flex gap-3">
            <div class="h-10 bg-gray-200 rounded w-28" />
            <div class="h-10 bg-gray-200 rounded w-24" />
            <div class="h-10 bg-gray-200 rounded w-10" />
          </div>
        </div>
      </div>
    </div>

    <!-- Основной контент -->
    <div class="p-6">
      <!-- Описание -->
      <div class="mb-6">
        <div class="h-5 bg-gray-200 rounded w-32 mb-3" />
        <div class="space-y-2">
          <div class="h-4 bg-gray-200 rounded w-full" />
          <div class="h-4 bg-gray-200 rounded w-11/12" />
          <div class="h-4 bg-gray-200 rounded w-4/5" />
        </div>
      </div>

      <!-- Услуги -->
      <div class="mb-6">
        <div class="h-5 bg-gray-200 rounded w-24 mb-3" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="border border-gray-100 rounded-lg p-4">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2" />
            <div class="h-6 bg-gray-200 rounded w-20" />
          </div>
          <div class="border border-gray-100 rounded-lg p-4">
            <div class="h-4 bg-gray-200 rounded w-2/3 mb-2" />
            <div class="h-6 bg-gray-200 rounded w-24" />
          </div>
          <div class="border border-gray-100 rounded-lg p-4">
            <div class="h-4 bg-gray-200 rounded w-4/5 mb-2" />
            <div class="h-6 bg-gray-200 rounded w-16" />
          </div>
          <div class="border border-gray-100 rounded-lg p-4">
            <div class="h-4 bg-gray-200 rounded w-3/5 mb-2" />
            <div class="h-6 bg-gray-200 rounded w-22" />
          </div>
        </div>
      </div>

      <!-- Галерея работ -->
      <div class="mb-6">
        <div class="h-5 bg-gray-200 rounded w-28 mb-3" />
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="aspect-square bg-gray-200 rounded-lg" />
          <div class="aspect-square bg-gray-200 rounded-lg" />
          <div class="aspect-square bg-gray-200 rounded-lg" />
          <div class="aspect-square bg-gray-200 rounded-lg" />
        </div>
      </div>

      <!-- Отзывы -->
      <div>
        <div class="h-5 bg-gray-200 rounded w-20 mb-4" />
        <div class="space-y-4">
          <!-- Отзыв 1 -->
          <div class="border border-gray-100 rounded-lg p-4">
            <div class="flex items-start gap-3 mb-3">
              <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0" />
              <div class="flex-1">
                <div class="h-4 bg-gray-200 rounded w-32 mb-1" />
                <div class="h-3 bg-gray-200 rounded w-20" />
              </div>
              <div class="h-4 bg-gray-200 rounded w-16" />
            </div>
            <div class="space-y-2">
              <div class="h-3 bg-gray-200 rounded w-full" />
              <div class="h-3 bg-gray-200 rounded w-3/4" />
            </div>
          </div>
          
          <!-- Отзыв 2 -->
          <div class="border border-gray-100 rounded-lg p-4">
            <div class="flex items-start gap-3 mb-3">
              <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0" />
              <div class="flex-1">
                <div class="h-4 bg-gray-200 rounded w-28 mb-1" />
                <div class="h-3 bg-gray-200 rounded w-24" />
              </div>
              <div class="h-4 bg-gray-200 rounded w-16" />
            </div>
            <div class="space-y-2">
              <div class="h-3 bg-gray-200 rounded w-11/12" />
              <div class="h-3 bg-gray-200 rounded w-4/5" />
              <div class="h-3 bg-gray-200 rounded w-2/3" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Компонент скелетона для профилей не требует логики
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
</style>