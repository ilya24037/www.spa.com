<!-- Skeleton загрузчик для детальной страницы объявления (как в Ozon) -->
<template>
  <div class="ad-detail-skeleton">
    <!-- Основная информация -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
      <div class="flex flex-col md:flex-row gap-6">
        <!-- Фото профиля -->
        <div class="w-32 h-32 bg-gray-200 rounded-lg animate-pulse flex-shrink-0" />

        <!-- Информация -->
        <div class="flex-1 space-y-4">
          <!-- Имя -->
          <div class="h-8 bg-gray-200 rounded animate-pulse w-1/3" />

          <!-- Рейтинг -->
          <div class="flex items-center gap-2">
            <div class="flex gap-1">
              <div v-for="i in 5" :key="i" class="w-5 h-5 bg-gray-200 rounded animate-pulse" />
            </div>
            <div class="h-5 w-24 bg-gray-200 rounded animate-pulse" />
          </div>

          <!-- Метаинформация -->
          <div class="flex flex-wrap gap-4">
            <div class="h-5 w-32 bg-gray-200 rounded animate-pulse" />
            <div class="h-5 w-28 bg-gray-200 rounded animate-pulse" />
            <div class="h-5 w-36 bg-gray-200 rounded animate-pulse" />
          </div>

          <!-- Описание -->
          <div class="space-y-2">
            <div class="h-4 bg-gray-200 rounded animate-pulse" />
            <div class="h-4 bg-gray-200 rounded animate-pulse w-5/6" />
            <div class="h-4 bg-gray-200 rounded animate-pulse w-4/6" />
          </div>
        </div>
      </div>
    </div>

    <!-- Контент в 3 колонки -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Левая колонка - услуги -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Услуги -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="h-6 w-24 bg-gray-200 rounded animate-pulse mb-4" />
          <div class="space-y-3">
            <div v-for="i in 4" :key="`service-${i}`" class="p-4 border border-gray-200 rounded-lg">
              <div class="flex justify-between items-start">
                <div class="flex-1 space-y-2">
                  <div class="h-5 bg-gray-200 rounded animate-pulse w-3/4" />
                  <div class="h-4 bg-gray-200 rounded animate-pulse w-1/2" />
                </div>
                <div class="h-6 w-20 bg-gray-200 rounded animate-pulse" />
              </div>
            </div>
          </div>
        </div>

        <!-- Галерея -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="h-6 w-32 bg-gray-200 rounded animate-pulse mb-4" />
          <div class="grid grid-cols-3 gap-2">
            <div v-for="i in 6" :key="`photo-${i}`" class="aspect-square bg-gray-200 rounded-lg animate-pulse" />
          </div>
        </div>

        <!-- Отзывы -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="h-6 w-24 bg-gray-200 rounded animate-pulse mb-4" />
          <div class="space-y-4">
            <div v-for="i in 2" :key="`review-${i}`" class="pb-4 border-b border-gray-200 last:border-b-0">
              <div class="flex items-start gap-3 mb-2">
                <div class="w-10 h-10 bg-gray-200 rounded-full animate-pulse" />
                <div class="flex-1">
                  <div class="h-5 w-32 bg-gray-200 rounded animate-pulse mb-1" />
                  <div class="flex gap-1 mb-2">
                    <div v-for="star in 5" :key="`star-${star}`" class="w-4 h-4 bg-gray-200 rounded animate-pulse" />
                  </div>
                  <div class="space-y-1">
                    <div class="h-4 bg-gray-200 rounded animate-pulse" />
                    <div class="h-4 bg-gray-200 rounded animate-pulse w-4/5" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Правая колонка - контакты и расписание -->
      <div class="space-y-6">
        <!-- Кнопки действий -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="space-y-3">
            <div class="h-12 bg-gray-200 rounded-lg animate-pulse" />
            <div class="h-12 bg-gray-200 rounded-lg animate-pulse" />
            <div class="flex gap-2">
              <div class="h-10 bg-gray-200 rounded-lg animate-pulse flex-1" />
              <div class="h-10 bg-gray-200 rounded-lg animate-pulse flex-1" />
            </div>
          </div>
        </div>

        <!-- График работы -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="h-6 w-32 bg-gray-200 rounded animate-pulse mb-4" />
          <div class="space-y-2">
            <div v-for="i in 7" :key="`day-${i}`" class="flex justify-between items-center py-2">
              <div class="h-4 w-16 bg-gray-200 rounded animate-pulse" />
              <div class="h-4 w-24 bg-gray-200 rounded animate-pulse" />
            </div>
          </div>
        </div>

        <!-- Адрес -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="h-6 w-20 bg-gray-200 rounded animate-pulse mb-4" />
          <div class="space-y-2">
            <div class="h-4 bg-gray-200 rounded animate-pulse" />
            <div class="h-4 bg-gray-200 rounded animate-pulse w-3/4" />
          </div>
          <div class="h-32 bg-gray-200 rounded-lg animate-pulse mt-4" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Skeleton для детальной страницы объявления
// Показывается во время загрузки данных
</script>

<style scoped>
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
.ad-detail-skeleton .bg-gray-200 {
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
