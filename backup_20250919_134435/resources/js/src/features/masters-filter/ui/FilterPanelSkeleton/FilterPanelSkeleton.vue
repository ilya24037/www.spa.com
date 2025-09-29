<!-- Skeleton загрузчик для панели фильтров -->
<template>
  <div class="filter-panel-skeleton bg-white rounded-lg shadow-sm">
    <!-- Заголовок -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="h-6 w-24 bg-gray-200 rounded animate-pulse" />
    </div>

    <div class="p-6 space-y-8">
      <!-- Секция 1: Цена -->
      <div>
        <div class="h-5 w-20 bg-gray-200 rounded animate-pulse mb-4" />
        <div class="flex items-center gap-2">
          <div class="h-10 bg-gray-200 rounded-lg animate-pulse flex-1" />
          <div class="w-4 h-px bg-gray-300" />
          <div class="h-10 bg-gray-200 rounded-lg animate-pulse flex-1" />
        </div>
      </div>

      <!-- Секция 2: Чекбоксы -->
      <div>
        <div class="h-5 w-32 bg-gray-200 rounded animate-pulse mb-4" />
        <div class="space-y-3">
          <div v-for="i in 5" :key="`checkbox-${i}`" class="flex items-center gap-3">
            <div class="w-5 h-5 bg-gray-200 rounded animate-pulse" />
            <div class="h-4 bg-gray-200 rounded animate-pulse" :style="`width: ${60 + Math.random() * 40}%`" />
          </div>
        </div>
      </div>

      <!-- Секция 3: Дополнительные опции -->
      <div>
        <div class="h-5 w-28 bg-gray-200 rounded animate-pulse mb-4" />
        <div class="space-y-3">
          <div v-for="i in 3" :key="`option-${i}`" class="flex items-center gap-3">
            <div class="w-5 h-5 bg-gray-200 rounded animate-pulse" />
            <div class="h-4 bg-gray-200 rounded animate-pulse" :style="`width: ${50 + Math.random() * 30}%`" />
          </div>
        </div>
      </div>

      <!-- Секция 4: Рейтинг -->
      <div>
        <div class="h-5 w-20 bg-gray-200 rounded animate-pulse mb-4" />
        <div class="space-y-3">
          <div v-for="i in 2" :key="`rating-${i}`" class="flex items-center gap-3">
            <div class="w-5 h-5 bg-gray-200 rounded-full animate-pulse" />
            <div class="flex gap-1">
              <div v-for="star in 5" :key="`star-${star}`" class="w-4 h-4 bg-gray-200 rounded animate-pulse" />
            </div>
            <div class="h-4 w-16 bg-gray-200 rounded animate-pulse" />
          </div>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="pt-4 space-y-3">
        <div class="h-10 bg-gray-200 rounded-lg animate-pulse" />
        <div class="h-10 bg-gray-200 rounded-lg animate-pulse" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Skeleton для панели фильтров
</script>

<style scoped>
.filter-panel-skeleton {
  width: 320px;
  min-width: 320px;
  max-width: 320px;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Градиентная анимация как в Ozon */
.filter-panel-skeleton .bg-gray-200 {
  background: linear-gradient(
    90deg,
    #f3f4f6 25%,
    #e5e7eb 50%,
    #f3f4f6 75%
  );
  background-size: 200% 100%;
  animation: loading 1.5s ease-in-out infinite;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>