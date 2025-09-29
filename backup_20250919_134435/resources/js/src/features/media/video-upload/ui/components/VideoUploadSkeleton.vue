<template>
  <div class="video-upload-skeleton space-y-4">
    <!-- Заголовок секции -->
    <div class="flex justify-between items-center">
      <div class="h-6 w-20 bg-gray-200 rounded animate-pulse" />
      <div class="h-4 w-16 bg-gray-200 rounded animate-pulse" />
    </div>

    <!-- Зона загрузки skeleton -->
    <div class="border-2 border-dashed border-gray-200 rounded-lg p-8 animate-pulse">
      <div class="flex flex-col items-center space-y-3">
        <div class="w-12 h-12 bg-gray-200 rounded" />
        <div class="h-4 w-48 bg-gray-200 rounded" />
        <div class="h-3 w-32 bg-gray-200 rounded" />
        <div class="h-10 w-32 bg-gray-200 rounded-md" />
      </div>
    </div>

    <!-- Список видео skeleton -->
    <div class="space-y-2">
      <div 
        v-for="i in 2" 
        :key="i"
        class="flex items-center gap-4 p-3 bg-white rounded-lg border animate-pulse"
      >
        <!-- Превью -->
        <div class="w-24 h-16 bg-gray-200 rounded" />
        
        <!-- Информация -->
        <div class="flex-1 space-y-2">
          <div class="h-4 w-3/4 bg-gray-200 rounded" />
          <div class="h-3 w-1/2 bg-gray-200 rounded" />
        </div>
        
        <!-- Кнопка удаления -->
        <div class="w-8 h-8 bg-gray-200 rounded" />
      </div>
    </div>

    <!-- Информация об ограничениях skeleton -->
    <div class="space-y-1">
      <div class="h-3 w-32 bg-gray-200 rounded animate-pulse" />
      <div class="h-3 w-40 bg-gray-200 rounded animate-pulse" />
      <div class="h-3 w-48 bg-gray-200 rounded animate-pulse" />
    </div>
  </div>
</template>

<script setup lang="ts">
// Компонент скелетона для загрузки видео секции
// Показывается во время загрузки данных
</script>