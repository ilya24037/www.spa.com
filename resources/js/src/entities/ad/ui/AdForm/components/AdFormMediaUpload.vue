<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
      Медиа-файлы
    </label>
    <div class="mt-2 border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
      <p class="text-gray-500">Нажмите для загрузки фото/видео</p>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors?: any }>()
</script>