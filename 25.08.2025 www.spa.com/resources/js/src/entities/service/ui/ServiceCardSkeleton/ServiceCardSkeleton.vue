<!-- Skeleton загрузчик для карточки услуги -->
<template>
  <div class="service-card-skeleton bg-white rounded-lg shadow-sm p-4">
    <div class="flex items-start gap-4">
      <!-- Иконка услуги -->
      <div class="w-12 h-12 bg-gray-200 rounded-lg animate-pulse flex-shrink-0" />
      
      <!-- Контент -->
      <div class="flex-1 space-y-2">
        <!-- Название услуги -->
        <div class="h-5 bg-gray-200 rounded animate-pulse w-3/4" />
        
        <!-- Описание -->
        <div class="space-y-1">
          <div class="h-3 bg-gray-200 rounded animate-pulse" />
          <div class="h-3 bg-gray-200 rounded animate-pulse w-5/6" />
        </div>
        
        <!-- Цена и длительность -->
        <div class="flex items-center gap-4 pt-2">
          <div class="h-5 w-20 bg-gray-200 rounded animate-pulse" />
          <div class="h-4 w-16 bg-gray-200 rounded animate-pulse" />
        </div>
      </div>
      
      <!-- Кнопка действия -->
      <div class="w-24 h-8 bg-gray-200 rounded-lg animate-pulse flex-shrink-0" />
    </div>
  </div>
</template>

<script setup lang="ts">
// Skeleton для карточки услуги
</script>

<style scoped>
.service-card-skeleton {
  min-height: 100px;
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

/* Градиентная анимация */
.service-card-skeleton .bg-gray-200 {
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