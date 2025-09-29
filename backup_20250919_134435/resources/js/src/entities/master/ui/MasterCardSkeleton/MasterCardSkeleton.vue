<!-- Skeleton загрузчик для карточки мастера (как в Ozon) -->
<template>
  <div class="master-card-skeleton bg-white rounded-lg shadow overflow-hidden">
    <!-- Изображение skeleton -->
    <div class="relative h-64 bg-gray-200 animate-pulse">
      <!-- Иконка избранного -->
      <div class="absolute top-2 right-2 w-10 h-10 bg-gray-300 rounded-full animate-pulse" />
      
      <!-- Бейджи skeleton -->
      <div class="absolute top-2 left-2 flex gap-2">
        <div class="w-16 h-6 bg-gray-300 rounded animate-pulse" />
        <div class="w-20 h-6 bg-gray-300 rounded animate-pulse" />
      </div>
    </div>

    <!-- Контент skeleton -->
    <div class="p-4 space-y-3">
      <!-- Имя -->
      <div class="h-6 bg-gray-200 rounded animate-pulse" />
      
      <!-- Рейтинг и отзывы -->
      <div class="flex items-center gap-2">
        <div class="flex gap-1">
          <div v-for="i in 5" :key="i" class="w-4 h-4 bg-gray-200 rounded animate-pulse" />
        </div>
        <div class="w-16 h-4 bg-gray-200 rounded animate-pulse" />
      </div>

      <!-- Услуги -->
      <div class="space-y-2">
        <div class="h-4 bg-gray-200 rounded animate-pulse w-3/4" />
        <div class="h-4 bg-gray-200 rounded animate-pulse w-1/2" />
      </div>

      <!-- Цена -->
      <div class="flex items-baseline gap-2">
        <div class="h-6 w-20 bg-gray-200 rounded animate-pulse" />
        <div class="h-4 w-12 bg-gray-200 rounded animate-pulse" />
      </div>

      <!-- Адрес -->
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 bg-gray-200 rounded animate-pulse" />
        <div class="h-4 bg-gray-200 rounded animate-pulse flex-1" />
      </div>

      <!-- Кнопки -->
      <div class="flex gap-2 pt-2">
        <div class="h-10 bg-gray-200 rounded-lg animate-pulse flex-1" />
        <div class="h-10 bg-gray-200 rounded-lg animate-pulse flex-1" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Skeleton компонент для карточки мастера
// Показывается во время загрузки данных
</script>

<style scoped>
.master-card-skeleton {
  min-height: 420px;
}

/* Пульсирующая анимация как в Ozon */
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

/* Градиентная анимация для более красивого эффекта */
.master-card-skeleton .bg-gray-200 {
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