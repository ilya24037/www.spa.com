<!-- Skeleton loader для PhotoUpload -->
<template>
  <div class="photo-upload-skeleton space-y-4 animate-pulse">
    <div class="flex justify-between items-center">
      <div class="h-6 bg-gray-200 rounded w-24"></div>
      <div class="h-4 bg-gray-200 rounded w-16"></div>
    </div>
    
    <!-- Сетка скелетонов фото -->
    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
      <div v-for="i in 6" :key="i" class="aspect-square bg-gray-200 rounded-lg"></div>
    </div>
    
    <!-- Скелетон настроек -->
    <div class="border-t pt-4 mt-4">
      <div class="h-4 bg-gray-200 rounded w-32 mb-3"></div>
      <div class="space-y-2">
        <div class="h-4 bg-gray-200 rounded w-48"></div>
        <div class="h-4 bg-gray-200 rounded w-40"></div>
        <div class="h-4 bg-gray-200 rounded w-36"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Skeleton loader component for PhotoUpload
</script>